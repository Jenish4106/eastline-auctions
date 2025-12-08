<!DOCTYPE html>

<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
    data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Login Cover - Pages</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/../../rb-equipment-sales/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/fonts/tabler-icons.css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/fonts/flag-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/css/rtl/core.css"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/css/rtl/theme-default.css"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/libs/typeahead-js/typeahead.css" />
    <!-- Vendor -->
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/libs/formvalidation/dist/css/formValidation.min.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="/../../rb-equipment-sales/assets/vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="/../../rb-equipment-sales/assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="/../../rb-equipment-sales/assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="/../../rb-equipment-sales/assets/js/config.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .cursor-pointer {
            border-top-right-radius: 0.375rem !important;
            border-bottom-right-radius: 0.375rem !important;
        }

        .authentication-wrapper.authentication-cover .authentication-inner .auth-cover-bg .auth-illustration {
            z-index: 1 !important;
            max-width: 65% !important;
        }
    </style>
</head>

<body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover authentication-bg">
        <div class="authentication-inner row">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="/../../rb-equipment-sales/assets/img/illustrations/auth-login-illustration-light.png"
                        alt="auth-login-cover" class="img-fluid my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-login-illustration-light.png"
                        data-app-dark-img="illustrations/auth-login-illustration-dark.png" />

                    <img src="/../../rb-equipment-sales/assets/img/illustrations/bg-shape-image-light.png" alt="auth-login-cover"
                        class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png" />
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
                <div class="w-px-400 mx-auto">
                    <h3 class="mb-1 fw-bold">Welcome to RB Equipment 👋</h3>
                    @if (session('success'))
                        <div class="alert alert-success mt-3 alert-dismissible" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger mt-3 alert-dismissible" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <p class="mb-3 text-muted">Empowering your assessments, one test at a time.</p>

                    <form class="mb-3" id="LoginForm">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email"
                                placeholder="Please Enter your email" autofocus />
                            <div class="invalid-feedback" id="email-error"></div>
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                            </div>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                                <div class="invalid-feedback" id="password-error"></div>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100" id="submitButton">
                            Sign In
                        </button>

                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <script>
                            $('input, select, textarea').on('input', function() {
                                $(this).removeClass('is-invalid');
                                $('#' + $(this).attr('id') + '-error').text('');

                                $('.alert').remove();
                            });

                            $('#LoginForm').on('submit', function(e) {
                                e.preventDefault();

                                $('.alert').remove();

                                var formData = new FormData(this);
                                var submitButton = $('#submitButton');

                                submitButton.html(
                                    '<span class="spinner-border spinner-border-sm align-middle me-2" role="status" aria-hidden="true" style="vertical-align: middle;"></span>Signing in<span class="loading-dots">...</span>'
                                ).prop('disabled', true);

                                $.ajax({
                                    url: '{{ route('admin.login.check') }}',
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        var redirectUrl = response.redirect_url;
                                        window.location.href = redirectUrl;
                                    },
                                    error: function(xhr) {
                                        $('.is-invalid').removeClass('is-invalid');
                                        $('.invalid-feedback').text('');
                                        $('.alert').remove();

                                        var errors = xhr.responseJSON.errors;

                                        if (xhr.status === 422) {
                                            $.each(errors, function(key, value) {
                                                $('#' + key).addClass('is-invalid');
                                                $('#' + key + '-error').text(value[0]);
                                            });
                                        } else {
                                            var errorMessage = xhr.responseJSON.message || xhr.responseJSON.errors || "An unknown error occurred";

                                            $('.fw-bold').after(
                                                '<div id="login-error-message" class="alert alert-danger mt-3 alert-dismissible" role="alert">' +
                                                errorMessage +
                                                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                                '</div>'
                                            );
                                        }
                                    },
                                    complete: function() {
                                        submitButton.text('Sign in').prop('disabled', false);
                                    }
                                });
                            });
                        </script>
                    </form>
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js /../../rb-equipment-sales/assets/vendor/js/core.js -->
    <script src="/../../rb-equipment-sales/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/popper/popper.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/js/bootstrap.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/node-waves/node-waves.js"></script>

    <script src="/../../rb-equipment-sales/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/i18n/i18n.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/typeahead-js/typeahead.js"></script>

    <script src="/../../rb-equipment-sales/assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="/../../rb-equipment-sales/assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js"></script>
    <script src="/../../rb-equipment-sales/assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js"></script>

    <!-- Main JS -->
    <script src="/../../rb-equipment-sales/assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="/../../rb-equipment-sales/assets/js/pages-auth.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
