<?php
require 'db.php';
session_start();

$token = $_GET['token'] ?? '';
$errorMessage = '';
$tokenValid = false;
$userId = null;

// --- Verify Reset Token ---
if ($token) {
    // 1. Fetch Token Data
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reset) {
        // 2. Check Expiration
        if (strtotime($reset['expires_at']) > time()) {
            $tokenValid = true;
            $userId = $reset['user_id'];
        } else {
            $errorMessage = "This reset link has expired. Please request a new one.";
        }
    } else {
        $errorMessage = "Invalid reset link.";
    }
} else {
    $errorMessage = "No reset token provided.";
}

// --- Handle Password Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // 1. Match Passwords
    if ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match";
    } else {
        // 2. Enforce Password Strength
        $passwordErrors = [];
        if (strlen($password) < 8) {
            $passwordErrors[] = "at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $passwordErrors[] = "at least one capital letter";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $passwordErrors[] = "at least one punctuation mark";
        }
        
        if (!empty($passwordErrors)) {
            $errorMessage = "Password must contain " . implode(', ', $passwordErrors) . ".";
        } else {
            // 3. Update Password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $userId]);
            
            // 4. Invalidate Token (Prevent Replay Attacks)
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            // 5. Success Redirect
            header("Location: login.php?reset=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 text-center">
                <div class="mb-3">
                    <span class="badge bg-info fs-6 px-4 py-2">RESET PASSWORD</span>
                </div>
                <h2 class="mb-2 fw-bold text-primary-custom">🔐 Set New Password</h2>
                <h5 class="mb-4 text-muted">Enter your new password</h5>

                <?php if (!empty($errorMessage)) echo "<div class='alert alert-danger bg-danger bg-opacity-10 border-danger text-danger'>$errorMessage</div>"; ?>

                <?php if ($tokenValid): ?>
                <form method="post">
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">New Password</label>
                        <input class="form-control form-control-lg" type="password" name="password" placeholder="Enter new password" required>
                        <small class="text-muted">Must be 8+ characters with a capital letter and punctuation</small>
                    </div>
                    <div class="mb-4 text-start">
                        <label class="form-label text-light">Confirm New Password</label>
                        <input class="form-control form-control-lg" type="password" name="confirm_password" placeholder="Re-enter new password" required>
                    </div>
                    <button class="btn btn-info w-100 btn-lg mb-3">Reset Password</button>
                </form>
                <?php else: ?>
                <div class="mt-4">
                    <a href="forgot_password.php" class="btn btn-warning btn-lg">Request New Reset Link</a>
                </div>
                <?php endif; ?>

                <p class="text-muted mb-0 mt-3">
                    <a href="login.php" class="text-primary-custom text-decoration-none">← Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
