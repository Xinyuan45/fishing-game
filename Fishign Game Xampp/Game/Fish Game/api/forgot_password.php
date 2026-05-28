<?php
require 'db.php';
session_start();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // 1. Validate Email Existence
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // 2. Generate Secure Token
        // Create a cryptographically secure random token (64 hex characters)
        $token = bin2hex(random_bytes(32));
        // Set expiration time (1 hour from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // 3. Store Token in Database
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expiresAt]);
        
        // 4. Generate Reset Link (Simulated Email)
        // In a Production Environment, use PHPMailer or similar to send this link via email.
        $resetLink = "http://localhost/dashboard/Game/Fish%20Game/reset_password.php?token=" . $token;
        $successMessage = "Password reset link generated! (In production, this would be sent to your email)<br><br>" .
                         "<strong>Reset Link:</strong><br>" .
                         "<a href='$resetLink' class='text-break'>$resetLink</a><br><br>" .
                         "<small class='text-muted'>This link will expire in 1 hour.</small>";
    } else {
        // Security Note: In a high-security app, you might want generic messages to prevent email enumeration.
        $errorMessage = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 text-center">
                <div class="mb-3">
                    <span class="badge bg-warning fs-6 px-4 py-2">FORGOT PASSWORD</span>
                </div>
                <h2 class="mb-2 fw-bold text-primary-custom">🔑 Reset Your Password</h2>
                <h5 class="mb-4 text-muted">Enter your email to receive a reset link</h5>

                <?php if (!empty($successMessage)) echo "<div class='alert alert-success bg-success bg-opacity-10 border-success text-success'>$successMessage</div>"; ?>
                <?php if (!empty($errorMessage)) echo "<div class='alert alert-danger bg-danger bg-opacity-10 border-danger text-danger'>$errorMessage</div>"; ?>

                <?php if (empty($successMessage)): ?>
                <form method="post">
                    <div class="mb-4 text-start">
                        <label class="form-label text-light">Email Address</label>
                        <input class="form-control form-control-lg" type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <button class="btn btn-warning w-100 btn-lg mb-3">Send Reset Link</button>
                </form>
                <?php endif; ?>

                <p class="text-muted mb-0">
                    <a href="login.php" class="text-primary-custom text-decoration-none">← Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
