<?php
require 'db.php';

try {
    // 1. Create user_discoveries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_discoveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        first_caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id),
        UNIQUE KEY unique_discovery (user_id, fish_type_id)
    )");
    echo "Table 'user_discoveries' created successfully.<br>";

    // 2. Backfill from existing fish_catches
    // We select distinct user_id and fish_type_id from fish_catches
    // and insert them into user_discoveries if they don't exist.
    $sql = "
        INSERT IGNORE INTO user_discoveries (user_id, fish_type_id, first_caught_at)
        SELECT user_id, fish_type_id, MIN(caught_at)
        FROM fish_catches
        GROUP BY user_id, fish_type_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Backfilled " . $stmt->rowCount() . " discoveries from existing inventory.<br>";
    echo "Migration complete!";

} catch (PDOException $e) {
    die("Migration Error: " . $e->getMessage());
}
?>
