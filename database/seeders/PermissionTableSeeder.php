<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
        //    'Super role',
           'View user',
           'Create user',
           'Update user',
           'Delete user',
           
           'View role',
           'Create role',
           'Update role',
           'Delete role',
           'Add user-role',
           'Update user-role',
           'Remove user-role',

           'View permission',
           'Create permission',
           'Update permission',
           'Delete permission',

           'dashboard',

           'View flock',
           'Create flock',
           'Update flock',
           'Delete flock',

           'View weekly-entry',
           'Create weekly-entry',
           'Update weekly-entry',
           'Delete weekly-entry',

           'View daily-entry',
           'Create daily-entry',
           'Update daily-entry',
           'Delete daily-entry',

        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if($word == "user")
                Permission::Create(['name' => $permission,'title'=>"User Management"]);

                if($word == "role" || $word == "user-role")
                Permission::Create(['name' => $permission,'title'=>"Role Management"]);

                if($word == "permission")
                Permission::Create(['name' => $permission,'title'=>"Permission Management"]);

                if($word == "dashboard")
                Permission::Create(['name' => $permission,'title'=>"Dashboard Management"]);

                if($word == "flock")
                Permission::Create(['name' => $permission,'title'=>"flock Management"]);

                if($word == "weekly-entry")
                Permission::Create(['name' => $permission,'title'=>"Week Entry Management"]);

                if($word == "daily-entry")
                Permission::Create(['name' => $permission,'title'=>"Daily Entry Management"]);

               

            }
            //  Permission::Create(['name' => $permission]);
        }
    }
}
