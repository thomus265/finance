<?php
session_start();
$role = $_GET['role'];
if ($role == '1') {
    $_SESSION['user_id'] =1;
    $_SESSION['user_type'] = 'admin';
    header('location: admin/');
}   
else if($role == '2'){
    $_SESSION['user_id'] =4;
    $_SESSION['user_type'] = 'finance';
    header('location: finance/dashboard.php');
}else if($role == '3'){
    $_SESSION['user_id'] =3;
    $_SESSION['user_type'] = 'user';
    header('location: user/');
}

?>