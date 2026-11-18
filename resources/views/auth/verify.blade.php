@extends('layouts.auth')
@section('title', 'Verify Your Email')
@section('panel-title', 'One more step.')
@section('panel-subtitle', 'Confirm your email so password resets can reach you.')

@section('form')
    <div class="text-center mt-2">
        <span class="pl-auth-icon"><i class="ri-mail-check-line"></i></span>
        <h5 class="fs-2xl fw-semibold">Verify your email address</h5>
        <p class="text-muted">We sent a verification link to <b>{{ auth()->user()->email }}</b>. Open it to confirm your address.</p>
    </div>
    @if (session('resent'))
        <div class="alert alert-success border-0 mt-3" role="alert">A fresh verification link has been sent to your email address.</div>
    @endif
    <form method="POST" action="{{ route('verification.resend') }}" class="mt-4" data-loading-text="Sending...">
        @csrf
        <button type="submit" class="btn btn-primary w-100 apple-button"><span>Send the link again</span></button>
    </form>
    <div class="mt-4 text-center">
        <a href="{{ route('home') }}" class="fw-semibold text-primary text-decoration-none">Skip for now</a>
    </div>
@endsection
