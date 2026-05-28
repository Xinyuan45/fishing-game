<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['pending_fish'])) {
    exit(json_encode(['error' => 'No pending fish']));
}

$user = $_SESSION['user'];
$pendingFish = $_SESSION['pending_fish'];
$success = filter_var($_POST['success'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Clear active fishing state cookies immediately to prevent replay/reuse
setcookie('pending_fish', '', time() - 3600, '/');
setcookie('used_bait_id', '', time() - 3600, '/');

if (!$success) {
    // --- Minigame Failed ---
    // User lost the minigame. The fish escapes and the bait is consumed.
    
    // 1. Consume Bait
    if (isset($_SESSION['used_bait_id'])) {
        $baitId = $_SESSION['used_bait_id'];
        // Ideally, infinite bait (ID 1) shouldn't be decreased, but if quantity logic applies:
        $pdo->prepare("UPDATE user_baits SET quantity = quantity - 1 WHERE user_id = ? AND bait_id = ? AND quantity > 0")
            ->execute([$user['id'], $baitId]);
            
        // Get remaining bait count for UI update
        $stmt = $pdo->prepare("SELECT quantity FROM user_baits WHERE user_id = ? AND bait_id = ?");
        $stmt->execute([$user['id'], $baitId]);
        $remaining = $stmt->fetchColumn() ?: 0;
        
        unset($_SESSION['used_bait_id']);
    }
    
    unset($_SESSION['pending_fish']);
    exit(json_encode([
        'success' => false,
        'message' => '💔 The fish got away! (Bait lost)',
        'bait_remaining' => $remaining ?? 0,
        'bait_id' => $baitId ?? 1
    ]));
}

// Success - add fish to database
$fishName = $pendingFish['name'];
$rarity = $pendingFish['rarity'];
$weight = $pendingFish['weight'] ?? 1.0;

// Calculate price based on weight and rarity
// Base price per kg varies by rarity
$basePricePerKg = match($rarity) {
    'Rare' => 20,
    'Epic' => 50,
    'Legendary' => 150,
    default => 10
};

// Calculate final value: base price per kg * weight
$calculatedValue = round($basePricePerKg * $weight);

// Check if fish type exists
$stmt = $pdo->prepare("SELECT id FROM fish_types WHERE name = ?");
$stmt->execute([$fishName]);
$fishTypeId = $stmt->fetchColumn();

if (!$fishTypeId) {
    // Insert new fish type with calculated value
    $stmt = $pdo->prepare("INSERT INTO fish_types (name, rarity, value) VALUES (?, ?, ?)");
    $stmt->execute([$fishName, $rarity, $calculatedValue]);
    $fishTypeId = $pdo->lastInsertId();
} else {
    // Update value for existing fish type (in case weight affects it)
    $stmt = $pdo->prepare("UPDATE fish_types SET value = ? WHERE id = ?");
    $stmt->execute([$calculatedValue, $fishTypeId]);
}

// Determine if this fish type is new for the user
$isNewFish = false;
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_discoveries WHERE user_id = ? AND fish_type_id = ?");
$checkStmt->execute([$user['id'], $fishTypeId]);
if ($checkStmt->fetchColumn() == 0) {
    $isNewFish = true;
}

// Record the catch with weight
$stmt = $pdo->prepare("INSERT INTO fish_catches (user_id, fish_type_id, weight) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $fishTypeId, $weight]);

// Record discovery (ignore if already exists)
$pdo->prepare("INSERT IGNORE INTO user_discoveries (user_id, fish_type_id) VALUES (?, ?)")
    ->execute([$user['id'], $fishTypeId]);

// Consume bait (all baits including worm)
if (isset($_SESSION['used_bait_id'])) {
    $baitId = $_SESSION['used_bait_id'];
    $pdo->prepare("UPDATE user_baits SET quantity = quantity - 1 WHERE user_id = ? AND bait_id = ? AND quantity > 0")
        ->execute([$user['id'], $baitId]);
    
    // Fetch remaining
    $stmt = $pdo->prepare("SELECT quantity FROM user_baits WHERE user_id = ? AND bait_id = ?");
    $stmt->execute([$user['id'], $baitId]);
    $baitRemaining = $stmt->fetchColumn() ?: 0;
    
    unset($_SESSION['used_bait_id']);
}

// Award XP based on rarity
$xpGain = match($rarity) {
    'Rare' => 25,
    'Epic' => 75,
    'Legendary' => 200,
    default => 10
};

$currentXP = $user['xp'] ?? 0;
$currentLevel = $user['level'] ?? 1;
$newXP = $currentXP + $xpGain;

// Check for level up
$xpNeeded = $currentLevel * 100;
$leveledUp = false;
$newLevel = $currentLevel;

while ($newXP >= $xpNeeded && $newLevel < 50) {
    $newXP -= $xpNeeded;
    $newLevel++;
    $xpNeeded = $newLevel * 100;
    $leveledUp = true;
}

// Update user XP and level
$pdo->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?")->execute([$newXP, $newLevel, $user['id']]);
$_SESSION['user']['xp'] = $newXP;
$_SESSION['user']['level'] = $newLevel;

// --- Update User Stats ---
// 1. Total Catches
$pdo->prepare("INSERT INTO user_stats (user_id, total_catches) VALUES (?, 1) 
    ON DUPLICATE KEY UPDATE total_catches = total_catches + 1")->execute([$user['id']]);

// 2. Rarity Specific Catches (rare_catches, epic_catches, etc.)
// Security: Whitelist column names to prevent SQL injection
$rarityColumn = strtolower($rarity) . '_catches';
if (in_array($rarityColumn, ['rare_catches', 'epic_catches', 'legendary_catches'])) {
    $pdo->prepare("UPDATE user_stats SET $rarityColumn = $rarityColumn + 1 WHERE user_id = ?")->execute([$user['id']]);
}



// Clear pending fish
unset($_SESSION['pending_fish']);

// Get rarity class for display
$rarityClass = match($rarity) {
    'Rare' => 'text-primary',
    'Epic' => 'text-purple',
    'Legendary' => 'text-warning',
    default => 'text-secondary'
};

$message = "🎉 You caught a <span class='$rarityClass fw-bold'>$rarity $fishName</span>!<br>";
if (!empty($pendingFish['image'])) {
    $message .= "<img src='images/fish/" . htmlspecialchars($pendingFish['image']) . "' alt='Fish' style='width: 100px; height: auto; margin: 10px 0;'><br>";
}
if ($isNewFish) {
    $message .= "<small class='text-success'>🆕 New fish discovered!</small><br>";
}
$message .= "<small class='text-success'>⚖️ Weight: {$weight}kg | 💰 Value: {$calculatedValue} coins</small><br>";
$message .= "<small class='text-info'>+$xpGain XP</small>";

if ($leveledUp) {
    $message .= "<br><span class='text-warning fw-bold'>🎊 Level Up! You are now level $newLevel!</span>";
}



echo json_encode([
    'success' => true,
    'message' => $message,
    'newLevel' => $newLevel,
    'bait_remaining' => $baitRemaining ?? 0,
    'bait_id' => $baitId ?? 1
]);
?>
