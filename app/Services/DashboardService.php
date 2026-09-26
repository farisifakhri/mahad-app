<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function __construct(private MonitoringService $monitoring, private OperationalQuery $queries, private WeeklyCalendar $calendar) {}

    public function overview(User $actor): array
    {
        $today = CarbonImmutable::now(config('sipma.timezone'));
        $groups = $this->monitoring->groups($actor)->get();
        $sessions = $this->queries->sessions($actor)->where('status', 'open')->whereBetween('date', [$this->calendar->start($today)->toDateString(), $this->calendar->end($today)->toDateString()])->with('attendances')->get();
        $completion = $groups->map(function ($group) use ($sessions) {
            $rows = $sessions->where('group_id', $group->id);
            $expected = $rows->sum(fn ($session) => count($session->roster ?? []));
            $recorded = $rows->sum(fn ($session) => $session->attendances->whereIn('student_id', array_column($session->roster ?? [], 'student_id'))->count());

            return ['group' => $group, 'sessions' => $rows->count(), 'expected' => $expected, 'recorded' => $recorded, 'percentage' => $expected ? round($recorded / $expected * 100) : null];
        });

        return ['groups' => $groups, 'completion' => $completion, 'todaySessions' => $sessions->filter(fn ($session) => $session->date->toDateString() === $today->toDateString()),
            'incomplete' => $sessions->filter(fn ($session) => $session->attendances->count() < count($session->roster ?? [])),
            'cutoff' => $this->calendar->cutoff($today), 'start' => $this->calendar->start($today), 'end' => $this->calendar->end($today),
            'reports' => $this->queries->periods($actor)->whereNotNull('finalized_at')->limit(5)->get()];
    }
}
