<!DOCTYPE html>

<!-- =========================================================
* UNDP finance Digitalization system Login page
==============================================================

* Created by:21/07/2025
* Author(s): Thomson thom.
* Version:1.0 Beta
* last update: 

=========================================================
 -->
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="./public/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <base href="/">
    <title>Undp Finance | Registration</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="./public/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="./public/assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="./public/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./public/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./public/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="./public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="./public/assets/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="./public/assets/css/modes.css" />
    <!-- Helpers -->
    <script src="./public/assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="./public/assets/js/config.js"></script>
</head>

<body>
    <!-- Content -->

    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Register Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="index.html" class="app-brand-lik gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="./public/assets/img/favicon/favicon.ico" />
                                </span>
                                <br />

                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-2">Register with UNDP Finance</h4>


                        <form id="register-form" class="mb-3" action="index.html" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Enter your username" autofocus />
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    placeholder="Enter your email" />
                                <span id="email-error"></span>
                            </div>


                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms-conditions"
                                        name="terms" />
                                    <label class="form-check-label" for="terms-conditions">
                                        I agree to
                                        <a href="javascript:void(0);">privacy policy & terms</a>
                                    </label>
                                </div>
                            </div>
                            <button class="btn btn-primary d-grid w-100">Register</button>
                        </form>

                        <p class="text-center">
                            <span>Already have an account?</span>
                            <a href="./">
                                <span>Sign in instead</span>
                            </a>
                        </p>
                    </div>
                </div>
                <!-- Register Card -->
            </div>
        </div>
    </div>


    <script src="./public/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./public/assets/vendor/libs/popper/popper.js"></script>
    <script src="./public/assets/vendor/js/bootstrap.js"></script>
    <script src="./public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="./public/assets/vendor/js/menu.js"></script>

    <script src="./public/assets/js/main.js"></script>

    <script async defer src="https://buttons.github.io/buttons.js"></script>

    <script>
    let typingTimer;
    const doneTypingInterval = 1000; // 1 second
    let emailIsValid = false;

    $('#email').on('input', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(checkEmail, doneTypingInterval);
    });

    $('#email').on('keydown', function() {
        clearTimeout(typingTimer);
    });

    function checkEmail() {
        const email = $('#email').val().trim();
        const domainRegex = /^[a-zA-Z0-9._%+-]+@undp\.org$/;

        // Check for domain
        if (!domainRegex.test(email)) {
            $('#email-error')
                .text('Only @undp.org emails are allowed.')
                .css('color', 'red')
                .show();
            $('#email').removeClass('valid-input').addClass('invalid-input');
            $('#submit-btn').prop('disabled', true);
            emailIsValid = false;
            return;
        }

        // Proceed with AJAX check
        $.ajax({
            url: './model/checkEmailReg.php',
            method: 'POST',
            data: {
                email: email
            },
            success: function(response) {
                if (response === 'exists') {
                    $('#email-error').text('This email is already registered.').css('color', 'red').show();
                    $('#email').removeClass('valid-input invalid-input').addClass('invalid-input');
                    $('#submit-btn').prop('disabled', true);
                    emailIsValid = false;
                } else if (response === 'not_exists') {
                    $('#email-error').text('Email is valid.').css('color', 'green').show();
                    $('#email').removeClass('valid-input invalid-input').addClass('valid-input');
                    $('#submit-btn').prop('disabled', false);
                    emailIsValid = true;
                } else if (response === 'invalid_domain') {
                    $('#email-error').text('Only @undp.org emails are allowed.').css('color', 'red').show();
                    $('#email').removeClass('valid-input invalid-input').addClass('invalid-input');
                    $('#submit-btn').prop('disabled', true);
                    emailIsValid = false;
                } else {
                    $('#email-error').text('Error checking email.').css('color', 'orange').show();
                    $('#email').removeClass('valid-input invalid-input');
                    $('#submit-btn').prop('disabled', true);
                    emailIsValid = false;
                }
            }
        });
    }
    $('#register-form').on('submit', function(e) {
        if (!emailIsValid) {
            e.preventDefault();
            alert('Please enter a valid and available email address.');
        }
    });
    </script>
</body>

</html>