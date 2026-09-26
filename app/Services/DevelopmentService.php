<?php

namespace App\Services;

use App\DTOs\DateRange;
use App\Models\ActivitySession;
use App\Models\Student;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Repositories\StudentRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DevelopmentService
{
    public function __construct(private MonitoringService $monitoring, private StudentRepository $students) {}

    public function summary(User $actor, Student $student, DateRange $range): array
    {
        abort_unless($this->students->canAccess($actor, $student->id), 403);
        $attendanceQuery = $this->monitoring->attendances($actor)->where('student_id', $student->id)
            ->whereHas('activitySession', fn ($query) => $query->whereBetween('date', [$range->from, $range->to]));
        $counts = ['HADIR' => 0, 'ALFA' => 0, 'IZIN' => 0, 'SAKIT' => 0];
        foreach ((clone $attendanceQuery)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
            $counts[$status] = (int) $total;
        }
        $expected = ActivitySession::where('status', 'open')->whereBetween('date', [$range->from, $range->to])->where('roster', 'like', '%'.$student->id.'%')->get()
            ->filter(fn ($session) => app(AttendanceWorkflow::class)->rosterContains($session, $student->id) && $session->ends_at
                && CarbonImmutable::parse($session->date->toDateString().' '.$session->ends_at, config('sipma.timezone'))->lessThanOrEqualTo(CarbonImmutable::now(config('sipma.timezone'))))->count();
        $violationQuery = $this->monitoring->violations($actor)->where('student_id', $student->id)->whereBetween('occurred_on', [$range->from, $range->to]);

        return ['counts' => $counts, 'unrecorded' => max(0, $expected - array_sum($counts)), 'expected' => $expected,
            'attendance_percentage' => $expected ? round($counts['HADIR'] / $expected * 100, 1) : null,
            'attendances' => (clone $attendanceQuery)->latest()->limit(100)->get(), 'violations' => (clone $violationQuery)->with('media')->latest()->limit(100)->get(), 'violation_count' => $violationQuery->count()];
    }

    public function reportVersions(User $actor, DateRange $range): Collection
    {
        $ids = $this->students->visibleTo($actor)->pluck('id')->all();
        if ($ids === []) {
            return collect();
        }

        return WeeklyReport::with('period')->whereHas('period', fn ($query) => $query->whereDate('starts_on', '<=', $range->to)->whereDate('ends_on', '>=', $range->from))
            ->where(function ($query) use ($ids) {
                foreach ($ids as $id) {
                    $query->orWhere('snapshot', 'like', '%'.$id.'%');
                }
            })
            ->latest('id')->limit(100)->get()->map(function ($report) use ($ids) {
                $snapshot = $report->snapshot;
                $sessions = collect($snapshot['sessions'])->map(function ($session) use ($ids) {
                    $session['students'] = array_values(array_filter($session['students'], fn ($student) => in_array($student['student_id'], $ids, true)));

                    return $session;
                })->filter(fn ($session) => $session['students'] !== [])->values()->all();

                // Return only the relevant rows, never the full group totals or another child's reason.
                return ['version' => $report->version, 'group' => $snapshot['group'], 'from' => $snapshot['starts_on'], 'to' => $snapshot['ends_on'], 'published_at' => $report->created_at, 'sessions' => $sessions];
            })->filter(fn ($report) => $report['sessions'] !== [])->values();
    }
}
