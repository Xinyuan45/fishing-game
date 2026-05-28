<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Handle profile update
// --- Handle Profile Update Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $newUsername = trim($_POST['username']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    // Check if the desired username is already taken by ANOTHER user
    // SQL: Select ID where username matches but ID is NOT the current user's ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$newUsername, $userId]);
    
    if ($stmt->fetchColumn()) {
        $error = "Username already taken!";
    } else {
        // 1. Update Username
        $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$newUsername, $userId]);
        
        // Update session to reflect new username immediately
        $_SESSION['user']['username'] = $newUsername;
        
        // 2. Update Password (only if both fields are filled)
        if (!empty($currentPassword) && !empty($newPassword)) {
            // --- Password Validation Rules ---
            $passwordErrors = [];
            if (strlen($newPassword) < 8) {
                $passwordErrors[] = "at least 8 characters";
            }
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $passwordErrors[] = "at least one capital letter";
            }
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
                $passwordErrors[] = "at least one punctuation mark";
            }
            
            if (!empty($passwordErrors)) {
                $error = "New password must contain " . implode(', ', $passwordErrors) . ".";
            } else {
                // --- Verify Current Password ---
                // We must verify the user knows their current password before allowing a change
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $storedHash = $stmt->fetchColumn();
                
                if (password_verify($currentPassword, $storedHash)) {
                    // Hash the new password and update database
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Current password is incorrect!";
                }
            }
        } else {
            // Only username was updated
            $success = "Username updated successfully!";
        }
    }
}

// Get user stats
$stmt = $pdo->prepare("SELECT * FROM user_stats WHERE user_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total fish count
// Get total fish count
$totalFish = $stats['total_catches'] ?? 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-card p-5">
                <h2 class="text-primary-custom fw-bold mb-4">👤 My Profile</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <!-- Stats Overview -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="glass-card p-3 text-center">
                            <h3 class="text-warning"><?= $user['level'] ?? 1 ?></h3>
                            <small class="text-muted">Level</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card p-3 text-center">
                            <h3 class="text-warning"><?= number_format($user['coins']) ?></h3>
                            <small class="text-muted">Coins</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card p-3 text-center">
                            <h3 class="text-warning"><?= $totalFish ?></h3>
                            <small class="text-muted">Total Fish</small>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Form -->
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Change Password (Optional)</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" placeholder="Enter current password">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Enter new password">
                        <small class="text-muted">Must be 8+ characters with a capital letter and punctuation</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
