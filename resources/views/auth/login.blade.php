<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">


    
<!-- Mirrored from themesbrand.com/steex/layouts/auth-signin.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 12 Jun 2023 02:58:32 GMT -->
<head>

        <meta charset="utf-8">
        <title>Sign In | Vite-ESchool</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="school  App" name="description">
        <meta content="Themesbrand" name="author">
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico')}}">

        <!-- Fonts css load -->
        <link rel="preconnect" href="https://fonts.googleapis.com/">
        <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
        <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">

        <!-- Layout config Js -->
        <script src="{{ asset('theme/layouts/assets/js/layout.js')}}"></script>
        <!-- Bootstrap Css -->
        <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
        <!-- Icons Css -->
        <link href="{{ asset('theme/layouts/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css">
        <!-- App Css-->
        <link href="{{ asset('theme/layouts/assets/css/app.min.css')}}" rel="stylesheet" type="text/css">
        <!-- custom Css-->
        <link href="{{ asset('theme/layouts/assets/css/custom.min.css')}}" rel="stylesheet" type="text/css">
        <style>

            @media (max-width: 576px) {
                .auth-effect-main {
                    width: 200px;
                    height: 200px;
                }
                .auth-user-list li {
                    width: 40px;
                    height: 40px;
                }
                .auth-user-list li:nth-child(1) { transform: translate(80px, 0); }
                .auth-user-list li:nth-child(2) { transform: rotate(72deg) translate(78px, 0); }
                .auth-user-list li:nth-child(3) { transform: rotate(144deg) translate(82px, 0); }
                .auth-user-list li:nth-child(4) { transform: rotate(216deg) translate(79px, 0); }
                .auth-user-list li:nth-child(5) { transform: rotate(288deg) translate(81px, 0); }
            }

            @media (max-width: 576px) {
                .avatar-tooltip {
                    font-size: 12px;
                    padding: 3px 8px;
                    bottom: 50px;
                }
            }
          
            /* Ensure the parent container is positioned relatively to act as the reference point */
            .auth-effect-main {
                position: relative;
                width: 300px; /* Adjust based on your design */
                height: 300px; /* Match width to keep it circular */
            }

            /* Style the auth-user-list to be a container for orbiting avatars */
            .auth-user-list {
                position: absolute;
                width: 100%;
                height: 100%;
                list-style: none;
                padding: 0;
                margin: 0;
            }

            /* Style each avatar item */
            .auth-user-list li {
                position: absolute;
                width: 50px; /* Size of the avatar */
                height: 50px;
                transform-origin: center center;
            }

            /* Define animations for each avatar with different directions, speeds, and radii */
            .auth-user-list li:nth-child(1) {
                animation: orbit-clockwise 9s linear infinite;
                transform: translate(120px, 0); /* Base radius */
            }

            .auth-user-list li:nth-child(2) {
                animation: orbit-counterclockwise 11s linear infinite;
                transform: rotate(72deg) translate(115px, 0); /* Slightly varied radius */
            }

            .auth-user-list li:nth-child(3) {
                animation: orbit-clockwise 10s linear infinite;
                transform: rotate(144deg) translate(125px, 0); /* Slightly varied radius */
            }

            .auth-user-list li:nth-child(4) {
                animation: orbit-counterclockwise 8s linear infinite;
                transform: rotate(216deg) translate(118px, 0); /* Slightly varied radius */
            }

            .auth-user-list li:nth-child(5) {
                animation: orbit-clockwise 12s linear infinite;
                transform: rotate(288deg) translate(122px, 0); /* Slightly varied radius */
            }

            /* Keyframes for clockwise orbit */
            @keyframes orbit-clockwise {
                from {
                    transform: rotate(0deg) translate(120px, 0) rotate(0deg);
                }
                to {
                    transform: rotate(360deg) translate(120px, 0) rotate(-360deg);
                }
            }

            /* Keyframes for counterclockwise orbit */
            @keyframes orbit-counterclockwise {
                from {
                    transform: rotate(0deg) translate(120px, 0) rotate(0deg);
                }
                to {
                    transform: rotate(-360deg) translate(120px, 0) rotate(360deg);
                }
            }

            /* Ensure avatars remain circular and visible */
            .avatar-sm {
                width: 50px;
                height: 50px;
            }

            .avatar-title {
                width: 100%;
                height: 100%;
                overflow: hidden;
            }

            .avatar-title img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .avatar-tooltip {`
                background-color: #6c757d; /* Secondary color from bg-secondary */
                color: #fff;
                border: 1px solid #fff;
            }
        </style>

    </head>

    <body>


        <section class="auth-page-wrapper  position-relative d-flex align-items-center justify-content-center min-vh-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-11">
                        <div class="card mb-0">
                            <div class="row g-0 align-items-center">
                                {{-- <div class="col-xxl-5">
                                    <div class="card auth-card bg-secondary h-100 border-0 shadow-none d-none d-sm-block mb-0">
                                        <div class="card-body py-5 d-flex justify-content-between flex-column">
                                            <div class="text-center">
                                                <h3 class="text-white">Start your journey with us.</h3>
                                                <p class="text-white opacity-75 fs-base">It makes school operations SEEMLESS...</p>
                                            </div>
                                
                                            <div class="auth-effect-main my-5 position-relative rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                                <div class="effect-circle-1 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                    <div class="effect-circle-2 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                        <div class="effect-circle-3 mx-auto rounded-circle position-relative text-white fs-4xl d-flex align-items-center justify-content-center">
                                                            Welcome to <span class="text-primary ms-1">Vite-ESchool</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul class="auth-user-list list-unstyled">
                                                    @foreach ($users->take(5) as $index => $user)
                                                        <li>
                                                            <a href="{{ route('users.show', $user->id) }}" class="avatar-sm d-inline-block" title="{{ $user->name }}">
                                                                <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                                    <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" class="img-fluid">
                                                                </div>
                                                                <span class="avatar-tooltip">{{ $user->name }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                
                                            <div class="text-center">
                                                <p class="text-white opacity-75 mb-0 mt-3">
                                                    © <script>document.write(new Date().getFullYear())</script> Vite-ESchool. Created with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                              
                                <!--end col-->
                                <div class="col-xxl-6 mx-auto">
                                    <div class="card mb-0 border-0 shadow-none mb-0">
                                        <div class="card-body p-sm-5 m-lg-4">
                                            <div class="text-center mt-5">
                                                <h5 class="fs-3xl">Poultritics Portal</h5>
                                                <p class="text-muted">Sign in to continue</p>
                                            </div>
                                            <div class="p-2 mt-5">
                                                <form method="POST" action="{{ route('login') }}">
                                                @csrf
                            
                                                    <div class="mb-3">
                                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                                        <div class="position-relative ">
                                                            <input type="email" class="form-control  password-input @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                                            @error('email')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                   
                            
                                                    <div class="mb-3">
                                                         @if (Route::has('password.request'))
                                                                <div class="float-end">
                                                                    <a href="{{ route('password.request') }}" class="text-muted">Forgot password?</a>
                                                                </div>
                                                         @endif
                                                        <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                                            <input type="password" id="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" autocomplete="current-password" placeholder="Enter password" id="password-input" required>
                                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                        </div>
                                                        @error('password')
                                                            <span class="invalid-feedback" role="alert">
                                                                <strong>{{ $message }}</strong>
                                                            </span>
                                                        @enderror
                                                    </div>

                            
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="auth-remember-check">Remember me</label>
                                                    </div>
                            
                                                    <div class="mt-4">
                                                        <button class="btn btn-primary w-100" type="submit">Sign In</button>
                                                    </div>
                            
                                                    <!-- <div class="mt-4 pt-2 text-center">
                                                        <div class="signin-other-title position-relative">
                                                            <h5 class="fs-sm mb-4 title">Sign In with</h5>
                                                        </div>
                                                        <div class="pt-2 hstack gap-2 justify-content-center">
                                                            <button type="button" class="btn btn-subtle-primary btn-icon"><i class="ri-facebook-fill fs-lg"></i></button>
                                                            <button type="button" class="btn btn-subtle-danger btn-icon"><i class="ri-google-fill fs-lg"></i></button>
                                                            <button type="button" class="btn btn-subtle-dark btn-icon"><i class="ri-github-fill fs-lg"></i></button>
                                                            <button type="button" class="btn btn-subtle-info btn-icon"><i class="ri-twitter-fill fs-lg"></i></button>
                                                        </div>
                                                    </div> -->
                                                </form>
                            
                                                <div class="text-center mt-5">
                                                    <p class="mb-0">Don't have an account ? <a href="{{ route('register') }}" class="fw-semibold text-secondary text-decoration-underline"> SignUp</a> </p>
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
        <script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js')}}"></script>
        <script src="{{ asset('theme/layouts/assets/js/plugins.js')}}"></script>
        

        
        <script src="{{ asset('theme/layouts/assets/js/pages/password-addon.init.js')}}"></script>
        
        <!--Swiper slider js-->
        <script src="{{ asset('theme/layouts/assets/libs/swiper/swiper-bundle.min.js')}}"></script>
        
        <!-- swiper.init js -->
        <script src="{{ asset('theme/layouts/assets/js/pages/swiper.init.js')}}"></script>

    </body>


<!-- Mirrored from themesbrand.com/steex/layouts/auth-signin.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 12 Jun 2023 02:58:33 GMT -->
</html>