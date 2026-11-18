@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">User Overview - {{ $user->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Overview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xxl-12">

                    <!-- Tabs & Navigation -->
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                        <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails">
                                    <i class="ri-user-line me-1"></i> Profile & Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#activityTimeline">
                                    <i class="ri-history-line me-1"></i> Activity Timeline
                                </a>
                            </li>
                        </ul>
                        <div class="flex-shrink-0 ms-auto">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- Personal Details -->
                                <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                    <div class="text-center mb-5">
                                        <div class="position-relative d-inline-block">
                                            <div class="avatar-xxl">
                                                @php
                                                    $avatarUrl = asset('images/default-avatar.png');
                                                    $hasAvatar = false;

                                                    if ($user->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_image)) {
                                                        $avatarUrl = asset('storage/' . $user->profile_image);
                                                        $hasAvatar = true;
                                                    }

                                                    $initials = strtoupper(
                                                        substr($user->first_name ?? ($user->name ? explode(' ', $user->name)[0] : 'U'), 0, 1) .
                                                        substr($user->last_name ?? (explode(' ', $user->name)[1] ?? ''), 0, 1)
                                                    );
                                                @endphp

                                                @if($hasAvatar)
                                                    <img src="{{ $avatarUrl }}?t={{ time() }}"
                                                         alt="Profile"
                                                         class="rounded-circle img-thumbnail"
                                                         style="width: 150px; height: 150px; object-fit: cover;">
                                                @else
                                                    <div class="avatar-title rounded-circle bg-light text-primary fs-1"
                                                         style="width: 150px; height: 150px; line-height: 150px;">
                                                        {{ $initials }}
                                                    </div>
                                                @endif
                                            </div>
                                            <h4 class="mt-3 mb-1">{{ $user->name }}</h4>
                                            <p class="text-muted mb-0">{{ $user->email }}</p>
                                            <div class="mt-2">
                                                @foreach($user->roles as $role)
                                                    <span class="badge bg-info me-1">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- View-only personal information -->
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Personal Information</h5></div>

                                            <div class="col-md-6">
                                                <label>First Name</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->firstname ?? $user->first_name ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Last Name</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->lastname ?? $user->last_name ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Other Names</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->othernames ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Phone Number</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->phone ?? $user->phone_number ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Gender</label>
                                                <input type="text" class="form-control" value="{{ ucfirst($userbio?->gender ?? $user->gender ?? 'N/A') }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Nationality</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->nationality ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Date of Birth</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->dob ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-12">
                                                <label>Address</label>
                                                <textarea class="form-control" rows="3" readonly>{{ $userbio?->address ?? 'N/A' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Timeline -->
                                <div class="tab-pane" id="activityTimeline" role="tabpanel">
                                    <h5 class="mb-4">Account Activity Timeline</h5>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->created_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Account Created</h6>
                                                <p class="text-muted mb-0">User account was registered on the platform</p>
                                            </div>
                                        </div>

                                        @if($user->email_verified_at)
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->email_verified_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Email Verified</h6>
                                                <p class="text-muted mb-0">Email address was confirmed</p>
                                            </div>
                                        </div>
                                        @endif

                                        @if($userbio?->updated_at)
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $userbio->updated_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Profile Updated</h6>
                                                <p class="text-muted mb-0">Personal information was last modified</p>
                                            </div>
                                        </div>
                                        @endif

                                        @if($user->roles->count())
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->updated_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Role Assignment</h6>
                                                <p class="text-muted mb-0">Assigned roles: {{ $user->roles->pluck('name')->implode(', ') }}</p>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="timeline-item">
                                            <div class="timeline-date">—</div>
                                            <div class="timeline-content">
                                                <h6>Last Login</h6>
                                                <p class="text-muted mb-0">Login activity tracking coming soon</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 18px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #4e73df;
    border: 3px solid #fff;
    box-shadow: 0 0 0 4px #e3e8ff;
}
.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}
.timeline-content h6 {
    margin: 0 0 5px 0;
    color: #495057;
}
.avatar-xxl {
    width: 150px;
    height: 150px;
}
.avatar-xxl img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.border-bottom {
    border-bottom: 2px solid var(--bs-border-color) !important;
}
</style>
@endsection
