<?php
/* navbar.php — included as a component on every page */
?>
<nav class="navbar" id="mainNavbar">
    <div class="nav-container">

        <a href="index.php" class="nav-brand">
            <img src="images/findIcon.png" alt="FindIt logo">FindIt
        </a>

        <!-- Desktop centre links -->
        <div class="nav-center">
            <ul class="nav-links">
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="browse.php" class="<?= basename($_SERVER['PHP_SELF']) == 'browse.php' ? 'active' : '' ?>">Browse</a></li>
                <li><a href="index.php#recent-reports">Recent Reports</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="report.php" class="<?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">Report</a></li>
                <?php else: ?>
                    <li><a href="#" onclick="openLoginModal()">Report</a></li>
                <?php endif; ?>
                <li><a href="index.php#how-it-works">How It Works</a></li>
            </ul>
        </div>

        <!-- Desktop right: profile dropdown or login button -->
        <div class="nav-right">
            <?php if (isset($_SESSION['user'])): ?>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
                        <span class="profile-name">
                            <?= htmlspecialchars(strtok($_SESSION['user']['name'], " ")) ?>
                        </span>
                        <img
                            src="<?= htmlspecialchars($_SESSION['user']['picture']) ?>"
                            alt="Profile photo of <?= htmlspecialchars($_SESSION['user']['name']) ?>"
                            class="profile-avatar"
                            referrerpolicy="no-referrer"
                        >
                    </a>
                    <ul class="dropdown-menu" id="profile-dropdown-menu">
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
                                <span class="material-symbols-outlined">feedback</span>
                                Feedback
                            </a>
                        </li>
                        <li class="dropdown-divider"></li>
                        <li>
                            <a href="auth/logout.php">
                                <span class="material-symbols-outlined">logout</span>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="#" class="nav-login-button" onclick="openLoginModal()">Login</a>
            <?php endif; ?>

            <!-- Hamburger button (mobile only) -->
            <button class="nav-hamburger" id="hamburgerBtn" aria-label="Open menu" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>

    </div>
</nav>

<!-- Overlay backdrop -->
<div class="nav-overlay" id="navOverlay"></div>

<!-- Mobile slide-in menu -->
<nav class="nav-drawer" id="navDrawer" aria-label="Mobile menu">
    <div class="nav-drawer-header">
        <h2>Menu</h2>
        <button class="nav-drawer-close" id="closeDrawerBtn" aria-label="Close menu">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <?php if (isset($_SESSION['user'])): ?>
        <div class="nav-drawer-user">
            <img src="<?= htmlspecialchars($_SESSION['user']['picture']) ?>" 
                 alt="<?= htmlspecialchars($_SESSION['user']['name']) ?>" 
                 class="nav-drawer-avatar"
                 referrerpolicy="no-referrer">
            <div>
                <p class="nav-drawer-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
                <p class="nav-drawer-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>
        </div>
    <?php endif; ?>
    <hr class="nav-drawer-divider">

        <?php if (isset($_SESSION['user'])): ?>
        <ul class="nav-drawer-links">
            <li>
                <a href="profile.php">
                    <span class="material-symbols-outlined">person</span>
                    Profile
                </a>
            </li>
            <li>
                <a href="feedback.php">
                    <span class="material-symbols-outlined">feedback</span>
                    Feedback
                </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href="admin/dashboard.php">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        Admin Dashboard
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
    <hr class="nav-drawer-divider">

    <ul class="nav-drawer-links">
        <li>
            <a href="index.php">
                <span class="material-symbols-outlined">home</span>
                Home
            </a>
        </li>
        <li>
            <a href="browse.php">
                <span class="material-symbols-outlined">search</span>
                Browse Items
            </a>
        </li>
        <li>
            <a href="index.php#recent-reports">
                <span class="material-symbols-outlined">history</span>
                Recent Reports
            </a>
        </li>
        <li>
            <a href="index.php#how-it-works">
                <span class="material-symbols-outlined">help_outline</span>
                How It Works
            </a>
        </li>
        <?php if (isset($_SESSION['user'])): ?>
            <li>
                <a href="report.php">
                    <span class="material-symbols-outlined">add_circle</span>
                    Report Item
                </a>
            </li>
        <?php endif; ?>
        <hr class="nav-drawer-divider">
        <li>
            <a href="auth/logout.php" class="logout-link">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </a>
        </li>
    </ul>
    <?php if (!isset($_SESSION['user'])): ?>
        <div class="nav-drawer-login-section">
            <p>Join our community to report and find lost items</p>
            <a href="#" class="nav-drawer-login-btn" onclick="openLoginModal(); closeDrawer();">Log In / Sign Up</a>
        </div>
    <?php endif; ?>
</nav>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav" id="bottomNav" aria-label="Quick navigation">
    <a href="index.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">home</span>
        <span class="mobile-nav-label">Home</span>
    </a>
    <a href="browse.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) == 'browse.php' ? 'active' : '' ?>">
        <span class="material-symbols-outlined">search</span>
        <span class="mobile-nav-label">Browse</span>
    </a>
    <?php if (isset($_SESSION['user'])): ?>
        <a href="report.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">add_circle</span>
            <span class="mobile-nav-label">Report</span>
        </a>
    <?php else: ?>
        <a href="#" class="mobile-nav-item" onclick="openLoginModal()">
            <span class="material-symbols-outlined">add_circle</span>
            <span class="mobile-nav-label">Report</span>
        </a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user'])): ?>
        <a href="profile.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">person</span>
            <span class="mobile-nav-label">Profile</span>
        </a>
    <?php else: ?>
        <a href="#" class="mobile-nav-item" onclick="openLoginModal()">
            <span class="material-symbols-outlined">login</span>
            <span class="mobile-nav-label">Login</span>
        </a>
    <?php endif; ?>
</nav>