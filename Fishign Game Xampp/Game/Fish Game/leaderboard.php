<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// --- Leaderboard Logic ---
// Fetch the top 10 players based on:
// 1. Wealth (Coins) DESC
// 2. Total Fish Caught DESC (Tie-breaker)
// COALESCE(..., 0) ensures that users with 0 catches don't break the query or show NULL
$stmt = $pdo->query("
    SELECT u.username, COALESCE(s.total_catches, 0) AS total_fish, u.coins
    FROM users u
    LEFT JOIN user_stats s ON u.id = s.user_id
    ORDER BY u.coins DESC, total_fish DESC
    LIMIT 10
");
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leaderboard - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="glass-card p-5">
                <h2 class="text-center text-primary-custom fw-bold mb-4">🏆 Hall of Fame</h2>
                <p class="text-center text-muted mb-5">Top 10 Legends of the Deep</p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="text-primary-custom border-bottom border-secondary border-opacity-50">
                                <th scope="col" class="py-3">Rank</th>
                                <th scope="col" class="py-3">Captain</th>
                                <th scope="col" class="py-3 text-center">Total Catch</th>
                                <th scope="col" class="py-3 text-end">Wealth (Coins)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $index => $p): ?>
                                <tr class="align-middle">
                                    <td class="py-3">
                                        <?php if ($index === 0): ?>
                                            <span class="fs-4">🥇</span>
                                        <?php elseif ($index === 1): ?>
                                            <span class="fs-4">🥈</span>
                                        <?php elseif ($index === 2): ?>
                                            <span class="fs-4">🥉</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-25 rounded-circle p-2" style="width: 30px; height: 30px;"><?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['username']) ?></td>
                                    <td class="text-center text-info"><?= $p['total_fish'] ?> 🐟</td>
                                    <td class="text-end text-warning fw-bold"><?= number_format($p['coins']) ?> 🪙</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
