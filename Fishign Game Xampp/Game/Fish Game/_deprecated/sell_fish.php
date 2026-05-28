<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    exit("Please log in first.");
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// 接收要卖的鱼纪录 ID
$catch_id = $_POST['catch_id'] ?? null;
if (!$catch_id) {
    exit("Invalid request.");
}

// 查询该条鱼的价值
$stmt = $pdo->prepare("
    SELECT f.value 
    FROM fish_catches c 
    JOIN fish_types f ON c.fish_type_id = f.id 
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$catch_id, $user_id]);
$fish = $stmt->fetch();

if (!$fish) {
    exit("Fish not found.");
}

$value = (int)$fish['value'];

// 删除这条钓鱼纪录
$delete = $pdo->prepare("DELETE FROM fish_catches WHERE id = ? AND user_id = ?");
$delete->execute([$catch_id, $user_id]);

// 加金币
$update = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
$update->execute([$value, $user_id]);

// 更新 session（刷新金币显示）
$_SESSION['user']['coins'] += $value;

echo "💰 Sold for $value coins!";
?>
