<?php
require 'db.php';

try {
    // Add weight column to fish_catches table
    $pdo->exec("ALTER TABLE fish_catches ADD COLUMN IF NOT EXISTS weight DECIMAL(5,2) DEFAULT 1.0");
    
    echo "Weight column added to fish_catches table!<br>";
    echo "Fish prices will now be calculated based on weight.<br>";
    echo "Formula: Base Price per KG × Weight = Final Price<br>";
    echo "<br>Base Prices per KG:<br>";
    echo "- Common: 10 coins/kg<br>";
    echo "- Rare: 20 coins/kg<br>";
    echo "- Epic: 50 coins/kg<br>";
    echo "- Legendary: 150 coins/kg<br>";
    
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
