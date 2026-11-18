@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('panel-title', 'Locked out? It happens.')
@section('panel-subtitle', 'We will email you a link to choose a new password.')

@section('form')
    <div class="text-center mt-2">
        <span class="pl-auth-icon"><i class="ri-mail-send-line"></i></span>
        <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">Forgot Password?</h5>
        <p class="text-muted mb-0">Enter the email on your account and we'll send you a reset link.</p>
    </div>

    <div class="p-2 mt-4">
        @if (session('status'))
            <div class="alert alert-success border-0 mb-4" role="alert" style="border-left: 4px solid #10b981 !important;">
                <i class="ri-checkbox-circle-line me-1"></i> {{ session('status') }}
                <div class="small mt-1">The link works for {{ config('auth.passwords.users.expire', 60) }} minutes. Check your spam folder if it doesn't arrive.</div>
            </div>
        @endif

        @if (in_array(config('mail.default'), ['log', 'array'], true))
            <div class="alert alert-warning border-0 small mb-4" role="alert">
                <i class="ri-alert-line me-1"></i> Email isn't set up on this server yet, so reset emails can't be delivered.
                Ask a farm administrator for a reset link instead.
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" data-loading-text="Sending link...">
            @csrf
            <div class="mb-4">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input id="email" type="email" class="form-control apple-input @error('email') is-invalid @enderror" name="email"
                       value="{{ old('email', request('email')) }}" required autocomplete="email" autofocus placeholder="you@farm.com">
                @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>
            <button class="btn btn-primary w-100 apple-button" type="submit"><span>Send Reset Link</span></button>
        </form>

        <p class="pl-auth-note text-center mt-4 mb-0">
            <i class="ri-information-line"></i>
            No access to that email any more? Ask a farm administrator: they can send you a new reset link from <b>Users</b>.
        </p>
    </div>
    <div class="mt-4 text-center">
        <p class="mb-0">Remembered it? <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none">Back to Sign In</a></p>
    </div>
@endsection
