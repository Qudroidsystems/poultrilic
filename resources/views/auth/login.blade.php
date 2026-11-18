@extends('layouts.auth')
@section('title', 'Sign In')

@section('form')
    @php $farmName = \App\Support\FarmSettings::text('farm_name'); @endphp
    <div class="text-center mt-2">
        <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">{{ $farmName ?: 'Farm' }} Portal</h5>
        <p class="text-muted">Sign in to continue</p>
    </div>

    <div class="p-2 mt-3">
        <form method="POST" action="{{ route('login') }}" id="loginForm" data-loading-text="Signing In...">
            @csrf

            @if (session('session_expired') || session('error') || request()->boolean('expired'))
                <div id="sessionExpiredAlert" class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #f59e0b;">
                    <div class="d-flex align-items-center">
                        <i class="ri-alert-line me-2" style="font-size: 18px; color: #d97706;"></i>
                        <div>
                            <strong style="color: #92400e;">{{ session('error') ? 'Please sign in again' : 'Session Expired' }}</strong>
                            <p class="mb-0 small" style="color: #78350f;">{{ session('error') ?? 'Your session has expired. Please login again.' }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #ef4444;">
                    <div class="d-flex align-items-center">
                        <i class="ri-error-warning-line me-2" style="font-size: 18px;"></i>
                        <div>
                            <strong>We couldn't sign you in</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="mb-4">
                <label for="email" class="form-label">Email or username <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="text" class="form-control apple-input @error('email') is-invalid @enderror"
                           id="email" name="email" placeholder="you@farm.com" value="{{ old('email') }}"
                           required autocomplete="username" autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-muted text-decoration-none">Forgot password?</a>
                    @endif
                </div>
                <div class="position-relative auth-pass-inputgroup">
                    <input type="password" id="password" class="form-control apple-input pe-5 @error('password') is-invalid @enderror"
                           name="password" autocomplete="current-password" placeholder="Enter your password" required>
                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon" aria-label="Show password">
                        <i class="ri-eye-fill align-middle"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary w-100 apple-button" type="submit" id="loginButton">
                    <span>Sign In</span>
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="text-center mt-4">
                <p class="mb-0 text-muted small">Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-none">Sign Up</a></p>
            </div>
        @endif
    </div>
@endsection

@push('auth-toasts')
    @if ($errors->has('email') || $errors->has('password'))
        showToast('Login Failed', {{ \Illuminate\Support\Js::from($errors->first('email') ?: $errors->first('password')) }}, 'error');
    @endif
    @if (session('session_expired') || request()->boolean('expired'))
        showToast('Session Expired', 'Your session has expired. Please login again.', 'warning');
    @endif
@endpush
