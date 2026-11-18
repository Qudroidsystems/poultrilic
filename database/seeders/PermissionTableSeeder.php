<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Every permission the app checks, grouped by the title shown on the Permissions page.
 * Safe to run again: missing permissions are added and titles corrected; nothing is removed.
 */
class PermissionTableSeeder extends Seeder
{
    public const GROUPS = [
        'User Management' => ['View user', 'Create user', 'Update user', 'Delete user'],
        'Role Management' => ['View role', 'Create role', 'Update role', 'Delete role', 'Add user-role', 'Update user-role', 'Remove user-role'],
        'Permission Management' => ['View permission', 'Create permission', 'Update permission', 'Delete permission'],
        'Dashboard Management' => ['dashboard'],
        'Flock Management' => ['View flock', 'Create flock', 'Update flock', 'Delete flock', 'Merge flock'],
        'Week Entry Management' => ['View weekly-entry', 'Create weekly-entry', 'Update weekly-entry', 'Delete weekly-entry'],
        'Daily Entry Management' => ['View daily-entry', 'Create daily-entry', 'Update daily-entry', 'Delete daily-entry'],
        'Livestock' => [
            'View livestock', 'Create livestock record', 'Update livestock record', 'Delete livestock record',
            'Manage livestock units', 'Manage livestock types',
        ],
        'Imports & Reports' => ['Import records', 'View reports', 'Manage reports'],
        'Sales Management' => ['View sale', 'Create sale', 'Update sale', 'Delete sale', 'Create payment', 'Delete payment'],
        'Customer Management' => ['View customer', 'Create customer', 'Update customer', 'Delete customer'],
        'Expense Management' => ['View expense', 'Create expense', 'Update expense', 'Delete expense'],
        'Feed Management' => ['View feed', 'Create feed', 'Update feed', 'Delete feed'],
        'Settings' => ['View settings', 'Update settings'],
        'Website' => ['Manage website'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GROUPS as $title => $names) {
            foreach ($names as $name) {
                Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => $title]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** All permission names. */
    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }
}
