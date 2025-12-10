@extends('Admin.Particals.app')

@section('title', 'User Details')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0">User Details</h5>
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Users
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xl-4 col-lg-5 col-md-5">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <div class="user-avatar-section">
                                                            <div class="d-flex align-items-center flex-column">
                                                                <div class="avatar avatar-xl bg-primary rounded-circle mb-3">
                                                                    <span class="avatar-initial rounded-circle fs-2">{{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}</span>
                                                                </div>
                                                                <div class="user-info text-center">
                                                                    <h5>{{ $user->first_name }} {{ $user->last_name }}</h5>
                                                                    <span class="badge bg-label-secondary">{{ $user->company_name ?? 'N/A' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="d-flex justify-content-around flex-wrap my-4 py-3">
                                                            <div class="d-flex align-items-start me-4 mt-3 gap-3">
                                                                <span class="badge bg-label-primary p-2 rounded"><i class="fas fa-calendar-alt"></i></span>
                                                                <div>
                                                                    <h5 class="mb-0">{{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y') }}</h5>
                                                                    <span>Registration Date</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <h5 class="pb-2 border-bottom mb-3">Details</h5>
                                                        <div class="info-container">
                                                            <ul class="list-unstyled">
                                                                <li class="mb-3">
                                                                    <span class="me-2">ID:</span>
                                                                    <span class="fw-medium">#{{ $user->id }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">Email:</span>
                                                                    <span class="fw-medium">{{ $user->email }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">Phone:</span>
                                                                    <span class="fw-medium">{{ $user->phone_no ?? 'N/A' }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">Status:</span>
                                                                    <span class="badge {{ $user->status == 1 ? 'bg-label-success' : 'bg-label-danger' }}">
                                                                        {{ $user->status == 1 ? 'Active' : 'Blocked' }}
                                                                    </span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">License Status:</span>
                                                                    @if($user->license)
                                                                        <span class="badge {{ $user->license->status == 1 ? 'bg-label-success' : ($user->license->status == 2 ? 'bg-label-danger' : 'bg-label-warning') }}">
                                                                            {{ $user->license->status == 1 ? 'Approved' : ($user->license->status == 2 ? 'Declined' : 'Pending') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-label-secondary">Not Uploaded</span>
                                                                    @endif
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">Address:</span>
                                                                    <span class="fw-medium">{{ $user->address ?? 'N/A' }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">City:</span>
                                                                    <span class="fw-medium">{{ $user->city ?? 'N/A' }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">State:</span>
                                                                    <span class="fw-medium">{{ $user->state ?? 'N/A' }}</span>
                                                                </li>
                                                                <li class="mb-3">
                                                                    <span class="me-2">Zip Code:</span>
                                                                    <span class="fw-medium">{{ $user->zip_code ?? 'N/A' }}</span>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xl-8 col-lg-7 col-md-7">
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="card-title mb-0">License Information</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        @if($user->license)
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">License ID</label>
                                                                    <div class="form-control-plaintext fw-medium">#{{ $user->license->id }}</div>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Upload Date</label>
                                                                    <div class="form-control-plaintext fw-medium">{{ \Carbon\Carbon::parse($user->license->created_at)->format('M d, Y h:i A') }}</div>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <div>
                                                                        @if($user->license->status == 1)
                                                                            <span class="badge bg-success">Approved</span>
                                                                        @elseif($user->license->status == 2)
                                                                            <span class="badge bg-danger">Declined</span>
                                                                        @else
                                                                            <span class="badge bg-warning">Pending</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Document</label>
                                                                    <div>
                                                                        @php
                                                                            $fileName = basename($user->license->file);
                                                                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                                                        @endphp
                                                                        
                                                                        @if(in_array($fileExtension, ['jpg', 'jpeg', 'png']))
                                                                            <a href="/{{ $user->license->file }}" target="_blank">
                                                                                <img src="/{{ $user->license->file }}" alt="License Document" class="img-fluid rounded" style="max-height: 200px;">
                                                                            </a>
                                                                        @elseif($fileExtension === 'pdf')
                                                                            <a href="/{{ $user->license->file }}" target="_blank" class="btn btn-outline-primary">
                                                                                <i class="fas fa-file-pdf me-1"></i>View PDF Document
                                                                            </a>
                                                                        @else
                                                                            <a href="/{{ $user->license->file }}" target="_blank" class="btn btn-outline-secondary">
                                                                                <i class="fas fa-file me-1"></i>Download Document
                                                                            </a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if($user->license->status == 0)
                                                                    <div class="col-12 mt-3">
                                                                        <div class="d-flex gap-2">
                                                                            <button class="btn btn-success approve-license" data-user-id="{{ $user->id }}" data-license-id="{{ $user->license->id }}">
                                                                                <i class="fas fa-check me-1"></i>Approve
                                                                            </button>
                                                                            <button class="btn btn-danger decline-license ms-2" data-user-id="{{ $user->id }}" data-license-id="{{ $user->license->id }}">
                                                                                <i class="fas fa-times me-1"></i>Decline
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="text-center py-5">
                                                                <i class="fas fa-file-upload fa-3x mb-3 text-muted"></i>
                                                                <h6>No license document uploaded</h6>
                                                                <p class="text-muted">This user hasn't uploaded any license document yet.</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="card-title mb-0">Account Actions</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="d-flex gap-3">
                                                            @if($user->status == 1)
                                                                <button class="btn btn-warning block-user" data-id="{{ $user->id }}">
                                                                    <i class="fas fa-ban me-1"></i>Block User
                                                                </button>
                                                            @else
                                                                <button class="btn btn-success unblock-user" data-id="{{ $user->id }}">
                                                                    <i class="fas fa-check-circle me-1"></i>Unblock User
                                                                </button>
                                                            @endif
                                                            
                                                            <button class="btn btn-danger delete-user" data-id="{{ $user->id }}" data-name="{{ $user->first_name }} {{ $user->last_name }}">
                                                                <i class="fas fa-trash-alt me-1"></i>Delete User
                                                            </button>
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

                    @include('Admin.Layouts.Footer')
                    <div class="content-backdrop fade"></div>
                </div>
            </div>

            <div class="layout-overlay layout-menu-toggle"></div>
            <div class="drag-target"></div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle user blocking
            $(document).on('click', '.block-user', function() {
                const userId = $(this).data('id');
                
                Swal.fire({
                    title: 'Block User?',
                    text: 'Are you sure you want to block this user? They will no longer be able to access the system.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, block user!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.users.change.status') }}",
                            type: 'POST',
                            data: {
                                id: userId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> User blocked successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                // Reload the page to reflect changes
                                location.reload();
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'There was an error blocking the user.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Handle user unblocking
            $(document).on('click', '.unblock-user', function() {
                const userId = $(this).data('id');
                
                Swal.fire({
                    title: 'Unblock User?',
                    text: 'Are you sure you want to unblock this user? They will regain access to the system.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, unblock user!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.users.change.status') }}",
                            type: 'POST',
                            data: {
                                id: userId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> User unblocked successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                // Reload the page to reflect changes
                                location.reload();
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'There was an error unblocking the user.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Handle user deletion
            $(document).on('click', '.delete-user', function() {
                const userId = $(this).data('id');
                const userName = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete user: ${userName}. This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.users.delete') }}",
                            type: 'POST',
                            data: {
                                id: userId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> User deleted successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                // Redirect to users list
                                window.location.href = "{{ route('admin.users.index') }}";
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'There was an error deleting the user.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Handle license approval
            $(document).on('click', '.approve-license', function() {
                const userId = $(this).data('user-id');
                const licenseId = $(this).data('license-id');
                
                Swal.fire({
                    title: 'Approve License?',
                    text: 'Are you sure you want to approve this license?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, approve it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.license.approve') }}",
                            type: 'POST',
                            data: {
                                user_id: userId,
                                license_id: licenseId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> License approved successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                // Reload the page to reflect changes
                                location.reload();
                            },
                            error: function(xhr) {
                                const alertHtml = `
                                    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Error!</strong> There was an error approving the license.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 5000);
                            }
                        });
                    }
                });
            });
            
            // Handle license decline
            $(document).on('click', '.decline-license', function() {
                const userId = $(this).data('user-id');
                const licenseId = $(this).data('license-id');
                
                Swal.fire({
                    title: 'Decline License?',
                    text: 'Are you sure you want to decline this license?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, decline it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.license.decline') }}",
                            type: 'POST',
                            data: {
                                user_id: userId,
                                license_id: licenseId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                const alertHtml = `
                                    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Success!</strong> License declined successfully
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                // Reload the page to reflect changes
                                location.reload();
                            },
                            error: function(xhr) {
                                const alertHtml = `
                                    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                        <strong>Error!</strong> There was an error declining the license.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 5000);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection