<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Handle add to aquarium
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fish_type_id'])) {
    $fishTypeId = $_POST['add_fish_type_id'];
    
    // Check if user has caught this fish
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fish_catches WHERE user_id = ? AND fish_type_id = ?");
    $stmt->execute([$userId, $fishTypeId]);
    
    if ($stmt->fetchColumn() > 0) {
        $pdo->prepare("INSERT IGNORE INTO aquarium_fish (user_id, fish_type_id) VALUES (?, ?)")
            ->execute([$userId, $fishTypeId]);
        $message = "Fish added to aquarium!";
        $msgType = "success";
    }
}

// Handle remove from aquarium
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_fish_type_id'])) {
    $fishTypeId = $_POST['remove_fish_type_id'];
    $pdo->prepare("DELETE FROM aquarium_fish WHERE user_id = ? AND fish_type_id = ?")
        ->execute([$userId, $fishTypeId]);
    $message = "Fish removed from aquarium!";
    $msgType = "info";
}

// Get aquarium fish
$stmt = $pdo->prepare("
    SELECT ft.*, af.added_at
    FROM aquarium_fish af
    JOIN fish_types ft ON af.fish_type_id = ft.id
    WHERE af.user_id = ?
    ORDER BY ft.rarity DESC, ft.name ASC
");
$stmt->execute([$userId]);
$aquariumFish = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available fish (caught but not in aquarium)
$stmt = $pdo->prepare("
    SELECT DISTINCT ft.*
    FROM fish_catches fc
    JOIN fish_types ft ON fc.fish_type_id = ft.id
    WHERE fc.user_id = ?
    AND ft.id NOT IN (SELECT fish_type_id FROM aquarium_fish WHERE user_id = ?)
    ORDER BY ft.rarity DESC, ft.name ASC
");
$stmt->execute([$userId, $userId]);
$availableFish = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Aquarium - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <?php if (isset($message)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom fw-bold">🐠 My Aquarium</h2>
        <span class="text-muted"><?= count($aquariumFish) ?> fish on display</span>
    </div>

    <?php if (count($aquariumFish) === 0): ?>
        <div class="glass-card p-5 text-center mb-4">
            <h4 class="text-muted">Your aquarium is empty!</h4>
            <p>Add your favorite catches to showcase them here.</p>
        </div>
    <?php else: ?>
        <div class="glass-card p-4 mb-4">
            <h5 class="mb-3">On Display</h5>
            <div class="row g-3">
                <?php foreach ($aquariumFish as $fish): ?>
                    <?php
                    $rarityClass = match ($fish['rarity']) {
                        'Rare' => 'rarity-rare',
                        'Epic' => 'rarity-epic',
                        'Legendary' => 'rarity-legendary',
                        default => 'rarity-common'
                    };
                    ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="glass-card p-3 text-center">
                            <div class="display-4 mb-2">🐟</div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($fish['name']) ?></h6>
                            <span class="badge bg-dark <?= $rarityClass ?> mb-2"><?= $fish['rarity'] ?></span>
                            <form method="post">
                                <input type="hidden" name="remove_fish_type_id" value="<?= $fish['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger w-100">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($availableFish) > 0): ?>
        <div class="glass-card p-4">
            <h5 class="mb-3">Available to Add</h5>
            <div class="row g-3">
                <?php foreach ($availableFish as $fish): ?>
                    <?php
                    $rarityClass = match ($fish['rarity']) {
                        'Rare' => 'rarity-rare',
                        'Epic' => 'rarity-epic',
                        'Legendary' => 'rarity-legendary',
                        default => 'rarity-common'
                    };
                    ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="glass-card p-3 text-center">
                            <div class="display-4 mb-2">🐟</div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($fish['name']) ?></h6>
                            <span class="badge bg-dark <?= $rarityClass ?> mb-2"><?= $fish['rarity'] ?></span>
                            <form method="post">
                                <input type="hidden" name="add_fish_type_id" value="<?= $fish['id'] ?>">
                                <button class="btn btn-sm btn-primary w-100">Add to Aquarium</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
