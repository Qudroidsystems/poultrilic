@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">User Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">User Overview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xxl-12">

                    <!-- Back Button & Tabs -->
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                        <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                    Profile & Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#activityTimeline" role="tab">
                                    Activity Timeline
                                </a>
                            </li>
                        </ul>
                        <div class="flex-shrink-0 ms-auto">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left align-baseline me-1"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- Tab: Profile & Editable Details -->
                                <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                    <form id="updateUserForm" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $user->id }}">

                                        <!-- Profile Header -->
                                        <div class="text-center mb-4">
                                            <div class="position-relative d-inline-block">
                                                <div class="avatar-xl">
                                                    @if($user->profile_image)
                                                        <img src="{{ asset('storage/' . $user->profile_image) }}"
                                                             alt="Profile" class="rounded-circle img-thumbnail" id="profilePreview">
                                                    @else
                                                        <div class="avatar-title rounded-circle bg-light text-primary fs-1">
                                                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <label for="profile_image" class="position-absolute bottom-0 end-0 btn btn-sm btn-icon btn-primary rounded-circle">
                                                    <i class="ph-camera fs-16"></i>
                                                    <input type="file" id="profile_image" name="profile_image" class="d-none" accept="image/*">
                                                </label>
                                            </div>
                                            <h5 class="mt-3">{{ $user->name }}</h5>
                                            <p class="text-muted">{{ $user->email }}</p>
                                        </div>

                                        <div class="row g-4">

                                            <!-- First Name -->
                                            <div class="col-lg-6">
                                                <label class="form-label">First Name</label>
                                                <input type="text" name="first_name" class="form-control"
                                                       value="{{ $user->first_name }}" required>
                                            </div>

                                            <!-- Last Name -->
                                            <div class="col-lg-6">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control"
                                                       value="{{ $user->last_name }}">
                                            </div>

                                            <!-- Email -->
                                            <div class="col-lg-6">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" name="email" class="form-control"
                                                       value="{{ $user->email }}" required>
                                            </div>

                                            <!-- Phone Number -->
                                            <div class="col-lg-6">
                                                <label class="form-label">Phone Number</label>
                                                <input type="text" name="phone_number" class="form-control"
                                                       value="{{ $user->phone_number }}">
                                            </div>

                                            <!-- Gender -->
                                            <div class="col-lg-6">
                                                <label class="form-label">Gender</label>
                                                <select name="gender" class="form-control">
                                                    <option value="">Select Gender</option>
                                                    <option value="male" {{ $user->gender === 'male' ? 'selected' : '' }}>Male</option>
                                                    <option value="female" {{ $user->gender === 'female' ? 'selected' : '' }}>Female</option>
                                                    <option value="other" {{ $user->gender === 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>

                                            <!-- Date of Birth -->
                                            <div class="col-lg-6">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="date" name="date_of_birth" class="form-control"
                                                       value="{{ $user->date_of_birth?->format('Y-m-d') }}">
                                            </div>

                                            <!-- Roles (Read-only display) -->
                                            <div class="col-lg-12">
                                                <label class="form-label">Roles</label>
                                                <div>
                                                    @if($user->roles->count())
                                                        @foreach($user->roles as $role)
                                                            <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                                        @endforeach
                                                    @else
                                                        <span class="badge bg-secondary">No Role Assigned</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Save Button -->
                                            <div class="col-lg-12">
                                                <div class="hstack gap-2 justify-content-end mt-4">
                                                    <button type="submit" class="btn btn-primary" id="saveBtn">
                                                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                                        Save Changes
                                                    </button>
                                                </div>
                                                <div id="updateMessage" class="mt-3"></div>
                                            </div>

                                        </div>
                                    </form>
                                </div>

                                <!-- Tab: Activity Timeline -->
                                <div class="tab-pane" id="activityTimeline" role="tabpanel">
                                    <h5 class="mb-4">Account Activity</h5>
                                    <div class="timeline">
                                        <!-- Account Created -->
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->created_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Account Created</h6>
                                                <p class="text-muted mb-0">User registered on the platform</p>
                                            </div>
                                        </div>

                                        <!-- Email Verified -->
                                        @if($user->email_verified_at)
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->email_verified_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Email Verified</h6>
                                                <p class="text-muted mb-0">User confirmed their email address</p>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Last Updated -->
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->updated_at->diffForHumans() }}</div>
                                            <div class="timeline-content">
                                                <h6>Profile Last Updated</h6>
                                                <p class="text-muted mb-0">Last modification to user data</p>
                                            </div>
                                        </div>

                                        <!-- Placeholder for future events (e.g., password change, login) -->
                                        <div class="timeline-item">
                                            <div class="timeline-date">—</div>
                                            <div class="timeline-content">
                                                <h6>Last Login</h6>
                                                <p class="text-muted mb-0">Not tracked yet</p>
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

<!-- SweetAlert2 & Toastr (or use your own notification lib) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Profile Image Preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Update User via AJAX
document.getElementById('updateUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const saveBtn = document.getElementById('saveBtn');
    const messageDiv = document.getElementById('updateMessage');

    saveBtn.disabled = true;
    saveBtn.querySelector('.spinner-border').classList.remove('d-none');

    fetch(`/users/${formData.get('id')}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': formData.get('_token'),
            'X-HTTP-Method-Override': 'PUT'  // Laravel recognizes this as update
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success!', data.message, 'success');
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            // Update name in header if changed
            if (data.user.name) {
                document.querySelector('.card-body h5').textContent = data.user.name;
            }
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join('<br>') : data.message;
            Swal.fire('Error', errors, 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.querySelector('.spinner-border').classList.add('d-none');
    });
});
</script>

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
</style>
@endsection
