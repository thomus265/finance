<?php
    $page = 'Login';
    require('includes/top.php');
?>
    <!-- Content -->

    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Register -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="index.html" class="app-brand-lik gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="./public/assets/img/favicon/favicon.ico" />
                                </span>
                                <br/>
                                
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-2">Welcome to UNDP Finance</h4>
                        <p class="mb-4">Please sign-in to your account </p>

                        <form id="magicForm" class="mb-3" action="index.html" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    placeholder="Enter your email " autofocus />
                            </div>

                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Get magic link</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>New on our platform?</span>
                            <a href="create_account/">
                                <span>Create an account</span>
                            </a>
                        </p>

                        <div>
                            <h3>DEV</h3>
                            <a href="dev.php?role=1">Admin</a>
                            <a href="dev.php?role=2">Finance</a>
                            <a href="dev.php?role=3">User</a>
                        </div>
                    </div>
                </div>
                <!-- /Register -->
            </div>
        </div>
    </div>


    <script>
    $('#magicForm').submit(function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Sending...',
            text: 'Please wait while we process your request.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.post('./model/auth.php', $(this).serialize(), function(data) {
            Swal.fire({
                title: 'Response',
                text: data,
                icon: 'Magic link is sent!' 
            });
        }).fail(function(xhr) {
            Swal.fire({
                title: 'Error',
                text: 'Something went wrong. Please try again.',
                icon: 'error'
            });
        });
    });
    </script>

    <?php require('includes/bottom.php');