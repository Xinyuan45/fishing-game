<?php
// Start Session (Required to access $_SESSION)
session_start();

// Destroy Session Data (Logs out the user)
session_destroy();

// Redirect to Login Page
header("Location: login.php");
exit;
?>
