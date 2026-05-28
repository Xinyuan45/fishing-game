<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    exit("Please log in first.");
}

$user = $_SESSION['user'];

// Get equipped rod luck
$stmt = $pdo->prepare("
    SELECT r.luck_multiplier 
    FROM user_rods ur 
    JOIN rods r ON ur.rod_id = r.id 
    WHERE ur.user_id = ? AND ur.is_equipped = 1
");
$stmt->execute([$user['id']]);
$luck = $stmt->fetchColumn() ?: 1.0; // Default to 1.0 if no rod found

// Call Java API with luck
$api_url = "http://localhost:8080/api/fish?luck=" . $luck;
$response = @file_get_contents($api_url);

if ($response === FALSE) {
    exit("<span class='text-danger'>⚠️ Unable to connect to Java API.</span>");
}

$data = json_decode($response, true);
$fish_name = $data['fish'] ?? "Unknown Fish";
$rarity = $data['rarity'] ?? "Common";

// 检查鱼种是否存在
$stmt = $pdo->prepare("SELECT id FROM fish_types WHERE name = ?");
$stmt->execute([$fish_name]);
$fish = $stmt->fetch();

if (!$fish) {
    // 稀有度不同可给不同价值
    $value = match($rarity) {
        "Rare" => 30,
        "Epic" => 60,
        "Legendary" => 100,
        default => 10
    };
    $insertFish = $pdo->prepare("INSERT INTO fish_types (name, rarity, value) VALUES (?, ?, ?)");
    $insertFish->execute([$fish_name, $rarity, $value]);
    $fish_id = $pdo->lastInsertId();
} else {
    $fish_id = $fish['id'];
}

// 存入钓鱼纪录
$stmt = $pdo->prepare("INSERT INTO fish_catches (user_id, fish_type_id) VALUES (?, ?)");
$stmt->execute([$user['id'], $fish_id]);

// 颜色显示
$color = match($rarity) {
    "Rare" => "text-primary",
    "Epic" => "text-warning",
    "Legendary" => "text-danger",
    default => "text-secondary"
};

echo "🎣 You caught a <span class='fw-bold $color'>$fish_name</span> (<span class='$color'>$rarity</span>)!";
?>
