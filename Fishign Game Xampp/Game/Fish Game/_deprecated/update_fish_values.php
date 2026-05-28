<?php
require 'db.php';

try {
    // Update fish values based on location
    // Sunny Coast fish - base values (10-500)
    $pdo->exec("UPDATE fish_types SET value = 10 WHERE rarity = 'Common' AND name IN ('Carp', 'Goldfish', 'Sardine', 'Anchovy', 'Mackerel')");
    $pdo->exec("UPDATE fish_types SET value = 50 WHERE rarity = 'Rare' AND name IN ('Sea Bass', 'Red Snapper', 'Flounder', 'Mullet')");
    $pdo->exec("UPDATE fish_types SET value = 150 WHERE rarity = 'Epic' AND name IN ('Dolphin', 'Manta Ray', 'Sailfish')");
    $pdo->exec("UPDATE fish_types SET value = 500 WHERE rarity = 'Legendary' AND name IN ('Golden Marlin', 'Sea Dragon')");
    
    // Coral Reef fish - 2x multiplier (20-1000)
    $pdo->exec("UPDATE fish_types SET value = 20 WHERE rarity = 'Common' AND name IN ('Clownfish', 'Tang', 'Parrotfish', 'Angelfish', 'Butterflyfish')");
    $pdo->exec("UPDATE fish_types SET value = 100 WHERE rarity = 'Rare' AND name IN ('Lionfish', 'Moray Eel', 'Octopus', 'Pufferfish')");
    $pdo->exec("UPDATE fish_types SET value = 300 WHERE rarity = 'Epic' AND name IN ('Giant Clam', 'Sea Turtle', 'Reef Shark', 'Barracuda')");
    $pdo->exec("UPDATE fish_types SET value = 1000 WHERE rarity = 'Legendary' AND name IN ('Rainbow Serpent', 'Coral Guardian')");
    
    // Deep Trench fish - 4x multiplier (40-2000)
    $pdo->exec("UPDATE fish_types SET value = 40 WHERE rarity = 'Common' AND name IN ('Lanternfish', 'Hatchetfish', 'Viperfish', 'Dragonfish')");
    $pdo->exec("UPDATE fish_types SET value = 200 WHERE rarity = 'Rare' AND name IN ('Gulper Eel', 'Fangtooth', 'Anglerfish', 'Giant Isopod')");
    $pdo->exec("UPDATE fish_types SET value = 600 WHERE rarity = 'Epic' AND name IN ('Giant Squid', 'Colossal Squid', 'Oarfish', 'Frilled Shark')");
    $pdo->exec("UPDATE fish_types SET value = 2000 WHERE rarity = 'Legendary' AND name IN ('Kraken', 'Leviathan', 'Abyssal Wyrm')");
    
    // Abyssal Void fish - 8x multiplier (80-4000)
    $pdo->exec("UPDATE fish_types SET value = 80 WHERE rarity = 'Common' AND name IN ('Ghostfish', 'Void Shrimp', 'Shadow Eel', 'Phantom Ray')");
    $pdo->exec("UPDATE fish_types SET value = 400 WHERE rarity = 'Rare' AND name IN ('Void Stalker', 'Abyss Crawler', 'Dark Manta', 'Spectral Squid')");
    $pdo->exec("UPDATE fish_types SET value = 1200 WHERE rarity = 'Epic' AND name IN ('Elder Kraken', 'Void Leviathan', 'Nightmare Whale', 'Abyssal Dragon')");
    $pdo->exec("UPDATE fish_types SET value = 4000 WHERE rarity = 'Legendary' AND name IN ('Megalodon', 'Cthulhu', 'Void Emperor', 'Ancient One')");
    
    echo "Fish values updated based on location!<br>";
    echo "Sunny Coast: 1x (10-500)<br>";
    echo "Coral Reef: 2x (20-1000)<br>";
    echo "Deep Trench: 4x (40-2000)<br>";
    echo "Abyssal Void: 8x (80-4000)<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
