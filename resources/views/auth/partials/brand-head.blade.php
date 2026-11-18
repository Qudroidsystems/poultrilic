{{-- Favicon and the farm name for the sign-in pages (csskabba's auth pages show the school logo here). --}}
@php
    $farmName = \App\Support\FarmSettings::text('farm_name') ?: 'Poultrilic';
@endphp
<link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('theme/layouts/assets/images/favicon.ico') }}">
<style>
    .pl-auth-logo { display:inline-flex; align-items:center; gap:10px; text-decoration:none; }
    .pl-auth-logo .mark { width:44px; height:44px; border-radius:13px; display:inline-flex; align-items:center; justify-content:center;
        font-size:24px; color:#fff; background:linear-gradient(135deg,#0f2342,#0d9488); box-shadow:0 6px 18px rgba(13,148,136,.3); }
    .pl-auth-logo .word { font-weight:700; font-size:24px; letter-spacing:-.02em; color:#0f2342; }
    [data-bs-theme=dark] .pl-auth-logo .word { color:#f5f5f7; }
    .pl-orbit-icon { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:20px; color:#0d9488; }
</style>
