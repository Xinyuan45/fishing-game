<?php
session_start();
require 'db.php';

// --- Admin Authorization Check ---
// 1. Check if session has admin flag
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] != 1) {
    // 2. Re-verify against database
    if (isset($_SESSION['user'])) {
        $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['is_admin']) {
            $_SESSION['user']['is_admin'] = 1;
            $_SESSION['user']['role'] = $user['role']; // Sync role
        } else {
            header("Location: index.php");
            exit;
        }
    } else {
        header("Location: login.php");
        exit;
    }
}

// Current User Role
$currentUserRole = $_SESSION['user']['role'] ?? 'admin';
$isSuperAdmin = ($currentUserRole === 'super_admin');

$message = "";

// --- Handle Admin Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action 1: Update User Stats (Available to all admins)
    if (isset($_POST['update_user'])) {
        $uid = $_POST['user_id'];
        $coins = $_POST['coins'];
        $level = $_POST['level'];
        
        // Prevent modifying other Super Admins if you are just a regular admin (though UI hides it, validation is key)
        if (!$isSuperAdmin) {
            // Check target user role
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $targetRole = $stmt->fetchColumn();
            if ($targetRole === 'super_admin') {
                $message = "❌ You cannot modify a Super Admin.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET coins = ?, level = ? WHERE id = ?");
                $stmt->execute([$coins, $level, $uid]);
                $message = "User updated!";
            }
        } else {
            // Processing Role Changes (Super Admin Only)
            if (isset($_POST['role'])) {
                $newRole = $_POST['role'];
                // Safety: Don't demote yourself by accident
                if ($uid == $_SESSION['user']['id'] && $newRole !== 'super_admin') {
                     $message = "❌ You cannot demote yourself.";
                } else {
                    $isAdmin = ($newRole === 'admin' || $newRole === 'super_admin') ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE users SET coins = ?, level = ?, role = ?, is_admin = ? WHERE id = ?");
                    $stmt->execute([$coins, $level, $newRole, $isAdmin, $uid]);
                    $message = "User updated (including Role)!";
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET coins = ?, level = ? WHERE id = ?");
                $stmt->execute([$coins, $level, $uid]);
                 $message = "User updated!";
            }
        }
    }
    
    // Action 2: Delete User (Super Admin Only)
    if (isset($_POST['delete_user'])) {
        if ($isSuperAdmin) {
            $uid = $_POST['user_id'];
            
            // Safety: Don't delete yourself
            if ($uid == $_SESSION['user']['id']) {
                 $message = "❌ You cannot delete yourself.";
            } else {
                // Cleanup Protocol
                $pdo->prepare("DELETE FROM user_baits WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM user_rods WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM user_maps WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM user_stats WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM fish_catches WHERE user_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
                $message = "User deleted!";
            }
        } else {
            $message = "❌ Only Super Admins can delete users.";
        }
    }
}

// Fetch Data
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalUsers = count($users);
$totalCatches = $pdo->query("SELECT COUNT(*) FROM fish_catches")->fetchColumn();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Fish Game</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="text-primary-custom">🛠️ Admin Dashboard</h1>
            <span class="badge <?= $isSuperAdmin ? 'bg-warning text-dark' : 'bg-primary' ?>">
                <?= $isSuperAdmin ? '👑 Super Admin' : '🛡️ Admin' ?>
            </span>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light">Back to Game</a>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-success' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="glass-card p-4 mb-4">
        <h4 class="mb-3">🚀 Quick Actions</h4>
        <div class="d-flex gap-3 flex-wrap">
            <a href="admin_fish_images.php" class="btn btn-lg btn-primary shadow">
                🖼️ Fish Image Manager
            </a>
            <a href="admin_shop_manager.php" class="btn btn-lg btn-warning shadow">
                🛒 Shop Manager
            </a>
            <a href="dashboard.php" class="btn btn-lg btn-success shadow">
                🎮 Play Game
            </a>
            <!-- Only Super Admin can access setup/promotion tools ideally, but the setup file handles its own check now -->
            <?php if ($isSuperAdmin): ?>
            <a href="admin_setup.php" class="btn btn-lg btn-outline-info">
                 ⚙️ System Setup
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="glass-card p-4 text-center">
                <h3>👥 Total Users</h3>
                <h2 class="text-info"><?= $totalUsers ?></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="glass-card p-4 text-center">
                <h3>🐟 Total Fish Caught</h3>
                <h2 class="text-success"><?= $totalCatches ?></h2>
            </div>
        </div>
    </div>

    <!-- User Management -->
    <div class="glass-card p-4">
        <h3 class="mb-3">User Management</h3>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Coins</th>
                        <th>Level</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <input type="number" name="coins" value="<?= $u['coins'] ?>" class="form-control form-control-sm" style="width: 100px;">
                            </td>
                            <td>
                                <input type="number" name="level" value="<?= $u['level'] ?>" class="form-control form-control-sm" style="width: 80px;">
                            </td>
                            <td>
                                <?php if ($isSuperAdmin): ?>
                                    <select name="role" class="form-select form-select-sm" style="width: 120px;">
                                        <option value="user" <?= (!isset($u['role']) || $u['role'] == 'user') ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= (isset($u['role']) && $u['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="super_admin" <?= (isset($u['role']) && $u['role'] == 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                                    </select>
                                <?php else: ?>
                                    <?php 
                                        $displayRole = $u['role'] ?? ($u['is_admin'] ? 'admin' : 'user');
                                        $badgeClass = match($displayRole) {
                                            'super_admin' => 'bg-warning text-dark',
                                            'admin' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($displayRole) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="submit" name="update_user" class="btn btn-sm btn-primary">Save</button>
                                
                                <?php if ($isSuperAdmin && $u['id'] != $_SESSION['user']['id']): ?>
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to permanently delete this user?')">Delete</button>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
