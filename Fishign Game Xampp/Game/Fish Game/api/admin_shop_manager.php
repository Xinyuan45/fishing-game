<?php
session_start();
require 'db.php';

// Reliable Admin Check (Same as admin.php)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] != 1) {
    // Re-check DB in case session is stale
    if (isset($_SESSION['user'])) {
        $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['is_admin']) {
            $_SESSION['user']['is_admin'] = 1;
            $_SESSION['user']['role'] = $user['role'];
        } else {
            header("Location: index.php");
            exit;
        }
    } else {
        header("Location: login.php");
        exit;
    }
}

$message = "";
$msgType = "";

// Handle Uploads & Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Update Rod ---
    if (isset($_POST['update_rod'])) {
        $id = $_POST['rod_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $luck = $_POST['luck_multiplier'];
        
        // 1. Update basic info (Name, Price, Stats)
        $sql = "UPDATE rods SET name = ?, price = ?, luck_multiplier = ? WHERE id = ?";
        $params = [$name, $price, $luck, $id];
        $pdo->prepare($sql)->execute($params);
        $message = "Rod updated!";
        $msgType = "success";

        // 2. Handle Image Upload for Rod
        if (isset($_FILES['rod_image']) && $_FILES['rod_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['rod_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                // Sanitize filename to be safe
                $filename = preg_replace('/[^a-z0-9]+/', '_', strtolower($name)) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['rod_image']['tmp_name'], "images/rods/$filename")) {
                    // Update DB with new image path
                    $pdo->prepare("UPDATE rods SET image = ? WHERE id = ?")->execute([$filename, $id]);
                    $message .= " Image uploaded!";
                }
            }
        }
    }

    // --- Update Bait ---
    if (isset($_POST['update_bait'])) {
        $id = $_POST['bait_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $boost = $_POST['rarity_boost'];
        
        // 1. Update basic info
        $sql = "UPDATE baits SET name = ?, price = ?, rarity_boost = ? WHERE id = ?";
        $params = [$name, $price, $boost, $id];
        $pdo->prepare($sql)->execute($params);
        $message = "Bait updated!";
        $msgType = "success";

        // 2. Handle Image Upload for Bait
        if (isset($_FILES['bait_image']) && $_FILES['bait_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bait_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $filename = preg_replace('/[^a-z0-9]+/', '_', strtolower($name)) . '_' . time() . '.' . $ext;
                // Ensure directory exists
                if (!is_dir('images/baits')) mkdir('images/baits', 0777, true);
                
                if (move_uploaded_file($_FILES['bait_image']['tmp_name'], "images/baits/$filename")) {
                    $pdo->prepare("UPDATE baits SET image = ? WHERE id = ?")->execute([$filename, $id]);
                    $message .= " Image uploaded!";
                }
            }
        }
    }
}

// Fetch Data
$rods = $pdo->query("SELECT * FROM rods ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
$baits = $pdo->query("SELECT * FROM baits ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Shop Manager - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom">🛒 Shop Manager</h2>
        <a href="admin.php" class="btn btn-outline-light">⬅️ Back to Admin</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="shopTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="rods-tab" data-bs-toggle="tab" data-bs-target="#rods" type="button" role="tab">🎣 Rods</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="baits-tab" data-bs-toggle="tab" data-bs-target="#baits" type="button" role="tab">🪱 Baits</button>
        </li>
    </ul>

    <div class="tab-content" id="shopTabsContent">
        <!-- Rods Tab -->
        <div class="tab-pane fade show active" id="rods" role="tabpanel">
            <div class="row g-4">
                <?php foreach ($rods as $rod): ?>
                    <div class="col-md-4">
                        <div class="glass-card p-3">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="rod_id" value="<?= $rod['id'] ?>">
                                
                                <div class="text-center mb-3">
                                    <div class="mx-auto bg-dark rounded d-flex align-items-center justify-content-center mb-2" style="width: 100px; height: 100px;">
                                        <?php if (!empty($rod['image'])): ?>
                                            <img src="images/rods/<?= $rod['image'] ?>" style="max-width: 100%; max-height: 100%;">
                                        <?php else: ?>
                                            <span class="fs-1">🎣</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="rod_image" class="form-control form-control-sm">
                                </div>

                                <div class="mb-2">
                                    <label class="small text-muted">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($rod['name']) ?>" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted">Price 🪙</label>
                                        <input type="number" name="price" class="form-control" value="<?= $rod['price'] ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted">Luck (x)</label>
                                        <input type="number" step="0.1" name="luck_multiplier" class="form-control" value="<?= $rod['luck_multiplier'] ?>" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_rod" class="btn btn-primary w-100">💾 Save Changes</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Baits Tab -->
        <div class="tab-pane fade" id="baits" role="tabpanel">
            <div class="row g-4">
                <?php foreach ($baits as $bait): ?>
                    <div class="col-md-4">
                        <div class="glass-card p-3">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="bait_id" value="<?= $bait['id'] ?>">
                                
                                <div class="text-center mb-3">
                                    <div class="mx-auto bg-dark rounded d-flex align-items-center justify-content-center mb-2" style="width: 100px; height: 100px;">
                                        <?php if (!empty($bait['image'])): ?>
                                            <img src="images/baits/<?= $bait['image'] ?>" style="max-width: 100%; max-height: 100%;">
                                        <?php else: ?>
                                            <span class="fs-1">🪱</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="bait_image" class="form-control form-control-sm">
                                </div>

                                <div class="mb-2">
                                    <label class="small text-muted">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($bait['name']) ?>" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="small text-muted">Price 🪙</label>
                                        <input type="number" name="price" class="form-control" value="<?= $bait['price'] ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted">Boost (x)</label>
                                        <input type="number" step="0.1" name="rarity_boost" class="form-control" value="<?= $bait['rarity_boost'] ?>" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_bait" class="btn btn-primary w-100">💾 Save Changes</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
