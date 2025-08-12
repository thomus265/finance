<?php
require_once '../controller/main.php';

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    $db->query("SELECT user_id FROM users WHERE user_email = ?", $email);

    if ($db->numRows() > 0) {
        echo 'exists';
    } else {
        echo 'not_exists';
    }
}