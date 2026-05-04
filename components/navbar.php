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
                    <li><a href="#" onclick="openLoginModal()">Report</a></li>
                <?php endif; ?>
                <li><a href="index.php#how-it-works">How It Works</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <ul class="nav-links">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
                            <span class="profile-name">
                                <?= htmlspecialchars($_SESSION['user']['name']) ?>
                            </span>
                            <img src="<?= htmlspecialchars($_SESSION['user']['picture']) ?>" alt="Profile" class="profile-avatar" referrerpolicy="no-referrer">
                        </a>
                        <ul class="dropdown-menu" id="profileDropdown">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li>
                                    <a href="admin/dashboard.php">
                                        <span class="material-symbols-outlined">admin_panel_settings</span>
                                        Admin Dashboard
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a href="profile.php">
                                    <span class="material-symbols-outlined">account_circle</span>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a href="feedback.php">
                                    <span class = "material-symbols-outlined">feedback</span>
                                    Feedback
                                </a>
                            </li>
                            <li>
                                <a href="auth/logout.php">
                                    <span class="material-symbols-outlined">logout</span>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="#" class="nav-login-button" onclick="openLoginModal()">Login</a>
                    </li>
                <?php endif; ?>
                    <button class="nav-hamburger" aria-label="Toggle menu" aria-expanded="false">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>
            </ul>
        </div>
    </div>
</nav>

<div class="nav-mobile-overlay" aria-hidden="true"></div>

<div class="nav-mobile-menu" aria-label="Mobile navigation" role="dialog" aria-modal="true">
    <ul class="nav-mobile-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="browse.php">Browse Items</a></li>
        <li><a href="help.php">Help Center</a></li>
        <?php if (isset($_SESSION['user'])): ?>
            <li><a href="report.php">Report Item</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="my-reports.php">My Reports</a></li>
        <?php endif; ?>
    </ul>

    <hr class="nav-mobile-divider">

    <?php if (isset($_SESSION['user'])): ?>
        <a href="logout.php" class="nav-mobile-login" style="background: #c0392b;">Log Out</a>
    <?php else: ?>
        <a href="#" onclick="openLoginModal()" class="nav-mobile-login">Log In / Sign Up</a>
    <?php endif; ?>
</div>