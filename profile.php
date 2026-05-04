<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php"); 
    exit();
}

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
    // fallback: own profile
    $user = $_SESSION['user'];
}

$user_email = $user['email'];

// --- Pagination Logic ---
$limit = 6; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM items WHERE user_email = ?");
$count_stmt->bind_param("s", $user_email);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_items = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

$stmt = $conn->prepare("SELECT * FROM items WHERE user_email = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $user_email, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/home/navbar.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css">
    <link rel="stylesheet" href="css/pages-section.css">
    <link rel="stylesheet" href="css/profile/profile.css">
    <link rel="stylesheet" href="css/profile/delete-modals.css">
</head>
<body>
    
    <?php include 'components/navbar.php'; ?>
    <?php include 'auth/login.php'; ?>

    <div class="profile-container">
        <div class="profile-top-grid">
            <header class="profile-header card">
                <div class="profile-info-wrapper">
                    <img src="<?= htmlspecialchars($user['picture'] ?? 'images/default-avatar.png') ?>" alt="Profile Avatar" class="profile-avatar-large" referrerpolicy="no-referrer">
                    <div class="profile-details">
                        <h1 class="profile-name"><?= htmlspecialchars($user['name']) ?></h1>
                        <p class="profile-email"><?= htmlspecialchars($user_email) ?></p>
                        <span class="auth-badge">Connected via Google</span>
                    </div>
                </div>
            </header>

            <aside class="account-settings card">
                <h3 class="settings-title">Account Actions</h3>
                <div class="settings-actions">
                    <a href="logout.php" class="action-btn logout">Logout</a>
                    <button class="action-btn danger-btn w-full text-center" onclick="openAccountDeleteModal()">Delete Account</button>
                </div>
            </aside>
        </div>

        <section class="my-reports-section">
            <div class="section-header">
                <h2 class="section-title">My Reports</h2>
                <a href="report.php" class="btn primary-btn">+ New Report</a>
            </div>
            
            <div class="profile-reports-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="report-card">
                            <div class="card-image-container">
                                <?php if (!empty($row['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($row['image_path']) ?>" class="card-image" alt="Item Image">
                                <?php else: ?>
                                    <div class="card-image placeholder-image">No Image</div>
                                <?php endif; ?>
                                
                                <div class="card-badges">
                                    <?php if (strtolower($row['status'] ?? 'active') === 'found'): ?>
                                        <span class="badge status-badge found" style="background-color: #3d7a54;">Found</span>
                                    <?php else: ?>
                                        <span class="badge status-badge <?= strtolower($row['type']) ?>" 
                                              style="background-color: <?= strtolower($row['type']) === 'lost' ? '#d9534f' : '#3d7a54' ?>;">
                                            <?= ucfirst(htmlspecialchars($row['type'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-content">
                                <div class="card-meta">
                                    <span class="meta-item">📅 <?= !empty($row['date_lost_found']) ? date('M d, Y', strtotime($row['date_lost_found'])) : date('M d, Y', strtotime($row['created_at'])) ?></span>
                                </div>
                                
                                <h3 class="card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                                <p class="card-desc" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($row['description']) ?></p>
                                <hr class="card-divider">
                                
                                <div class="profile-card-actions">
                                    <a href="item_details.php?id=<?= $row['id'] ?>" class="p-btn view-btn">View</a>
                                    <a href="edit_report.php?id=<?= $row['id'] ?>" class="p-btn edit-btn">Edit</a>
                                    <button onclick="openItemDeleteModal(<?= $row['id'] ?>)" class="p-btn delete-btn" style="border:none; cursor:pointer;">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 12px; border: 1px solid #e2ebe6;">
                        <p style="color: #666; margin-bottom: 15px;">You haven't posted any reports yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'components/delete-item-modal.php'; ?>
    <?php include 'components/delete-account-modal.php'; ?>

    <script src="js/loginModal.js"></script>
    <script src="js/DropDown.js"></script>
    <script src="js/ProfileModals.js"></script>
</body>
</html>