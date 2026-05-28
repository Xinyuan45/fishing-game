<?php
// Start Session to access user data
session_start();
require 'db.php';

// Authentication Check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
// Get the current Map ID (default to map 1: Sunny Coast)
$mapId = $_GET['map_id'] ?? 1;

// --- Fetch Map Details ---
// Retrieves map name and difficulty modifiers
$mapStmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
$mapStmt->execute([$mapId]);
$map = $mapStmt->fetch();

// Invalid map ID protection
if (!$map) {
    header("Location: maps.php");
    exit;
}

// --- Fetch Available Bat ---
// Join baits with user_baits to see what the user owns
// We include baits with qty > 0 OR bait_id=1 (Worms/Free bait)
$baitStmt = $pdo->prepare("SELECT b.*, ub.quantity FROM baits b 
    LEFT JOIN user_baits ub ON b.id = ub.bait_id AND ub.user_id = ? 
    WHERE ub.quantity > 0 OR b.id = 1 
    ORDER BY b.price ASC");
$baitStmt->execute([$user['id']]);
$availableBaits = $baitStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Equipped Rod ---
// Gets the single rod marked as is_equipped=1 for this user
$rodStmt = $pdo->prepare("
    SELECT r.* 
    FROM user_rods ur 
    JOIN rods r ON ur.rod_id = r.id 
    WHERE ur.user_id = ? AND ur.is_equipped = 1
");
$rodStmt->execute([$user['id']]);
$equippedRod = $rodStmt->fetch(PDO::FETCH_ASSOC);

// Fallback: Use 'Bamboo Pole' if no rod is found in database
if (!$equippedRod) {
    $equippedRod = [
        'name' => 'Bamboo Pole',
        'image' => '', 
        'luck_multiplier' => 1.0,
        'description' => 'A simple stick.'
    ];
}

// --- Check Bait Availability ---
// Flag to disable the "Cast Line" button if player has absolutely nothing
$hasBait = false;
foreach ($availableBaits as $bait) {
    if (($bait['quantity'] ?? 0) > 0) {
        $hasBait = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fishing - <?= htmlspecialchars($map['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Fishing Scene Styles */
        .fishing-scene {
            position: relative;
            width: 100%;
            height: 300px;
            background: linear-gradient(to bottom, #87CEEB 0%, #E0F7FA 60%, #0288D1 60%, #01579B 100%);
            border-radius: 12px;
            overflow: hidden;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.3);
            /* display: none;  Removed to show map always */
            transition: background 0.5s ease;
        }

        /* Map 1: Sunny Coast (Default) */
        .scene-map-1 { 
            background: linear-gradient(to bottom, #87CEEB 0%, #E0F7FA 60%, #0288D1 60%, #01579B 100%); 
        }

        /* Map 2: Coral Reef (Tropical/Turquoise) */
        .scene-map-2 { 
            background: linear-gradient(to bottom, #4DD0E1 0%, #E0F7FA 60%, #00838F 60%, #006064 100%); 
        }
        .scene-map-2 .sun { 
            background: radial-gradient(circle, #FFF59D, #FFEE58);
            box-shadow: 0 0 50px #FFEE58;
        }
        .scene-map-2 .water-surface { background: rgba(255,255,255,0.4); }

        /* Map 3: Deep Trench (Dark Blue/Indigo, No Sun) */
        .scene-map-3 { 
            background: linear-gradient(to bottom, #1A237E 0%, #283593 100%); 
        }
        .scene-map-3 .sun { display: none; }
        .scene-map-3 .cloud { opacity: 0.1; }
        .scene-map-3 .water-surface { opacity: 0.1; }
        .scene-map-3 .fishing-line { background: rgba(255, 255, 255, 0.3); }

        /* Map 4: Abyssal Void (Dark Purple/Black) */
        .scene-map-4 { 
            background: linear-gradient(to bottom, #311B92 0%, #000000 100%); 
        }
        .scene-map-4 .sun { 
            display: block; 
            background: radial-gradient(circle, #D1C4E9, #673AB7);
            box-shadow: 0 0 30px #673AB7;
            top: 50px; left: 50px; right: auto;
        }
        .scene-map-4 .cloud { display: none; }
        .scene-map-4 .bobber { box-shadow: 0 0 10px #fff; }

        .sun {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, #FFD54F, #FFB300);
            border-radius: 50%;
            box-shadow: 0 0 40px #FFD54F;
        }

        .cloud {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            animation: drift 20s linear infinite;
        }

        .cloud:nth-child(1) { top: 40px; left: -100px; width: 120px; height: 40px; animation-duration: 25s; }
        .cloud:nth-child(2) { top: 80px; left: -150px; width: 80px; height: 30px; animation-duration: 18s; animation-delay: 5s; }

        @keyframes drift {
            from { transform: translateX(-200px); }
            to { transform: translateX(800px); }
        }

        .water-surface {
            position: absolute;
            top: 60%;
            left: 0;
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.3);
            /* filter: blur(2px); Removed for performance */
        }

        /* Bobber */
        .bobber-container {
            position: absolute;
            top: 60%; /* Water line */
            left: 50%;
            transform: translate(-50%, -20px); /* Adjust so bottom touches water */
            z-index: 10;
        }

        .bobber {
            width: 20px;
            height: 20px;
            background: radial-gradient(circle at 30% 30%, #ff4444, #cc0000);
            border-radius: 50%;
            border-bottom: 2px solid #fff;
            position: relative;
            /* box-shadow: 2px 2px 5px rgba(0,0,0,0.3); Removed for performance */
        }
        
        .bobber.custom-bait {
            background: none !important;
            border: none !important;
            border-radius: 0 !important;
        }
        .bobber.custom-bait::before, .bobber.custom-bait::after {
            display: none;
        }

        .bobber::before { /* Top stick */
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 15px;
            background: #fff;
            border-radius: 2px 2px 0 0;
        }

        .bobber::after { /* Bottom stick (underwater) */
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 10px;
            background: #333;
            opacity: 0.5;
        }

        /* Fishing Line */
        .fishing-line {
            position: absolute;
            top: -200px; /* Coming from off screen */
            left: 50%;
            width: 1px;
            height: 200px;
            background: rgba(255, 255, 255, 0.6);
            transform-origin: bottom center;
        }

        /* Animations */
        .animate-cast {
            animation: castLine 1s ease-out forwards;
        }

        @keyframes castLine {
            0% { top: -100px; opacity: 0; }
            100% { top: 60%; opacity: 1; }
        }

        .animate-float {
            animation: floatBobber 2s ease-in-out infinite;
        }

        @keyframes floatBobber {
            0%, 100% { transform: translate(-50%, -20px) rotate(-5deg); }
            50% { transform: translate(-50%, -15px) rotate(5deg); }
        }

        .animate-bite {
            animation: biteBobber 0.5s ease-in-out infinite;
        }

        @keyframes biteBobber {
            0%, 100% { transform: translate(-50%, -10px); }
            50% { transform: translate(-50%, 10px); }
        }

        .ripple {
            position: absolute;
            top: 60%;
            left: 50%;
            transform: translate(-50%, -50%) scaleX(3);
            width: 0;
            height: 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            opacity: 0;
        }

        .animate-ripple {
            animation: rippleEffect 2s linear infinite;
        }

        @keyframes rippleEffect {
            0% { width: 0; height: 0; opacity: 0.8; }
            100% { width: 100px; height: 30px; opacity: 0; }
        }

        /* Minigame Common Styles */
        .minigame-container, .reel-game, .sequence-game {
            position: relative;
            width: 100%;
            height: 50px;
            background: #333;
            border-radius: 25px;
            margin: 20px 0;
            overflow: hidden;
            display: none;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.5);
        }

        .sequence-game {
            height: auto !important;
            min-height: 50px;
            padding: 5px 10px;
            word-wrap: break-word; /* Ensure wrapping */
        }

        /* Game 1: Classic Bar */
        .minigame-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 10px;
            height: 100%;
            background: #fff;
            box-shadow: 0 0 10px #fff;
        }

        .minigame-target {
            position: absolute;
            top: 0;
            height: 100%;
            background: rgba(0, 255, 0, 0.5);
            border-left: 2px solid #0f0;
            border-right: 2px solid #0f0;
        }

        /* Game 2: Reel In (Stardew Style) */
        .reel-game {
            display: none;
            position: relative;
            width: 300px;
            height: 300px;
            background: #222;
            border: 4px solid #555;
            border-radius: 10px;
            margin: 20px auto;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        
        .reel-game-inner {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .reel-container {
            position: relative;
            width: 80%;
            height: 100%;
            background: #1a1a1a;
            border-right: 2px solid #555;
            overflow: hidden;
        }

        .reel-bar {
            position: absolute;
            left: 0;
            width: 100%;
            height: 20%; /* Catch area size */
            background: rgba(100, 255, 100, 0.4);
            border-top: 2px solid #00ff00;
            border-bottom: 2px solid #00ff00;
            bottom: 0;
            transition: bottom 0.05s linear;
        }

        .reel-fish {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 24px;
            bottom: 10%;
            transition: bottom 0.1s linear;
            z-index: 5;
        }

        .progress-container {
            position: relative;
            width: 20%;
            height: 100%;
            background: #333;
        }

        .progress-bar-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20%; /* Start at 20% */
            background: linear-gradient(to top, #ff4444, #ffbb33, #00C851);
            transition: height 0.1s linear;
        }

        /* Game 3: Sequence */
        .sequence-display {
            font-size: 24px;
            letter-spacing: 10px;
            text-align: center;
            line-height: 50px;
            color: #fff;
        }

        .seq-correct { color: #00C851; text-shadow: 0 0 10px #00C851; }
        .seq-pending { color: #fff; opacity: 0.5; }

        /* Balance Game */
        .balance-game {
            display: none;
            position: relative;
            width: 100%;
            height: 100px;
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid var(--primary);
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
        }
        .balance-bar {
            width: 100%;
            height: 4px;
            background: #555;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        .balance-zone {
            width: 30%;
            height: 100%;
            background: rgba(0, 200, 81, 0.3);
            position: absolute;
            left: 35%;
            top: 0;
            border-left: 2px solid #00C851;
            border-right: 2px solid #00C851;
        }
        .balance-ball {
            width: 20px;
            height: 20px;
            background: #ffbb33;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: left 0.05s linear;
        }
        .timer-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #ff4444;
            width: 100%;
        }

        .minigame-instructions {
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 0.8rem;
            color: #aaa;
            pointer-events: none;
        }

        /* Bait Card Styles */
        .bait-grid-container {
            width: 100%;
        }
        .bait-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.1);
            height: 100%; /* Fill column height */
        }
        .bait-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            z-index: 5; /* Ensure hover is on top */
        }
        .bait-card.selected {
            border: 2px solid #FFD54F;
            background: rgba(255, 213, 79, 0.15);
            box-shadow: 0 0 15px rgba(255, 213, 79, 0.4);
            transform: scale(1.05);
            z-index: 10; /* Ensure selection is on top */
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-card p-5">
                <div class="mb-3">
                    <span class="badge bg-info">📍 <?= htmlspecialchars($map['name']) ?></span>
                    <a href="maps.php" class="btn btn-sm btn-outline-secondary ms-2">Change Location</a>
                </div>
                
                <h2 class="mb-4 text-primary-custom">🎣 Cast Your Line</h2>
                <p class="text-muted mb-3">Patience is key, Captain <strong><?= htmlspecialchars($user['username']) ?></strong>.</p>
                
                <!-- Equipped Rod Display -->
                <div class="mb-4">
                    <label class="form-label text-start w-100 mb-2">My Gear:</label>
                    <div class="glass-card p-3 d-flex align-items-center gap-3">
                        <div class="bg-dark rounded p-2 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <?php if (!empty($equippedRod['image'])): ?>
                                <img src="images/rods/<?= htmlspecialchars($equippedRod['image']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <span style="font-size: 40px;">🎣</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-start">
                            <h5 class="mb-1 text-warning fw-bold"><?= htmlspecialchars($equippedRod['name']) ?></h5>
                            <div class="text-info small mb-1">Luck: x<?= $equippedRod['luck_multiplier'] ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($equippedRod['description'] ?? 'Ready to fish!') ?></div>
                        </div>
                        <div class="ms-auto text-end">
                            <a href="shop.php" class="btn btn-sm btn-outline-light">Change Rod</a>
                        </div>
                    </div>
                </div>

                <!-- Bait Selection (Cards) -->
                <div class="mb-4">
                    <label class="form-label text-start w-100 mb-2">Select Bait:</label>
                    <!-- Using row g-3 for grid layout with gaps -->
                    <div class="row g-3 pb-3 px-2" id="bait-container">
                        <!-- Hidden input to match existing JS logic -->
                        <input type="hidden" id="baitSelect" value="<?= $availableBaits[0]['id'] ?? '' ?>">
                        
                        <?php if (count($availableBaits) > 0): ?>
                            <?php foreach ($availableBaits as $index => $bait): ?>
                                <!-- 4 items per row on desktop (col-lg-3), 2 on mobile (col-6) -->
                                <div class="col-6 col-lg-3">
                                    <div class="bait-card glass-card p-3 text-center rounded d-flex flex-column align-items-center justify-content-center <?= $index === 0 ? 'selected' : '' ?>" 
                                         onclick="selectBait(this, <?= $bait['id'] ?>)"
                                         data-id="<?= $bait['id'] ?>"
                                         data-image="<?= htmlspecialchars($bait['image'] ?? '') ?>"
                                         data-name="<?= htmlspecialchars($bait['name']) ?>">
                                        
                                        <div class="mb-2">
                                            <?php if (!empty($bait['image'])): ?>
                                                 <img src="images/baits/<?= htmlspecialchars($bait['image']) ?>" style="width: 64px; height: 64px; object-fit: contain;" alt="<?= htmlspecialchars($bait['name']) ?>">
                                            <?php else: ?>
                                                <?php 
                                                    $icon = match(strtolower($bait['name'])) {
                                                        'worm' => '🪱',
                                                        'shrimp' => '🦐',
                                                        'squid' => '🦑',
                                                        'golden lure' => '✨',
                                                        default => '🎣'
                                                    };
                                                ?>
                                                <span class="display-4"><?= $icon ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="fw-bold mb-1 fs-5"><?= htmlspecialchars($bait['name']) ?></div>
                                        <div class="badge bg-secondary rounded-pill mb-2"><?= $bait['quantity'] ?? 0 ?> left</div>
                                        <div class="text-info small">Luck x<?= $bait['rarity_boost'] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-muted w-100 py-3">No bait available.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$hasBait): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>⚠️ Out of Bait!</strong> 
                            <a href="shop.php" class="alert-link">Buy more bait</a> to continue fishing.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Realistic Fishing Scene -->
                <div id="fishing-scene" class="fishing-scene scene-map-<?= $mapId ?> mb-4">
                    <div class="sky">
                        <div class="sun"></div>
                        <div class="cloud"></div>
                        <div class="cloud"></div>
                    </div>
                    <div class="ocean">
                        <div class="water-surface"></div>
                    </div>
                    
                    <div id="bobber-group" class="bobber-container">
                        <div class="fishing-line"></div>
                        <div class="bobber"></div>
                    </div>
                    
                    <div id="ripple-effect" class="ripple"></div>
                    
                    <div class="position-absolute bottom-0 w-100 text-center pb-3">
                        <span id="fishing-status" class="badge bg-dark bg-opacity-50 fs-6">Ready to Fish!</span>
                    </div>
                </div>

                <!-- Minigame 1: Classic Bar -->
                <div id="minigame" class="minigame-container">
                    <div class="minigame-target"></div>
                    <div class="minigame-bar"></div>
                    <div class="minigame-instructions">Press SPACE in the green zone!</div>
                </div>

                <!-- Minigame 2: Reel In -->
                <div id="reel-game" class="reel-game">
                    <div class="reel-game-inner">
                        <div class="reel-container">
                            <div class="reel-bar"></div>
                            <div class="reel-fish">🐟</div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar-fill"></div>
                        </div>
                    </div>
                    <div class="minigame-instructions" style="text-shadow: 1px 1px 2px black;">Hold SPACE to raise bar! Keep fish inside!</div>
                </div>

                <!-- Minigame 3: Sequence -->
                <div id="sequence-game" class="sequence-game">
                    <div id="sequence-display" class="sequence-display"></div>
                    <div class="timer-bar" id="seq-timer"></div>
                    <div class="minigame-instructions">Type the arrows! Hurry!</div>
                </div>

                <!-- Minigame 4: Balance -->
                <div id="balance-game" class="balance-game">
                    <div class="balance-zone"></div>
                    <div class="balance-bar"></div>
                    <div class="balance-ball"></div>
                    <div class="timer-bar" id="bal-timer"></div>
                    <div class="minigame-instructions">Use LEFT/RIGHT to keep the ball in the center!</div>
                </div>

                <div id="result" class="mb-4"></div>

                <button id="fishButton" class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg" <?= !$hasBait ? 'disabled title="You need bait to fish!"' : '' ?>>
                    🌊 Cast Line
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="game_config.js?v=<?= time() ?>"></script>
<script>
$(document).ready(function () {
    let mapId = <?= $mapId ?>;
    
    // Global function for onclick
        
    window.selectBait = function(element, id) {
        // Visual Update
        $('.bait-card').removeClass('selected');
        $(element).addClass('selected');
        
        // Logic Update
        $('#baitSelect').val(id);
        
        // Bobber Update
        const image = $(element).data('image');
        const name = $(element).data('name') || 'worm';
        const bobber = $('.bobber');
        
        // Icon Logic (Same as PHP match)
        let icon = '🎣';
        const lowerName = name.toLowerCase();
        if (lowerName.includes('worm')) icon = '🪱';
        else if (lowerName.includes('shrimp')) icon = '🦐';
        else if (lowerName.includes('squid')) icon = '🦑';
        else if (lowerName.includes('golden')) icon = '✨';
        
        if (image) {
            bobber.addClass('custom-bait').html(`<img src="images/baits/${image}" style="width: 50px; height: 50px; object-fit: contain;">`);
        } else {
            bobber.addClass('custom-bait').html(`<span style="font-size: 40px;">${icon}</span>`);
        }
    };
    
    // Init: Ensure the first available bait is selected if any
    const firstBait = $('.bait-card').first();
    if (firstBait.length > 0) {
        // Manually trigger the selection logic to ensure visuals update immediately
        selectBait(firstBait[0], firstBait.data('id'));
    }
    
    let minigameActive = false;
    let currentGameType = 0;
    let animationFrame;
    let cooldownActive = false; // Prevent accidental spam

    // Game 1 Vars
    let barPosition = 0;
    let barDirection = 1;
    let barSpeed = GameConfig.game1.baseSpeed;
    let targetStart = 40;
    let targetWidth = GameConfig.game1.baseTargetWidth;

    // Game 2 Vars (Reel In)
    let reelPosition = 0; // 0-100 (Bottom to Top)
    let reelVelocity = 0;
    let reelGravity = GameConfig.game2.reelGravity;
    let reelLift = GameConfig.game2.reelLift;
    let fishPosition = 0; // 0-100
    let fishTarget = 0;
    let fishSpeed = 1;
    let fishTimer = 0;
    let catchProgress = GameConfig.game2.startProgress;
    let isReeling = false;
    let catchRate = GameConfig.game2.baseCatchRate;
    let drainRate = GameConfig.game2.baseDrainRate;

    // Game 3 Vars (Sequence)
    let sequence = [];
    let seqIndex = 0;
    const arrows = ['⬆️', '⬇️', '⬅️', '➡️'];
    const keys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
    let seqTimer = 0;
    let seqMaxTime = GameConfig.game3.baseTime;

    // Game 4 Vars (Balance)
    let balPosition = 50; // 0-100
    let balVelocity = 0;
    let balGravity = 0; // Random pull
    let balTimer = 0;
    let balMaxTime = GameConfig.game4.duration;
    let leftPressed = false;
    let rightPressed = false;

    // --- CORE GAME LOOP: CASTING LINE ---
    $("#fishButton").click(function () {
        let btn = $(this);
        let baitId = $("#baitSelect").val();
        
        // 1. Disable button to prevent spamming
        btn.prop("disabled", true);
        $("#result").html("");
        
        // 2. UI Reset: Hide old result, show scene, reset animation classes
        $("#fishing-scene").show();
        $("#fishing-status").text("Casting line...");
        $("#bobber-group").removeClass("animate-float animate-bite").addClass("animate-cast");
        $("#ripple-effect").removeClass("animate-ripple");
        
        // 3. Animation Timing
        // After 1 second (cast animation duration), switch to "Floating" state
        setTimeout(() => {
            $("#bobber-group").removeClass("animate-cast").addClass("animate-float");
            $("#ripple-effect").addClass("animate-ripple");
            $("#fishing-status").text("Waiting for a bite...");
        }, 1000);

        // 4. SERVER REQUEST: Hook Fish
        // We ask the server to decide WHAT fish is on the line (based on map & luck)
        $.ajax({
            url: "hook_fish.php?map_id=<?= $mapId ?>&bait_id=" + baitId,
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                    $("#fishing-scene").hide();
                    $("#result").html(`<div class="alert alert-danger">${data.error}</div>`);
                    btn.prop("disabled", false);
                    return;
                }

                // 5. WAIT SIMULATION
                // Randomly wait 2-4 seconds before the "Bite" happens
                let waitTime = Math.floor(Math.random() * 2000) + 2000;
                
                setTimeout(() => {
                    // 6. FISH BITE!
                    // Change animation to "Bite" (bobbing heavily)
                    $("#bobber-group").removeClass("animate-float").addClass("animate-bite");
                    $("#fishing-status").text("Something's on the line!");
                    $("#fishing-status").removeClass("bg-dark").addClass("bg-danger");
                    
                    // Show specific fish sprite in minigame if provided
                    if (data.image) {
                        $(".reel-fish").html(`<img src="images/fish/${data.image}" style="width: 30px; height: auto;">`);
                    }

                    // 7. START MINIGAME
                    // After 1.5s of "Biting", transition to the minigame view
                    setTimeout(() => {
                        $("#fishing-scene").hide();
                        $("#fishing-status").removeClass("bg-danger").addClass("bg-dark");
                        
                        // Select which minigame to play based on Map ID
                        if (mapId == 1) startMinigame1(data.difficulty);
                        else if (mapId == 2) startMinigame2(data.difficulty);
                        else if (mapId == 3) startMinigame3(data.difficulty);
                        else startMinigame4(data.difficulty); 
                        
                    }, 1500);
                }, waitTime);
            },
            error: function () {
                $("#fishing-scene").hide();
                $("#result").html("<div class='alert alert-danger'>⚠️ Connection failed</div>");
                btn.prop("disabled", false);
            }
        });
    });

    // --- GAME 1: Classic Bar ---
    function startMinigame1(difficulty) {
        currentGameType = 1;
        barSpeed = GameConfig.game1.baseSpeed + (difficulty * GameConfig.game1.speedMultiplier);
        targetWidth = Math.max(GameConfig.game1.minTargetWidth, GameConfig.game1.baseTargetWidth - (difficulty * GameConfig.game1.widthMultiplier));
        targetStart = Math.random() * (100 - targetWidth);

        $(".minigame-target").css({ left: targetStart + "%", width: targetWidth + "%" });
        barPosition = 0;
        barDirection = 1;
        minigameActive = true;
        $("#minigame").show();
        animateBar1();
    }

    function animateBar1() {
        if (!minigameActive || currentGameType !== 1) return;
        barPosition += barSpeed * barDirection;
        if (barPosition >= 100) { barPosition = 100; barDirection = -1; }
        else if (barPosition <= 0) { barPosition = 0; barDirection = 1; }
        // Optimization: Use transform instead of left to prevent layout thrashing
        // Note: We need to calculate percentage relative to container width, but left% is fine if transform is tricky to align without changing CSS.
        // Actually, CSS 'left' on absolute is okay-ish, but transform is better.
        // For now, sticking to style directly but ensuring we don't do it if position hasn't changed much? No, every frame needs it.
        // Let's stick to .css() but ensure it's performant.
        // Better: el.style.left = ... (Native DOM is faster than jQuery)
        document.querySelector(".minigame-bar").style.left = barPosition + "%";
        animationFrame = requestAnimationFrame(animateBar1);
    }

    // --- GAME 2: Reel In (Stardew Style) ---
    function startMinigame2(difficulty) {
        currentGameType = 2;
        reelPosition = 10;
        reelVelocity = 0;
        fishPosition = 50;
        fishTarget = 50;
        catchProgress = GameConfig.game2.startProgress;
        isReeling = false;
        
        // Apply Config Visuals
        $(".reel-bar").css("height", GameConfig.game2.barHeight + "%");
        
        // Difficulty scaling
        fishSpeed = GameConfig.game2.baseFishSpeed + (difficulty * GameConfig.game2.fishSpeedMultiplier);
        catchRate = Math.max(GameConfig.game2.minCatchRate, GameConfig.game2.baseCatchRate - (difficulty * GameConfig.game2.catchRateMultiplier));
        drainRate = GameConfig.game2.baseDrainRate + (difficulty * GameConfig.game2.drainRateMultiplier);
        
        minigameActive = true;
        $("#reel-game").show();
        animateReel();
    }

    function animateReel() {
        if (!minigameActive || currentGameType !== 2) return;

        // 1. Update Fish Position
        fishTimer++;
        if (fishTimer > 50) { // Change target every ~1s
            fishTimer = 0;
            fishTarget = Math.random() * 90; // Keep within bounds
        }
        
        // Move fish towards target
        if (fishPosition < fishTarget) fishPosition += fishSpeed;
        else if (fishPosition > fishTarget) fishPosition -= fishSpeed;
        
        // Erratic movement chance
        if (Math.random() < 0.05) fishTarget = Math.random() * 90;

        // 2. Update Reel Bar (Player)
        if (isReeling) {
            reelVelocity += reelLift;
        } else {
            reelVelocity -= reelGravity;
        }
        
        // Apply velocity and friction
        reelVelocity *= 0.9; 
        reelPosition += reelVelocity;
        
        // Bounds check (bounce slightly)
        if (reelPosition < 0) { reelPosition = 0; reelVelocity = 0; }
        if (reelPosition > (100 - GameConfig.game2.barHeight)) { reelPosition = (100 - GameConfig.game2.barHeight); reelVelocity = 0; }

        // 3. Check Overlap & Progress
        // Bar covers reelPosition to reelPosition + barHeight
        // Fish is at fishPosition (center)
        // Check if fish is inside bar
        let barBottom = reelPosition;
        let barTop = reelPosition + GameConfig.game2.barHeight;
        
        if (fishPosition >= barBottom && fishPosition <= barTop) {
            catchProgress += catchRate; // Catching!
            $(".reel-bar").css("background", "rgba(100, 255, 100, 0.6)");
        } else {
            catchProgress -= drainRate; // Losing!
            $(".reel-bar").css("background", "rgba(255, 100, 100, 0.6)");
        }
        
        // Clamp progress
        if (catchProgress > 100) catchProgress = 100;
        if (catchProgress < 0) catchProgress = 0;

        // 4. Update Visuals
        $(".reel-bar").css("bottom", reelPosition + "%");
        $(".reel-fish").css("bottom", fishPosition + "%");
        $(".progress-bar-fill").css("height", catchProgress + "%");
        
        // Color progress bar
        if (catchProgress < 30) $(".progress-bar-fill").css("background", "#ff4444");
        else if (catchProgress < 70) $(".progress-bar-fill").css("background", "#ffbb33");
        else $(".progress-bar-fill").css("background", "#00C851");

        // 5. Win/Loss Condition
        if (catchProgress >= 100) {
            finishMinigame(true);
            return;
        }
        if (catchProgress <= 0) {
            finishMinigame(false);
            return;
        }

        animationFrame = requestAnimationFrame(animateReel);
    }

    // --- GAME 3: Sequence ---
    function startMinigame3(difficulty) {
        currentGameType = 3;
        sequence = [];
        seqIndex = 0;
        seqTimer = GameConfig.game3.baseTime - (difficulty * GameConfig.game3.timePenalty);
        seqMaxTime = seqTimer;
        
        let length = GameConfig.game3.baseLength + Math.floor(difficulty * GameConfig.game3.lengthMultiplier);
        let html = "";
        for(let i=0; i<length; i++) {
            let r = Math.floor(Math.random() * 4);
            sequence.push(r);
            
            // 0:Up, 1:Down, 2:Left, 3:Right
            // CORRECTION: User says it's "totally opposite". 
            // This implies the base image likely faces LEFT.
            // If base is LEFT:
            // Up (0): rotate(90deg)
            // Down (1): rotate(-90deg)
            // Left (2): scaleX(1) (Default)
            // Right (3): scaleX(-1) (Flip)
            
            let transform = "";
            
            if (r === 0) transform = "rotate(90deg)";       // Up
            else if (r === 1) transform = "rotate(-90deg)"; // Down
            else if (r === 2) transform = "scaleX(1)";      // Left
            else if (r === 3) transform = "scaleX(-1)";     // Right

            html += `<span id="seq-${i}" class="seq-pending" style="display:inline-block;">
                        <img src="images/fish/default_fish.png" style="width: 1em; height: auto; transform: ${transform}; vertical-align: middle;">
                     </span>`;
        }
        $("#sequence-display").html(html);
        
        // Apply Visual Config
        $("#sequence-game").css("height", GameConfig.game3.containerHeight);
        $("#sequence-display").css({
            "font-size": GameConfig.game3.iconSize,
            "letter-spacing": GameConfig.game3.spacing,
            "line-height": GameConfig.game3.containerHeight // Center vertically
        });
        
        minigameActive = true;
        $("#sequence-game").show();
        animateSequence();
    }

    function animateSequence() {
        if (!minigameActive || currentGameType !== 3) return;
        seqTimer--;
        
        let pct = (seqTimer / seqMaxTime) * 100;
        $("#seq-timer").css("width", pct + "%");

        if (seqTimer <= 0) {
            finishMinigame(false); // Time out
            return;
        }
        animationFrame = requestAnimationFrame(animateSequence);
    }

    // --- GAME 4: Balance ---
    function startMinigame4(difficulty) {
        currentGameType = 4;
        balPosition = 50;
        balVelocity = 0;
        balGravity = (Math.random() - 0.5) * GameConfig.game4.gravity;
        balTimer = GameConfig.game4.duration;
        balMaxTime = GameConfig.game4.duration;
        
        minigameActive = true;
        $("#balance-game").show();
        animateBalance();
    }

    function animateBalance() {
        if (!minigameActive || currentGameType !== 4) return;
        
        // Physics
        // Random "wind" changes gravity slightly
        if (Math.random() < GameConfig.game4.windChance) balGravity += (Math.random() - 0.5) * GameConfig.game4.windStrength;
        
        // Input influence
        if (leftPressed) balVelocity -= GameConfig.game4.inputStrength;
        if (rightPressed) balVelocity += GameConfig.game4.inputStrength;
        
        balVelocity += balGravity;
        balVelocity *= GameConfig.game4.friction; // Friction
        balPosition += balVelocity;
        
        // Bounds check
        if (balPosition < 0 || balPosition > 100) {
            finishMinigame(false); // Fell off
            return;
        }
        
        // Timer
        balTimer--;
        let pct = (balTimer / balMaxTime) * 100;
        $("#bal-timer").css("width", pct + "%");
        
        if (balTimer <= 0) {
            // MUST be in the green zone to win
            let zoneStart = GameConfig.game4.zoneLeft;
            let zoneEnd = zoneStart + GameConfig.game4.zoneWidth;
            
            if (balPosition >= zoneStart && balPosition <= zoneEnd) {
                finishMinigame(true); // Survived AND inside zone!
            } else {
                $("#result").html("<div class='alert alert-warning'>You were outside the green zone!</div>");
                finishMinigame(false); // Survived but failed objective
            }
            return;
        }
        
        $(".balance-ball").css("left", balPosition + "%");
        animationFrame = requestAnimationFrame(animateBalance);
    }

    // --- INPUT HANDLING ---
    $(document).keydown(function (e) {
        if (cooldownActive) {
            e.preventDefault(); 
            return;
        }

        if (!minigameActive) {
            if (e.key === " " && !$("#fishButton").prop("disabled")) {
                e.preventDefault();
                $("#fishButton").click();
            }
            return;
        }

        // Game 1 Input
        if (currentGameType === 1 && e.key === " ") {
            e.preventDefault();
            let tolerance = GameConfig.game1.tolerance;
            let success = barPosition >= (targetStart - tolerance) && barPosition <= (targetStart + targetWidth + tolerance);
            finishMinigame(success);
        }

        // Game 2 Input (Reel In)
        if (currentGameType === 2 && e.key === " ") {
            e.preventDefault();
            isReeling = true;
        }

        // Game 3 Input (Sequence)
        if (currentGameType === 3) {
            if (keys.includes(e.key)) {
                e.preventDefault();
                let expected = sequence[seqIndex];
                let inputIndex = keys.indexOf(e.key);
                
                if (inputIndex === expected) {
                    $(`#seq-${seqIndex}`).removeClass("seq-pending").addClass("seq-correct");
                    seqIndex++;
                    if (seqIndex >= sequence.length) {
                        finishMinigame(true);
                    }
                } else {
                    finishMinigame(false); // Wrong key
                }
            }
        }

        // Game 4 Input (Balance)
        if (currentGameType === 4) {
            if (e.key === "ArrowLeft") leftPressed = true;
            if (e.key === "ArrowRight") rightPressed = true;
        }
    });

    $(document).keyup(function (e) {
        if (currentGameType === 2 && e.key === " ") {
            isReeling = false;
        }
        if (currentGameType === 4) {
            if (e.key === "ArrowLeft") leftPressed = false;
            if (e.key === "ArrowRight") rightPressed = false;
        }
    });

    // --- MINIGAME COMPLETION ---
    function finishMinigame(success) {
        // Stop Loop
        minigameActive = false;
        cooldownActive = true; 
        setTimeout(() => { cooldownActive = false; }, 1000);

        cancelAnimationFrame(animationFrame);
        // Hide Minigame UI
        $(".minigame-container, .reel-game, .sequence-game, .balance-game").hide();

        // Send Result to Server
        $.ajax({
            url: "catch_fish.php",
            method: "POST",
            dataType: 'json',
            data: { success: success }, // Send boolean success/fail
            success: function (data) {
                // Show Result Message
                $("#result").html(`<div class="alert alert-${data.success ? 'success' : 'warning'}">${data.message}</div>`);
                $("#fishButton").prop("disabled", false);
                
                // Update Badge if leveled up
                if (data.newLevel) {
                    $(".badge.bg-primary").text("Lv " + data.newLevel);
                }
                
                // Update Bait Quantity in UI
                if (data.bait_id && data.bait_remaining !== undefined) {
                    const baitCard = $(`.bait-card[data-id='${data.bait_id}']`);
                    if (baitCard.length) {
                        baitCard.find('.badge').text(`${data.bait_remaining} left`);
                        
                        // Visual cue if out of bait
                        if (data.bait_remaining <= 0) {
                             baitCard.addClass('opacity-50'); 
                        }
                    }
                }
            },
            error: function () {
                $("#result").html("<div class='alert alert-danger'>⚠️ Failed to process catch</div>");
                $("#fishButton").prop("disabled", false);
            }
        });
    }
});
</script>
</body>
</html>
