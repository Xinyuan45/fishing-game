<?php
// Start Session (Required to access $_SESSION)
session_start();

// Destroy Session Data (Logs out the user)
session_destroy();

// Clear persistent auth cookie
setcookie('auth_user_id', '', time() - 3600, '/');

// Redirect to Login Page
header("Location: login.php");
exit;
?>
