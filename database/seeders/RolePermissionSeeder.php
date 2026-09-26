<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'users.manage', 'groups.manage', 'students.manage', 'activities.manage', 'violation_categories.manage',
            'groups.view', 'students.view', 'activities.view', 'violation_categories.view',
            'attendances.view', 'attendances.record', 'submissions.view', 'submissions.review',
            'violations.view', 'violations.record', 'reports.view', 'parents.manage',
            'portal.self.view', 'portal.submissions.create', 'portal.children.view',
        ];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $readPermissions = ['groups.view', 'students.view', 'activities.view', 'violation_categories.view', 'attendances.view', 'violations.view', 'reports.view'];
        $map = [
            UserRoleEnum::SUPER_ADMIN->value => $permissions,
            UserRoleEnum::MURABBI->value => $readPermissions,
            UserRoleEnum::MUDABBIR->value => array_merge($readPermissions, ['attendances.record', 'submissions.view', 'submissions.review', 'violations.record']),
            UserRoleEnum::MAHASANTRI->value => ['portal.self.view', 'portal.submissions.create'],
            UserRoleEnum::ORANG_TUA->value => ['portal.children.view'],
        ];
        foreach ($map as $name => $allowed) {
            Role::findOrCreate($name, 'web')->syncPermissions($allowed);
        }
        User::each(fn (User $user) => $user->syncRoles([$user->role->value]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
