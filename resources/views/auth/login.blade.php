<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>Sign In | Poultritics Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Poultry Management Portal" name="description">
    <meta content="Your Company" name="author">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Layout config Js -->
    <script src="{{ asset('theme/layouts/assets/js/layout.js') }}"></script>

    <!-- Bootstrap Css -->
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <!-- App Css-->
    <link href="{{ asset('theme/layouts/assets/css/app.min.css') }}" rel="stylesheet" type="text/css">
    <!-- custom Css-->
    <link href="{{ asset('theme/layouts/assets/css/custom.min.css') }}" rel="stylesheet" type="text/css">

    <style>
        .auth-logo {
            max-width: 180px;
            height: auto;
        }

        @media (max-width: 576px) {
            .auth-logo {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>

<section class="auth-page-wrapper position-relative d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="card mb-0 border-0 shadow">
                    <div class="row g-0 align-items-center">
                        <div class="col-xxl-6 mx-auto">
                            <div class="card mb-0 border-0 shadow-none">
                                <div class="card-body p-4 p-sm-5 m-lg-4">
                                    <!-- Logo & Title -->
                                    <div class="text-center mb-5">
                                        <img src="{{ asset('website/assets/images/logo/prime-farm-logo2.jpeg') }}" 
                                             alt="Poultritics Portal Logo" 
                                             class="auth-logo mb-4">

                                        <h4 class="fs-2xl fw-bold">Prime Farm Portal</h4>
                                        <p class="text-muted mt-2">Sign in to continue to your dashboard</p>
                                    </div>

                                    <!-- Login Form -->
                                    <div class="p-2">
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf

                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" 
                                                       class="form-control @error('email') is-invalid @enderror" 
                                                       id="email" 
                                                       name="email" 
                                                       placeholder="Enter your email" 
                                                       value="{{ old('email') }}" 
                                                       required 
                                                       autocomplete="email" 
                                                       autofocus>
                                                @error('email')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                @if (Route::has('password.request'))
                                                    <div class="float-end">
                                                        <a href="{{ route('password.request') }}" class="text-muted small">Forgot password?</a>
                                                    </div>
                                                @endif
                                                <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                                <div class="position-relative auth-pass-inputgroup mb-3">
                                                    <input type="password" 
                                                           class="form-control pe-5 password-input @error('password') is-invalid @enderror" 
                                                           name="password" 
                                                           placeholder="Enter password" 
                                                           id="password" 
                                                           required 
                                                           autocomplete="current-password">
                                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" 
                                                            type="button" 
                                                            id="password-addon">
                                                        <i class="ri-eye-fill align-middle"></i>
                                                    </button>
                                                </div>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="remember">Remember me</label>
                                            </div>

                                            <div class="mt-4">
                                                <button class="btn btn-primary w-100" type="submit">Sign In</button>
                                            </div>
                                        </form>

                                        <div class="text-center mt-5">
                                            <p class="mb-0 text-muted">
                                                Don't have an account? 
                                                <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-underline"> Sign Up</a>
                                            </p>
                                        </div>
                                    </div>
                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </div>
                        <!--end col-->
                    </div>
                    <!--end row-->
                </div>
            </div>
            <!--end col-->
        </div>
        <!--end row-->
    </div>
    <!--end container-->
</section>

<!-- JAVASCRIPT -->
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/plugins.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/pages/password-addon.init.js') }}"></script>

</body>
</html>