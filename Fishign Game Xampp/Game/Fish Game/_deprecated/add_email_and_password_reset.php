<?php
require 'db.php';

try {
    echo "<h2>Adding email support and password reset functionality...</h2>";
    
    // Add email column to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE");
    echo "✓ Email column added to users table<br>";
    
    // Create password_resets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✓ Password resets table created<br>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Database migration complete!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='register.php'>Register with email support</a></li>";
    echo "<li><a href='login.php'>Login</a></li>";
    echo "<li><a href='forgot_password.php'>Test forgot password</a></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
