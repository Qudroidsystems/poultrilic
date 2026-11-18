@extends('layouts.auth')
@section('title', 'Sign Up')
@section('panel-title', 'Join the farm team')
@section('panel-subtitle', 'Create your account; an administrator gives you access.')

@section('form')
    <div class="text-center mt-2">
        <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">Create your account</h5>
        <p class="text-muted">After you sign up, a farm administrator assigns your role.</p>
    </div>
    <div class="p-2 mt-4">
        <form method="POST" action="{{ route('register') }}" id="registerForm" data-loading-text="Creating account...">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input id="name" type="text" class="form-control apple-input @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="e.g. Grace Nakato">
                @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="mb-4">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input id="email" type="email" class="form-control apple-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@farm.com">
                @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password" class="form-control apple-input password-input pe-5 @error('password') is-invalid @enderror"
                           placeholder="Create a strong password" id="password-input" name="password" autocomplete="new-password" required data-password-rules>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">
                        <i class="ri-eye-fill align-middle"></i>
                    </button>
                </div>
                @error('password')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="password-confirm">Confirm Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password" class="form-control apple-input pe-5" placeholder="Type the password again" id="password-confirm" name="password_confirmation" autocomplete="new-password" required>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password">
                        <i class="ri-eye-fill align-middle"></i>
                    </button>
                </div>
            </div>

            @include('auth.partials.password-rules')

            <div class="mt-4">
                <button class="btn btn-primary w-100 apple-button" type="submit" id="registerButton"><span>Create Account</span></button>
            </div>
        </form>
    </div>
    <div class="mt-4 text-center">
        <p class="mb-0">Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none">Sign In</a></p>
    </div>
@endsection
