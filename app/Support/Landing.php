<?php

namespace App\Support;

use App\Models\User;

/**
 * Where a person lands after signing in: the first page their role can open.
 * Without this, roles that lack the "dashboard" permission (e.g. Sales Officer)
 * were sent to /dashboard and shown a 403 error.
 */
class Landing
{
    private const ORDER = [
        'dashboard'  => 'dashboard',
        'View flock' => 'poultry.overview',
        'View sale'  => 'sales.index',
        'View reports' => 'reports.index',
        'View user'  => 'users.index',
    ];

    public static function for(?User $user): string
    {
        if ($user) {
            foreach (self::ORDER as $permission => $route) {
                if (\Route::has($route) && $user->can($permission)) {
                    return route($route);
                }
            }
        }

        return route('account.pending');
    }
}
