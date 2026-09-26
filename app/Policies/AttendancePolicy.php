<?php

namespace App\Policies;

use App\Models\ActivitySession;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Models\WeeklyPeriod;
use App\Repositories\GroupRepository;
use App\Repositories\StudentRepository;
use App\Services\AttendanceWorkflow;
use App\Services\WeeklyCalendar;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'murabbi', 'mudabbir', 'ketua_mudabbir', 'mahasantri', 'orang_tua']);
    }

    public function view(User $user, Attendance $record): bool
    {
        if ($user->hasAnyRole(['super_admin', 'murabbi', 'mudabbir', 'ketua_mudabbir'])) {
            return app(GroupRepository::class)->canAccess($user, $record->activitySession->group_id);
        }

        return app(StudentRepository::class)->canAccess($user, $record->student_id);
    }

    // Pass the target student: Gate::authorize('create', [Attendance::class, $student]).
    public function create(User $user, ?Student $student = null, ?ActivitySession $session = null): bool
    {
        return $student !== null && $session !== null && app(AttendanceWorkflow::class)->canRecord($user, $session)
            && app(AttendanceWorkflow::class)->rosterContains($session, $student->id);
    }

    public function update(User $user, Attendance $record): bool
    {
        return app(AttendanceWorkflow::class)->canRecord($user, $record->activitySession)
            && app(AttendanceWorkflow::class)->rosterContains($record->activitySession, $record->student_id);
    }

    public function correct(User $user, Attendance $record): bool
    {
        return $user->hasRole('ketua_mudabbir') && $user->can('attendances.correct')
            && app(GroupRepository::class)->canAccess($user, $record->activitySession->group_id)
            && WeeklyPeriod::where('group_id', $record->activitySession->group_id)
                ->whereDate('starts_on', app(WeeklyCalendar::class)->start($record->activitySession->date))
                ->whereNotNull('finalized_at')->exists();
    }

    public function delete(User $user, Attendance $record): bool
    {
        return false;
    }

    public function restore(User $user, Attendance $record): bool
    {
        return false;
    }

    public function forceDelete(User $user, Attendance $record): bool
    {
        return false;
    }
}
