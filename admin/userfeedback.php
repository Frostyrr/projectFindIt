<?php
session_start();
require_once '../db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// Save a flash message to show after redirect
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);

    try {
        $s = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        $s->bind_param("i", $feedback_id);
        $s->execute();
        $s->close();
        flash('success', "Feedback #$feedback_id deleted.");
    } catch (mysqli_sql_exception $e) {
        flash('error', "Delete failed: " . $e->getMessage());
    }

    header("Location: userfeedback.php");
    exit();
}

// Get and clear flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Search and category filter
$search    = trim($_GET['search']   ?? '');
$fcategory = $_GET['category'] ?? '';

// Build WHERE clause
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $like    = "%$search%";
    $where  .= " AND (f.subject LIKE ? OR f.message LIKE ? OR u.name LIKE ?)";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= "sss";
}
if ($fcategory) {
    $where    .= " AND f.category = ?";
    $params[]  = $fcategory;
    $types    .= "s";
}

$from = "FROM feedback f LEFT JOIN users u ON f.user_email = u.email $where";

// Fetch all feedback
$ds = $conn->prepare("SELECT f.*, u.name AS user_name $from ORDER BY f.created_at DESC");
if ($types) $ds->bind_param($types, ...$params);
$ds->execute();
$feedbacks = $ds->get_result();
$ds->close();

// Count total
$cs = $conn->prepare("SELECT COUNT(f.id) AS c $from");
if ($types) $cs->bind_param($types, ...$params);
$cs->execute();
$total = $cs->get_result()->fetch_assoc()['c'];
$cs->close();

// Summary stats (only total and avg rating)
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        ROUND(AVG(rating), 1) AS avg_rating
    FROM feedback
")->fetch_assoc();

// Star helper
function stars(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback — FindIt Admin</title>
    <link rel="icon" type="image/x-icon" href="../images/findIconWithBG.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/userfeedback.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../admin/sidebar.dashboard.php'; ?>

<div class="dashboard-main">

    <header class="dashboard-topbar">
        <span class="topbar-title">Feedback</span>
        <div class="topbar-right">
            <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            <a href="../auth/logout.php" class="topbar-logout">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <div class="dashboard-body">

        <!-- Flash message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($flash['msg']) ?>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- Stat cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Total Feedback</span>
                    <span class="stat-value"><?= $stats['total'] ?></span>
                    <span class="stat-sub">All submissions</span>
                </div>
                <div class="stat-icon green"><i class="fas fa-comment-dots"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Avg Rating</span>
                    <span class="stat-value"><?= $stats['avg_rating'] ?? '—' ?></span>
                    <span class="stat-sub">Out of 5 stars</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-star"></i></div>
            </div>
        </div>

        <!-- Feedback list -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Feedback List</h2>
                <span class="results-summary"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Search and filter -->
            <div class="filter-bar">
                <form method="GET" action="">
                    <input type="text"
                           name="search"
                           class="filter-input"
                           placeholder="Search by subject, message or user…"
                           value="<?= htmlspecialchars($search) ?>">

                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="bug"        <?= $fcategory === 'bug'        ? 'selected' : '' ?>>Bug / Technical</option>
                        <option value="suggestion" <?= $fcategory === 'suggestion' ? 'selected' : '' ?>>Suggestion</option>
                        <option value="content"    <?= $fcategory === 'content'    ? 'selected' : '' ?>>Content</option>
                        <option value="account"    <?= $fcategory === 'account'    ? 'selected' : '' ?>>Account</option>
                        <option value="other"      <?= $fcategory === 'other'      ? 'selected' : '' ?>>Other</option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-magnifying-glass"></i> Search
                    </button>

                    <?php if ($search || $fcategory): ?>
                        <a href="userfeedback.php" class="filter-reset">
                            <i class="fas fa-xmark"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Feedback cards -->
            <div class="feedback-grid">
                <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
                    <?php while ($row = $feedbacks->fetch_assoc()): ?>
                        <div class="feedback-card">

                            <!-- Subject and category badge -->
                            <div class="feedback-card-header">
                                <span class="feedback-subject"><?= htmlspecialchars($row['subject']) ?></span>
                                <span class="feedback-category <?= $row['category'] ?>">
                                    <?= ucfirst($row['category']) ?>
                                </span>
                            </div>

                            <!-- User and date -->
                            <div class="feedback-meta">
                                <span>👤 <?= htmlspecialchars($row['user_name'] ?? 'Unknown') ?></span>
                                <span>📅 <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                            </div>

                            <!-- Message -->
                            <p class="feedback-message"><?= htmlspecialchars($row['message']) ?></p>

                            <!-- Stars and delete button -->
                            <div class="feedback-footer">
                                <span class="feedback-stars">
                                    <?php if ($row['rating']): ?>
                                        <?= stars((int)$row['rating']) ?>
                                    <?php else: ?>
                                        <span class="no-rating">No rating</span>
                                    <?php endif; ?>
                                </span>
                                <button class="btn-delete-feedback"
                                        onclick="openDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['subject'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>

                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="cards-empty">
                        <i class="fas fa-comment-slash" style="font-size:32px;"></i>
                        No feedback found<?= ($search || $fcategory) ? ' matching your filters' : '' ?>.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Delete confirm modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <i class="fas fa-triangle-exclamation" style="font-size:36px; color:#f59e0b;"></i>
        <h3>Delete Feedback?</h3>
        <p id="deleteModalText">Are you sure you want to delete this feedback?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="feedback_id" id="deleteFeedbackId">
                <button type="submit" class="btn-confirm-delete">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<script src="../js/userfeedback.js"></script>
</body>
</html>
<?php $conn->close(); ?>