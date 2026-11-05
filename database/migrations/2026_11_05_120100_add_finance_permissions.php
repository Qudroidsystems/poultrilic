<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permissions for the expenses and settings pages and grants them to the
 * roles that already administer the farm: "Super Admin" plus any role that can open
 * the dashboard. Everyone else can be granted them from the Roles page.
 */
return new class extends Migration
{
    private array $permissions = [
        'View expense', 'Create expense', 'Update expense', 'Delete expense',
        'View settings', 'Update settings',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['title' => str_contains($name, 'settings') ? 'Settings Management' : 'Expense Management']
            );
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
