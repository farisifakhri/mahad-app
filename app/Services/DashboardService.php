<?php

namespace App\Services;

use App\Models\AbsenceSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function __construct(private MonitoringService $monitoring, private OperationalQuery $queries, private WeeklyCalendar $calendar) {}

    public function overview(User $actor): array
    {
        $today = CarbonImmutable::now(config('sipma.timezone'));
        $groups = $this->monitoring->groups($actor)->with('mudabbirs')->get();
        $sessions = $this->queries->sessions($actor)->where('status', 'open')->whereBetween('date', [$this->calendar->start($today)->toDateString(), $this->calendar->end($today)->toDateString()])->with('attendances')->get();
        $completion = $groups->map(function ($group) use ($sessions) {
            $rows = $sessions->where('group_id', $group->id);
            $expected = $rows->sum(fn ($session) => count($session->roster ?? []));
            $recorded = $rows->sum(fn ($session) => $session->attendances->whereIn('student_id', array_column($session->roster ?? [], 'student_id'))->count());

            return ['group' => $group, 'sessions' => $rows->count(), 'expected' => $expected, 'recorded' => $recorded, 'percentage' => $expected ? round($recorded / $expected * 100) : null];
        });
        $records = $sessions->flatMap(fn ($session) => $session->attendances->whereIn('student_id', array_column($session->roster ?? [], 'student_id')));
        $reviews = AbsenceSubmission::whereIn('activity_session_id', $sessions->pluck('id'))->whereNotNull('reviewed_at')->get();
        $staffPerformance = $groups->flatMap->mudabbirs->unique('id')->map(fn ($staff) => [
            'name' => $staff->name, 'role' => $staff->role->getLabel(),
            'groups' => $groups->filter(fn ($group) => $group->mudabbirs->contains('id', $staff->id))->pluck('name')->implode(', '),
            'recorded' => $records->where('recorded_by', $staff->id)->count(),
            'revised' => $records->where('version', '>', 1)->where('updated_by', $staff->id)->count(),
            'reviews' => $reviews->where('reviewed_by', $staff->id)->count(),
        ])->values();

        return ['groups' => $groups, 'completion' => $completion, 'staffPerformance' => $staffPerformance, 'todaySessions' => $sessions->filter(fn ($session) => $session->date->toDateString() === $today->toDateString()),
            'incomplete' => $sessions->filter(fn ($session) => $session->attendances->count() < count($session->roster ?? [])),
            'cutoff' => $this->calendar->cutoff($today), 'start' => $this->calendar->start($today), 'end' => $this->calendar->end($today),
            'reports' => $this->queries->periods($actor)->whereNotNull('finalized_at')->limit(5)->get()];
    }
}
