@extends('layouts.auth')
@section('title', 'Waiting for Access')
@section('panel-title', 'Welcome aboard.')
@section('panel-subtitle', 'Your account is ready; your role is on its way.')

@section('form')
    <div class="text-center mt-2">
        <span class="pl-auth-icon"><i class="ri-time-line"></i></span>
        <h5 class="fs-2xl fw-semibold">Hi {{ auth()->user()->first_name }}, you're signed in</h5>
        <p class="text-muted">Your account doesn't have a role yet, so there is nothing to show you.
            A farm administrator needs to give you a role (for example Record Keeper or Sales Officer) under
            <b>Users &amp; Privileges</b>. Once that is done, sign in again or refresh this page.</p>
    </div>
    <div class="d-flex gap-2 mt-4">
        <a href="{{ route('dashboard') }}" class="btn btn-light w-50 apple-button" style="color:#0f2342"><span>Refresh</span></a>
        <form method="POST" action="{{ route('logout') }}" class="w-50">
            @csrf
            <button class="btn btn-primary w-100 apple-button" type="submit"><span>Sign Out</span></button>
        </form>
    </div>
@endsection
