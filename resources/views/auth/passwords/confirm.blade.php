@extends('layouts.auth')
@section('title', 'Confirm Password')
@section('panel-title', 'Just checking it is you.')
@section('panel-subtitle', 'Sensitive pages ask for your password again.')

@section('form')
    <div class="text-center mt-2">
        <span class="pl-auth-icon"><i class="ri-shield-keyhole-line"></i></span>
        <h5 class="fs-2xl fw-semibold">Confirm your password</h5>
        <p class="text-muted mb-0">Please enter your password to continue.</p>
    </div>
    <div class="p-2 mt-4">
        <form method="POST" action="{{ route('password.confirm') }}" data-loading-text="Checking...">
            @csrf
            <div class="mb-4">
                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                <div class="position-relative auth-pass-inputgroup">
                    <input id="password" type="password" class="form-control apple-input pe-5 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" autofocus>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" aria-label="Show password"><i class="ri-eye-fill align-middle"></i></button>
                </div>
                @error('password')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
            </div>
            <button class="btn btn-primary w-100 apple-button" type="submit"><span>Confirm Password</span></button>
        </form>
    </div>
    <div class="mt-4 text-center">
        <a href="{{ route('password.request') }}" class="fw-semibold text-primary text-decoration-none">Forgot your password?</a>
    </div>
@endsection
