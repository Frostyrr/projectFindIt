<?php
/* navbar.php — included as a component on every page */
?>
<nav class="navbar">
    <div class="nav-container">

        <a href="index.php" class="nav-brand">
            <img src="images/findIcon.png" alt="FindIt logo">FindIt
        </a>

        <!-- Desktop centre links -->
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

        <!-- Desktop right: profile dropdown or login + hamburger -->
        <div class="nav-right">
            <ul class="nav-links">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
                            <span class="profile-name">
                                <?= htmlspecialchars($_SESSION['user']['name']) ?>
                            </span>
                            <img
                                src="<?= htmlspecialchars($_SESSION['user']['picture']) ?>"
                                alt="Profile photo of <?= htmlspecialchars($_SESSION['user']['name']) ?>"
                                class="profile-avatar"
                                referrerpolicy="no-referrer"
                            >
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
                                    <span class="material-symbols-outlined">feedback</span>
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

                <!-- Hamburger — visible on mobile only via CSS -->
                <li style="list-style:none; display:flex; align-items:center;">
                    <button
                        class="nav-hamburger"
                        aria-label="Toggle menu"
                        aria-expanded="false"
                        aria-controls="mobileMenu"
                    >
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>
                </li>
            </ul>
        </div>

    </div>
</nav>

<!-- Dim overlay (click to close drawer) -->
<div class="nav-mobile-overlay" aria-hidden="true"></div>

<!-- Slide-in mobile drawer -->
<nav
    class="nav-mobile-menu"
    id="mobileMenu"
    aria-label="Mobile navigation"
    role="dialog"
    aria-modal="true"
>
    <!-- Drawer header mirrors the main navbar brand -->
    <div class="nav-mobile-header">
        <a href="index.php" class="nav-mobile-brand">
            <img src="images/findIcon.png" alt="FindIt logo">FindIt
        </a>
    </div>

    <!-- Navigation links -->
    <ul class="nav-mobile-links">
        <li>
            <a href="index.php">
                <span class="material-symbols-outlined">home</span>
                Home
            </a>
        </li>
        <li>
            <a href="browse.php">
                <span class="material-symbols-outlined">grid_view</span>
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
                <span class="material-symbols-outlined">info</span>
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
    </ul>

    <hr class="nav-mobile-divider">

    <?php if (isset($_SESSION['user'])): ?>
        <ul class="nav-mobile-links" style="padding-top:4px;">
            <li>
                <a href="profile.php">
                    <span class="material-symbols-outlined">account_circle</span>
                    Profile
                </a>
            </li>
            <li>
                <a href="my-reports.php">
                    <span class="material-symbols-outlined">folder_open</span>
                    My Reports
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
        <hr class="nav-mobile-divider">
        <a href="auth/logout.php" class="nav-mobile-login" style="background: var(--red-500);">
            Log Out
        </a>
    <?php else: ?>
        <a href="#" onclick="openLoginModal(); return false;" class="nav-mobile-login">
            Log In / Sign Up
        </a>
    <?php endif; ?>
</nav>

<script>
/* Dropdown — inline so it works without navbar.js */
(function () {
    window.toggleDropdown = function (e) {
        e.preventDefault();
        e.stopPropagation();
        var menu = document.getElementById('profileDropdown');
        if (!menu) return;
        var isOpen = menu.classList.toggle('open');
        if (isOpen) {
            var close = function (ev) {
                if (!menu.contains(ev.target)) {
                    menu.classList.remove('open');
                    document.removeEventListener('click', close);
                }
            };
            document.addEventListener('click', close);
        }
    };
})();
</script>