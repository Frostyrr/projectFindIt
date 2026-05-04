<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// ── Determine which user's profile to show ───────────────────
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_user = $stmt->get_result();

    if ($result_user->num_rows === 0) {
        die("User not found.");
    }

    $user = $result_user->fetch_assoc();
} else {
    $user = $_SESSION['user'];
}

$user_email = $user['email'];

// ── View context flags ───────────────────────────────────────
$is_admin         = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_own_profile   = isset($_SESSION['user']) && $_SESSION['user']['email'] === $user_email;
$viewing_as_admin = $is_admin && !$is_own_profile;

// ── Pagination ───────────────────────────────────────────────
$limit  = 6;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM items WHERE user_email = ?");
$count_stmt->bind_param("s", $user_email);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

$stmt = $conn->prepare("SELECT * FROM items WHERE user_email = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $user_email, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// ── Pagination URL helper ─────────────────────────────────────
// Preserves ?id= when admin is viewing someone else
function page_url(int $p, ?int $uid): string {
    return '?' . ($uid !== null ? "id=$uid&" : '') . "page=$p";
}
$uid_param = $viewing_as_admin ? (int)$user['id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $viewing_as_admin ? htmlspecialchars($user['name']) . ' — Admin View' : 'My Profile' ?> - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/home/navbar.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css">
    <link rel="stylesheet" href="css/pages-section.css">
    <link rel="stylesheet" href="css/profile/profile.css">
    <link rel="stylesheet" href="css/profile/modals.profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <?php include 'auth/login.php'; ?>
    <?php endif; ?>

    <div class="profile-container <?= $viewing_as_admin ? 'admin-view' : '' ?>">

        <?php if ($viewing_as_admin): ?>
        <div class="admin-view-banner">
            <i class="fas fa-shield-halved"></i>
            <span>
                You are viewing this profile as an <strong>Administrator</strong>.
                <a href="admin/users.php">← Back to Users</a>
            </span>
        </div>
        <?php endif; ?>

        <div class="profile-top-grid">

            <!-- Profile header (same for all views) -->
            <header class="profile-header card">
                <div class="profile-info-wrapper">
                    <img src="<?= htmlspecialchars($user['picture'] ?? 'images/default-avatar.png') ?>"
                         alt="Profile Avatar"
                         class="profile-avatar-large"
                         referrerpolicy="no-referrer">
                    <div class="profile-details">
                        <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
                        <p class="profile-email"><?= htmlspecialchars($user_email) ?></p>
                        <span class="auth-badge">Connected via Google</span>
                    </div>
                </div>
            </header>

            <!-- Aside: switches between admin controls and owner actions -->
            <?php if ($viewing_as_admin): ?>

            <aside class="account-settings card">
                <h3 class="admin-controls-title">Admin Controls</h3>
                <ul class="admin-meta-list">
                    <li>
                        <span class="meta-label">User ID</span>
                        <span class="meta-value">#<?= (int)$user['id'] ?></span>
                    </li>
                    <li>
                        <span class="meta-label">Role</span>
                        <span class="role-badge <?= strtolower($user['role'] ?? 'user') ?>">
                            <?= ucfirst(htmlspecialchars($user['role'] ?? 'user')) ?>
                        </span>
                    </li>
                    <li>
                        <span class="meta-label">OAuth</span>
                        <span class="meta-value"><?= ucfirst(htmlspecialchars($user['oauth_provider'] ?? 'N/A')) ?></span>
                    </li>
                    <li>
                        <span class="meta-label">Total Reports</span>
                        <span class="meta-value"><?= $total_items ?></span>
                    </li>
                </ul>
                <hr class="admin-divider">
                <div class="admin-actions-group">
                    <a href="admin/users.php" class="admin-back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                    <button class="admin-delete-user-btn"
                            onclick="openProfileModal('adminDeleteUserModal')">
                        <i class="fas fa-user-xmark"></i> Delete This Account
                    </button>
                </div>
            </aside>

            <?php else: ?>

            <aside class="account-settings card">
                <h3 class="settings-title">Account Actions</h3>
                <div class="settings-actions">
                    <a href="logout.php" class="action-btn logout">Logout</a>
                    <button class="action-btn danger-btn w-full text-center"
                            onclick="openProfileModal('ownerDeleteAccountModal')">
                        Delete Account
                    </button>
                </div>
            </aside>

            <?php endif; ?>
        </div>

        <!-- Reports section -->
        <section class="my-reports-section">
            <div class="section-header">
                <h2 class="section-title">
                    <?= $viewing_as_admin
                        ? htmlspecialchars($user['name']) . "'s Reports"
                        : 'My Reports' ?>
                </h2>
                <?php if (!$viewing_as_admin): ?>
                    <a href="report.php" class="btn primary-btn">+ New Report</a>
                <?php endif; ?>
            </div>

            <div class="profile-reports-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="report-card">
                            <div class="card-image-container">
                                <?php if (!empty($row['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($row['image_path']) ?>"
                                         class="card-image" alt="Item Image">
                                <?php else: ?>
                                    <div class="card-image placeholder-image">No Image</div>
                                <?php endif; ?>
                                <div class="card-badges">
                                    <?php if (strtolower($row['status'] ?? 'active') === 'found'): ?>
                                        <span class="badge status-badge found"
                                              style="background-color:#3d7a54;">Found</span>
                                    <?php else: ?>
                                        <span class="badge status-badge <?= strtolower($row['type']) ?>"
                                              style="background-color:<?= strtolower($row['type']) === 'lost' ? '#d9534f' : '#3d7a54' ?>;">
                                            <?= ucfirst(htmlspecialchars($row['type'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-content">
                                <div class="card-meta">
                                    <span class="meta-item">
                                        📅 <?= !empty($row['date_lost_found'])
                                                ? date('M d, Y', strtotime($row['date_lost_found']))
                                                : date('M d, Y', strtotime($row['created_at'])) ?>
                                    </span>
                                </div>
                                <h3 class="card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                                <p class="card-desc"
                                   style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?= htmlspecialchars($row['description']) ?>
                                </p>
                                <hr class="card-divider">

                                <div class="profile-card-actions">
                                    <a href="item_details.php?id=<?= $row['id'] ?>"
                                       class="p-btn view-btn">View</a>

                                    <?php if (!$viewing_as_admin): ?>
                                        <!-- Owner: Edit → edit_report.php -->
                                        <a href="edit_report.php?id=<?= $row['id'] ?>"
                                           class="p-btn edit-btn">Edit</a>

                                        <!-- Owner: Delete → opens owner item modal -->
                                        <button class="p-btn delete-btn"
                                                style="border:none;cursor:pointer;"
                                                onclick="openOwnerItemDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>')">
                                            Delete
                                        </button>
                                    <?php else: ?>
                                        <!-- Admin: Delete → opens admin item modal -->
                                        <button class="p-btn delete-btn"
                                                style="border:none;cursor:pointer;"
                                                onclick="openAdminItemDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>')">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="no-results"
                         style="grid-column:1/-1;text-align:center;padding:40px;background:white;border-radius:12px;border:1px solid #e2ebe6;">
                        <p style="color:#666;margin-bottom:15px;">
                            <?= $viewing_as_admin
                                ? 'This user has not posted any reports yet.'
                                : "You haven't posted any reports yet." ?>
                        </p>
                        <?php if (!$viewing_as_admin): ?>
                            <a href="report.php" class="btn first-report">Create your first report</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= page_url($page - 1, $uid_param) ?>" class="page-link">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?= page_url($i, $uid_param) ?>"
                           class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= page_url($page + 1, $uid_param) ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>


    <!-- ════════════════════════════════════════════════════
         OWNER MODALS
    ════════════════════════════════════════════════════ -->
    <?php if (!$viewing_as_admin): ?>

    <!-- Owner: Delete Item -->
    <div id="ownerDeleteItemModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Delete Report</h3>
            <p>Are you sure you want to permanently delete
               <strong id="ownerDeleteItemName"></strong>?
               This cannot be undone.</p>
            <form method="POST" action="components/delete-item.php">
                <input type="hidden" name="item_id"  id="ownerDeleteItemId">
                <input type="hidden" name="redirect" value="profile.php">
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel"
                            onclick="closeProfileModal('ownerDeleteItemModal')">Cancel</button>
                    <button type="submit" class="modal-btn danger">Delete Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Owner: Delete Account -->
    <div id="ownerDeleteAccountModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Delete Your Account</h3>
            <p>Are you sure you want to permanently delete your account?
               All your reports will also be removed.</p>
            <p class="modal-warning">⚠ This action cannot be undone.</p>
            <form method="POST" action="delete_account.php">
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel"
                            onclick="closeProfileModal('ownerDeleteAccountModal')">Cancel</button>
                    <button type="submit" class="modal-btn danger">Delete My Account</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>


    <!-- ════════════════════════════════════════════════════
         ADMIN MODALS
    ════════════════════════════════════════════════════ -->
    <?php if ($viewing_as_admin): ?>

    <!-- Admin: Delete Item  →  admin/dashboard.php handles action=delete -->
    <div id="adminDeleteItemModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Delete Report</h3>
            <p>Permanently delete <strong id="adminDeleteItemName"></strong>?
               This cannot be undone.</p>
            <form method="POST" action="admin/dashboard.php">
                <input type="hidden" name="action"  value="delete">
                <input type="hidden" name="item_id" id="adminDeleteItemId">
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel"
                            onclick="closeProfileModal('adminDeleteItemModal')">Cancel</button>
                    <button type="submit" class="modal-btn danger">Delete Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin: Delete User  →  admin/users.php handles action=delete_user -->
    <div id="adminDeleteUserModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color:#dc2626;">Delete User Account</h3>
            <p>Permanently delete the account of
               <strong><?= htmlspecialchars($user['name']) ?></strong>?
               All their reports will also be removed.</p>
            <p class="modal-warning">⚠ This action cannot be undone.</p>
            <form method="POST" action="admin/users.php">
                <input type="hidden" name="action"  value="delete_user">
                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel"
                            onclick="closeProfileModal('adminDeleteUserModal')">Cancel</button>
                    <button type="submit" class="modal-btn danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>


    <!-- ════════════════════════════════════════════════════
         SCRIPTS
    ════════════════════════════════════════════════════ -->
    <script src="js/ProfileModals.js"></script>

    <script src="js/loginModal.js"></script>
    <script src="js/DropDown.js"></script>
</body>
</html>