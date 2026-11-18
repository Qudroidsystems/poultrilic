<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Landing;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;   // includes lock-out after 5 wrong tries (1 minute)

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username()
    {
        return 'email';
    }

    /** The box accepts an email address or a username. */
    protected function credentials(Request $request)
    {
        $login = trim((string) $request->input('email'));
        $field = str_contains($login, '@') ? 'email' : 'username';

        return [$field => $login, 'password' => $request->input('password')];
    }

    protected function validateLogin(Request $request)
    {
        $request->validate(['email' => 'required|string|max:255', 'password' => 'required|string'],
            ['email.required' => 'Enter your email or username.']);
    }

    public function redirectPath()
    {
        return Landing::for(auth()->user());
    }

    protected function authenticated(Request $request, $user)
    {
        // A saved "intended" page is only followed if it stays on this site.
        return redirect()->intended($this->redirectPath());
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
