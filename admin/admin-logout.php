<?php
require_once '../includes/functions.php';
startSession();

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Redirect to admin login
header("Location: admin-login.php");
exit();
?>
