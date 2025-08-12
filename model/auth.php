<?php

    require_once('../controller/main.php');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo $auth->send_magic_link($_POST['email']);
    }


