<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Landing;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Self sign-up. New accounts get no role; a farm administrator assigns one.
 * Turn sign-up off with ALLOW_REGISTRATION=false in .env.
 */
class RegisterController extends Controller
{
    use RegistersUsers;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function redirectPath()
    {
        return Landing::for(auth()->user());
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
    }

    protected function create(array $data)
    {
        $parts = explode(' ', trim($data['name']), 2);

        return User::forceCreate([
            'first_name' => $parts[0],
            'last_name'  => $parts[1] ?? '',
            'username'   => $data['email'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
        ]);
    }
}
