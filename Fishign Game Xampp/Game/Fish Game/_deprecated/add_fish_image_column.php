<?php
require 'db.php';

try {
    echo "<h2>Adding image column to fish_types...</h2>";

    // specific SQL for MariaDB/MySQL to add column if not exists
    // Note: IF NOT EXISTS for ADD COLUMN is supported in newer MariaDB versions, 
    // but for compatibility we can check information_schema or just use a try-catch for the specific error.
    
    // Check if column exists first
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fish_types' AND COLUMN_NAME = 'image'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE fish_types ADD COLUMN image VARCHAR(255) DEFAULT 'default_fish.png'");
        echo "✓ Added 'image' column to 'fish_types' table with default 'default_fish.png'<br>";
    } else {
        echo "ℹ️ 'image' column already exists in 'fish_types' table<br>";
    }

    echo "<h3 style='color: green;'>✅ Migration complete!</h3>";
    echo "<a href='index.php'>Back to Home</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
