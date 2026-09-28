<?php

namespace App\Actions;

use App\Models\AbsenceSubmission;
use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewAbsenceSubmission
{
    public function execute(User $actor, AbsenceSubmission $submission, string $decision, ?string $notes, int $version): AbsenceSubmission
    {
        Validator::make(['decision' => $decision, 'notes' => $notes, 'version' => $version], ['decision' => ['required', 'in:APPROVED,REJECTED'], 'notes' => ['nullable', 'string', 'max:2000'], 'version' => ['required', 'integer', 'min:1']])->validate();
        Gate::forUser($actor)->authorize('review', $submission);

        return DB::transaction(function () use ($actor, $submission, $decision, $notes, $version) {
            $session = ActivitySession::findOrFail($submission->activity_session_id);
            $period = app(AttendanceWorkflow::class)->period($session);
            $session = ActivitySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $attendance = Attendance::where('activity_session_id', $session->id)->where('student_id', $submission->student_id)->lockForUpdate()->first();
            $submission = AbsenceSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('review', $submission);
            if ($version !== $submission->version) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan berubah. Muat ulang.']);
            }
            app(AttendanceWorkflow::class)->assertWindow($session, $period);
            app(AttendanceWorkflow::class)->assertRoster($session, $submission->student_id);
            $submission->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_notes' => $notes, 'version' => $submission->version + 1]);
            if ($decision === 'APPROVED') {
                if ($attendance) {
                    $attendance->update(['status' => $submission->type, 'updated_by' => $actor->id, 'version' => $attendance->version + 1]);
                } else {
                    Attendance::create(['student_id' => $submission->student_id, 'activity_session_id' => $session->id, 'status' => $submission->type, 'recorded_by' => $actor->id, 'updated_by' => $actor->id, 'version' => 1]);
                }
            }

            return $submission->fresh();
        });
    }
}
