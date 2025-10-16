<?php
// Admin Panel Entry Point
// Redirect to appropriate page based on login status

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';

// Check if user is logged in
if (isLoggedIn()) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
} else {
    // User not logged in, redirect to login page
    header('Location: login.php');
}
exit;
?>