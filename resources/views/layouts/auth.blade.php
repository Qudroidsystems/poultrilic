<!doctype html>
{{-- Sign-in pages shell, taken from csskabba-portal's login page (Apple-style inputs, animated side panel). --}}
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sign In') | {{ \App\Support\FarmSettings::text('farm_name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="{{ \App\Support\FarmSettings::text('farm_name') }} — staff portal" name="description">
    <meta content="Qudroid Systems" name="author">
    @include('auth.partials.brand-head')
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
        /* =====================================================
           APPLE OS STYLE LOGIN PAGE
           ===================================================== */

        /* Smooth page entrance animation */
        @keyframes pageFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }

        @keyframes fieldPulse {
            0%, 100% { border-color: #e2e8f0; box-shadow: 0 0 0 0 rgba(79, 142, 247, 0); }
            50% { border-color: #4f8ef7; box-shadow: 0 0 0 4px rgba(79, 142, 247, 0.15); }
        }

        @keyframes successCheck {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes orbitClockwise {
            from { transform: rotate(0deg) translate(120px, 0) rotate(0deg); }
            to { transform: rotate(360deg) translate(120px, 0) rotate(-360deg); }
        }

        @keyframes orbitCounterClockwise {
            from { transform: rotate(0deg) translate(120px, 0) rotate(0deg); }
            to { transform: rotate(-360deg) translate(120px, 0) rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(20px); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Page container animation */
        .auth-page-wrapper {
            animation: pageFadeInUp 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
        }

        /* Card entrance animation */
        .card {
            animation: fadeInScale 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            border-radius: 28px !important;
            overflow: hidden;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.98);
        }

        /* Left panel animation */
        .auth-card {
            animation: slideInLeft 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border-radius: 0 !important;
        }

        /* Right panel animation */
        .col-xxl-6 {
            animation: slideInRight 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            animation-delay: 0.1s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        /* Apple-style input fields */
        .apple-input {
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            background: #f8fafc !important;
            transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1) !important;
            font-size: 16px !important;
            padding: 12px 16px !important;
        }

        .apple-input:focus {
            border-color: #4f8ef7 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(79, 142, 247, 0.1), 0 2px 8px rgba(0, 0, 0, 0.05) !important;
            outline: none !important;
        }

        .apple-input:hover {
            border-color: #cbd5e1 !important;
            background: #ffffff !important;
        }

        /* Apple-style button */
        .apple-button {
            background: #4f8ef7 !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 14px 20px !important;
            font-weight: 600 !important;
            font-size: 16px !important;
            transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1) !important;
            position: relative;
            overflow: hidden;
        }

        .apple-button:hover {
            background: #3b7ae3 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 142, 247, 0.35);
        }

        .apple-button:active {
            transform: translateY(1px);
        }

        /* Shake animation for error */
        .shake-field {
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        /* Field pulse on focus */
        .field-pulse {
            animation: fieldPulse 0.6s ease;
        }

        /* Label styling */
        .form-label {
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 6px;
        }

        /* Checkbox styling */
        .form-check-input {
            border-radius: 6px !important;
            border: 1.5px solid #cbd5e1 !important;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background-color: #4f8ef7 !important;
            border-color: #4f8ef7 !important;
        }

        /* Error message styling */
        .invalid-feedback {
            font-size: 12px;
            margin-top: 6px;
            animation: slideInRight 0.3s ease;
        }

        /* Password toggle button */
        .password-addon {
            border-radius: 0 12px 12px 0 !important;
            padding: 0 16px !important;
            transition: opacity 0.2s ease;
        }

        .password-addon:hover {
            opacity: 0.7;
        }

        /* Loading state on button */
        .apple-button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .apple-button.loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            top: 50%;
            left: 50%;
            margin-left: -9px;
            margin-top: -9px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .apple-button.loading span {
            opacity: 0;
        }

        /* Effect circles */
        .effect-circle-1,
        .effect-circle-2,
        .effect-circle-3 {
            transition: all 0.3s ease;
        }

        .effect-circle-1 {
            animation: pulse 2s infinite;
        }

        /* Auth effect main container */
        .auth-effect-main {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }

        /* Avatar orbit animations */
        .auth-user-list {
            position: absolute;
            width: 100%;
            height: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
            top: 0;
            left: 0;
        }

        .auth-user-list li {
            position: absolute;
            width: 50px;
            height: 50px;
            transform-origin: center center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .auth-user-list li:nth-child(1) {
            animation: orbitClockwise 12s linear infinite;
            transform: translate(120px, 0);
        }

        .auth-user-list li:nth-child(2) {
            animation: orbitCounterClockwise 14s linear infinite;
            transform: rotate(72deg) translate(115px, 0);
        }

        .auth-user-list li:nth-child(3) {
            animation: orbitClockwise 10s linear infinite;
            transform: rotate(144deg) translate(125px, 0);
        }

        .auth-user-list li:nth-child(4) {
            animation: orbitCounterClockwise 11s linear infinite;
            transform: rotate(216deg) translate(118px, 0);
        }

        .auth-user-list li:nth-child(5) {
            animation: orbitClockwise 13s linear infinite;
            transform: rotate(288deg) translate(122px, 0);
        }

        .auth-user-list li:hover {
            animation-play-state: paused !important;
            transform: scale(1.2) !important;
            z-index: 10;
        }

        .auth-user-list li:hover .avatar-title {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.7);
        }

        /* Avatar styling */
        .avatar-sm {
            width: 50px;
            height: 50px;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            overflow: hidden;
            border: 2px solid white;
            transition: box-shadow 0.3s ease;
            border-radius: 50%;
        }

        .avatar-title img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Tooltip styling */
        .avatar-tooltip {
            background-color: #1e293b;
            color: #fff;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
        }

        /* School logo styling */
        .school-login-logo {
            height: 55px;
            width: auto;
            border-radius: 14px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .school-login-logo:hover {
            transform: scale(1.02);
        }

        /* Logo container animation */
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
            animation: fadeInScale 0.5s ease;
        }

        /* No staff message styling */
        .no-staff-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-staff-icon {
            font-size: 48px;
            margin-bottom: 10px;
            animation: fadeInScale 0.5s ease;
        }

        /* Toast notification */
        .login-success {
            position: fixed;
            top: 24px;
            right: 24px;
            color: white;
            padding: 16px 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            animation: successCheck 0.4s cubic-bezier(0.34, 1.3, 0.64, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 420px;
            min-width: 300px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .login-success.success { background: linear-gradient(135deg, #10b981, #059669); }
        .login-success.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .login-success.info { background: linear-gradient(135deg, #4f8ef7, #3b7ae3); }
        .login-success.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }

        .login-success .toast-icon {
            font-size: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .login-success .toast-content {
            flex: 1;
        }

        .login-success .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .login-success .toast-message {
            font-size: 12px;
            opacity: 0.92;
            line-height: 1.4;
        }

        /* Gold border highlight for session expired */
        .session-expired-highlight {
            border-left: 4px solid #f59e0b !important;
            transition: all 0.5s ease;
        }

        .session-expired-highlight-remove {
            border-left: 4px solid transparent !important;
            transition: all 0.5s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .auth-effect-main { width: 200px; height: 200px; }
            .auth-user-list li { width: 40px; height: 40px; }
            .auth-user-list li:nth-child(1) { transform: translate(80px, 0); }
            .auth-user-list li:nth-child(2) { transform: rotate(72deg) translate(78px, 0); }
            .auth-user-list li:nth-child(3) { transform: rotate(144deg) translate(82px, 0); }
            .auth-user-list li:nth-child(4) { transform: rotate(216deg) translate(79px, 0); }
            .auth-user-list li:nth-child(5) { transform: rotate(288deg) translate(81px, 0); }
            @keyframes orbitClockwise {
                from { transform: rotate(0deg) translate(80px, 0) rotate(0deg); }
                to { transform: rotate(360deg) translate(80px, 0) rotate(-360deg); }
            }
            @keyframes orbitCounterClockwise {
                from { transform: rotate(0deg) translate(80px, 0) rotate(0deg); }
                to { transform: rotate(-360deg) translate(80px, 0) rotate(360deg); }
            }
            .school-login-logo { height: 40px; }
            .login-success { top: 12px; right: 12px; left: 12px; max-width: none; min-width: auto; }
        }

        /* password checklist (csskabba register page) */
.password-strength-container {
            transition: all 0.3s ease;
        }
.password-requirement {
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
.password-requirement.valid {
            color: #10b981;
        }
.password-requirement.valid i {
            color: #10b981;
        }
.password-requirement.invalid {
            color: #94a3b8;
        }
.password-requirement i {
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .pl-auth-note { font-size: 13px; color: #64748b; }
        .pl-auth-icon { width: 64px; height: 64px; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 30px; color: #0d9488; background: rgba(13,148,136,.1); margin-bottom: 14px; }
    </style>
    @stack('auth-styles')
</head>

<body>
    <section class="auth-page-wrapper position-relative d-flex align-items-center justify-content-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="card mb-0 border-0 shadow-lg">
                        <div class="row g-0 align-items-center">
                            @include('auth.partials.side-panel', ['title' => trim($__env->yieldContent('panel-title')) ?: null, 'subtitle' => trim($__env->yieldContent('panel-subtitle')) ?: null])
                            <!--end col-->

                            <div class="col-xxl-6 mx-auto">
                                <div class="card mb-0 border-0 shadow-none mb-0" style="background: transparent;">
                                    <div class="card-body p-sm-5 m-lg-4" id="loginCardBody">
                                        @include('auth.partials.logo')
                                        @yield('form')
                                    </div><!-- end card body -->
                                </div><!-- end card -->
                            </div>
                            <!--end col-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/js/plugins.js')}}"></script>
    <script>
        // =====================================================
        // TOAST NOTIFICATION FUNCTION
        // =====================================================
        function showToast(title, message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.login-success');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = 'login-success ' + type;

            const icons = {
                success: 'ri-checkbox-circle-line',
                error: 'ri-close-circle-line',
                info: 'ri-information-line',
                warning: 'ri-alert-line'
            };

            toast.innerHTML = `
                <i class="${icons[type] || icons.info} toast-icon"></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;

            document.body.appendChild(toast);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.4s ease forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 400);
            }, 5000);

            // Allow manual dismiss by clicking
            toast.addEventListener('click', function() {
                this.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(() => {
                    if (this.parentNode) {
                        this.remove();
                    }
                }, 300);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function (el) {
                new bootstrap.Tooltip(el, { template: '<div class="tooltip avatar-tooltip" role="tooltip"><div class="tooltip-inner"></div></div>' });
            });
            document.querySelectorAll('.auth-user-list li').forEach(function (item) {
                item.addEventListener('mouseenter', function () { this.style.animationPlayState = 'paused'; });
                item.addEventListener('mouseleave', function () { this.style.animationPlayState = 'running'; });
            });

            // focus pulse, and clear an error as soon as the field changes
            document.querySelectorAll('.apple-input').forEach(function (input) {
                input.addEventListener('focus', function () {
                    this.classList.add('field-pulse');
                    setTimeout(() => this.classList.remove('field-pulse'), 600);
                });
                input.addEventListener('input', function () { this.classList.remove('is-invalid'); });
            });
            document.querySelectorAll('.apple-input.is-invalid').forEach(function (el) {
                el.classList.add('shake-field');
                if (navigator.vibrate) navigator.vibrate(100);
                setTimeout(() => el.classList.remove('shake-field'), 400);
            });

            // show / hide password (every eye button toggles the input next to it)
            document.querySelectorAll('.password-addon').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = this.parentElement.querySelector('input');
                    if (!input) return;
                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    const icon = this.querySelector('i');
                    if (icon) icon.className = (show ? 'ri-eye-off-fill' : 'ri-eye-fill') + ' align-middle';
                });
            });

            // live password checklist (register / reset)
            const pw = document.querySelector('[data-password-rules]');
            if (pw) {
                const rules = {
                    'pass-length': v => v.length >= 8,
                    'pass-lower': v => /[a-z]/.test(v),
                    'pass-upper': v => /[A-Z]/.test(v),
                    'pass-number': v => /[0-9]/.test(v),
                };
                pw.addEventListener('input', function () {
                    Object.entries(rules).forEach(([id, test]) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        const ok = test(pw.value);
                        el.classList.toggle('valid', ok);
                        el.classList.toggle('invalid', !ok);
                        const i = el.querySelector('i');
                        if (i) i.className = ok ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill';
                    });
                });
            }

            // loading state on submit
            document.querySelectorAll('form[data-loading-text]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const btn = form.querySelector('button[type=submit]');
                    if (!btn) return;
                    btn.classList.add('loading');
                    const span = btn.querySelector('span');
                    if (span) span.textContent = form.dataset.loadingText;
                    btn.disabled = true;
                });
            });

            @if (session('status'))
                showToast('Done', {{ \Illuminate\Support\Js::from(session('status')) }}, 'success');
            @endif
            @stack('auth-toasts')
        });
    </script>
    @stack('auth-scripts')
</body>
</html>
