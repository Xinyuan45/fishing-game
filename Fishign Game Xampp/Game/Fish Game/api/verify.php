<?php
require 'db.php';

$success = false;
$error = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Find user with this token
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Mark as verified and remove token
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $update->execute([$user['id']]);
            
            $success = true;
            $username = $user['username'];
        } else {
            $error = "Invalid or expired verification token.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Account - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="glass-card p-5">
                <?php if ($success): ?>
                    <div class="mb-4">
                        <span class="display-1">✅</span>
                    </div>
                    <h2 class="text-success mb-3">Account Verified!</h2>
                    <p class="mb-4">Welcome aboard, <strong><?= htmlspecialchars($username) ?></strong>! Your email has been successfully verified.</p>
                    <a href="login.php" class="btn btn-primary btn-lg w-100">Login to Fish</a>
                <?php else: ?>
                    <div class="mb-4">
                        <span class="display-1">❌</span>
                    </div>
                    <h2 class="text-danger mb-3">Verification Failed</h2>
                    <p class="mb-4 text-muted"><?= htmlspecialchars($error) ?></p>
                    <a href="register.php" class="btn btn-outline-light">Back to Registration</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
