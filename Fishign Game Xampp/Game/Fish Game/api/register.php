<?php
// Include the database connection file to establish a connection to the database
require 'db.php';

// Check if the form has been submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data to prevent whitespace issues
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // --- Validation Section ---
    
    // Check if the email address is in a valid format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    }
    // Check if the password and confirm password fields match
    elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    }
    // Check password strength complexity
    else {
        $passwordErrors = []; // Array to collect specific password errors

        // Rule: Password must be at least 8 characters long
        if (strlen($password) < 8) {
            $passwordErrors[] = "at least 8 characters";
        }
        // Rule: Password must contain at least one uppercase letter [A-Z]
        if (!preg_match('/[A-Z]/', $password)) {
            $passwordErrors[] = "at least one capital letter";
        }
        // Rule: Password must contain at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $passwordErrors[] = "at least one punctuation mark";
        }
        
        // If there are validation errors, construct the error message
        if (!empty($passwordErrors)) {
            $error = "Password must contain " . implode(', ', $passwordErrors) . ".";
        } else {
            // --- User Creation Section ---

            // Hash the password for safety
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Generate Verification Token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Prepare the SQL statement
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, coins, verification_token, is_verified) VALUES (?, ?, ?, 200, ?, 0)");
            try {
                // Execute the insert
                $stmt->execute([$username, $email, $passwordHash, $verificationToken]);
                
                $newUserId = $pdo->lastInsertId();
                
                // --- Initialize Items (Maps, Rods, Stats, Baits) ---
                // 1. Map
                $sunnyCoastId = $pdo->query("SELECT id FROM maps WHERE name = 'Sunny Coast'")->fetchColumn();
                if ($sunnyCoastId) {
                    $pdo->prepare("INSERT INTO user_maps (user_id, map_id) VALUES (?, ?)")->execute([$newUserId, $sunnyCoastId]);
                }
                
                // 2. Rod
                $bambooRodId = $pdo->query("SELECT id FROM rods WHERE name = 'Bamboo Pole'")->fetchColumn();
                if ($bambooRodId) {
                    $pdo->prepare("INSERT INTO user_rods (user_id, rod_id, is_equipped) VALUES (?, ?, 1)")->execute([$newUserId, $bambooRodId]);
                }
                
                // 3. Stats
                $pdo->prepare("INSERT INTO user_stats (user_id) VALUES (?)")->execute([$newUserId]);
                
                // 4. Baits
                $baits = $pdo->query("SELECT id FROM baits")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($baits as $baitId) {
                    $quantity = ($baitId == 1) ? 10 : 0; 
                    $pdo->prepare("INSERT INTO user_baits (user_id, bait_id, quantity) VALUES (?, ?, ?)")
                        ->execute([$newUserId, $baitId, $quantity]);
                }

                // --- Send Verification Email ---
                $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $verificationToken;
                
                $subject = "Verify your Deep Ocean Fishing Account";
                $message = "Hi $username,\n\nWelcome to Deep Ocean Fishing! Please click the link below to verify your account:\n\n$verifyLink\n\nHappy Fishing!";
                $headers = "From: noreply@deepoceanfishing.com";
                
                // Attempt to send email (suppressed error)
                @mail($email, $subject, $message, $headers);

                // --- LOCAL DEBUG: Log Email to File ---
                // Since actual email sending fails on localhost without SMTP, we save it to a file.
                $logContent = "
                <html>
                <head><title>Last Sent Email</title></head>
                <body style='font-family: sans-serif; padding: 20px;'>
                    <h2>[Debug] Email Sent To: $email</h2>
                    <hr>
                    <p><strong>Subject:</strong> $subject</p>
                    <pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>$message</pre>
                    <hr>
                    <p><a href='$verifyLink' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Account</a></p>
                </body>
                </html>";
                file_put_contents('last_email.html', $logContent);

                // Show Success Message
                $successMessage = "Registration successful! <br>A verification email has been sent to <strong>$email</strong>.<br><br>
                <strong>⚠️ LOCALHOST NOTICE:</strong><br>
                If you don't receive the email (common on XAMPP), check the file <strong><a href='last_email.html' target='_blank'>last_email.html</a></strong> in your project folder to see the email and click the link.";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'username') !== false) {
                    $error = "Username already exists!";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $error = "Email already exists!";
                } else {
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Deep Ocean Fishing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 text-center">
                <div class="mb-3">
                    <span class="badge bg-success fs-6 px-4 py-2">REGISTER</span>
                </div>
                <h2 class="mb-2 fw-bold text-primary-custom">🎣 Create New Account</h2>
                <h5 class="mb-4 text-muted">Join Deep Ocean Fishing</h5>

                <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if (!empty($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>

                <!-- Registration Form -->
                <form method="post">
                    <!-- Username Input Field -->
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">Username</label>
                        <!-- value="..." preserves the user's input if validation fails -->
                        <input class="form-control form-control-lg" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    
                    <!-- Email Input Field -->
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">Email</label>
                        <input class="form-control form-control-lg" type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <!-- Password Input Field -->
                    <div class="mb-3 text-start">
                        <label class="form-label text-light">Password</label>
                        <input class="form-control form-control-lg" type="password" name="password" placeholder="Choose a password" required>
                        <small class="text-muted">Must be 8+ characters with a capital letter and punctuation</small>
                    </div>
                    
                    <!-- Confirm Password Input Field -->
                    <div class="mb-4 text-start">
                        <label class="form-label text-light">Confirm Password</label>
                        <input class="form-control form-control-lg" type="password" name="confirm_password" placeholder="Re-enter your password" required>
                    </div>
                    
                    <!-- Submit Button -->
                    <button class="btn btn-success w-100 btn-lg mb-3">Create Account</button>
                </form>

                <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-primary-custom text-decoration-none">Login here</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
