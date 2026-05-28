<?php
require 'db.php';

try {
    echo "<h2>Setting up fish_test database...</h2>";
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        coins INT DEFAULT 100,
        level INT DEFAULT 1,
        xp INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Users table created<br>";
    
    // Create fish_types table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fish_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        rarity VARCHAR(20) NOT NULL,
        value INT NOT NULL
    )");
    echo "✓ Fish types table created<br>";
    
    // Create fish_catches table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fish_catches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        weight FLOAT DEFAULT 1.0,
        caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id)
    )");
    echo "✓ Fish catches table created<br>";
    
    // Create user_discoveries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_discoveries (
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, fish_type_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id)
    )");
    echo "✓ User discoveries table created<br>";
    
    // Create maps table
    $pdo->exec("CREATE TABLE IF NOT EXISTS maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        unlock_cost INT DEFAULT 0,
        image VARCHAR(255)
    )");
    echo "✓ Maps table created<br>";
    
    // Create user_maps table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_maps (
        user_id INT NOT NULL,
        map_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, map_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (map_id) REFERENCES maps(id)
    )");
    echo "✓ User maps table created<br>";
    
    // Create rods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        luck_multiplier FLOAT NOT NULL,
        image VARCHAR(255) DEFAULT 'default_rod.png'
    )");
    echo "✓ Rods table created<br>";
    
    // Create user_rods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rod_id INT NOT NULL,
        is_equipped BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (rod_id) REFERENCES rods(id)
    )");
    echo "✓ User rods table created<br>";
    
    // Create baits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS baits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        rarity_boost FLOAT DEFAULT 1.0,
        image VARCHAR(255)
    )");
    echo "✓ Baits table created<br>";
    
    // Create user_baits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_baits (
        user_id INT NOT NULL,
        bait_id INT NOT NULL,
        quantity INT DEFAULT 0,
        PRIMARY KEY (user_id, bait_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (bait_id) REFERENCES baits(id)
    )");
    echo "✓ User baits table created<br>";
    
    // Create user_stats table (simplified - no achievements)
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
    echo "✓ User stats table created<br>";
    
    echo "<hr>";
    
    // Insert default maps if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM maps");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO maps (name, description, unlock_cost, image) VALUES 
            ('Sunny Coast', 'A peaceful beach perfect for beginners', 0, 'sunny_coast.jpg'),
            ('Coral Reef', 'Vibrant underwater ecosystem', 500, 'coral_reef.jpg'),
            ('Deep Abyss', 'Dark waters hiding rare creatures', 2000, 'deep_abyss.jpg'),
            ('Arctic Waters', 'Frozen seas with legendary fish', 5000, 'arctic_waters.jpg')
        ");
        echo "✓ Default maps inserted<br>";
    }
    
    // Insert default rods if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM rods");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO rods (name, price, luck_multiplier, image) VALUES 
            ('Bamboo Pole', 0, 1.0, 'bamboo_pole.png'),
            ('Fiberglass Rod', 500, 1.2, 'fiberglass_rod.png'),
            ('Deep Sea Destroyer', 2000, 1.5, 'deep_sea_destroyer.png'),
            ('Poseidon''s Trident', 10000, 2.0, 'trident.png')
        ");
        echo "✓ Default rods inserted<br>";
    }
    
    // Insert default baits if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM baits");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO baits (name, price, rarity_boost, image) VALUES 
            ('Worm', 5, 1.0, 'worm.png'),
            ('Shrimp', 20, 1.3, 'shrimp.png'),
            ('Squid', 50, 1.6, 'squid.png'),
            ('Golden Lure', 200, 2.0, 'golden_lure.png')
        ");
        echo "✓ Default baits inserted<br>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Database setup complete for fish_test!</h3>";
    echo "<p>All tables created successfully. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='register.php'>Register a new account</a></li>";
    echo "<li><a href='login.php'>Login</a></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
