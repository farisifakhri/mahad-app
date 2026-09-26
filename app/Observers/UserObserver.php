<?php

namespace App\Observers;

use App\Models\User;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function saved(User $user): void
    {
        // SIPMA uses one enum role per user. Keep the Spatie pivot in sync.
        if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
            $user->syncRoles([Role::findOrCreate($user->role->value, 'web')]);
        }
    }
}
