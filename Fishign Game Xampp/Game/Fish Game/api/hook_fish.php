<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    exit(json_encode(['error' => 'Not logged in']));
}

$user = $_SESSION['user'];
$mapId = $_GET['map_id'] ?? 1;

// Get map name
$mapStmt = $pdo->prepare("SELECT name FROM maps WHERE id = ?");
$mapStmt->execute([$mapId]);
$mapName = $mapStmt->fetchColumn();

if (!$mapName) {
    exit(json_encode(['error' => 'Invalid map']));
}

// Check if user has unlocked this map
$checkAccess = $pdo->prepare("SELECT COUNT(*) FROM user_maps WHERE user_id = ? AND map_id = ?");
$checkAccess->execute([$user['id'], $mapId]);

if ($checkAccess->fetchColumn() == 0) {
    exit(json_encode(['error' => 'Map not unlocked']));
}

// Get equipped rod luck
$stmt = $pdo->prepare("
    SELECT r.luck_multiplier 
    FROM user_rods ur 
    JOIN rods r ON ur.rod_id = r.id 
    WHERE ur.user_id = ? AND ur.is_equipped = 1
");
$stmt->execute([$user['id']]);
$rodLuck = $stmt->fetchColumn() ?: 1.0;

// Get bait and apply luck bonus
$baitId = $_GET['bait_id'] ?? 1;
$baitStmt = $pdo->prepare("SELECT b.rarity_boost, ub.quantity 
    FROM baits b 
    LEFT JOIN user_baits ub ON b.id = ub.bait_id AND ub.user_id = ? 
    WHERE b.id = ?");
$baitStmt->execute([$user['id'], $baitId]);
$baitData = $baitStmt->fetch();

// Check if bait exists and has quantity
if (!$baitData) {
    exit(json_encode(['error' => 'Invalid bait selected']));
}

$baitQuantity = $baitData['quantity'] ?? 0;

// Prevent fishing if bait quantity is 0
if ($baitQuantity <= 0) {
    exit(json_encode(['error' => 'Out of bait! Please buy more bait from the shop.']));
}

// --- Prepare Fishing Session ---

// 1. Don't consume bait yet
// We only verify it exists. Consumption happens on "Catch" (catch_fish.php).
// Store ID to track which bait to subtract later.
$_SESSION['used_bait_id'] = $baitId;

// 2. Calculate Catch Luck
// Total Luck = Rod Multiplier * Bait Multiplier
// This value is sent to the Game Engine (Java API) to influence fish rarity.
$totalLuck = $rodLuck * floatval($baitData['rarity_boost']);

// --- Hybrid Fishing System ---
// 1. Check for Local Custom Fish on this Map
$customFishStmt = $pdo->prepare("SELECT * FROM fish_types WHERE is_custom = 1 AND map_id = ?");
$customFishStmt->execute([$mapId]);
$customFishes = $customFishStmt->fetchAll(PDO::FETCH_ASSOC);

$useCustomFish = false;
$selectedCustomFish = null;

// 2. Decide Source: Custom vs API
// If custom fish exist for this map, we have a 50% chance to pick one.
// You can adjust this probability as needed.
if (!empty($customFishes)) {
    // 50% chance effectively serves as a balance between "Local" and "Global" ecosystems
    if (rand(1, 100) <= 50) { 
        $useCustomFish = true;
        // Pick a random custom fish based on rod luck? 
        // For simplicity, we just pick a random one for now, or weighted random based on rarity.
        $selectedCustomFish = $customFishes[array_rand($customFishes)];
    }
}

$data = [];

if ($useCustomFish && $selectedCustomFish) {
    // --- Use Local Custom Fish ---
    $data = [
        'fish' => $selectedCustomFish['name'],
        'rarity' => $selectedCustomFish['rarity'], // Capitalized in DB?
        'difficulty' => match($selectedCustomFish['rarity']) {
            'Rare' => 'Medium',
            'Epic' => 'Hard',
            'Legendary' => 'Insane',
            default => 'Easy'
        },
        'image' => $selectedCustomFish['image']
    ];
} else {
    // Call Java API with luck and location (dynamically resolved URL)
    $api_url = $api_base_url . "/api/fish?luck=" . $totalLuck . "&location=" . urlencode($mapName);
    $response = @file_get_contents($api_url);

    if ($response === FALSE) {
        // Fallback if API fails: Create a generic fish
        // This prevents the game from hard crashing if the Java server is down
        $data = [
            'fish' => 'Glitch Fish',
            'rarity' => 'Common',
            'difficulty' => 1
        ];
    } else {
        $data = json_decode($response, true);
    }
}

// --- Fish Weight Calculation ---
// Weight is determined by: Base Rarity Weight * Rod Multiplier + Bait Bonus * Random Factor

// 1. Base Weight (Heavier fish are rarer)
$baseWeight = match($data['rarity'] ?? 'Common') {
    'Rare' => 2.5,
    'Epic' => 5.0,
    'Legendary' => 10.0,
    default => 1.0
};

// 2. Rod Multiplier (Better rods catch heavier fish)
$rodWeightMultiplier = $rodLuck; // 1.0 to 2.5

// 3. Bait Bonus (Better baits add raw weight)
$baitWeightBonus = (floatval($baitData['rarity_boost']) - 1.0) * 2; // 0 to 2 kg

// 4. Random Variance (+/- 20%)
$randomFactor = (rand(80, 120) / 100); 

$fishWeight = ($baseWeight * $rodWeightMultiplier + $baitWeightBonus) * $randomFactor;
$fishWeight = round($fishWeight, 2);

// Check if image is already provided (Custom Fish), otherwise fetch from DB (API Fish)
if (!empty($data['image'])) {
    $fishImage = $data['image'];
} else {
    $imgStmt = $pdo->prepare("SELECT image FROM fish_types WHERE name = ?");
    $imgStmt->execute([$data['fish']]);
    $fishImage = $imgStmt->fetchColumn();

    if (!$fishImage) {
        $fishImage = 'default_fish.png';
    }
}

// Store pending fish in session
$_SESSION['pending_fish'] = [
    'name' => $data['fish'] ?? 'Unknown Fish',
    'rarity' => $data['rarity'] ?? 'Common',
    'difficulty' => $data['difficulty'] ?? 1,
    'map_id' => $mapId,
    'weight' => $fishWeight,
    'rod_luck' => $rodLuck,
    'bait_bonus' => floatval($baitData['rarity_boost']),
    'image' => $fishImage
];

// Return fish data and minigame parameters
echo json_encode([
    'fish' => $data['fish'],
    'rarity' => $data['rarity'],
    'rarity' => $data['rarity'],
    'difficulty' => (function($d) {
        if (is_numeric($d)) return (int)$d;
        return match(strtolower($d)) {
            'easy' => 1,
            'medium' => 2,
            'hard' => 3,
            'extreme' => 4,
            'insane', 'legendary' => 5,
            default => 1
        };
    })($data['difficulty'] ?? 1),
    'image' => $fishImage
]);
?>
