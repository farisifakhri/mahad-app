<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use App\Services\GroupOrganizationScope;

class GroupOrganizationPolicy
{
    public function organize(User $actor, Group $group): bool
    {
        return $actor->hasAnyRole(['super_admin', 'mudabbir']) && $actor->can('groups.organize') && app(GroupOrganizationScope::class)->canAccessGroup($actor, $group->id);
    }

    public function assign(User $actor, Student $student, Group $target): bool
    {
        return $this->organize($actor, $target) && app(GroupOrganizationScope::class)->canAccessGroup($actor, $student->group_id) && $student->group->mabna_id === $target->mabna_id;
    }
}
