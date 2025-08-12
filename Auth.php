<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Verification</title>
    <!-- UIkit CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.23.11/dist/css/uikit.min.css" />

    <!-- UIkit JS -->
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.23.11/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.23.11/dist/js/uikit-icons.min.js"></script>
</head>
<style>
body {
    background-color: #007bff !important;
    height: 100vh;

}

h2 {
    color: #f2f2f2 !important;
}

.uk-button {
    border-radius: 25px;
}

img {
    width: 20em
}
</style>

<body>
    <div class="uk-position-center">
        <center>
            <img src="./public/assets/img/illustrations/girl-doing-yoga-light.png" />

            <?php
            require 'controller/main.php';

            $baseUrl = $_SERVER['HTTP_HOST']; // e.g. localhost
            $link = "http://$baseUrl/";

            if (!isset($_GET['token'])) {
                echo "Missing token.";
                exit;
            }

            $auth = new Auth();
            $user_id = $auth->validate_magic_link($_GET['token']);

            if ($user_id) {
                $_SESSION['user_id'] = $user_id;
                $user = $user->get_user($user_id);
                $usetype = $user['user_type'];

                /**$user = (new Database())->query(
                    "SELECT user_name FROM users WHERE user_id = ?", $user_id
                )->fetchArray();*/

                /*echo "Welcome, " . htmlspecialchars($user['user_name']) . "! You are now logged in. <a href='protected.php'>Continue</a>";*/

                if ($usetype == '1') {
                    header("location: admin/");
                }
                else if($usetype == '2'){
                    header("location: dashboard/");
                }else{
                    header("location: home/");
                }
            } else {
                echo "<h2>Invalid or expired link.</h2> <br/> ";
                echo "<a class='uk-button uk-button-secondary' href=\"$link\">Login Again</a>";
                
            }
        ?>
        </center>
    </div>

</body>

</html>
<?php
//session_start();