<?php
require 'db.php';

try {
    echo "<h1>🔄 Resetting Database...</h1>";
    
    // Disable Foreign Key Checks and Primary Key Requirement to allow rebuilds on cloud databases
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    try {
        $pdo->exec("SET SESSION sql_require_primary_key = 0");
    } catch (PDOException $e) {
        // Ignore if user lacks permissions or variable is unsupported
    }
    
    $tables = [
        'user_baits', 'user_rods', 'user_maps', 'user_stats', 'user_discoveries',
        'fish_catches', 'users', 'rods', 'baits', 'maps', 'fish_types'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "Deleted table: $table<br>";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<hr>";

    // --- 1. Users Table (Updated with Role) ---
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        coins INT DEFAULT 100,
        level INT DEFAULT 1,
        xp INT DEFAULT 0,
        is_admin BOOLEAN DEFAULT 0,
        role VARCHAR(20) DEFAULT 'user',
        is_verified BOOLEAN DEFAULT 0,
        verification_token VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Table 'users' created.<br>";

    // --- 2. Maps Table ---
    $pdo->exec("CREATE TABLE maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        unlock_cost INT DEFAULT 0,
        image VARCHAR(255) DEFAULT 'default_map.jpg'
    )");
    echo "✅ Table 'maps' created.<br>";

    // --- 3. Fish Types Table (Updated with Custom Support) ---
    $pdo->exec("CREATE TABLE fish_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        rarity VARCHAR(20) NOT NULL,
        value INT NOT NULL,
        image VARCHAR(255) DEFAULT 'default_fish.png',
        is_custom BOOLEAN DEFAULT 0,
        map_id INT,
        FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE SET NULL
    )");
    echo "✅ Table 'fish_types' created.<br>";

    // --- 4. Progression Tables ---
    $pdo->exec("CREATE TABLE fish_catches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        weight FLOAT DEFAULT 1.0,
        caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE user_discoveries (
        user_id INT NOT NULL,
        fish_type_id INT NOT NULL,
        discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, fish_type_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (fish_type_id) REFERENCES fish_types(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE user_maps (
        user_id INT NOT NULL,
        map_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, map_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE
    )");
    echo "✅ Progression tables (catches, discoveries, maps) created.<br>";

    // --- 5. Shop Tables (Rods & Baits) ---
    $pdo->exec("CREATE TABLE rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        luck_multiplier FLOAT NOT NULL,
        image VARCHAR(255) DEFAULT 'default_rod.png'
    )");
    
    $pdo->exec("CREATE TABLE user_rods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rod_id INT NOT NULL,
        is_equipped BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (rod_id) REFERENCES rods(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE baits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        price INT NOT NULL,
        rarity_boost FLOAT DEFAULT 1.0,
        image VARCHAR(255),
        description TEXT
    )");

    $pdo->exec("CREATE TABLE user_baits (
        user_id INT NOT NULL,
        bait_id INT NOT NULL,
        quantity INT DEFAULT 0,
        PRIMARY KEY (user_id, bait_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (bait_id) REFERENCES baits(id) ON DELETE CASCADE
    )");
    echo "✅ Shop tables (rods, baits) created.<br>";

    // --- 6. Stats Table ---
    $pdo->exec("CREATE TABLE user_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        total_catches INT DEFAULT 0,
        total_value_earned INT DEFAULT 0,
        rare_catches INT DEFAULT 0,
        epic_catches INT DEFAULT 0,
        legendary_catches INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'user_stats' created.<br>";

    echo "<hr>";
    
    // --- SEED DATA ---
    
    // Maps
    $pdo->exec("INSERT INTO maps (name, description, unlock_cost, image) VALUES 
        ('Sunny Coast', 'A peaceful beach perfect for beginners.', 0, 'sunny_coast.jpg'),
        ('Coral Reef', 'Vibrant underwater ecosystem with exotic fish.', 500, 'coral_reef.jpg'),
        ('Deep Trench', 'Dark waters hiding mysterious creatures.', 2000, 'deep_trench.jpg'),
        ('Abyssal Void', 'The darkest depths where legends dwell.', 5000, 'abyssal_void.jpg')
    ");
    echo "🌱 Default Maps inserted.<br>";

    // Rods
    $pdo->exec("INSERT INTO rods (name, price, luck_multiplier, image) VALUES 
        ('Bamboo Pole', 0, 1.0, 'bamboo_pole.png'),
        ('Fiberglass Rod', 500, 1.2, 'fiberglass_rod.png'),
        ('Deep Sea Destroyer', 2000, 1.5, 'deep_sea_destroyer.png'),
        ('Poseidon\'s Trident', 10000, 2.0, 'trident.png')
    ");
    echo "🌱 Default Rods inserted.<br>";

    // Baits
    $pdo->exec("INSERT INTO baits (name, price, rarity_boost, image, description) VALUES 
        ('Worm', 5, 1.0, 'worm.png', 'Basic bait. Good for small fish.'),
        ('Shrimp', 20, 1.3, 'shrimp.png', 'Fresh shrimp. Increases rare find chance.'),
        ('Squid', 50, 1.6, 'squid.png', 'Juicy squid. Great for big catches.'),
        ('Golden Lure', 200, 2.0, 'golden_lure.png', 'Shiny lure that attracts legendary fish.')
    ");
    echo "🌱 Default Baits inserted.<br>";

    echo "<hr>";
    echo "<h2 style='color: green'>✅ Database Reset Successfully!</h2>";
    echo "<p>Next Steps:</p>";
    echo "<ol>";
    echo "<li><a href='register.php'>Register a New Account</a></li>";
    echo "<li><a href='admin_setup.php'>Claim Super Admin Role</a></li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2 style='color: red'>❌ Fatal Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
