<?php
// Include database connection configuration
require 'db.php';

// Start a new session or resume the existing one
// This is crucial for tracking the logged-in user across pages
session_start();

$successMessage = '';

// Check URL parameters for success messages (sent from other pages like register.php)
if (isset($_GET['registered'])) {
    $successMessage = "Registration successful! Please login with your credentials.";
} elseif (isset($_GET['reset'])) {
    $successMessage = "Password reset successful! Please login with your new password.";
}

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize username input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepare a SQL query to select the user by username
    // Using prepared statements prevents SQL Injection attacks
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    // Fetch the user record as an associative array
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Authentication Logic ---
    // 1. Check if user was found ($user is not false)
    // 2. enforce Strict Case Sensitivity for username (PHP's === operator)
    // 3. Verify the submitted password against the stored hash using password_verify()
    if ($user && $user['username'] === $username && password_verify($password, $user['password_hash'])) {
        // Check Email Verification
        if ($user['is_verified'] == 0) {
            $error = "Please verify your email address before logging in.<br>Check your inbox for the activation link.";
        } else {
            // Authentication Successful:
            // Store the user data in the session variable so they stay logged in
            $_SESSION['user'] = $user;
            
            // Redirect the user to the dashboard
            header("Location: dashboard.php");
            exit; // Always exit after a header redirect
        }
    } else {
        // Authentication Failed: Set an error message
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 text-center">
                <div class="mb-3">
                    <span class="badge bg-primary fs-6 px-4 py-2">LOGIN</span>
                </div>
                <h2 class="mb-2 fw-bold text-primary-custom text-nowrap">🎣 Login to Your Account</h2>
                <h5 class="mb-4 text-muted text-nowrap">Welcome Back to Deep Ocean</h5>
                
                <?php if (!empty($successMessage)) echo "<div class='alert alert-success bg-success bg-opacity-10 border-success text-success'>$successMessage</div>"; ?>
                <?php if (!empty($error)) echo "<div class='alert alert-danger bg-danger bg-opacity-10 border-danger text-danger'>$error</div>"; ?>
                
                <!-- Login Form -->
                <form method="post">
                    <!-- Username Input -->
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">Username</label>
                        <input class="form-control form-control-lg" name="username" placeholder="Enter your username" required>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">Password</label>
                        <input class="form-control form-control-lg" type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <!-- Forgot Password Link -->
                    <div class="mb-3 text-end">
                        <a href="forgot_password.php" class="text-primary-custom text-decoration-none small">Forgot Password?</a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button class="btn btn-primary w-100 btn-lg mb-3">Login</button>
                </form>
                
                <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-primary-custom text-decoration-none">Register here</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
