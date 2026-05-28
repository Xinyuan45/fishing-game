<?php
require 'db.php';

try {
    // Add level and xp to users table if not exists
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS level INT DEFAULT 1");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS xp INT DEFAULT 0");
    
    // Create daily_rewards table
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        last_claim_date DATE,
        streak_days INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create achievements table
    $pdo->exec("CREATE TABLE IF NOT EXISTS achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        icon VARCHAR(50),
        requirement_type VARCHAR(50),
        requirement_value INT
    )");

    // Create user_achievements table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_id INT NOT NULL,
        progress INT DEFAULT 0,
        unlocked_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (achievement_id) REFERENCES achievements(id),
        UNIQUE KEY unique_user_achievement (user_id, achievement_id)
    )");

    // Create user_stats table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        total_catches INT DEFAULT 0,
        total_value_earned INT DEFAULT 0,
        rare_catches INT DEFAULT 0,
        epic_catches INT DEFAULT 0,
        legendary_catches INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create aquarium_fish table
    $pdo->exec("CREATE TABLE IF NOT EXISTS aquarium_fish (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        is_displayed BOOLEAN DEFAULT TRUE,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id),
        UNIQUE KEY unique_user_aquarium_fish (user_id, fish_type_id)
    )");

    // Insert achievements if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM achievements");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO achievements (name, description, icon, requirement_type, requirement_value) VALUES 
            ('First Catch', 'Catch your first fish', '🎣', 'total_catches', 1),
            ('Collector I', 'Catch 10 fish', '🐟', 'total_catches', 10),
            ('Collector II', 'Catch 50 fish', '🐠', 'total_catches', 50),
            ('Collector III', 'Catch 100 fish', '🦈', 'total_catches', 100),
            ('Rare Hunter', 'Catch 10 Rare fish', '💎', 'rare_catches', 10),
            ('Epic Master', 'Catch 5 Epic fish', '⚡', 'epic_catches', 5),
            ('Legend', 'Catch 1 Legendary fish', '👑', 'legendary_catches', 1),
            ('Map Explorer', 'Unlock all maps', '🗺️', 'maps_unlocked', 4),
            ('Wealthy', 'Earn 10,000 coins total', '💰', 'total_value_earned', 10000),
            ('Rod Collector', 'Own all rods', '🎣', 'rods_owned', 4)
        ");
        echo "Achievements inserted.<br>";
    }

    // Initialize stats for existing users
    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($users as $userId) {
        // Daily rewards
        $stmt = $pdo->prepare("INSERT IGNORE INTO daily_rewards (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        // User stats
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_stats (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        // Initialize achievement progress
        $achievements = $pdo->query("SELECT id FROM achievements")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($achievements as $achId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->execute([$userId, $achId]);
        }
    }

    echo "Engagement features setup complete!";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
