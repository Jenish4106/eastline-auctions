@extends('Admin.Particals.app')

@section('title', 'User Administration | Test Craft')

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
                                    <h4>Users</h4>
                                </div>
                                <div class="w-50 text-end">
                                    <div class="btn-group me-2">
                                        <button type="button" class="btn btn-primary dropdown-toggle action-button"
                                            data-bs-toggle="dropdown" aria-expanded="false" disabled id="bulk-action-btn">
                                            <span>Action</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><span class="dropdown-header">Status</span></li>
                                            <li>
                                                <a class="dropdown-item status-action-active" href="javascript:void(0);"
                                                    data-status="1">Active</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item status-action-inactive" href="javascript:void(0);"
                                                    data-status="0">Inactive</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="javascript:void(0);"
                                                    id="delete-selected">Bulk Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                    @if (checkPermission($permissions, 'users', 'create'))
                                        <button type="button" class="btn btn-primary" id="addBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#addUserPanel">
                                            <i class="ti ti-plus me-sm-1"></i>
                                            <span>Add</span>
                                        </button>
                                    @endif
                                    @if (checkPermission($permissions, 'users', 'create'))
                                        <button type="button" class="btn btn-primary ms-2" id="importBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#importUserPanel">
                                            <i class="ti ti-upload me-sm-1"></i>
                                            <span>Import</span>
                                        </button>
                                    @endif
                                    <div class="btn-group ms-2">
                                        <button type="button" class="btn btn-primary dropdown-toggle"
                                            data-bs-toggle="dropdown" aria-expanded="false" id="columnVisibilityBtn">
                                            <i class="ti ti-eye me-sm-1"></i>
                                            <span>Columns</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><span class="dropdown-header">Column Visibility</span></li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="1"><i class="ti ti-user me-2"></i><span>Name</span></a>
                                            </li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="2"><i class="ti ti-mail me-2"></i><span>Email</span></a>
                                            </li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="3"><i class="ti ti-phone me-2"></i><span>Phone
                                                        No.</span></a></li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="4"><i class="ti ti-photo me-2"></i><span>Profile
                                                        Picture</span></a></li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="5"><i
                                                        class="ti ti-toggle-right me-2"></i><span>Status</span></a></li>
                                            <li><a class="dropdown-item column-toggle" href="javascript:void(0);"
                                                    data-column="6"><i class="ti ti-clock me-2"></i><span>Last Login
                                                        At</span></a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" id="show-all-columns"><i
                                                        class="ti ti-eye me-2"></i><span>Show All Columns</span></a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" id="hide-all-columns"><i
                                                        class="ti ti-eye-off me-2"></i><span>Hide All Columns</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert container -->
                            <div class="w-100 mt-3" id="alert-container"></div>
                            <div id="offcanvasAlertBox" class="w-100 mt-3"></div>

                            <!-- Offcanvas for Add/Edit User -->
                            <div class="offcanvas offcanvas-end" tabindex="-1" id="addUserPanel"
                                aria-labelledby="addUserLabel">
                                <div class="offcanvas-header">
                                    <h5 id="addUserLabel" class="offcanvas-title">Add User</h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>

                                <form id="add-user-form" class="d-flex flex-column h-100" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="id" id="user_id">
                                    <div class="offcanvas-body flex-grow-1">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Enter user name">
                                            <div class="invalid-feedback" id="name-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Enter email address">
                                            <div class="invalid-feedback" id="email-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                placeholder="Enter phone number">
                                            <div class="invalid-feedback" id="phone-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Enter password">
                                            <div class="invalid-feedback" id="password-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="profile_picture" class="form-label">Profile Picture</label>
                                            <input type="file" class="form-control" id="profile_picture"
                                                name="profile_picture" accept="image/*">
                                            <div class="invalid-feedback" id="profile_picture-error"></div>

                                            <!-- Image Preview Container -->
                                            <div id="image-preview-container" class="mt-3" style="display: none;">
                                                <div class="d-flex align-items-center">
                                                    <img id="image-preview" src="" alt="Profile Preview"
                                                        class="rounded-circle me-3"
                                                        style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #e9ecef;">
                                                    <div>
                                                        <small class="text-muted d-block">Image Preview</small>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            id="remove-image">
                                                            <i class="ti ti-trash me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Current Image Display (for edit mode) -->
                                            <div id="current-image-container" class="mt-3" style="display: none;">
                                                <div class="d-flex align-items-center">
                                                    <img id="current-image" src="" alt="Current Profile"
                                                        class="rounded-circle me-3"
                                                        style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #e9ecef;">
                                                    <div>
                                                        <small class="text-muted d-block">Current Image</small>
                                                        <small class="text-info">Upload new image to replace</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="is_active" class="form-label">Status</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active"
                                                    name="is_active" value="1" checked>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                            </div>
                                            <div class="invalid-feedback" id="is_active-error"></div>
                                        </div>
                                    </div>

                                    <div class="offcanvas-footer border-top bg-white p-3 d-flex justify-content-between align-items-center"
                                        style="position:sticky;bottom:0;z-index:10;">
                                        <button type="submit" class="btn btn-primary w-100" id="saveBtn">Save</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Offcanvas for Import Users -->
                            <div class="offcanvas offcanvas-end" tabindex="-1" id="importUserPanel"
                                aria-labelledby="importUserLabel">
                                <div class="offcanvas-header">
                                    <h5 id="importUserLabel" class="offcanvas-title">Import Users</h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>

                                <form id="import-user-form" class="d-flex flex-column h-100"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="offcanvas-body flex-grow-1">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">Import Instructions:</h6>
                                            <ul class="mb-0">
                                                <li>Supported formats: Excel (.xlsx, .xls) and CSV (.csv)</li>
                                                <li>Required columns: Name, Email, Phone, Password</li>
                                                <li>Optional columns: Is Active (1 for Active, 0 for Inactive)</li>
                                            </ul>
                                            <hr>
                                            <a href="{{ route('users.downloadTemplate') }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-download me-1"></i>Download Template
                                            </a>
                                        </div>

                                        <div class="mb-3">
                                            <label for="import_file" class="form-label">Select File</label>
                                            <input type="file" class="form-control" id="import_file"
                                                name="import_file" accept=".xlsx,.xls,.csv">
                                            <div class="invalid-feedback" id="import_file-error"></div>
                                        </div>

                                        <div id="import-preview" class="mb-3" style="display: none;">
                                            <h6>File Preview:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm" id="preview-table">
                                                    <thead class="table-light">
                                                        <tr></tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="offcanvas-footer border-top bg-white p-3 d-flex justify-content-between align-items-center"
                                        style="position:sticky;bottom:0;z-index:10;">
                                        <button type="submit" class="btn btn-primary w-100" id="importBtn">Import
                                            Users</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card-datatable table-responsive pt-0">
                                <table class="table w-100" id="usersTable" style="width: 100% !important;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input class="form-check-input" type="checkbox" id="select-all-users">
                                            </th>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Name</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">Phone No.</th>
                                            <th class="text-center">Profile Picture</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Last Login At</th>
                                            <th class="text-center">Action</th>
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
            const offcanvasPanel = new bootstrap.Offcanvas('#addUserPanel');
            let isEditMode = false;
            let table;

            function loadUsers() {
                $.ajax({
                    url: "{{ route('users.fetch') }}",
                    success: function(response) {
                        const hasPermission = response.has_action_permission;

                        const columns = [{
                                data: null,
                                render: function(data, type, row, meta) {
                                    return '<input class="form-check-input user-select" type="checkbox" value="' +
                                        row.id + '">';
                                },
                                orderable: false,
                                searchable: false,
                                width: '5%'
                            },
                            {
                                data: null,
                                render: function(data, type, row, meta) {
                                    return meta.row + 1;
                                },
                                width: '8%'
                            },
                            {
                                data: 'name',
                                width: '15%'
                            },
                            {
                                data: 'email',
                                width: '20%'
                            },
                            {
                                data: 'phone',
                                width: '15%'
                            },
                            {
                                data: 'profile_picture',
                                orderable: false,
                                searchable: false,
                                width: '12%'
                            },
                            {
                                data: 'is_active',
                                width: '10%'
                            },
                            {
                                data: 'last_login_at',
                                width: '15%',
                                visible: false
                            }
                        ];

                        if (hasPermission) {
                            columns.push({
                                data: 'action',
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '10%'
                            });
                        } else {
                            $('#usersTable thead tr th').eq(7).remove();
                        }

                        if (table) {
                            table.ajax.reload(null, false);
                        } else {
                            table = $('#usersTable').DataTable({
                                processing: true,
                                serverSide: false,
                                ajax: {
                                    url: "{{ route('users.fetch') }}",
                                    dataSrc: 'data'
                                },
                                columns: columns,
                                responsive: true,
                                paging: true,
                                searching: true,
                                lengthMenu: [
                                    [10, 25, 50, 75, 100, -1],
                                    [10, 25, 50, 75, 100, 'All']
                                ],
                                ordering: true,
                                autoWidth: false,
                                scrollX: false,
                                scrollCollapse: true,
                                initComplete: function() {
                                    setTimeout(function() {
                                        updateColumnToggleStates();
                                    }, 50);
                                }
                            });
                        }
                    }
                });
            }


            loadUsers();

            $(document).on('click', '.column-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const dropdownIndex = parseInt($(this).data('column'));
                const columnIndex = dropdownIndex + 1;
                const $item = $(this);
                const $icon = $item.find('i');

                if (table) {
                    const column = table.column(columnIndex);
                    const isVisible = column.visible();

                    if (isVisible) {
                        column.visible(false);
                        $icon.removeClass('ti-eye').addClass('ti-eye-off');
                        $item.addClass('text-muted');
                        $item.removeClass('bg-primary text-white');
                    } else {
                        column.visible(true);
                        $icon.removeClass('ti-eye-off').addClass('ti-eye');
                        $item.removeClass('text-muted');
                        $item.addClass('bg-primary text-white');
                    }

                    setTimeout(function() {
                        adjustTableWidth();
                    }, 50);
                }
            });

            $(document).on('click', '#show-all-columns', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (table) {
                    table.columns().every(function() {
                        this.visible(true);
                    });

                    $('.column-toggle').each(function() {
                        const $item = $(this);
                        const $icon = $item.find('i');
                        $icon.removeClass('ti-eye-off').addClass('ti-eye');
                        $item.removeClass('text-muted');
                        $item.addClass('bg-primary text-white');
                    });

                    setTimeout(function() {
                        adjustTableWidth();
                    }, 100);
                }
            });

            $(document).on('click', '#hide-all-columns', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (table) {
                    table.columns().every(function(index) {
                        if (index !== 0 && index !== 1 && index !== 8) {
                            this.visible(false);
                        }
                    });

                    $('.column-toggle').each(function() {
                        const $item = $(this);
                        const $icon = $item.find('i');
                        $icon.removeClass('ti-eye').addClass('ti-eye-off');
                        $item.addClass('text-muted');
                        $item.removeClass('bg-primary text-white');
                    });

                    setTimeout(function() {
                        adjustTableWidth();
                    }, 50);
                }
            });

            function updateColumnToggleStates() {
                if (table) {
                    $('.column-toggle').each(function() {
                        const dropdownIndex = parseInt($(this).data('column'));
                        const columnIndex = dropdownIndex + 1;
                        const $item = $(this);
                        const $icon = $item.find('i');
                        const column = table.column(columnIndex);

                        if (column.visible()) {
                            $icon.removeClass('ti-eye-off').addClass('ti-eye');
                            $item.removeClass('text-muted');
                            $item.addClass('bg-primary text-white');
                        } else {
                            $icon.removeClass('ti-eye').addClass('ti-eye-off');
                            $item.addClass('text-muted');
                            $item.removeClass('bg-primary text-white');
                        }
                    });

                    setTimeout(function() {
                        adjustTableWidth();
                    }, 50);
                }
            }

            function adjustTableWidth() {
                if (!table) return;

                table.columns.adjust();

                table.draw(false);
            }

            $('input, select, textarea').on('input', function() {
                $(this).removeClass('is-invalid');
                $('#' + $(this).attr('id') + '-error').text('');
            });

            $('#addBtn').on('click', function() {
                isEditMode = false;
                $('#addUserLabel').text('Add User');
                $('#saveBtn').text('Save');
                $('#add-user-form')[0].reset();
                $('#user_id').val('');
            });

            $(document).on('click', '.item-edit', function() {
                isEditMode = true;
                const id = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const isActive = $(this).data('is_active');

                $('#user_id').val(id);
                $('#name').val(name);
                $('#email').val(email);
                $('#phone').val(phone);

                if (isActive == 1) {
                    $('#is_active').prop('checked', true);
                } else {
                    $('#is_active').prop('checked', false);
                }

                $('#password').closest('.mb-3').hide();
                $('#password').removeAttr('required');
                $('#addUserLabel').text('Edit User');
                $('#saveBtn').text('Update');
                offcanvasPanel.show();
            });

            function validateForm() {
                let hasError = false;

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                const name = $('#name').val().trim();
                if (!name) {
                    $('#name').addClass('is-invalid');
                    $('#name-error').text('Name is required.');
                    hasError = true;
                } else if (name.length < 2) {
                    $('#name').addClass('is-invalid');
                    $('#name-error').text('Name must be at least 2 characters long.');
                    hasError = true;
                } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                    $('#name').addClass('is-invalid');
                    $('#name-error').text('Name can only contain letters and spaces.');
                    hasError = true;
                }

                const email = $('#email').val().trim();
                if (!email) {
                    $('#email').addClass('is-invalid');
                    $('#email-error').text('Email is required.');
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    $('#email').addClass('is-invalid');
                    $('#email-error').text('Please enter a valid email address.');
                    hasError = true;
                }

                const phone = $('#phone').val().trim();
                if (!phone) {
                    $('#phone').addClass('is-invalid');
                    $('#phone-error').text('Phone number is required.');
                    hasError = true;
                } else if (phone.length < 10 || phone.length > 15) {
                    $('#phone').addClass('is-invalid');
                    $('#phone-error').text('Phone number must be between 10 and 15 characters.');
                    hasError = true;
                } else if (!/^[0-9+\-\s()]+$/.test(phone)) {
                    $('#phone').addClass('is-invalid');
                    $('#phone-error').text(
                        'Phone number can only contain numbers, spaces, hyphens, plus signs and parentheses.');
                    hasError = true;
                }

                if (!isEditMode) {
                    const password = $('#password').val().trim();
                    if (!password) {
                        $('#password').addClass('is-invalid');
                        $('#password-error').text('Password is required.');
                        hasError = true;
                    } else if (password.length < 6) {
                        $('#password').addClass('is-invalid');
                        $('#password-error').text('Password must be at least 6 characters long.');
                        hasError = true;
                    }
                }

                const profilePicture = $('#profile_picture')[0].files[0];
                if (profilePicture) {
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
                    const maxSize = 2 * 1024 * 1024; // 2MB

                    if (!allowedTypes.includes(profilePicture.type)) {
                        $('#profile_picture').addClass('is-invalid');
                        $('#profile_picture-error').text(
                            'Please choose a valid image file (jpeg, jpg, png, gif, svg).');
                        hasError = true;
                    } else if (profilePicture.size > maxSize) {
                        $('#profile_picture').addClass('is-invalid');
                        $('#profile_picture-error').text('Image file size must be less than 2MB.');
                        hasError = true;
                    }
                }

                return !hasError;
            }

            $('#add-user-form').on('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) {
                    return;
                }

                const formData = new FormData(this);
                const url = isEditMode ? '{{ route('users.update') }}' :
                    '{{ route('users.insert') }}';

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#alert-container').html(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);

                        $('#add-user-form')[0].reset();
                        $('#user_id').val('');
                        $('#is_active').prop('checked', true);
                        loadUsers();
                        offcanvasPanel.hide();

                        setTimeout(() => {
                            $('.alert').alert('close');
                        }, 4000);
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON.errors || {};
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').text('');

                        $.each(errors, function(key, value) {
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '-error').text(value[0]);
                        });

                        if (xhr.responseJSON.message) {
                            $('#alert-container').html(`
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ${xhr.responseJSON.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `);
                        }
                    }
                });
            });

            // Reset Password
            $(document).on('click', '.item-reset-password', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Reset Password',
                    text: `Are you sure you want to reset password for ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, reset it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('users.resetPassword') }}',
                            type: 'POST',
                            data: {
                                id: id,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'Password Reset Successfully!',
                                    html: `
                                        <div class="text-start">
                                            <p><strong>New Password:</strong></p>
                                            <div class="alert alert-info">
                                                <code>${response.new_password}</code>
                                            </div>
                                            <p class="text-warning">
                                                <i class="ti ti-alert-triangle me-1"></i>
                                                Please copy this password and share it securely with the user.
                                            </p>
                                        </div>
                                    `,
                                    icon: 'success',
                                    showCancelButton: true,
                                    cancelButtonText: 'Close'
                                });
                            },
                            error: function(xhr) {
                                let message = 'Something went wrong!';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', message, 'error');
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.item-delete', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This user will be deleted permanently!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('users.delete') }}',
                            type: 'POST',
                            data: {
                                id: id,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $('#alert-container').html(`
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                                loadUsers();

                                setTimeout(() => {
                                    $('.alert').alert('close');
                                }, 4000);
                            },
                            error: function() {
                                $('#alert-container').html(`
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Something went wrong!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                            }
                        });
                    }
                });
            });

            // Bulk action functionality
            function updateBulkActionBtn() {
                const checked = $('.user-select:checked').length;
                $('#bulk-action-btn').prop('disabled', checked === 0);
            }

            $(document).on('change', '.user-select', updateBulkActionBtn);
            $(document).on('change', '#select-all-users', function() {
                $('.user-select').prop('checked', this.checked);
                updateBulkActionBtn();
            });

            $(document).on('change', '.user-select', function() {
                if (!this.checked) $('#select-all-users').prop('checked', false);
                else if ($('.user-select:checked').length === $('.user-select').length)
                    $('#select-all-users').prop('checked', true);
            });

            // Bulk Delete
            $(document).on('click', '#delete-selected', function() {
                const ids = $('.user-select:checked').map(function() {
                    return $(this).val();
                }).get();

                if (ids.length === 0) return;

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Selected users will be deleted permanently!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete!',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('users.bulkDelete') }}',
                            type: 'POST',
                            data: {
                                ids: ids,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $('#alert-container').html(`
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                                loadUsers();
                                $('#select-all-users').prop('checked', false);
                                $('.user-select').prop('checked', false);
                                updateBulkActionBtn();
                                setTimeout(() => {
                                    $('.alert').alert('close');
                                }, 4000);
                            },
                            error: function() {
                                $('#alert-container').html(`
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Something went wrong!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                            }
                        });
                    }
                });
            });

            // Bulk Status Update
            $(document).on('click', '.status-action-active, .status-action-inactive', function() {
                const status = $(this).data('status');
                const ids = $('.user-select:checked').map(function() {
                    return $(this).val();
                }).get();

                if (ids.length === 0) return;

                const statusText = status == 1 ? 'activate' : 'deactivate';

                Swal.fire({
                    title: 'Update Status?',
                    text: `Are you sure you want to ${statusText} selected users?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${statusText}!`,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('users.bulkUpdateStatus') }}',
                            type: 'POST',
                            data: {
                                ids: ids,
                                is_active: status,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $('#alert-container').html(`
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        ${response.message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                                loadUsers();
                                $('#select-all-users').prop('checked', false);
                                $('.user-select').prop('checked', false);
                                updateBulkActionBtn();
                                setTimeout(() => {
                                    $('.alert').alert('close');
                                }, 4000);
                            },
                            error: function() {
                                $('#alert-container').html(`
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Something went wrong!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                `);
                            }
                        });
                    }
                });
            });

            // Import functionality
            const importOffcanvasPanel = new bootstrap.Offcanvas('#importUserPanel');

            // File preview functionality
            $('#import_file').on('change', function() {
                const file = this.files[0];

                // Clear previous validation
                $(this).removeClass('is-invalid');
                $('#import_file-error').text('');

                if (!file) {
                    $(this).addClass('is-invalid');
                    $('#import_file-error').text('Please select a file');
                    $('#import-preview').hide();
                    return;
                }

                // Validate file type
                const allowedTypes = ['.xlsx', '.xls', '.csv'];
                const fileName = file.name.toLowerCase();
                const isValidType = allowedTypes.some(type => fileName.endsWith(type));

                if (!isValidType) {
                    $(this).addClass('is-invalid');
                    $('#import_file-error').text('Please select a valid file type (Excel or CSV)');
                    $('#import-preview').hide();
                    return;
                }

                // Validate file size (2MB max)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    $(this).addClass('is-invalid');
                    $('#import_file-error').text('File size must be less than 2MB');
                    $('#import-preview').hide();
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    url: '{{ route('users.preview') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            displayPreview(response.data);
                            $('#import-preview').show();
                        } else {
                            $('#import_file').addClass('is-invalid');
                            $('#import_file-error').text(response.message ||
                                'Error reading file');
                            $('#import-preview').hide();
                        }
                    },
                    error: function(xhr) {
                        let message = 'Error reading file';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        $('#import_file').addClass('is-invalid');
                        $('#import_file-error').text(message);
                        $('#import-preview').hide();
                    }
                });
            });

            function displayPreview(data) {
                const $table = $('#preview-table');
                const $thead = $table.find('thead tr');
                const $tbody = $table.find('tbody');

                // Clear existing content
                $thead.empty();
                $tbody.empty();

                if (data.length === 0) return;

                // Add headers
                const headers = Object.keys(data[0]);
                headers.forEach(header => {
                    $thead.append(`<th>${header}</th>`);
                });

                // Add data rows (show first 5 rows)
                data.slice(0, 5).forEach(row => {
                    const $tr = $('<tr>');
                    headers.forEach(header => {
                        $tr.append(`<td>${row[header] || ''}</td>`);
                    });
                    $tbody.append($tr);
                });

                if (data.length > 5) {
                    $tbody.append(
                        `<tr><td colspan="${headers.length}" class="text-center text-muted">... and ${data.length - 5} more rows</td></tr>`
                    );
                }
            }

            // Import form submission
            $('#import-user-form').on('submit', function(e) {
                e.preventDefault();

                // Clear previous validation
                $('#import_file').removeClass('is-invalid');
                $('#import_file-error').text('');

                const file = $('#import_file')[0].files[0];
                if (!file) {
                    $('#import_file').addClass('is-invalid');
                    $('#import_file-error').text('Please select a file');
                    return;
                }

                // Validate file type
                const allowedTypes = ['.xlsx', '.xls', '.csv'];
                const fileName = file.name.toLowerCase();
                const isValidType = allowedTypes.some(type => fileName.endsWith(type));

                if (!isValidType) {
                    $('#import_file').addClass('is-invalid');
                    $('#import_file-error').text('Please select a valid file type (Excel or CSV)');
                    return;
                }

                // Validate file size (2MB max)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    $('#import_file').addClass('is-invalid');
                    $('#import_file-error').text('File size must be less than 2MB');
                    return;
                }

                const formData = new FormData(this);
                const $submitBtn = $('#importBtn');
                const originalText = $submitBtn.text();

                $submitBtn.prop('disabled', true).text('Importing...');

                $.ajax({
                    url: '{{ route('users.import') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        let alertClass = 'alert-success';
                        let alertTitle = 'Import Completed!';

                        if (response.failed_count > 0) {
                            alertClass = 'alert-warning';
                            alertTitle = 'Import Completed with Issues!';
                        }

                        let alertHtml = `
                            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                <h6>${alertTitle}</h6>
                                <ul class="mb-0">
                                    <li>Successfully imported: ${response.imported_count} users</li>
                                    <li>Skipped duplicates: ${response.skipped_count} users</li>
                                    <li>Failed imports: ${response.failed_count} users</li>
                                </ul>`;

                        if (response.errors && response.errors.length > 0) {
                            alertHtml += `<hr><strong>Errors:</strong><ul class="mb-0">`;
                            response.errors.forEach(error => {
                                alertHtml += `<li>${error}</li>`;
                            });
                            alertHtml += `</ul>`;
                        }

                        alertHtml +=
                            `<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;

                        $('#alert-container').html(alertHtml);

                        $('#import-user-form')[0].reset();
                        $('#import-preview').hide();
                        loadUsers();
                        importOffcanvasPanel.hide();

                        setTimeout(() => {
                            $('.alert').alert('close');
                        }, 8000);
                    },
                    error: function(xhr) {
                        let message = 'Import failed';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        $('#alert-container').html(`
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                ${message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Clear file input when import panel is closed
            $('#importUserPanel').on('hidden.bs.offcanvas', function() {
                $('#import-user-form')[0].reset();
                $('#import-preview').hide();
                $('#import_file').removeClass('is-invalid');
                $('#import_file-error').text('');
            });

            // Image preview functionality
            $('#profile_picture').on('change', function() {
                const file = this.files[0];
                const previewContainer = $('#image-preview-container');
                const previewImage = $('#image-preview');
                const currentImageContainer = $('#current-image-container');

                if (file) {
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        $(this).addClass('is-invalid');
                        $('#profile_picture-error').text('Please select a valid image file');
                        return;
                    }

                    // Validate file size (2MB max)
                    const maxSize = 2 * 1024 * 1024;
                    if (file.size > maxSize) {
                        $(this).addClass('is-invalid');
                        $('#profile_picture-error').text('Image file size must be less than 2MB');
                        return;
                    }

                    // Clear previous errors
                    $(this).removeClass('is-invalid');
                    $('#profile_picture-error').text('');

                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.attr('src', e.target.result);
                        previewContainer.show();
                        currentImageContainer.hide(); // Hide current image when new one is selected
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.hide();
                }
            });

            // Remove image preview
            $('#remove-image').on('click', function() {
                $('#profile_picture').val('');
                $('#image-preview-container').hide();
                $('#image-preview').attr('src', '');
            });

            // Show current image in edit mode
            $(document).on('click', '.item-edit', function() {
                isEditMode = true;
                const id = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const isActive = $(this).data('is_active');
                const profilePicture = $(this).data('profile_picture');

                $('#user_id').val(id);
                $('#name').val(name);
                $('#email').val(email);
                $('#phone').val(phone);

                if (isActive == 1) {
                    $('#is_active').prop('checked', true);
                } else {
                    $('#is_active').prop('checked', false);
                }

                // Handle profile picture display
                if (profilePicture && profilePicture !== 'null') {
                    $('#current-image').attr('src', '{{ asset('user_profile_pictures/') }}/' +
                        profilePicture);
                    $('#current-image-container').show();
                    $('#image-preview-container').hide();
                } else {
                    $('#current-image-container').hide();
                    $('#image-preview-container').hide();
                }

                // Hide password field in edit mode
                $('#password').closest('.mb-3').hide();
                $('#password').removeAttr('required');
                $('#addUserLabel').text('Edit User');
                $('#saveBtn').text('Update');
                offcanvasPanel.show();
            });

            // Clear image previews when form is reset
            $('#addBtn').on('click', function() {
                isEditMode = false;
                $('#addUserLabel').text('Add User');
                $('#saveBtn').text('Save');
                $('#add-user-form')[0].reset();
                $('#user_id').val('');

                // Clear image previews
                $('#image-preview-container').hide();
                $('#current-image-container').hide();
                $('#image-preview').attr('src', '');
                $('#current-image').attr('src', '');
            });
        });
    </script>
@endsection
