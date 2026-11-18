<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Landing;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    public function redirectPath()
    {
        return Landing::for(auth()->user());
    }

    protected function rules()
    {
        return [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    protected function resetPassword($user, $password)
    {
        // Hash only; wipe the old plain-text copy and sign out other devices.
        $user->forceFill([
            'password'       => \Hash::make($password),
            'wpassword'      => null,
            'remember_token' => Str::random(60),
        ])->save();

        event(new \Illuminate\Auth\Events\PasswordReset($user));
        $this->guard()->login($user);
    }

    protected function sendResetResponse(\Illuminate\Http\Request $request, $response)
    {
        return redirect($this->redirectPath())->with('success', 'Your password has been changed.');
    }
}
