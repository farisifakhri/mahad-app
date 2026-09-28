<?php

namespace App\Services;

use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Models\WeeklyReport;

class WeeklyReportService
{
    public function snapshot(WeeklyPeriod $period): array
    {
        $sessions = ActivitySession::where('group_id', $period->group_id)->where('status', 'open')->whereBetween('date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])->with('activity')->orderBy('date')->orderBy('starts_at')->get();
        $totals = ['HADIR' => 0, 'ALFA' => 0, 'IZIN' => 0, 'SAKIT' => 0, 'BELUM_DIISI' => 0];
        $data = [];
        foreach ($sessions as $session) {
            $attendances = Attendance::where('activity_session_id', $session->id)->get()->keyBy('student_id');
            $students = [];
            foreach ($session->roster ?? [] as $member) {
                $record = $attendances->get($member['student_id']);
                $status = $record?->status->value ?? 'BELUM_DIISI';
                $totals[$status]++;
                $students[] = $member + ['status' => $status, 'attendance_id' => $record?->id, 'notes' => $record?->notes, 'version' => $record?->version ?? 0];
            }
            $data[] = ['session_id' => $session->id, 'date' => $session->date->toDateString(), 'activity' => $session->activity->name, 'starts_at' => $session->starts_at, 'ends_at' => $session->ends_at, 'occurrence' => $session->occurrence, 'students' => $students];
        }

        return ['group_id' => $period->group_id, 'group' => $period->group->name, 'starts_on' => $period->starts_on->toDateString(), 'ends_on' => $period->ends_on->toDateString(), 'generated_at' => now()->toIso8601String(), 'totals' => $totals, 'sessions' => $data];
    }

    public function publish(User $actor, WeeklyPeriod $period, ?string $reason = null): WeeklyReport
    {
        $report = WeeklyReport::create(['weekly_period_id' => $period->id, 'version' => $period->current_version + 1, 'snapshot' => $this->snapshot($period), 'created_by' => $actor->id, 'reason' => $reason]);
        $period->update(['current_version' => $report->version]);

        return $report;
    }
}
