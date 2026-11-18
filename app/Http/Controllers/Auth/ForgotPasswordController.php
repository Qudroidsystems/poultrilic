<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('sendResetLinkEmail');
    }

    /**
     * Always give the same answer, whether or not the email has an account,
     * so the form can't be used to find out who is registered.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);
        $status = $this->broker()->sendResetLink($this->credentials($request));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'A link was sent a moment ago. Please wait a minute before asking again.']);
        }

        return back()->with('status', 'If that email has an account, a reset link is on its way.');
    }
}
