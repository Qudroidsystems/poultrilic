<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Farm roles with sensible permission sets. Safe to run again: roles are created if missing and their
 * permissions are resynced to the lists below (Super Admin and Admin always get every permission), so roles
 * pick up new permissions after an upgrade. Roles the farm created itself are left alone.
 */
class RoleTableSeeder extends Seeder
{
    public const ROLES = [
        'Super Admin' => ['badge' => 'badge bg-success', 'permissions' => '*'],
        'Admin' => ['badge' => 'badge bg-primary', 'permissions' => '*'],
        'Farm Manager' => ['badge' => 'badge bg-info', 'permissions' => [
            'dashboard', 'View flock', 'Create flock', 'Update flock', 'Merge flock',
            'View weekly-entry', 'Create weekly-entry', 'Update weekly-entry',
            'View daily-entry', 'Create daily-entry', 'Update daily-entry', 'Delete daily-entry',
            'View livestock', 'Create livestock record', 'Update livestock record', 'Delete livestock record', 'Manage livestock units',
            'Import records', 'View reports', 'Manage reports',
            'View sale', 'View customer', 'View expense', 'View feed', 'Create feed', 'Update feed', 'View settings',
        ]],
        'Record Keeper' => ['badge' => 'badge bg-warning', 'permissions' => [
            'View flock', 'View weekly-entry', 'Create weekly-entry',
            'View daily-entry', 'Create daily-entry', 'Update daily-entry',
            'View livestock', 'Create livestock record', 'Update livestock record',
            'Import records', 'View feed',
        ]],
        'Sales Officer' => ['badge' => 'badge bg-secondary', 'permissions' => [
            'View flock', 'View sale', 'Create sale', 'Update sale', 'Create payment',
            'View customer', 'Create customer', 'Update customer', 'View reports',
        ]],
        'Accountant' => ['badge' => 'badge bg-dark', 'permissions' => [
            'dashboard', 'View flock', 'View reports', 'Manage reports',
            'View sale', 'Create payment', 'Delete payment', 'View customer',
            'View expense', 'Create expense', 'Update expense', 'Delete expense',
            'View feed', 'Create feed', 'Update feed', 'View settings', 'Update settings',
        ]],
        'Viewer' => ['badge' => 'badge bg-light text-dark', 'permissions' => [
            'dashboard', 'View flock', 'View weekly-entry', 'View daily-entry', 'View livestock', 'View reports',
        ]],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $all = Permission::where('guard_name', 'web')->pluck('name')->all();

        foreach (self::ROLES as $name => $def) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (! $role->badge) {
                $role->forceFill(['badge' => $def['badge']])->save();
            }
            $wanted = $def['permissions'] === '*' ? $all : array_values(array_intersect($def['permissions'], $all));
            $role->syncPermissions($wanted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
