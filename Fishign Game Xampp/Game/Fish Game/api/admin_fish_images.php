<?php
session_start();
require 'db.php';

// --- Admin Authorization Check ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] != 1) {
    // Double Check DB
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

// --- Handle Image Upload ---
// Allows admins to upload custom images for specific fish types
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Create New Fish
    if (isset($_POST['create_fish'])) {
        $name = trim($_POST['name']);
        $rarity = $_POST['rarity'];
        $value = intval($_POST['value']);
        $mapId = intval($_POST['map_id']);
        
        // Handle Image
        $imageName = 'default_fish.png';
        if (isset($_FILES['fish_image']) && $_FILES['fish_image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['fish_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $newFilename = "custom_" . preg_replace('/[^a-z0-9]+/', '_', strtolower($name)) . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['fish_image']['tmp_name'], "images/fish/" . $newFilename)) {
                    $imageName = $newFilename;
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO fish_types (name, rarity, value, image, is_custom, map_id) VALUES (?, ?, ?, ?, 1, ?)");
        if ($stmt->execute([$name, $rarity, $value, $imageName, $mapId])) {
            $message = "✅ Custom fish '$name' created!";
        } else {
            $error = "❌ Failed to create fish.";
        }
    }

    // 2. Upload Image(s) for Existing Fish (Bulk Support)
    if (isset($_FILES['fish_images'])) {
        $uploadedCount = 0;
        $errorCount = 0;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Loop through the uploaded array
        // Structure: $_FILES['fish_images']['name'][ID]
        foreach ($_FILES['fish_images']['name'] as $fishId => $filename) {
            // Check if a file was actually uploaded for this ID
            if ($_FILES['fish_images']['error'][$fishId] === 0) {
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Security Check: Verify extension
                if (in_array($ext, $allowed)) {
                    // Generate unique name
                    $newFilename = "fish_" . $fishId . "_" . time() . "." . $ext;
                    $destination = "images/fish/" . $newFilename;
                    
                    if (move_uploaded_file($_FILES['fish_images']['tmp_name'][$fishId], $destination)) {
                        // Update DB record
                        $stmt = $pdo->prepare("UPDATE fish_types SET image = ? WHERE id = ?");
                        $stmt->execute([$newFilename, $fishId]);
                        $uploadedCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++; // Invalid type
                }
            }
        }
        
        if ($uploadedCount > 0) {
            $message = "✅ Successfully updated $uploadedCount fish images!";
            if ($errorCount > 0) $message .= " (Failed to upload $errorCount files).";
        } elseif ($errorCount > 0) {
            $error = "❌ Failed to upload $errorCount files.";
        }
    }

    // 3. Delete Custom Fish
    if (isset($_POST['delete_fish'])) {
        $fishId = $_POST['fish_id'];
        
        // Safety Check: Verify it is actually a custom fish before deleting
        $stmt = $pdo->prepare("SELECT name, image, is_custom FROM fish_types WHERE id = ?");
        $stmt->execute([$fishId]);
        $targetFish = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($targetFish && $targetFish['is_custom']) {
            // Optional: Delete the image file if it's not the default
            // (Skipping strict image deletion for now to avoid accidental data loss issues, but the db record goes)
            
            // FIX: Delete related catches AND discoveries first to avoid Foreign Key Constraint Errors
            $pdo->prepare("DELETE FROM fish_catches WHERE fish_type_id = ?")->execute([$fishId]);
            $pdo->prepare("DELETE FROM user_discoveries WHERE fish_type_id = ?")->execute([$fishId]);
            
            $stmt = $pdo->prepare("DELETE FROM fish_types WHERE id = ?");
            if ($stmt->execute([$fishId])) {
                $message = "🗑️ Custom fish '{$targetFish['name']}' has been deleted (along with its catch history).";
            } else {
                $error = "❌ Failed to delete fish.";
            }
        } else {
            $error = "❌ Cannot delete this fish (it might be a core game fish).";
        }
    }
}

// --- Sync with External API ---
// This block ensures the local `fish_types` table is up-to-date with the Game Engine API.
// It fetches all fish, checks if they exist locally, and inserts missing ones with default values.
$api_url = $api_base_url . "/api/fish/all";
$response = @file_get_contents($api_url);
$allFishData = $response ? json_decode($response, true) : [];

if (!empty($allFishData)) {
    $insertedCount = 0;
    foreach ($allFishData as $mapName => $rarities) {
        foreach ($rarities as $rarity => $fishes) {
            foreach ($fishes as $fishName) {
                // Check if fish already exists in local DB
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM fish_types WHERE name = ?");
                $stmt->execute([$fishName]);
                if ($stmt->fetchColumn() == 0) {
                    // Determine base value based on Rarity
                    $baseValue = match($rarity) {
                        'Rare' => 50,
                        'Epic' => 200,
                        'Legendary' => 1000,
                        default => 10
                    };
                    
                    // Insert new fish with default placeholder image
                    $stmt = $pdo->prepare("INSERT INTO fish_types (name, rarity, value, image) VALUES (?, ?, ?, 'default_fish.png')");
                    $stmt->execute([$fishName, $rarity, $baseValue]);
                    $insertedCount++;
                }
            }
        }
    }
    if ($insertedCount > 0) {
        $message = "✅ Synced $insertedCount new fish from API to database.";
    }
}

// Organize fish by Map
$fishByMap = [];
$assignedFishIds = [];

// Fetch all fish types
$fishTypes = $pdo->query("SELECT * FROM fish_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Initialize maps from API data
if (!empty($allFishData)) {
    foreach ($allFishData as $mapName => $data) {
        $fishByMap[$mapName] = [];
    }
}

// Sort fish into maps
foreach ($fishTypes as $fish) {
    $found = false;
    foreach ($allFishData as $mapName => $rarities) {
        foreach ($rarities as $rarity => $fishes) {
            if (in_array($fish['name'], $fishes)) {
                $fishByMap[$mapName][] = $fish;
                $assignedFishIds[] = $fish['id'];
                $found = true;
                break 2;
            }
        }
    }
    if (!$found) {
        $fishByMap['Unassigned / Others'][] = $fish;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fish Admin - Manage Images</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary-custom mb-0">⚙️ Fish Image Manager</h2>
        <a href="admin.php" class="btn btn-outline-light">⬅️ Back to Admin</a>
    </div>
    
    <div class="alert alert-info">
        ℹ️ This page automatically fetches the full fish list from the game engine so you can add images before catching them.
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Create New Fish Form -->
    <div class="glass-card p-4 mb-4">
        <h4 class="mb-3">➕ Create Custom Fish</h4>
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fish Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Neon Shark">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rarity</label>
                    <select name="rarity" class="form-select">
                        <option value="Common">Common</option>
                        <option value="Rare">Rare</option>
                        <option value="Epic">Epic</option>
                        <option value="Legendary">Legendary</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Base Value (Coins)</label>
                    <input type="number" name="value" class="form-control" value="10" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Map</label>
                    <select name="map_id" class="form-select">
                        <option value="1">Sunny Coast</option>
                        <option value="2">Coral Reef</option>
                        <option value="3">Deep Trench</option>
                        <option value="4">Abyssal Void</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Image (Optional)</label>
                    <input type="file" name="fish_image" class="form-control">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="create_fish" class="btn btn-success">✨ Create Fish</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Upload Form -->
    <form method="post" enctype="multipart/form-data">
        
        <!-- Sticky Save Button -->
        <div class="sticky-top bg-dark p-3 mb-4 rounded shadow d-flex justify-content-between align-items-center" style="top: 20px; z-index: 1000; border: 1px solid #444;">
            <h4 class="m-0 text-white">📸 Batch Image Uploader</h4>
            <button type="submit" class="btn btn-primary btn-lg px-5">💾 Upload All Changes</button>
        </div>

        <?php foreach ($fishByMap as $mapName => $fishes): ?>
            <?php if (empty($fishes)) continue; ?>
            <div class="mb-5">
                <h3 class="text-white border-bottom border-secondary pb-2 mb-3">
                    📍 <?= htmlspecialchars($mapName) ?>
                </h3>
                
                <div class="row g-4">
                    <?php foreach ($fishes as $fish): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="glass-card p-3 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;">
                                        <?php if (!empty($fish['image'])): ?>
                                            <img src="images/fish/<?= htmlspecialchars($fish['image']) ?>" style="width: 100%; height: auto;">
                                        <?php else: ?>
                                            <span style="font-size: 2rem;">🐟</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($fish['name']) ?></h5>
                                        <span class="badge bg-secondary"><?= $fish['rarity'] ?></span>
                                        <div class="text-muted small mt-1">Value: <?= $fish['value'] ?> coins</div>
                                        <?php if (isset($fish['is_custom']) && $fish['is_custom']): ?>
                                             <span class="badge bg-info">CUSTOM</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="input-group input-group-sm mb-2">
                                    <!-- Bulk Array Input -->
                                    <input type="file" class="form-control" name="fish_images[<?= $fish['id'] ?>]">
                                </div>
                                <div class="form-text small">Select new image to update.</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>

    <?php if (empty($fishTypes)): ?>
        <div class="text-center text-muted">
            <p>No fish types found in the database yet. Go fishing to discover some!</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
