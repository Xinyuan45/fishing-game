<?php
require 'db.php';

$message = "";
$setupMsg = "";

// Helper to check if any Super Admin exists
function superAdminExists($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    return $stmt->fetchColumn() > 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Claim Super Admin (Bootstrap)
    if (isset($_POST['claim_super_admin'])) {
        if (!superAdminExists($pdo)) {
            // No user logged in? Try to use session if available, or just rely on manual input if needed?
            // BETTER: Use the currently logged in user to be safe.
            session_start();
            if (isset($_SESSION['user'])) {
                $uid = $_SESSION['user']['id'];
                $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin', is_admin = 1 WHERE id = ?");
                if ($stmt->execute([$uid])) {
                    $_SESSION['user']['role'] = 'super_admin'; // Update session immediately
                    $_SESSION['user']['is_admin'] = 1;
                    $message = "<div class='alert alert-success'>🎉 You are now the Super Admin!</div>";
                }
            } else {
                 $message = "<div class='alert alert-danger'>You must be logged in to claim Super Admin.</div>";
            }
        } else {
             $message = "<div class='alert alert-danger'>Super Admin already exists.</div>";
        }
    }

    // 2. Promote User (Restricted to Super Admin)
    if (isset($_POST['promote_user']) && isset($_POST['username'])) {
        session_start();
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'super_admin') {
            $username = $_POST['username'];
            // Default promotion is to 'admin'
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin', is_admin = 1 WHERE username = ?");
            if ($stmt->execute([$username])) {
                if ($stmt->rowCount() > 0) {
                    $message = "<div class='alert alert-success'>User '$username' promoted to Admin!</div>";
                } else {
                    $message = "<div class='alert alert-warning'>User '$username' not found.</div>";
                }
            }
        } else {
             $message = "<div class='alert alert-danger'>⛔ Access Denied. Only Super Admins can promote users.</div>";
        }
    }
}

try {
    // --- Database Schema Check ---
    
    // 1. Check/Add 'role' column
    $checkRole = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($checkRole->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        $setupMsg .= "<div class='alert alert-info'>Added 'role' column to users table.</div>";
        
        // Migration: Set existing admins to 'admin' role
        $pdo->exec("UPDATE users SET role = 'admin' WHERE is_admin = 1");
        $setupMsg .= "<div class='alert alert-info'>Migrated existing admins to 'admin' role.</div>";
    }

    // 2. Maintain 'is_admin' for backward compatibility (optional, but good for safety)
    // We already have it.

    // 3. Check for 'is_custom' in fish_types (Existing check)
    $checkCustom = $pdo->query("SHOW COLUMNS FROM fish_types LIKE 'is_custom'");
    if ($checkCustom->rowCount() == 0) {
        $pdo->exec("ALTER TABLE fish_types ADD COLUMN is_custom BOOLEAN DEFAULT 0");
        $pdo->exec("ALTER TABLE fish_types ADD COLUMN map_id INT DEFAULT NULL");
        $setupMsg .= "<div class='alert alert-info'>Added 'is_custom' and 'map_id' columns to fish_types.</div>";
    }

    if (empty($setupMsg)) {
         $setupMsg = "<div class='alert alert-success'>Database is fully up to date.</div>";
    }

} catch (PDOException $e) {
    $setupMsg = "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}

$hasSuperAdmin = superAdminExists($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1>Admin System Setup</h1>
        <?= $setupMsg ?>
        <?= $message ?>
        
        <!-- Super Admin Bootstrap Section -->
        <?php if (!$hasSuperAdmin): ?>
            <div class="card bg-warning text-dark mt-4">
                <div class="card-body">
                    <h3>⚠️ No Super Admin Detected</h3>
                    <p>The system needs at least one Super Admin to manage roles effectively.</p>
                    <form method="post">
                        <button type="submit" name="claim_super_admin" class="btn btn-dark">👑 Claim Super Admin Role</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detailed Role Management Link -->
         <div class="card bg-secondary mt-4">
            <div class="card-body">
                <h3>System Management</h3>
                <p>For advanced user management, role assignment, and demotions, please use the main Admin Panel.</p>
                <a href="admin.php" class="btn btn-light">Go to Admin Panel</a>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-primary">Back to Game</a>
        </div>
    </div>
</body>
</html>
