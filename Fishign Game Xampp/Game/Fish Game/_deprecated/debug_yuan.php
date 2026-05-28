<?php
require 'db.php';
$username = 'Yuan';
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User: " . $user['username'] . "\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Coins: " . $user['coins'] . "\n";
    echo "Level: " . $user['level'] . "\n";
    
    $stmt = $pdo->prepare("SELECT b.name, ub.quantity FROM user_baits ub JOIN baits b ON ub.bait_id = b.id WHERE ub.user_id = ?");
    $stmt->execute([$user['id']]);
    $baits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Baits:\n";
    foreach ($baits as $b) {
        echo "- " . $b['name'] . ": " . $b['quantity'] . "\n";
    }
    if (empty($baits)) echo "- None\n";
    
    echo "\nTesting API...\n";
    $api_url = "http://localhost:8080/api/fish?luck=1.0&location=Sunny%20Coast";
    $response = @file_get_contents($api_url);
    if ($response === FALSE) {
        echo "API Failed.\n";
    } else {
        echo "API Response: " . $response . "\n";
    }
} else {
    echo "User not found.\n";
}
?>
