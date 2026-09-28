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
            'sessions.view', 'sessions.open', 'attendances.correct', 'reports.finalize',
            'corrections.review',
            'groups.organize',
        ];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $readPermissions = ['groups.view', 'students.view', 'activities.view', 'violation_categories.view', 'attendances.view', 'violations.view', 'reports.view', 'sessions.view'];
        $map = [
            UserRoleEnum::PENGASUH->value => $readPermissions,
            UserRoleEnum::SUPER_ADMIN->value => array_values(array_diff($permissions, ['sessions.open', 'attendances.record', 'attendances.correct', 'corrections.review'])),
            UserRoleEnum::MURABBI->value => array_merge($readPermissions, ['corrections.review']),
            UserRoleEnum::MUDABBIR->value => array_merge($readPermissions, ['attendances.record', 'submissions.view', 'submissions.review', 'violations.record', 'sessions.open', 'attendances.correct', 'reports.finalize', 'groups.organize']),
            UserRoleEnum::MAHASANTRI->value => ['portal.self.view', 'portal.submissions.create'],
            UserRoleEnum::ORANG_TUA->value => ['portal.children.view'],
        ];
        foreach ($map as $name => $allowed) {
            Role::findOrCreate($name, 'web')->syncPermissions($allowed);
        }
        User::query()->where('role', 'ketua_mudabbir')->update(['role' => UserRoleEnum::MUDABBIR->value]);
        User::each(fn (User $user) => $user->syncRoles([$user->role->value]));
        Role::where('name', 'ketua_mudabbir')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
