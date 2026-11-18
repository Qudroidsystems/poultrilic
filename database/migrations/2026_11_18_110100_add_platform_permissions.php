<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for imports, reports, flock merging and the livestock builder. Granted to Super Admin and any
 * role that can open the dashboard (the same rule as the finance permissions).
 */
return new class extends Migration
{
    public const GROUPS = [
        'Imports & Reports' => ['Import records', 'View reports', 'Manage reports'],
        'Flock Management' => ['Merge flock'],
        'Livestock' => [
            'View livestock', 'Create livestock record', 'Update livestock record', 'Delete livestock record',
            'Manage livestock units', 'Manage livestock types',
        ],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (self::GROUPS as $title => $names) {
            foreach ($names as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => $title]);
                $all[] = $name;
            }
        }

        $roles = Role::where('guard_name', 'web')
            ->where(fn ($q) => $q->where('name', 'Super Admin')->orWhereHas('permissions', fn ($p) => $p->where('name', 'dashboard')))
            ->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($all);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', collect(self::GROUPS)->flatten())->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
