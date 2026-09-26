<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Repositories\GroupRepository;
use App\Repositories\StudentRepository;

class ViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'murabbi', 'mudabbir', 'ketua_mudabbir', 'mahasantri', 'orang_tua']);
    }

    public function view(User $user, Violation $record): bool
    {
        return app(StudentRepository::class)->canAccess($user, $record->student_id);
    }

    // Pass the target student: Gate::authorize('create', [Violation::class, $student]).
    public function create(User $user, ?Student $student = null): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $student !== null && $user->hasAnyRole(['mudabbir', 'ketua_mudabbir']) && $user->can('violations.record')
            && app(GroupRepository::class)->canAccess($user, $student->group_id);
    }

    public function update(User $user, Violation $record): bool
    {
        return $this->view($user, $record) && ($user->hasRole('super_admin')
            || ($user->hasAnyRole(['mudabbir', 'ketua_mudabbir']) && $user->can('violations.record')));
    }

    public function delete(User $user, Violation $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Violation $record): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Violation $record): bool
    {
        return false;
    }
}
