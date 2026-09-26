<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GroupOrganizationScope
{
    public function groups(User $actor): Builder
    {
        $query = Group::query();
        if ($actor->hasRole('super_admin')) {
            return $query;
        }
        if (! $actor->hasAnyRole(['mudabbir'])) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('mabna_id', $actor->managedGroups()->select('groups.mabna_id'));
    }

    public function students(User $actor): Builder
    {
        return Student::whereIn('group_id', $this->groups($actor)->select('groups.id'))->with(['user', 'group']);
    }

    public function canAccessGroup(User $actor, string $id): bool
    {
        return $this->groups($actor)->whereKey($id)->exists();
    }
}
