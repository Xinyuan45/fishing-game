<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// --- Fetch Map & Fish Data ---
// 1. Get all maps to structure the encyclopedia sections
$maps = $pdo->query("SELECT * FROM maps ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Mock/Fetch Fish Data for UI
// In a real scenario, this might come from a Java API, but here we process the response 
// or fall back to an empty array.
$api_url = $api_base_url . "/api/fish/all"; // Example Internal API URL
$response = @file_get_contents($api_url);       // @ suppresses warnings if API is down
$allFishData = $response ? json_decode($response, true) : [];

// --- 3. Organized Fish by Map and Rarity ---
$fishByMap = [];
$rarityOrder = ['Common', 'Rare', 'Epic', 'Legendary'];
$rarityWeights = array_flip($rarityOrder); // ['Common' => 0, 'Rare' => 1, ...]

// Prepare Map buckets
foreach ($maps as $map) {
    $fishByMap[$map['name']] = [];
}

// 3a. Process API Fish
foreach ($allFishData as $mapName => $rarityGroups) {
    if (isset($fishByMap[$mapName])) {
        foreach ($rarityGroups as $rarity => $fishList) {
            foreach ($fishList as $fishName) {
                $fishByMap[$mapName][] = [
                    'name' => $fishName,
                    'rarity' => $rarity,
                    'is_custom' => false
                ];
            }
        }
    }
}

// 3b. Fetch & Merge Local Custom Fish
$stmt = $pdo->query("SELECT ft.*, m.name as map_name 
                     FROM fish_types ft 
                     JOIN maps m ON ft.map_id = m.id 
                     WHERE ft.is_custom = 1");
$customFishList = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($customFishList as $customFish) {
    $mapName = $customFish['map_name'];
    
    // Ensure map key exists (in case map name changed or mismatch)
    if (!isset($fishByMap[$mapName])) {
        $fishByMap[$mapName] = [];
    }
    
    $fishByMap[$mapName][] = [
        'name' => $customFish['name'],
        'rarity' => $customFish['rarity'],
        'is_custom' => true
    ];
}

// 3c. Sort Each Map by Rarity
foreach ($fishByMap as $mapName => &$fishList) {
    usort($fishList, function($a, $b) use ($rarityWeights) {
        $weightA = $rarityWeights[$a['rarity']] ?? 999;
        $weightB = $rarityWeights[$b['rarity']] ?? 999;
        
        if ($weightA === $weightB) {
            return strcmp($a['name'], $b['name']); // Secondary sort by name
        }
        return $weightA <=> $weightB;
    });
}
unset($fishList); // Break reference

// --- Get User Progress ---
// Fetch the names of all fish types this user has previously caught (for the "Caught" checks)
$stmt = $pdo->prepare("
    SELECT DISTINCT ft.name 
    FROM user_discoveries ud
    JOIN fish_types ft ON ud.fish_type_id = ft.id
    WHERE ud.user_id = ?
");
$stmt->execute([$user['id']]);
$caughtFish = $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns simple array of strings: ['Tuna', 'Clownfish', ...]

// --- Fetch Fish Images ---
// Key-Value pair for fast lookup: ['Tuna' => 'tuna.png']
$images = $pdo->query("SELECT name, image FROM fish_types")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fish Encyclopedia - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .fish-card {
            transition: transform 0.2s;
        }
        .fish-card:hover {
            transform: translateY(-5px);
        }
        .fish-card.caught {
            border: 2px solid #10b981;
        }
        .fish-card.uncaught {
            opacity: 0.6;
            filter: grayscale(50%);
        }
        .map-section {
            margin-bottom: 3rem;
        }
        .rarity-common { background-color: #6c757d; }
        .rarity-rare { background-color: #0d6efd; }
        .rarity-epic { background-color: #6f42c1; }
        .rarity-legendary { background-color: #ffc107; color: #000; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom fw-bold">📖 Fish Encyclopedia</h2>
        <div>
            <span class="badge bg-success me-2">Caught: <?= count($caughtFish) ?></span>
            <span class="badge bg-secondary">Total: <?= array_sum(array_map('count', $fishByMap)) ?></span>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <p class="mb-0">
            <strong>Legend:</strong> 
            <span class="badge bg-success ms-2">Green Border</span> = Caught
            <span class="badge bg-secondary ms-2">Faded</span> = Not Caught Yet
        </p>
    </div>

    <?php foreach ($maps as $map): ?>
        <?php if (!empty($fishByMap[$map['name']])): ?>
            <div class="map-section">
                <div class="d-flex align-items-center mb-3 p-2 rounded glass-card-hover" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapse-<?= $map['id'] ?>" 
                     aria-expanded="true" 
                     style="cursor: pointer;">
                    <h3 class="text-primary-custom mb-0">📍 <?= htmlspecialchars($map['name']) ?></h3>
                    <span class="badge bg-info ms-3"><?= count($fishByMap[$map['name']]) ?> species</span>
                    <span class="ms-auto text-muted">▼</span>
                </div>

                <div class="collapse show" id="collapse-<?= $map['id'] ?>">
                    <div class="row g-3">
                        <?php foreach ($fishByMap[$map['name']] as $fish): ?>
                            <?php 
                            $isCaught = in_array($fish['name'], $caughtFish);
                            $rarity = $fish['rarity'];
                            $rarityClass = match ($rarity) {
                                'Rare' => 'rarity-rare',
                                'Epic' => 'rarity-epic',
                                'Legendary' => 'rarity-legendary',
                                default => 'rarity-common'
                            };
                            ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="glass-card p-3 fish-card <?= $isCaught ? 'caught' : 'uncaught' ?>">
                                    <div class="text-center mb-2">
                                        <?php if (isset($images[$fish['name']]) && !empty($images[$fish['name']])): ?>
                                            <img src="images/fish/<?= htmlspecialchars($images[$fish['name']]) ?>" alt="<?= htmlspecialchars($fish['name']) ?>" class="img-fluid" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="display-4">🐟</span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold text-center mb-2"><?= htmlspecialchars($fish['name']) ?></h6>
                                    <div class="text-center">
                                        <span class="badge <?= $rarityClass ?>"><?= $rarity ?></span>
                                    </div>
                                    <?php if ($isCaught): ?>
                                        <div class="text-center mt-2">
                                            <small class="text-success">✓ Caught</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">Not caught</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
