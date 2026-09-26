<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('users')->where('role', 'ketua_mudabbir')->update(['role' => 'mudabbir']);
            $legacy = DB::table('roles')->where('name', 'ketua_mudabbir')->where('guard_name', 'web')->first();
            if (! $legacy) {
                return;
            }
            DB::table('roles')->insertOrIgnore(['name' => 'mudabbir', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('roles')->where('name', 'mudabbir')->where('guard_name', 'web')->value('id');
            foreach (DB::table('model_has_roles')->where('role_id', $legacy->id)->get() as $row) {
                DB::table('model_has_roles')->insertOrIgnore(['role_id' => $id, 'model_type' => $row->model_type, 'model_id' => $row->model_id]);
            }
            foreach (DB::table('role_has_permissions')->where('role_id', $legacy->id)->get() as $row) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $id, 'permission_id' => $row->permission_id]);
            }
            DB::table('roles')->where('id', $legacy->id)->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The merged assignments cannot be separated reliably; preserve users and history.
    }
};
