<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Services\WeeklyReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewAttendanceCorrection
{
    public function execute(User $actor, AttendanceCorrection $correction, string $decision, ?string $notes): AttendanceCorrection
    {
        Validator::make(['decision' => $decision, 'notes' => $notes], ['decision' => ['required', 'in:APPROVED,REJECTED'], 'notes' => ['nullable', 'string', 'max:2000']])->validate();
        Gate::forUser($actor)->authorize('review', $correction);

        return DB::transaction(function () use ($actor, $correction, $decision, $notes) {
            $period = WeeklyPeriod::whereKey($correction->weekly_period_id)->lockForUpdate()->firstOrFail();
            $attendance = Attendance::whereKey($correction->attendance_id)->lockForUpdate()->firstOrFail();
            $correction = AttendanceCorrection::whereKey($correction->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('review', $correction);
            if (! $period->finalized_at) {
                throw ValidationException::withMessages(['correction' => 'Laporan belum final.']);
            }
            if ($decision === 'APPROVED') {
                if ($attendance->version !== $correction->attendance_version) {
                    throw ValidationException::withMessages(['correction' => 'Absensi berubah. Tolak koreksi lama dan ajukan ulang.']);
                }
                $attendance->update(['status' => $correction->new_status, 'notes' => $correction->new_notes, 'updated_by' => $actor->id, 'version' => $attendance->version + 1]);
                $report = app(WeeklyReportService::class)->publish($actor, $period, $correction->reason);
            }
            $correction->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_notes' => $notes, 'weekly_report_id' => $report->id ?? null]);

            return $correction->fresh();
        });
    }
}
