<?php

namespace App\Actions;

use App\Enums\AttendanceStatusEnum;
use App\Models\AbsenceSubmission;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Services\AttendanceWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestAttendanceCorrection
{
    public function execute(User $actor, Attendance $attendance, array $data): AttendanceCorrection
    {
        $data = Validator::make($data, ['new_status' => ['required', Rule::enum(AttendanceStatusEnum::class)], 'new_notes' => ['nullable', 'string', 'max:2000'], 'reason' => ['required', 'string', 'min:5', 'max:2000'], 'version' => ['required', 'integer', 'min:1']])->validate();
        Gate::forUser($actor)->authorize('correct', $attendance);

        return DB::transaction(function () use ($actor, $attendance, $data) {
            $period = app(AttendanceWorkflow::class)->period($attendance->activitySession);
            $attendance = Attendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('correct', $attendance);
            if ((int) $data['version'] !== $attendance->version || AttendanceCorrection::where('attendance_id', $attendance->id)->where('status', 'PENDING')->exists()) {
                throw ValidationException::withMessages(['correction' => 'Data berubah atau sudah ada koreksi yang menunggu review.']);
            }
            if (in_array($data['new_status'], ['IZIN', 'SAKIT'], true) && ! AbsenceSubmission::where('student_id', $attendance->student_id)->where('activity_session_id', $attendance->activity_session_id)->where('status', 'APPROVED')->where('type', $data['new_status'])->exists()) {
                throw ValidationException::withMessages(['new_status' => 'IZIN/SAKIT membutuhkan pengajuan yang disetujui.']);
            }
            if ($attendance->status->value === $data['new_status'] && $attendance->notes === ($data['new_notes'] ?? null)) {
                throw ValidationException::withMessages(['new_status' => 'Koreksi harus mengubah status atau catatan.']);
            }

            return AttendanceCorrection::create(['attendance_id' => $attendance->id, 'weekly_period_id' => $period->id, 'requested_by' => $actor->id, 'attendance_version' => $attendance->version, 'old_status' => $attendance->status, 'new_status' => $data['new_status'], 'new_notes' => $data['new_notes'] ?? null, 'reason' => $data['reason'], 'status' => 'PENDING']);
        });
    }
}
