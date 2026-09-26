<?php

namespace App\Policies;

use App\Models\ActivitySession;
use App\Models\Group;
use App\Models\User;
use App\Repositories\GroupRepository;

class ActivitySessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sessions.view');
    }

    public function view(User $user, ActivitySession $session): bool
    {
        return $user->can('sessions.view') && app(GroupRepository::class)->canAccess($user, $session->group_id);
    }

    public function create(User $user, ?Group $group = null): bool
    {
        return $group !== null && $user->hasAnyRole(['murabbi', 'ketua_mudabbir']) && $user->can('sessions.open') && app(GroupRepository::class)->canAccess($user, $group->id);
    }

    public function record(User $user, ActivitySession $session): bool
    {
        return $user->hasAnyRole(['mudabbir', 'ketua_mudabbir']) && $user->can('attendances.record')
            && app(GroupRepository::class)->canAccess($user, $session->group_id);
    }

    public function update(User $user, ActivitySession $session): bool
    {
        return false;
    }

    public function delete(User $user, ActivitySession $session): bool
    {
        return false;
    }
}
