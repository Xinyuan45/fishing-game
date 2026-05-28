<?php
// Database Configuration
// These variables store the connection details for the MySQL database.
$host = 'localhost';        // Database host (usually 'localhost' for local development like XAMPP)
$dbname = 'fish_test';      // The name of the database we are connecting to
$username = 'root';         // Default database username for XAMPP is 'root'
$password = '';             // Default XAMPP password is empty string (if you set a password, enter it here)

try {
    // Establish a new PDO (PHP Data Objects) connection to the database
    // DSN (Data Source Name) includes host, database name, and character set (utf8mb4 for full Unicode support)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the Error Mode to Exception
    // This ensures that any database errors throw a PDOException, which we can catch and handle gracefully.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // [DEBUG] Uncomment the line below to test if the connection is successful
    // echo "Database connected successfully!";

    // --- Session User Data Refresh ---
    // If a user is already logged in (session id exists), we freshly fetch their data from the database.
    // This ensures that if their coins or stats change (e.g., in another tab), the current page has the latest data.
    if (isset($_SESSION['user']['id'])) {
        // Prepare a statement to fetch user details by ID
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        
        // Fetch the user's data as an associative array
        $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If the user still exists in the database, update the session variable with the fresh data
        if ($freshUser) {
            $_SESSION['user'] = $freshUser;
        }
    }

} catch (PDOException $e) {
    // Catch any database connection errors
    // The script will stop execution (die) and display the error message.
    die("Database connection failed: " . $e->getMessage());
}
?>
