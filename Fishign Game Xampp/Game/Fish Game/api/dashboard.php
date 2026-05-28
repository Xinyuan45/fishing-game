<?php
// Start the session to access user data
session_start();

// Include database connection
require 'db.php';

// Authentication Check:
// If the user variable is not set in the session, they are not logged in.
// Redirect them to the login page immediately.
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Assign user data to a local variable for easier access
$user = $_SESSION['user'];

// --- Level & XP Calculation ---
// Get current level (default to 1 if not set)
$level = $user['level'] ?? 1;

// Get current XP (default to 0 if not set)
$xp = $user['xp'] ?? 0;

// Calculate XP needed for the next level
// Formula: Current Level * 100 (e.g., Lv 1 needs 100 XP, Lv 2 needs 200 XP)
$xpNeeded = $level * 100;

// Calculate the percentage of progress towards the next level
// Usage: This value drives the width of the progress bar in the UI
$xpPercentage = ($xp / $xpNeeded) * 100;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">


    <div class="row g-4">
        <!-- Level & Stats Card -->
        <div class="col-md-4">
            <div class="glass-card p-4 text-center h-100">
                <h5 class="text-muted mb-3">Level <?= $level ?></h5>
                <div class="position-relative mb-2">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $xpPercentage ?>%"></div>
                    </div>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <small class="fw-bold text-black"><?= $xp ?> / <?= $xpNeeded ?> XP</small>
                    </div>
                </div>
                
                <hr class="border-secondary border-opacity-25 my-4">
                
                <h5 class="text-muted mb-3">Your Wealth</h5>
                <h1 class="display-4 fw-bold text-warning mb-0"><?= number_format($_SESSION['user']['coins']) ?> 🪙</h1>
                <p class="text-muted mt-2">Coins</p>
                
                <hr class="border-secondary border-opacity-25 my-4">
                
                <h5 class="text-muted mb-3">Equipped Rod</h5>
                <?php
                    // Query to find the currently equipped rod for the user
                    // Joins 'user_rods' (inventory) with 'rods' (item details)
                    $rodStmt = $pdo->prepare("
                        SELECT r.name 
                        FROM user_rods ur 
                        JOIN rods r ON ur.rod_id = r.id 
                        WHERE ur.user_id = ? AND ur.is_equipped = 1
                    ");
                    $rodStmt->execute([$user['id']]);
                    
                    // Fetch the name, or default to "Bamboo Pole" if nothing is found
                    $rodName = $rodStmt->fetchColumn() ?: "Bamboo Pole";
                ?>
                <h3 class="fw-bold text-info"><?= htmlspecialchars($rodName) ?> 🎣</h3>
            </div>
        </div>

        <!-- Actions -->
        <div class="col-md-8">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-center align-items-center">
                <h3 class="mb-4">Ready to set sail?</h3>
                <div class="d-flex gap-3">
                    <a href="fish.php" class="btn btn-primary btn-lg px-5 py-3 animate-float">
                        🎣 Go Fishing
                    </a>
                    <a href="myfish.php" class="btn btn-outline-info btn-lg px-5 py-3">
                        🐟 My Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
