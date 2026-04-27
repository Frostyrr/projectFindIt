<nav class="navbar">
    <div class="nav-container">
        <a href="home.php" class="nav-brand">
            <img src="images/findIcon.png">FindIt
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#recent-reports">Recent Reports</a></li>
                
            <?php if (isset($_SESSION['user'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="#" onclick="openModal()">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>