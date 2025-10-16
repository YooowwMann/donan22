<?php
require_once '../config_modern.php';

// Clear all session data
session_unset();
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Start new session for flash message
session_start();
$_SESSION['logout_message'] = 'You have been logged out successfully.';

// Redirect to login page
header('Location: login.php');
exit;
?>