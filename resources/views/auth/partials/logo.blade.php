@php
    $farmName = \App\Support\FarmSettings::text('farm_name');
    $farmLogo = \App\Services\Website\WebsiteContent::farm()->logo;
@endphp
<div class="logo-container text-center">
    <a href="{{ url('/') }}" class="pl-auth-logo">
        <span class="mark {{ $farmLogo ? 'img' : '' }}">@if ($farmLogo)<img src="{{ $farmLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span>
        <span class="word">{{ $farmName }}</span>
    </a>
</div>
