<?php

namespace App\Policies;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;

class ParentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'orang_tua']);
    }

    public function view(User $user, ParentModel $parent): bool
    {
        return $user->hasRole('super_admin') || ($user->hasRole('orang_tua') && $parent->user_id === $user->id);
    }

    public function viewChild(User $user, ParentModel $parent, Student $student): bool
    {
        return $this->view($user, $parent) && $parent->students()->whereKey($student->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, ParentModel $parent): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, ParentModel $parent): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, ParentModel $parent): bool
    {
        return false;
    }

    public function forceDelete(User $user, ParentModel $parent): bool
    {
        return false;
    }
}
