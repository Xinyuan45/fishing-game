<?php
require 'db.php';

try {
    echo "<h2>Updating Map Names...</h2>";
    
    // Fix Map 3
    $pdo->prepare("UPDATE maps SET name = 'Deep Trench' WHERE id = 3")->execute();
    echo "✓ Updated Map 3 to 'Deep Trench'<br>";
    
    // Fix Map 4
    $pdo->prepare("UPDATE maps SET name = 'Abyssal Void' WHERE id = 4")->execute();
    echo "✓ Updated Map 4 to 'Abyssal Void'<br>";
    
    echo "<h3>✅ Maps Updated!</h3>";
    echo "<a href='index.php'>Back to Game</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
