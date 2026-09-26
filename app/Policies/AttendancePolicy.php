<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Repositories\GroupRepository;
use App\Repositories\StudentRepository;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'murabbi', 'mudabbir', 'mahasantri', 'orang_tua']);
    }

    public function view(User $user, Attendance $record): bool
    {
        return app(StudentRepository::class)->canAccess($user, $record->student_id);
    }

    // Pass the target student: Gate::authorize('create', [Attendance::class, $student]).
    public function create(User $user, ?Student $student = null): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $student !== null && $user->hasRole('mudabbir') && $user->can('attendances.record')
            && app(GroupRepository::class)->canAccess($user, $student->group_id);
    }

    public function update(User $user, Attendance $record): bool
    {
        return $this->view($user, $record) && ($user->hasRole('super_admin')
            || ($user->hasRole('mudabbir') && $user->can('attendances.record')));
    }

    public function delete(User $user, Attendance $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Attendance $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Attendance $record): bool
    {
        return false;
    }
}
