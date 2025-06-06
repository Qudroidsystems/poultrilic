<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">



<!-- Mirrored from themesbrand.com/steex/layouts/auth-signup.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 12 Jun 2023 02:58:33 GMT -->
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

</head>

<body>


    <section class="auth-page-wrapper py-5 position-relative d-flex align-items-center justify-content-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="card mb-0">
                        <div class="row g-0 align-items-center">
                            <div class="col-xxl-5">
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
                                                <li>
                                                    <div class="avatar-sm d-inline-block">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-1.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-2.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-3.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-4.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-5.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                        
                                        <div class="text-center">
                                            <p class="text-white opacity-75 mb-0 mt-3">
                                                &copy; <script>document.write(new Date().getFullYear())</script> Vite-ESchool. Created with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6 mx-auto">
                                <div class="card mb-0 border-0 shadow-none mb-0">
                                    <div class="card-body p-sm-5 m-lg-4">
                                        <div class="text-center mt-2">
                                            <h5 class="fs-3xl">Create your free account</h5>
                                            {{-- <p class="text-muted">Get your free Steex account now</p> --}}
                                        </div>
                                        <div class="p-2 mt-5">
                                            <form method="POST" action="{{ route('register') }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="useremail" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                                    <div class="invalid-feedback">
                                                        Please enter email
                                                    </div>
                                                    @error('name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Fullname <span class="text-danger">*</span></label>
                                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                                    <div class="invalid-feedback">
                                                        Please enter Fullname
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                                                    <div class="position-relative auth-pass-inputgroup">
                                                        <input type="password" class="form-control password-input pe-5 @error('password') is-invalid @enderror" onpaste="return false" placeholder="Enter password" id="password-input" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" autocomplete="new-password" required>
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                        <div class="invalid-feedback">
                                                            Please enter password
                                                        </div>
                                                    </div>
                                                    @error('password')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                {{-- <div class="mb-4">
                                                    <p class="mb-0 fs-xs text-muted fst-italic">By registering you agree to the Steex <a href="pages-term-conditions.html" class="text-primary text-decoration-underline fst-normal fw-medium">Terms of Use</a></p>
                                                </div> --}}

                                                <div id="password-contain" class="p-3 bg-light mb-2 rounded">
                                                    <h5 class="fs-sm">Password must contain:</h5>
                                                    <p id="pass-length" class="invalid fs-xs mb-2">Minimum <b>8 characters</b></p>
                                                    <p id="pass-lower" class="invalid fs-xs mb-2">At <b>lowercase</b> letter (a-z)</p>
                                                    <p id="pass-upper" class="invalid fs-xs mb-2">At least <b>uppercase</b> letter (A-Z)</p>
                                                    <p id="pass-number" class="invalid fs-xs mb-0">A least <b>number</b> (0-9)</p>
                                                </div>

                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100" type="submit">Sign Up</button>
                                                </div>

                                                {{-- <div class="mt-4 text-center">
                                                    <div class="signin-other-title position-relative">
                                                        <h5 class="fs-sm mb-4 title text-muted">Create account with</h5>
                                                    </div>

                                                    <div>
                                                        <button type="button" class="btn btn-subtle-primary btn-icon "><i class="ri-facebook-fill fs-lg"></i></button>
                                                        <button type="button" class="btn btn-subtle-danger btn-icon "><i class="ri-google-fill fs-lg"></i></button>
                                                        <button type="button" class="btn btn-subtle-dark btn-icon "><i class="ri-github-fill fs-lg"></i></button>
                                                        <button type="button" class="btn btn-subtle-info btn-icon "><i class="ri-twitter-fill fs-lg"></i></button>
                                                    </div>
                                                </div> --}}
                                            </form>
                                        </div>
                                        <div class="mt-4 text-center">
                                            <p class="mb-0">Already have an account ? <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-underline"> Signin </a> </p>
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

    <!-- password create init js-->
    <script src="{{ asset('theme/layouts/assets/js/pages/passowrd-create.init.js')}}"></script>

</body>


</html>