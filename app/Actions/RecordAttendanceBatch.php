<?php

namespace App\Actions;

use App\Enums\AttendanceStatusEnum;
use App\Models\AbsenceSubmission;
use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordAttendanceBatch
{
    public function execute(User $actor, ActivitySession $session, array $rows): void
    {
        $validated = Validator::make(['rows' => $rows], [
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.student_id' => ['required', 'uuid', 'distinct'],
            'rows.*.status' => ['required', Rule::enum(AttendanceStatusEnum::class)],
            'rows.*.notes' => ['nullable', 'string', 'max:2000'],
            'rows.*.version' => ['required', 'integer', 'min:0'],
        ])->validate()['rows'];
        Gate::forUser($actor)->authorize('record', $session);
        DB::transaction(function () use ($actor, $session, $validated) {
            $workflow = app(AttendanceWorkflow::class);
            $period = $workflow->period($session);
            $session = ActivitySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('record', $session);
            $workflow->assertWindow($session, $period);
            foreach ($validated as $row) {
                $workflow->assertRoster($session, $row['student_id']);
                $record = Attendance::where('activity_session_id', $session->id)->where('student_id', $row['student_id'])->lockForUpdate()->first();
                if (($record?->version ?? 0) !== (int) $row['version']) {
                    throw ValidationException::withMessages(['rows' => 'Data telah berubah oleh pengguna lain. Muat ulang sebelum menyimpan.']);
                }
                if (in_array($row['status'], ['IZIN', 'SAKIT'], true) && ! AbsenceSubmission::where('student_id', $row['student_id'])
                    ->where('activity_session_id', $session->id)->where('status', 'APPROVED')->where('type', $row['status'])->exists()) {
                    throw ValidationException::withMessages(['rows' => 'Status IZIN/SAKIT memerlukan pengajuan yang sudah disetujui.']);
                }
                $attributes = ['status' => $row['status'], 'notes' => $row['notes'] ?? null, 'updated_by' => $actor->id];
                if ($record) {
                    $record->fill($attributes);
                    if ($record->isDirty(['status', 'notes'])) {
                        $record->version++;
                        $record->save();
                    }
                } else {
                    Attendance::create($attributes + ['student_id' => $row['student_id'], 'activity_session_id' => $session->id, 'recorded_by' => $actor->id, 'version' => 1]);
                }
            }
        });
    }
}
