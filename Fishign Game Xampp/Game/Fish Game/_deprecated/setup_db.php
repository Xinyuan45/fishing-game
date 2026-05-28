<?php
require 'db.php';

try {
    // Create rods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        luck_multiplier FLOAT NOT NULL,
        image VARCHAR(255) DEFAULT 'default_rod.png'
    )");

    // Create user_rods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rod_id INT NOT NULL,
        is_equipped BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (rod_id) REFERENCES rods(id)
    )");

    // Insert default rods if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM rods");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO rods (name, price, luck_multiplier, image) VALUES 
            ('Bamboo Pole', 0, 1.0, 'bamboo_pole.png'),
            ('Fiberglass Rod', 500, 1.2, 'fiberglass_rod.png'),
            ('Deep Sea Destroyer', 2000, 1.5, 'deep_sea_destroyer.png'),
            ('Poseidon\'s Trident', 10000, 2.0, 'trident.png')
        ");
        echo "Rods inserted.<br>";
    }

    // Give existing users the default rod
    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $defaultRodId = $pdo->query("SELECT id FROM rods WHERE name = 'Bamboo Pole'")->fetchColumn();

    foreach ($users as $userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_rods WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO user_rods (user_id, rod_id, is_equipped) VALUES (?, ?, 1)")
                ->execute([$userId, $defaultRodId]);
            echo "Given default rod to user $userId.<br>";
        }
    }

    echo "Database setup complete for Fishing Game 2.0!";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
