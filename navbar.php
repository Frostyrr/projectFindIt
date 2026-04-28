<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <img src="images/findIcon.png">FindIt
        </a>

        <div class="nav-center">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                <li><a href="index.php#recent-reports">Recent Reports</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="report.php">Report</a></li>
                <?php else: ?>
                    <li><a href="#" onclick="openModal()">Report</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="nav-right">
            <ul class="nav-links">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
                                <span class="profile-name">
                                    <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                                </span>
                            <img src="<?php echo htmlspecialchars($_SESSION['user']['picture']); ?>" alt="Profile" class="profile-avatar" referrerpolicy="no-referrer">
                        </a>
                        <ul class="dropdown-menu" id="profileDropdown">
                            <li>
                                <a href="profile.php">
                                    <span class="material-symbols-outlined">account_circle</span>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a href="logout.php">
                                    <span class="material-symbols-outlined">logout</span>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="#" class="nav-login-button" onclick="openModal()">
                            Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>