<?php
require_once 'includes/functions.php';
startSession();

// Destroy all session data
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to home page with success message
session_start();
$_SESSION['success_message'] = "You have been logged out successfully.";
header("Location: index.php");
exit();
?>
