<?php
require 'db.php';

try {
    // Create maps table
    $pdo->exec("CREATE TABLE IF NOT EXISTS maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        unlock_cost INT NOT NULL,
        description TEXT,
        image VARCHAR(255) DEFAULT 'default_map.png'
    )");

    // Create user_maps table to track unlocked maps
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        map_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (map_id) REFERENCES maps(id),
        UNIQUE KEY unique_user_map (user_id, map_id)
    )");

    // Insert maps if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM maps");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO maps (name, unlock_cost, description) VALUES 
            ('Sunny Coast', 0, 'A peaceful beach perfect for beginners. Common fish abound.'),
            ('Coral Reef', 500, 'Vibrant coral formations attract exotic species.'),
            ('Deep Trench', 2000, 'Dark waters hide mysterious creatures of the deep.'),
            ('Abyssal Void', 5000, 'The darkest depths where legends dwell.')
        ");
        echo "Maps inserted.<br>";
    }

    // Give all users access to Sunny Coast (free map)
    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $sunnyCoastId = $pdo->query("SELECT id FROM maps WHERE name = 'Sunny Coast'")->fetchColumn();

    foreach ($users as $userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_maps WHERE user_id = ? AND map_id = ?");
        $stmt->execute([$userId, $sunnyCoastId]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO user_maps (user_id, map_id) VALUES (?, ?)")
                ->execute([$userId, $sunnyCoastId]);
            echo "Unlocked Sunny Coast for user $userId.<br>";
        }
    }

    echo "Maps setup complete!";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
