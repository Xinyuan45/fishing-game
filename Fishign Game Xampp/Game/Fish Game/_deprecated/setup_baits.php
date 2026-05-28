<?php
require 'db.php';

try {
    // Create baits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS baits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        luck_bonus DECIMAL(3,2) NOT NULL,
        icon VARCHAR(10) DEFAULT '🪱',
        description TEXT
    )");

    // Create user_baits table for inventory
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_baits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bait_id INT NOT NULL,
        quantity INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (bait_id) REFERENCES baits(id),
        UNIQUE KEY unique_user_bait (user_id, bait_id)
    )");

    // Add daily_free_baits tracking to daily_rewards
    $pdo->exec("ALTER TABLE daily_rewards ADD COLUMN IF NOT EXISTS free_baits_claimed_today INT DEFAULT 0");
    $pdo->exec("ALTER TABLE daily_rewards ADD COLUMN IF NOT EXISTS registration_date DATE");

    // Insert baits if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM baits");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO baits (name, price, luck_bonus, icon, description) VALUES 
            ('Worm', 0, 0.00, '🪱', 'Basic bait. Free for new users!'),
            ('Shrimp', 50, 0.10, '🦐', 'Fresh shrimp. Slightly increases luck.'),
            ('Squid', 150, 0.25, '🦑', 'Juicy squid. Moderately increases luck.'),
            ('Crab', 300, 0.40, '🦀', 'Live crab. Significantly increases luck.'),
            ('Golden Lure', 1000, 0.75, '✨', 'Legendary lure. Greatly increases luck!')
        ");
        echo "Baits inserted.<br>";
    }

    // Initialize bait inventory for all users
    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $baits = $pdo->query("SELECT id FROM baits")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($users as $userId) {
        foreach ($baits as $baitId) {
            $pdo->prepare("INSERT IGNORE INTO user_baits (user_id, bait_id, quantity) VALUES (?, ?, 0)")
                ->execute([$userId, $baitId]);
        }
        
        // Set registration date for existing users (for free bait tracking)
        $pdo->prepare("UPDATE daily_rewards SET registration_date = CURDATE() WHERE user_id = ? AND registration_date IS NULL")
            ->execute([$userId]);
    }

    echo "Bait system setup complete!";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
