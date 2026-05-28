<nav class="navbar navbar-expand-lg navbar-dark bg-transparent border-bottom border-secondary border-opacity-25 mb-4">
    <div class="container">
        <!-- Brand Logo/Name -->
        <a class="navbar-brand fw-bold text-primary-custom" href="dashboard.php">🎣 Deep Ocean</a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">🏠 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="maps.php">🎣 Play</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php">🛒 Shop</a>
                </li>
                <!-- Dropdown for Collection (Inventory & Encyclopedia) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        🎒 Collection
                    </a>
                    <ul class="dropdown-menu glass-card border-0">
                        <li><a class="dropdown-item" href="myfish.php">🐟 Inventory</a></li>
                        <li><a class="dropdown-item" href="encyclopedia.php">📖 Index</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="leaderboard.php">📊 Leaderboard</a>
                </li>
            </ul>
            
            <!-- User Info Section (Right aligned) -->
            <div class="d-flex align-items-center">
                <!-- Display User Level -->
                <span class="badge bg-primary me-3">Lv <?= $_SESSION['user']['level'] ?? 1 ?></span>
                
                <!-- Display Username (linked to profile) -->
                <a href="profile.php" class="text-light me-3 text-decoration-none" style="cursor: pointer;">
                    Welcome, <?= htmlspecialchars($_SESSION['user']['username']) ?>
                </a>
                
                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
    </div>
</nav>
