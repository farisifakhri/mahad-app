<?php

namespace App\Actions;

use App\Models\AbsenceSubmission;
use App\Models\ActivitySession;
use App\Models\User;
use App\Services\AttendanceWorkflow;
use App\Services\WeeklyCalendar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitAbsence
{
    public function execute(User $actor, ActivitySession $session, array $data, ?UploadedFile $evidence = null): AbsenceSubmission
    {
        $data = Validator::make($data + ['evidence' => $evidence], [
            'type' => ['required', 'in:IZIN,SAKIT'], 'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ])->validate();
        $student = $actor->student()->whereHas('group')->firstOrFail();
        Gate::forUser($actor)->authorize('create', [AbsenceSubmission::class, $student]);

        return DB::transaction(function () use ($session, $student, $data, $evidence) {
            $period = app(AttendanceWorkflow::class)->period($session);
            $session = ActivitySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status !== 'open' || ! $session->opened_at || $period->finalized_at || app(WeeklyCalendar::class)->isLocked($session->date)) {
                throw ValidationException::withMessages(['session' => 'Sesi belum dibuka atau periode sudah terkunci.']);
            }
            app(AttendanceWorkflow::class)->assertRoster($session, $student->id);
            if (AbsenceSubmission::where('activity_session_id', $session->id)->where('student_id', $student->id)->exists()) {
                throw ValidationException::withMessages(['session' => 'Sudah ada pengajuan untuk sesi ini.']);
            }
            $submission = AbsenceSubmission::create(['student_id' => $student->id, 'activity_session_id' => $session->id, 'type' => $data['type'], 'reason' => $data['reason'], 'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'status' => 'PENDING']);
            if ($evidence) {
                $submission->addMedia($evidence)->toMediaCollection('evidence');
            }

            return $submission;
        });
    }
}
