<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="admin-sidebar">
    <a href="../index.php" class="sidebar-brand">
        <div class="sidebar-brand-icon"><img src="../images/findIcon.png" height=30px width=auto></div>
        <div>
            <div class="sidebar-brand-text">FindIt</div>
            <div class="sidebar-brand-sub">Admin Panel</div>
        </div>
    </a>
    <nav class="sidebar-nav">
        <div class="sidebar-label">Main</div>
        <a href="dashboard.php" class="sidebar-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Overview
        </a>
        <a href="items.php" class="sidebar-link <?= $current_page === 'items.php' ? 'active' : '' ?>">
            <i class="fas fa-box-open"></i> All Items
        </a>
        <a href="users.php" class="sidebar-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="userfeedback.php" class="sidebar-link <?= $current_page === 'feedback.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i> Feedbacks
        </a>

        <div class="sidebar-label" style="margin-top:10px">Content</div>
        <a href="../browse.php" class="sidebar-link" target="_blank">
            <i class="fas fa-arrow-up-right-from-square"></i> View Site
        </a>
        <a href="../report.php" class="sidebar-link <?= $current_page === 'report.php' ? 'active' : '' ?>">
            <i class="fas fa-flag"></i> Report Item
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
        </div>
    </div>
</aside>