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
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fa-solid fa-plus me-1"></i>Add User
                                    </button>
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
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addUserForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstName" name="first_name" placeholder="Enter first name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Enter last name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone_no" placeholder="Enter phone number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company" name="company_name" placeholder="Enter company name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" placeholder="Confirm password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Enter address">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="Enter city">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" placeholder="Enter state">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="zipCode" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zipCode" name="zip_code" placeholder="Enter zip code">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">
                            <i class="fas fa-save me-1"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <span id="modalUserId"></span></p>
                            <p><strong>First Name:</strong> <span id="modalFirstName"></span></p>
                            <p><strong>Last Name:</strong> <span id="modalLastName"></span></p>
                            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                            <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Company:</strong> <span id="modalCompanyName"></span></p>
                            <p><strong>City:</strong> <span id="modalCity"></span></p>
                            <p><strong>State:</strong> <span id="modalState"></span></p>
                            <p><strong>Zip Code:</strong> <span id="modalZipCode"></span></p>
                            <p><strong>Registration Date:</strong> <span id="modalRegistrationDate"></span></p>
                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
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
                        serverSide: false,
                        ajax: {
                            url: "{{ route('admin.users.fetch') }}",
                            dataSrc: 'data'
                        },
                        columns: [
                            { data: 'DT_RowIndex' },
                            { data: 'name' },
                            { data: 'email' },
                            { data: 'phone_no' },
                            { data: 'registration_date' },
                            { data: 'status' },
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
                
                const rowData = table.rows().data().toArray().find(user => user.id == userId);
                
                if (rowData) {
                    $('#modalUserId').text(rowData.id);
                    $('#modalFirstName').text(rowData.first_name);
                    $('#modalLastName').text(rowData.last_name);
                    $('#modalEmail').text(rowData.email);
                    $('#modalPhone').text(rowData.phone_no);
                    $('#modalCompanyName').text(rowData.company_name);
                    $('#modalCity').text(rowData.city);
                    $('#modalState').text(rowData.state);
                    $('#modalZipCode').text(rowData.zip_code);
                    $('#modalRegistrationDate').text(rowData.registration_date);
                    
                    // Set status with badge
                    const statusBadge = $(rowData.status);
                    $('#modalStatus').html(statusBadge);
                    
                    $('#userDetailsModal').modal('show');
                }
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
            
            // Handle Block User
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
            
            // Handle Unblock User
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
            
            // Handle Add User Form Submission
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Client-side validation
                let isValid = true;
                const errors = {};
                
                // Validate all fields
                const firstName = $('#firstName').val().trim();
                const lastName = $('#lastName').val().trim();
                const email = $('#email').val().trim();
                const phone = $('#phone').val().trim();
                const company = $('#company').val().trim();
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();
                const address = $('#address').val().trim();
                const city = $('#city').val().trim();
                const state = $('#state').val().trim();
                const zipCode = $('#zipCode').val().trim();
                
                if (!firstName) {
                    isValid = false;
                    errors.first_name = ['The first name field is required.'];
                }
                
                if (!lastName) {
                    isValid = false;
                    errors.last_name = ['The last name field is required.'];
                }
                
                if (!email) {
                    isValid = false;
                    errors.email = ['The email field is required.'];
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    isValid = false;
                    errors.email = ['The email must be a valid email address.'];
                }
                
                if (!phone) {
                    isValid = false;
                    errors.phone_no = ['The phone number field is required.'];
                }
                
                if (!company) {
                    isValid = false;
                    errors.company_name = ['The company name field is required.'];
                }
                
                if (!password) {
                    isValid = false;
                    errors.password = ['The password field is required.'];
                } else if (password.length < 8) {
                    isValid = false;
                    errors.password = ['The password must be at least 8 characters.'];
                }
                
                if (!confirmPassword) {
                    isValid = false;
                    errors.password_confirmation = ['The confirm password field is required.'];
                } else if (password !== confirmPassword) {
                    isValid = false;
                    errors.password_confirmation = ['The password confirmation does not match.'];
                }
                
                if (!address) {
                    isValid = false;
                    errors.address = ['The address field is required.'];
                }
                
                if (!city) {
                    isValid = false;
                    errors.city = ['The city field is required.'];
                }
                
                if (!state) {
                    isValid = false;
                    errors.state = ['The state field is required.'];
                }
                
                if (!zipCode) {
                    isValid = false;
                    errors.zip_code = ['The zip code field is required.'];
                }
                
                // If client-side validation fails, show errors
                if (!isValid) {
                    $.each(errors, function(key, value) {
                        // Convert snake_case to camelCase for field matching
                        let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
                        // Handle special cases
                        if (key === 'password_confirmation') {
                            fieldName = 'confirmPassword';
                        } else if (key === 'phone_no') {
                            fieldName = 'phone';
                        } else if (key === 'company_name') {
                            fieldName = 'company';
                        } else if (key === 'zip_code') {
                            fieldName = 'zipCode';
                        }
                        
                        const fieldElement = $('#' + fieldName);
                        fieldElement.addClass('is-invalid');
                        fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                    });
                    return;
                }
                
                // Get form data
                const formData = new FormData(this);
                
                // Disable submit button and show loading
                $('#saveUserBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
                
                // Submit form via AJAX
                $.ajax({
                    url: "{{ route('admin.users.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);
                        
                        // Reset form and close modal
                        $('#addUserForm')[0].reset();
                        $('#addUserModal').modal('hide');
                        
                        // Reload table
                        table.ajax.reload();
                        
                        // Re-enable submit button
                        $('#saveUserBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save User');
                        
                        // Auto-dismiss alert after 3 seconds
                        setTimeout(() => {
                            $('.alert').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    },
                    error: function(xhr) {
                        // Show field-wise errors
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                // Convert snake_case to camelCase for field matching
                                let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
                                // Handle special cases
                                if (key === 'password_confirmation') {
                                    fieldName = 'confirmPassword';
                                } else if (key === 'phone_no') {
                                    fieldName = 'phone';
                                } else if (key === 'company_name') {
                                    fieldName = 'company';
                                } else if (key === 'zip_code') {
                                    fieldName = 'zipCode';
                                }
                                
                                const fieldElement = $('#' + fieldName);
                                fieldElement.addClass('is-invalid');
                                fieldElement.after(`<div class="invalid-feedback">${value[0]}</div>`);
                            });
                        } else {
                            // Show general error message
                            let errorMessage = 'An error occurred while saving the user.';
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                    <strong>Error!</strong> ${errorMessage}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `;
                            $('body').append(alertHtml);
                            
                            // Auto-dismiss alert after 5 seconds
                            setTimeout(() => {
                                $('.alert').fadeOut('slow', function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        }
                        
                        // Re-enable submit button
                        $('#saveUserBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save User');
                    }
                });
            });
            
            // Password confirmation validation
            $('#addUserForm').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();
                
                if (password !== '' && confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $('#confirmPassword').removeClass('is-valid').addClass('is-invalid');
                        $('#confirmPassword').siblings('.invalid-feedback').remove();
                        $('#confirmPassword').after('<div class="invalid-feedback">Passwords do not match.</div>');
                    } else {
                        $('#confirmPassword').removeClass('is-invalid').addClass('is-valid');
                        $('#confirmPassword').siblings('.invalid-feedback').remove();
                    }
                } else {
                    $('#confirmPassword').removeClass('is-valid is-invalid');
                    $('#confirmPassword').siblings('.invalid-feedback').remove();
                }
            });
        });
    </script>
@endsection