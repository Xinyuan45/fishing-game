<?php
require 'db.php';

try {
    // Update bait prices
    $pdo->exec("UPDATE baits SET price = 5 WHERE name = 'Shrimp'");
    $pdo->exec("UPDATE baits SET price = 25 WHERE name = 'Squid'");
    $pdo->exec("UPDATE baits SET price = 50 WHERE name = 'Crab'");
    $pdo->exec("UPDATE baits SET price = 100 WHERE name = 'Golden Lure'");
    
    echo "Bait prices updated!<br>";
    echo "Worm: FREE<br>";
    echo "Shrimp: 5 coins<br>";
    echo "Squid: 25 coins<br>";
    echo "Crab: 50 coins<br>";
    echo "Golden Lure: 100 coins<br>";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
