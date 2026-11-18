<?php

namespace Database\Seeders;

use App\Models\BioModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The first Super Admin. Safe to run again: the account is created only if missing and an existing password is
 * never reset. Set ADMIN_EMAIL, ADMIN_PASSWORD and ADMIN_NAME in .env before seeding a live server.
 */
class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'eliabsiji@gmail.com');
        $name = trim(env('ADMIN_NAME', 'Ilemobayo Eliab'));
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::forceCreate([
                'first_name' => $first ?: 'Admin',
                'last_name' => $last ?: 'User',
                'username' => Str::before($email, '@') . (User::where('username', Str::before($email, '@'))->exists() ? '-' . Str::random(4) : ''),
                'email' => $email,
                'avatar' => 'unnamed.png',
                'password' => Hash::make(env('ADMIN_PASSWORD', '12345678')),
                'email_verified_at' => now(),
            ]);
        }

        if (class_exists(BioModel::class) && ! BioModel::where('user_id', $user->id)->exists()) {
            BioModel::create(['user_id' => $user->id, 'firstname' => $user->first_name, 'lastname' => $user->last_name]);
        }

        foreach (['Super Admin', 'Admin'] as $role) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        if (app()->runningInConsole() && isset($this->command) && env('ADMIN_PASSWORD') === null && $user->wasRecentlyCreated) {
            $this->command->warn("  Admin {$email} was created with the default password. Change it after signing in.");
        }
    }
}
