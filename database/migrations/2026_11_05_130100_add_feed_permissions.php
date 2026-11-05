<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for the feed stock page, granted to Super Admin and any role that can open the dashboard.
 */
return new class extends Migration
{
    private array $permissions = ['View feed', 'Create feed', 'Update feed', 'Delete feed'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => 'Feed Management']);
        }

        $roles = Role::where('guard_name', 'web')
            ->where(function ($q) {
                $q->where('name', 'Super Admin')
                  ->orWhereHas('permissions', fn ($p) => $p->where('name', 'dashboard'));
            })->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($this->permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
