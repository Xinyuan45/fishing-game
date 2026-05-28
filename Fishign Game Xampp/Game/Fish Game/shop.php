<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Handle Bait Purchase
// --- Handle Bait Purchase ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_bait_id'])) {
    $baitId = $_POST['buy_bait_id'];
    $quantity = (int)$_POST['quantity'];
    
    // 1. Fetch bait details (price, name) from database to ensure valid item
    $stmt = $pdo->prepare("SELECT * FROM baits WHERE id = ?");
    $stmt->execute([$baitId]);
    $bait = $stmt->fetch();

    if ($bait && $quantity > 0) {
        // Calculate total cost
        $totalCost = $bait['price'] * $quantity;
        
        // 2. Check if user has enough coins
        if ($user['coins'] >= $totalCost) {
            // 3. Deduct coins from user balance
            $newBalance = $user['coins'] - $totalCost;
            $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newBalance, $userId]);
            
            // Update session variable so the UI updates immediately
            $_SESSION['user']['coins'] = $newBalance;

            // 4. Add baits to user inventory
            // Use ON DUPLICATE KEY UPDATE to increment quantity if the user already has this bait
            $pdo->prepare("INSERT INTO user_baits (user_id, bait_id, quantity) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + ?")
                ->execute([$userId, $baitId, $quantity, $quantity]);
            
            $message = "Successfully purchased " . $quantity . "x " . htmlspecialchars($bait['name']) . "!";
            $msgType = "success";
        } else {
            $message = "Not enough coins!";
            $msgType = "danger";
        }
    }
}

// Handle Purchase
// --- Handle Rod Purchase ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_rod_id'])) {
    $rodId = $_POST['buy_rod_id'];
    
    // 1. Fetch rod details
    $rodStmt = $pdo->prepare("SELECT * FROM rods WHERE id = ?");
    $rodStmt->execute([$rodId]);
    $rod = $rodStmt->fetch();

    if ($rod) {
        // 2. Check if user already owns this rod
        $checkOwned = $pdo->prepare("SELECT COUNT(*) FROM user_rods WHERE user_id = ? AND rod_id = ?");
        $checkOwned->execute([$userId, $rodId]);
        
        if ($checkOwned->fetchColumn() > 0) {
            $message = "You already own this rod!";
            $msgType = "warning";
        } elseif ($user['coins'] >= $rod['price']) {
            // 3. Process Purchase
            // Deduct coins
            $newBalance = $user['coins'] - $rod['price'];
            $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newBalance, $userId]);
            $_SESSION['user']['coins'] = $newBalance; // Update session

            // Add rod to inventory (user_rods table)
            $pdo->prepare("INSERT INTO user_rods (user_id, rod_id) VALUES (?, ?)")->execute([$userId, $rodId]);
            
            $message = "Successfully purchased " . htmlspecialchars($rod['name']) . "!";
            $msgType = "success";
        } else {
            $message = "Not enough coins!";
            $msgType = "danger";
        }
    }
}

// Handle Equip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equip_rod_id'])) {
    $rodId = $_POST['equip_rod_id'];
    
    // Verify ownership
    $checkOwned = $pdo->prepare("SELECT id FROM user_rods WHERE user_id = ? AND rod_id = ?");
    $checkOwned->execute([$userId, $rodId]);
    
    if ($checkOwned->fetchColumn()) {
        // Unequip all
        $pdo->prepare("UPDATE user_rods SET is_equipped = 0 WHERE user_id = ?")->execute([$userId]);
        // Equip new
        $pdo->prepare("UPDATE user_rods SET is_equipped = 1 WHERE user_id = ? AND rod_id = ?")->execute([$userId, $rodId]);
        $message = "Rod equipped!";
        $msgType = "success";
    }
}

// Fetch all rods
$rods = $pdo->query("SELECT * FROM rods ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's owned rods
$ownedRods = $pdo->prepare("SELECT rod_id, is_equipped FROM user_rods WHERE user_id = ?");
$ownedRods->execute([$userId]);
$userRods = $ownedRods->fetchAll(PDO::FETCH_KEY_PAIR); // [rod_id => is_equipped]

// Fetch all baits
$baits = $pdo->query("SELECT * FROM baits ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's bait inventory
$userBaits = $pdo->prepare("SELECT bait_id, quantity FROM user_baits WHERE user_id = ?");
$userBaits->execute([$userId]);
$baitInventory = $userBaits->fetchAll(PDO::FETCH_KEY_PAIR); // [bait_id => quantity]

?>
<!DOCTYPE html>
<html>
<head>
    <title>Shop - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom fw-bold">🛒 Fishing Shop</h2>
        <h4 class="text-warning">Balance: <?= number_format($_SESSION['user']['coins']) ?> 🪙</h4>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($rods as $rod): ?>
            <?php 
                $isOwned = array_key_exists($rod['id'], $userRods);
                $isEquipped = $isOwned && $userRods[$rod['id']];
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="glass-card p-3 h-100 d-flex flex-column text-center">
                    <div class="mb-3">
                        <?php if (!empty($rod['image'])): ?>
                            <img src="images/rods/<?= htmlspecialchars($rod['image']) ?>" class="img-fluid" style="height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="display-1">🎣</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($rod['name']) ?></h5>
                    <p class="text-info small mb-2">
                        ✨ Luck: x<?= $rod['luck_multiplier'] ?><br>
                        ⚖️ Weight: +<?= ($rod['luck_multiplier'] - 1) * 10 ?>kg<br>
                        <small class="text-muted">Boosts rare fish chance & weight</small>
                    </p>
                    
                    <div class="mt-auto">
                        <?php if ($isEquipped): ?>
                            <button class="btn btn-success w-100" disabled>✓ Equipped</button>
                        <?php elseif ($isOwned): ?>
                            <form method="post">
                                <input type="hidden" name="equip_rod_id" value="<?= $rod['id'] ?>">
                                <button class="btn btn-outline-primary w-100">Equip</button>
                            </form>
                        <?php else: ?>
                            <h5 class="text-warning mb-2"><?= number_format($rod['price']) ?> 🪙</h5>
                            <form method="post">
                                <input type="hidden" name="buy_rod_id" value="<?= $rod['id'] ?>">
                                <button class="btn btn-primary w-100" <?= $_SESSION['user']['coins'] < $rod['price'] ? 'disabled' : '' ?>>
                                    Buy
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Baits Section -->
    <h3 class="text-primary-custom fw-bold mt-5 mb-4">🪱 Fishing Baits</h3>
    <div class="row g-4">
        <?php foreach ($baits as $bait): ?>
            <?php $owned = $baitInventory[$bait['id']] ?? 0; ?>
            <div class="col-md-6 col-lg-3">
                <div class="glass-card p-3 h-100 d-flex flex-column text-center">
                    <div class="mb-3">
                        <span class="display-1"><?php 
                            if (!empty($bait['image'])) {
                                echo '<img src="images/baits/' . htmlspecialchars($bait['image']) . '" class="img-fluid" style="height: 100px; object-fit: contain;">';
                            } else {
                                // Display emoji based on bait name
                                $icon = match(strtolower($bait['name'])) {
                                    'worm' => '🪱',
                                    'shrimp' => '🦐',
                                    'squid' => '🦑',
                                    'golden lure' => '✨',
                                    default => '🎣'
                                };
                                echo $icon;
                            }
                        ?></span>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($bait['name']) ?></h5>
                    <p class="text-info small mb-2">
                        ✨ Rarity Boost: x<?= $bait['rarity_boost'] ?><br>
                        <small class="text-muted">Increases rare fish chance</small>
                    </p>
                    <p class="text-success small">Owned: <?= $owned ?></p>
                    
                    <div class="mt-auto">
                        <?php if ($bait['price'] == 0): ?>
                            <p class="text-muted">FREE for new users!</p>
                        <?php else: ?>
                            <h5 class="text-warning mb-2"><?= number_format($bait['price']) ?> 🪙 each</h5>
                            <form method="post" class="bait-buy-form">
                                <input type="hidden" name="buy_bait_id" value="<?= $bait['id'] ?>">
                                <input type="hidden" name="quantity" class="quantity-input" value="10">
                                
                                <!-- Quick Buy Buttons -->
                                <div class="btn-group mb-2 w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary qty-btn" data-qty="10">10x</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary qty-btn" data-qty="25">25x</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary qty-btn active" data-qty="50">50x</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary qty-btn" data-qty="99">99x</button>
                                </div>
                                
                                <div class="total-cost mb-2">
                                    <small>Total: <span class="text-warning fw-bold cost-display"><?= $bait['price'] * 50 ?> 🪙</span></small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm w-100" <?= $_SESSION['user']['coins'] < $bait['price'] * 50 ? 'disabled' : '' ?>>
                                    🛒 Buy Now
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Quantity button selection
    $('.qty-btn').click(function() {
        const form = $(this).closest('.bait-buy-form');
        const qty = $(this).data('qty');
        const price = parseInt($(this).closest('.glass-card').find('.text-warning').first().text().replace(/[^0-9]/g, ''));
        const userCoins = <?= $_SESSION['user']['coins'] ?>;
        
        // Update active state
        form.find('.qty-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update hidden input
        form.find('.quantity-input').val(qty);
        
        // Update total cost display
        const totalCost = price * qty;
        form.find('.cost-display').text(totalCost.toLocaleString() + ' 🪙');
        
        // Enable/disable buy button based on coins
        const buyBtn = form.find('button[type="submit"]');
        if (userCoins >= totalCost) {
            buyBtn.prop('disabled', false);
        } else {
            buyBtn.prop('disabled', true);
        }
        
        // Add pulse animation
        form.find('.cost-display').addClass('animate-pulse');
        setTimeout(() => form.find('.cost-display').removeClass('animate-pulse'), 500);
    });
    
    // Initialize default selection (50x)
    $('.bait-buy-form').each(function() {
        $(this).find('.qty-btn[data-qty="50"]').trigger('click');
    });
});
</script>
</body>
</html>
