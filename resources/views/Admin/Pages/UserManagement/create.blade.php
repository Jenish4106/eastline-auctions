@extends('Admin.Particals.app')

@section('title', 'Create User')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('Admin.Layouts.Sidebar')

            <div class="layout-page">
                @include('Admin.Layouts.Navbar')

                <div class="content-wrapper">
                    <div class="mx-4 flex-grow-1 container-p-y">
                        <div class="d-flex mb-4">
                            <div class="w-50 text-start">
                                <h4>Create User</h4>
                            </div>
                            <div class="w-50 text-end">
                                <a href="{{ route('admin.users.management') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Back to Users
                                </a>
                            </div>
                        </div>

                        <form id="addUserForm">
                            @csrf
                            
                            <!-- First Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Personal Information</h5>
                                </div>
                                <div class="card-body">
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
                                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="phone" name="phone_no" placeholder="Enter phone number">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" placeholder="Confirm password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Second Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Address Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter address"></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="company" class="form-label">Company Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="company" name="company_name" placeholder="Enter company name">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="city" name="city" placeholder="Enter city">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="state" name="state" placeholder="Enter state">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="zipCode" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="zipCode" name="zip_code" placeholder="Enter zip code">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-secondary me-2" onclick="window.location='{{ route('admin.users.management') }}'">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="saveUserBtn">
                                        <i class="fas fa-save me-1"></i>Save User
                                    </button>
                                </div>
                            </div>
                        </form>
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
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let isValid = true;
                const errors = {};

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

                if (!isValid) {
                    $.each(errors, function(key, value) {
                        let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
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

                const formData = new FormData(this);

                $('#saveUserBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');

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
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                <strong>Success!</strong> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        $('body').append(alertHtml);

                        $('#addUserForm')[0].reset();

                        setTimeout(() => {
                            window.location.href = "{{ route('admin.users.management') }}";
                        }, 2000);
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                let fieldName = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
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
                            let errorMessage = 'An error occurred while saving the user.';
                            const alertHtml = `
                                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                                    <strong>Error!</strong> ${errorMessage}
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

                        $('#saveUserBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save User');
                    }
                });
            });

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