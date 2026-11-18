<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LivestockType;
use App\Models\User;
use App\Support\FarmSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    /** Public: lets the app check an address and brand itself. */
    public function config()
    {
        return $this->ok([
            'name' => FarmSettings::text('farm_name') ?: config('app.name'),
            'app_name' => 'Poultrilic',
            'tagline' => 'Farm records, analytics and reports',
            'color' => '#405189',
            'api_version' => 1,
            'currency' => '₦',
            'eggs_per_crate' => \App\Support\Eggs::PER_CRATE,
            'bag_weight_kg' => FarmSettings::bagWeightKg(),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:120',
        ]);

        $key = 'api-login:' . Str::lower($data['login']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return $this->fail('Too many attempts. Wait a minute and try again.', 429, 'throttled');
        }

        $login = trim($data['login']);
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->orWhere('phone_number', $login)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return $this->fail('The sign-in details are not correct.', 422, 'invalid_credentials', ['login' => ['The sign-in details are not correct.']]);
        }
        RateLimiter::clear($key);

        $token = $user->createToken(($data['device_name'] ?? null) ?: 'Poultrilic app')->plainTextToken;

        return $this->ok(['token' => $token, 'user' => $this->user($user)], 'Signed in.');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return $this->ok(null, 'If that address belongs to an account, a reset link has been sent to it.');
    }

    public function me(Request $request)
    {
        return $this->ok($this->user($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(null, 'Signed out.');
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->ok(null, 'Signed out of every device.');
    }

    /** The user as the app expects it: permissions decide which screens show; modules list the farm areas. */
    public static function user(User $user): array
    {
        $perms = $user->getAllPermissions()->pluck('name')->values()->all();
        if ($user->hasRole('Super Admin')) {
            $perms[] = '*';
        }
        $modules = [];
        if ($user->can('View flock')) {
            $modules[] = 'poultry';
        }
        if ($user->can('View livestock')) {
            foreach (LivestockType::where('active', true)->orderBy('sort')->pluck('slug') as $slug) {
                $modules[] = 'livestock:' . $slug;
            }
        }
        if ($user->can('View reports')) {
            $modules[] = 'reports';
        }
        if ($user->can('View sale')) {
            $modules[] = 'sales';
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => Str::before((string) $user->name, ' '),
            'email' => $user->email,
            'username' => $user->username,
            'phone_number' => $user->phone_number,
            'user_type' => 'staff',
            'user_type_label' => $user->getRoleNames()->first() ?? 'Staff',
            'avatar_url' => ($img = $user->avatar ?: $user->profile_image) ? (str_starts_with($img, 'http') ? $img : asset('storage/' . ltrim($img, '/'))) : null,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => array_values(array_unique($perms)),
            'modules' => $modules,
            'must_change_password' => false,
        ];
    }
}
