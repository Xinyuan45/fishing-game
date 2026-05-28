<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// --- Handle Map Unlock Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_map_id'])) {
    $mapId = $_POST['unlock_map_id'];
    
    // 1. Fetch Map Details to get Cost
    $mapStmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
    $mapStmt->execute([$mapId]);
    $map = $mapStmt->fetch();

    if ($map) {
        // 2. Check if already unlocked
        $checkUnlocked = $pdo->prepare("SELECT COUNT(*) FROM user_maps WHERE user_id = ? AND map_id = ?");
        $checkUnlocked->execute([$userId, $mapId]);
        
        if ($checkUnlocked->fetchColumn() > 0) {
            $message = "You already have access to this map!";
            $msgType = "warning";
        } elseif ($user['coins'] >= $map['unlock_cost']) {
            // 3. Process Transaction
            // Deduct Coins
            $newBalance = $user['coins'] - $map['unlock_cost'];
            $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newBalance, $userId]);
            $_SESSION['user']['coins'] = $newBalance;

            // Grant Access
            $pdo->prepare("INSERT INTO user_maps (user_id, map_id) VALUES (?, ?)")->execute([$userId, $mapId]);
            
            $message = "Successfully unlocked " . htmlspecialchars($map['name']) . "!";
            $msgType = "success";
        } else {
            $message = "Not enough coins!";
            $msgType = "danger";
        }
    }
}

// Fetch all maps
$maps = $pdo->query("SELECT * FROM maps ORDER BY unlock_cost ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's unlocked maps
$unlockedMaps = $pdo->prepare("SELECT map_id FROM user_maps WHERE user_id = ?");
$unlockedMaps->execute([$userId]);
$userMaps = $unlockedMaps->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Maps - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom fw-bold">🗺️ Fishing Locations</h2>
        <h4 class="text-warning">Balance: <?= number_format($_SESSION['user']['coins']) ?> 🪙</h4>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($maps as $map): ?>
            <?php $isUnlocked = in_array($map['id'], $userMaps); ?>
            <div class="col-md-6">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h4 class="fw-bold"><?= htmlspecialchars($map['name']) ?></h4>
                        <?php if ($isUnlocked): ?>
                            <span class="badge bg-success">Unlocked</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Locked</span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted mb-4"><?= htmlspecialchars($map['description']) ?></p>
                    
                    <div class="mt-auto">
                        <?php if ($isUnlocked): ?>
                            <a href="fish.php?map_id=<?= $map['id'] ?>" class="btn btn-primary w-100">
                                🎣 Fish Here
                            </a>
                        <?php elseif ($map['unlock_cost'] == 0): ?>
                            <button class="btn btn-success w-100" disabled>Free Access</button>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="unlock_map_id" value="<?= $map['id'] ?>">
                                <button class="btn btn-warning w-100" <?= $_SESSION['user']['coins'] < $map['unlock_cost'] ? 'disabled' : '' ?>>
                                    Unlock for <?= number_format($map['unlock_cost']) ?> 🪙
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
