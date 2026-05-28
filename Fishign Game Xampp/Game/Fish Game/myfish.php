<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// --- Handle Bulk Sell POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_fish_ids'])) {
    $fishIds = $_POST['sell_fish_ids'];
    
    if (!empty($fishIds)) {
        // 1. Calculate Total Value
        // Dynamically create placeholder string (?,?,?) for IN clause
        $placeholders = str_repeat('?,', count($fishIds) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT SUM(ft.value) as total_value
            FROM fish_catches fc
            JOIN fish_types ft ON fc.fish_type_id = ft.id
            WHERE fc.id IN ($placeholders) AND fc.user_id = ?
        ");
        // Flatten parameters array
        $stmt->execute([...$fishIds, $user['id']]);
        $totalValue = $stmt->fetchColumn() ?: 0;
        
        // 2. Delete Fish from Inventory
        // Verify ownership (AND user_id = ?) so users can't delete others' fish
        $stmt = $pdo->prepare("DELETE FROM fish_catches WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute([...$fishIds, $user['id']]);
        
        // 3. Add Coins to User
        $newBalance = $user['coins'] + $totalValue;
        $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newBalance, $user['id']]);
        $_SESSION['user']['coins'] = $newBalance;
        
        $message = "Sold " . count($fishIds) . " fish for " . number_format($totalValue) . " coins!";
        $msgType = "success";
    }
}

// Get filter
$rarityFilter = $_GET['rarity'] ?? 'all';

// Fetch fish with filter
$query = "
    SELECT c.id, f.name AS fish_name, f.rarity, f.value, f.image, c.weight, c.caught_at 
    FROM fish_catches c
    JOIN fish_types f ON c.fish_type_id = f.id
    WHERE c.user_id = ?
";

if ($rarityFilter !== 'all') {
    $query .= " AND f.rarity = ?";
}

$query .= " ORDER BY c.caught_at DESC";

$stmt = $pdo->prepare($query);
if ($rarityFilter !== 'all') {
    $stmt->execute([$user['id'], $rarityFilter]);
} else {
    $stmt->execute([$user['id']]);
}
$fishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get rarity counts
$rarityStats = $pdo->prepare("
    SELECT f.rarity, COUNT(*) as count
    FROM fish_catches c
    JOIN fish_types f ON c.fish_type_id = f.id
    WHERE c.user_id = ?
    GROUP BY f.rarity
");
$rarityStats->execute([$user['id']]);
$stats = $rarityStats->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Fish - Deep Ocean Fishing</title>
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
        <h2 class="text-primary-custom fw-bold">🐟 My Catch</h2>
        <div>
            <a href="fish.php" class="btn btn-primary">Go Fishing</a>
        </div>
    </div>

    <!-- Filter and Bulk Actions -->
    <div class="glass-card p-3 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <a href="?rarity=all" class="btn btn-sm <?= $rarityFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        All (<?= array_sum($stats) ?>)
                    </a>
                    <a href="?rarity=Common" class="btn btn-sm <?= $rarityFilter === 'Common' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Common (<?= $stats['Common'] ?? 0 ?>)
                    </a>
                    <a href="?rarity=Rare" class="btn btn-sm <?= $rarityFilter === 'Rare' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Rare (<?= $stats['Rare'] ?? 0 ?>)
                    </a>
                    <a href="?rarity=Epic" class="btn btn-sm <?= $rarityFilter === 'Epic' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Epic (<?= $stats['Epic'] ?? 0 ?>)
                    </a>
                    <a href="?rarity=Legendary" class="btn btn-sm <?= $rarityFilter === 'Legendary' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Legendary (<?= $stats['Legendary'] ?? 0 ?>)
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button id="selectAll" class="btn btn-sm btn-outline-info">Select All</button>
                <button id="sellSelected" class="btn btn-sm btn-success" disabled>Sell Selected</button>
            </div>
        </div>
    </div>

    <?php if (count($fishes) === 0): ?>
        <div class="glass-card p-5 text-center">
            <h4 class="text-muted">Your net is empty.</h4>
            <a href="fish.php" class="btn btn-primary mt-3">Cast a Line</a>
        </div>
    <?php else: ?>
        <form id="sellForm" method="post">
            <div class="row g-4">
                <?php foreach ($fishes as $fish): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card p-3 h-100 d-flex flex-column">
                            <div class="form-check mb-2">
                                <input class="form-check-input fish-checkbox" type="checkbox" name="sell_fish_ids[]" value="<?= $fish['id'] ?>" data-price="<?= $fish['value'] ?>" data-rarity="<?= $fish['rarity'] ?>" id="fish<?= $fish['id'] ?>">
                                <label class="form-check-label" for="fish<?= $fish['id'] ?>">
                                    <small class="text-muted">Select</small>
                                </label>
                            </div>
                            
                            <div class="text-center mb-3">
                                <?php if (!empty($fish['image'])): ?>
                                    <img src="images/fish/<?= htmlspecialchars($fish['image']) ?>" alt="<?= htmlspecialchars($fish['fish_name']) ?>" class="img-fluid" style="max-height: 100px;">
                                <?php else: ?>
                                    <span class="display-1">🐟</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($fish['fish_name']) ?></h5>
                                <?php
                                $rarity = $fish['rarity'];
                                $rarityClass = match ($rarity) {
                                    'Rare' => 'rarity-rare',
                                    'Epic' => 'rarity-epic',
                                    'Legendary' => 'rarity-legendary',
                                    default => 'rarity-common'
                                };
                                ?>
                                <span class="badge bg-dark <?= $rarityClass ?>"><?= htmlspecialchars($rarity) ?></span>
                            </div>
                            
                            <p class="text-info mb-1">⚖️ <?= $fish['weight'] ?? 1.0 ?>kg</p>
                            <p class="text-warning mb-2">💰 <?= number_format($fish['value']) ?> coins</p>
                            <p class="text-muted small mb-3">Caught: <?= $fish['caught_at'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Sell Confirmation Modal -->
<div class="modal fade" id="sellModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-white">💰 Confirm Sale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-white">
                <div id="sell-warning-container"></div>
                <p>Are you sure you want to sell <strong class="text-warning" id="sell-count">0</strong> fish?</p>
                <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                    <span>Total Value:</span>
                    <strong class="fs-4">💰 <span id="sell-total">0</span></strong>
                </div>
                <p class="small text-muted mt-2 mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmSellBtn">Confirm Sell</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    // Select all toggle
    $("#selectAll").click(function () {
        let allChecked = $(".fish-checkbox:checked").length === $(".fish-checkbox").length;
        $(".fish-checkbox").prop("checked", !allChecked);
        updateSellButton();
    });

    // Update sell button state
    $(".fish-checkbox").change(function () {
        updateSellButton();
    });

    function updateSellButton() {
        let selectedCount = $(".fish-checkbox:checked").length;
        $("#sellSelected").prop("disabled", selectedCount === 0);
        $("#sellSelected").text(selectedCount > 0 ? `Sell Selected (${selectedCount})` : "Sell Selected");
    }

    // Sell selected - Open Modal
    $("#sellSelected").click(function () {
        let count = $(".fish-checkbox:checked").length;
        let totalPrice = 0;
        let hasHighRarity = false;
        
        $(".fish-checkbox:checked").each(function() {
            totalPrice += parseInt($(this).data('price')) || 0;
            let rarity = $(this).data('rarity');
            if (rarity === 'Legendary' || rarity === 'Epic') {
                hasHighRarity = true;
            }
        });

        if (hasHighRarity) {
            $("#sell-warning-container").html(`
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <span class="fs-2 me-3">⚠️</span>
                    <div>
                        <strong>WARNING:</strong> You have selected <strong>LEGENDARY</strong> or <strong>EPIC</strong> fish!<br>
                        Are you sure you want to sell them?
                    </div>
                </div>
            `);
        } else {
            $("#sell-warning-container").html("");
        }

        $("#sell-count").text(count);
        $("#sell-total").text(totalPrice.toLocaleString());
        
        var sellModal = new bootstrap.Modal(document.getElementById('sellModal'));
        sellModal.show();
    });

    // Confirm Sell Action
    $("#confirmSellBtn").click(function () {
        $("#sellForm").submit();
    });
});
</script>

</body>
</html>