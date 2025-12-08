 <!-- Navbar -->
 <nav class="layout-navbar navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
     <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
         <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
             <i class="ti ti-menu-2 ti-sm"></i>
         </a>
     </div>

     <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
         <!-- Search -->
         <div class="navbar-nav align-items-center">
             <div class="nav-item navbar-search-wrapper mb-0">
                 <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
                     <i class="ti ti-search ti-md me-2"></i>
                     <span class="d-none d-md-inline-block text-muted">Search (Ctrl+/)</span>
                 </a>
             </div>
         </div>
         <!-- /Search -->

         <ul class="navbar-nav flex-row align-items-center ms-auto">
             <!-- User -->
             <li class="nav-item navbar-dropdown dropdown-user dropdown">
                 <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                     <div class="avatar avatar-online">
                         <img src="/../../rb-equipment-sales/assets/img/avatars/1.png" alt class="h-auto rounded-circle" />
                     </div>
                 </a>
                 <ul class="dropdown-menu dropdown-menu-end">
                     <li>
                         <a class="dropdown-item" href="{{ route('dashboard') }}">
                             <div class="d-flex">
                                 <div class="flex-shrink-0 me-3">
                                     <div class="avatar avatar-online">
                                         <img src="/../../rb-equipment-sales/assets/img/avatars/1.png" alt
                                             class="h-auto rounded-circle" />
                                     </div>
                                 </div>
                                 <div class="flex-grow-1">
                                     <span class="fw-semibold d-block">{{ Auth::guard('admin')->user()->name }}</span>
                                     <small
                                         class="text-muted">{{ \App\Models\Admin\Role::where('id', Auth::guard('admin')->user()->role_id)->first()->role_name }}</small>
                                 </div>
                             </div>
                         </a>
                     </li>
                     <li>
                         <div class="dropdown-divider"></div>
                     </li>
                     <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="offcanvas"
                         data-bs-target="#changePasswordPanel">
                         <i class="ti ti-lock me-2 ti-sm"></i>
                         <span class="align-middle">Change Password</span>
                     </a>
                     <li>
                         <a class="dropdown-item" href="{{ route('admin.logout') }}">
                             <i class="ti ti-logout me-2 ti-sm"></i>
                             <span class="align-middle">Log Out</span>
                         </a>
                     </li>
                 </ul>
             </li>
             <!--/ User -->
         </ul>
     </div>

     <!-- Search Small Screens -->
     <div class="navbar-search-wrapper search-input-wrapper d-none">
         <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..."
             aria-label="Search..." />
         <i class="ti ti-x ti-sm search-toggler cursor-pointer"></i>
     </div>
 </nav>
 <div class="offcanvas offcanvas-end" tabindex="-1" id="changePasswordPanel" aria-labelledby="changePasswordLabel">
     <div class="offcanvas-header">
         <h5 id="changePasswordLabel" class="offcanvas-title">Change Password</h5>
         <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
     </div>

     <form method="POST" class="d-flex flex-column h-100" id="change-password-form">
         @csrf
         <div class="offcanvas-body flex-grow-1">
             <div class="mb-3">
                 <label for="old_password" class="form-label">Current Password</label>
                 <input type="password" name="old_password" class="form-control" id="old_password"
                     placeholder="Enter Old Password">
                 <div class="invalid-feedback" id="old_password-error"></div>
             </div>
             <div class="mb-3">
                 <label for="new_password" class="form-label">New Password</label>
                 <input type="password" name="new_password" class="form-control" id="new_password"
                     placeholder="Enter New Password">
                 <div class="invalid-feedback" id="new_password-error"></div>
             </div>
             <div class="mb-3">
                 <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                 <input type="text" name="new_password_confirmation" class="form-control"
                     placeholder="Enter Confirm Password" id="new_password_confirmation">
                 <div class="invalid-feedback" id="new_password_confirmation-error"></div>
             </div>
         </div>

         <div class="offcanvas-footer p-3 border-top">
             <button type="submit" class="btn btn-primary w-100">Update Password</button>
         </div>
     </form>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <script>
         $(document).ready(function() {
             $('input, select, textarea').on('input', function() {
                 $(this).removeClass('is-invalid');
                 $('#' + $(this).attr('id') + '-error').text('');
             });

             $('#change-password-form').on('submit', function(e) {
                 e.preventDefault();

                 var formData = new FormData(this);

                 $.ajax({
                     url: '{{ route('change.password') }}',
                     type: 'POST',
                     data: formData,
                     processData: false,
                     contentType: false,
                     success: function(response) {
                         const offcanvasEl = document.getElementById(
                             'changePasswordPanel');
                         const offcanvas = bootstrap.Offcanvas.getInstance(
                             offcanvasEl);
                         if (offcanvas) {
                             offcanvas.hide();
                         }
                         $('#change-password-form')[0].reset();
                         Swal.fire({
                             icon: 'success',
                             title: 'Password Changed',
                             text: 'Your password has been updated successfully.',
                             confirmButtonText: 'OK'
                         });
                     },
                     error: function(xhr) {
                         var errors = xhr.responseJSON.errors;

                         if (xhr.status === 422) {
                             $('.is-invalid').removeClass('is-invalid');
                             $('.invalid-feedback').text('');

                             $.each(errors, function(key, value) {
                                 $('#' + key).addClass('is-invalid');
                                 $('#' + key + '-error').text(value[0]);
                             });
                         } else {
                             var errorMessage = xhr.responseJSON.message ||
                                 "An unknown error occurred";

                             Swal.fire({
                                 icon: 'error',
                                 title: 'Error',
                                 text: errorMessage,
                                 confirmButtonText: 'OK'
                             });
                         }
                     },
                 });
             });
         });
     </script>

 </div>

 <!-- / Navbar -->
