<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GroupRepository
{
    public function visibleTo(User $user): Builder
    {
        $query = Group::query();
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        if ($user->hasRole('murabbi')) {
            return $query->where('murabbi_id', $user->id);
        }
        if ($user->hasAnyRole(['mudabbir', 'ketua_mudabbir'])) {
            return $query->whereHas('mudabbirs', fn (Builder $users) => $users->where('users.id', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, string $groupId): bool
    {
        return $this->visibleTo($user)->whereKey($groupId)->exists();
    }
}
