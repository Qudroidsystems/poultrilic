@extends('layouts.auth')
@section('title', 'Choose a New Password')
@section('panel-title', 'Almost there.')
@section('panel-subtitle', 'Choose a new password and you are back in.')

@section('form')
    <div class="text-center mt-2">
        <span class="pl-auth-icon"><i class="ri-lock-password-line"></i></span>
        <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">Create new password</h5>
        <p class="text-muted mb-0">Your new password must be different from the old one.</p>
    </div>

    <div class="p-2 mt-4">
        <form method="POST" action="{{ route('password.update') }}" data-loading-text="Saving...">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input id="email" type="email" class="form-control apple-input @error('email') is-invalid @enderror" name="email"
                       value="{{ $email ?? old('email') }}" required autocomplete="email" @if (empty($email)) autofocus @else readonly @endif>
                @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="password-input">New Password <span class="text-danger">*</span></label>
                <div class="position-relative auth-pass-inputgroup">
                    <input type="password" class="form-control apple-input pe-5 @error('password') is-invalid @enderror" placeholder="Enter a new password"
                           id="password-input" name="password" autocomplete="new-password" required @if (! empty($email)) autofocus @endif data-password-rules>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password"><i class="ri-eye-fill align-middle"></i></button>
                </div>
                @error('password')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="confirm-password-input">Confirm Password <span class="text-danger">*</span></label>
                <div class="position-relative auth-pass-inputgroup">
                    <input type="password" class="form-control apple-input pe-5" placeholder="Type it again" id="confirm-password-input" name="password_confirmation" autocomplete="new-password" required>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password"><i class="ri-eye-fill align-middle"></i></button>
                </div>
            </div>

            @include('auth.partials.password-rules')

            <button class="btn btn-primary w-100 apple-button" type="submit"><span>Reset Password</span></button>
        </form>
    </div>
    <div class="mt-4 text-center">
        <p class="mb-0">Link not working? <a href="{{ route('password.request') }}" class="fw-semibold text-primary text-decoration-none">Request a new one</a></p>
    </div>
@endsection
