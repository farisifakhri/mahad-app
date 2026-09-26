<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentRepository
{
    public function __construct(private GroupRepository $groups) {}

    public function visibleTo(User $user): Builder
    {
        $query = Student::query();
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        if ($user->hasAnyRole(['murabbi', 'mudabbir', 'ketua_mudabbir'])) {
            return $query->whereIn('group_id', $this->groups->visibleTo($user)->select('groups.id'));
        }
        if ($user->hasRole('mahasantri')) {
            return $query->where('user_id', $user->id);
        }
        if ($user->hasRole('orang_tua')) {
            return $query->whereHas('parents', fn (Builder $parents) => $parents->where('parents.user_id', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, string $studentId): bool
    {
        return $this->visibleTo($user)->whereKey($studentId)->exists();
    }
}
