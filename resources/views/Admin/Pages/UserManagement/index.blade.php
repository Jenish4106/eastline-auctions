@extends('Admin.Particals.app')

@section('title', 'User Management')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="card p-4">
                            <div class="d-flex mb-1">
                                <div class="w-50 text-start">
                                    <h4>User Management</h4>
                                </div>
                                <div class="w-50 text-end">
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                        <i class="fa-solid fa-plus me-1"></i>Add User
                                    </a>
                                </div>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="usersTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Full Name</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">Phone No</th>
                                            <th class="text-center">Registration Date</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">License Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center"></tbody>
                                </table>
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
            let table;

            function loadUsers() {
                if (table) {
                    table.ajax.reload(null, false);
                } else {
                    table = $('#usersTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.users.fetch') }}",
                            type: 'GET'
                        },
                        columns: [
                            { data: 'DT_RowIndex', orderable: false },
                            { data: 'name' },
                            { data: 'email' },
                            { data: 'phone_no' },
                            { data: 'registration_date' },
                            { data: 'status' },
                            { data: 'license_status' },
                            { data: 'actions', orderable: false }
                        ],
                        responsive: false,
                        paging: true,
                        searching: true,
                        lengthMenu: [
                            [10, 25, 50, 75, 100, -1],
                            [10, 25, 50, 75, 100, 'All']
                        ],
                        ordering: true,
                        autoWidth: false,
                        scrollX: false,
                        scrollCollapse: true
                    });
                }
            }

            loadUsers();
            
            $(document).on('click', '.view-details', function() {
                const userId = $(this).data('id');
                window.location.href = "{{ route('admin.users.show', ['id' => '__ID__']) }}".replace('__ID__', userId);
            });
            
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
                                        <strong>Success!</strong> ${response.success}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                table.ajax.reload();
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
            
            $(document).on('click', '.block-user', function() {
                const userId = $(this).data('id');
                const userName = $(this).data('name');
                
                Swal.fire({
                    title: 'Block User?',
                    text: `You are about to block user: ${userName}. They will no longer be able to access the system.`,
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
                                        <strong>Success!</strong> ${response.success}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                table.ajax.reload();
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
            
            $(document).on('click', '.unblock-user', function() {
                const userId = $(this).data('id');
                const userName = $(this).data('name');
                
                Swal.fire({
                    title: 'Unblock User?',
                    text: `You are about to unblock user: ${userName}. They will regain access to the system.`,
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
                                        <strong>Success!</strong> ${response.success}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `;
                                $('body').append(alertHtml);
                                setTimeout(() => {
                                    $('.alert').fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                                table.ajax.reload();
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
        });
    </script>
@endsection