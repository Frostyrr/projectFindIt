<?php
session_start();
require_once '../db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Security check ───────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// ════════════════════════════════════════════════════════════
//  POST HANDLERS — always redirect after POST (PRG pattern)
//  so refreshing the page never re-submits a form
// ════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);

    // ── DELETE ───────────────────────────────────────────────
    if ($action === 'delete' && $item_id > 0) {
        try {
            // Grab image path before deleting the row
            $s = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $img = $s->get_result()->fetch_assoc();
            $s->close();

            // Remove uploaded image from disk if it exists
            if (!empty($img['image_path'])) {
                $full_path = '../' . ltrim($img['image_path'], '/');
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }

            $s = $conn->prepare("DELETE FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $s->close();

            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Item #$item_id has been deleted."];
        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Delete failed: " . $e->getMessage()];
        }

        header("Location: dashboard.php");
        exit();
    }

    // ── EDIT ─────────────────────────────────────────────────
    if ($action === 'edit' && $item_id > 0) {
        $item_name   = trim($_POST['item_name']    ?? '');
        $description = trim($_POST['description']  ?? '');
        $location    = trim($_POST['location']     ?? '');
        $type        = in_array($_POST['type']   ?? '', ['lost','found']) ? $_POST['type']   : 'lost';
        $status_raw = trim($_POST['status'] ?? '');
        $status = in_array($status_raw, ['active', 'claimed', 'resolved']) ? $status_raw : 'active';
        $date_lf     = !empty($_POST['date_lost_found']) ? $_POST['date_lost_found'] : null;

        if ($item_name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Item name cannot be empty."];
            header("Location: dashboard.php");
            exit();
        }

        try {
            $s = $conn->prepare("
                UPDATE items
                   SET item_name       = ?,
                       description     = ?,
                       location        = ?,
                       type            = ?,
                       status          = ?,
                       date_lost_found = ?
                 WHERE id = ?
            ");
            $s->bind_param("ssssssi",
                $item_name, $description, $location,
                $type, $status, $date_lf, $item_id
            );
            $s->execute();
            $s->close();

            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Item #$item_id updated successfully."];
        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Update failed: " . $e->getMessage()];
        }

        header("Location: dashboard.php");
        exit();
    }
}

// ════════════════════════════════════════════════════════════
//  FLASH MESSAGE (set by the redirect above)
// ════════════════════════════════════════════════════════════
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ════════════════════════════════════════════════════════════
//  FETCH DATA
// ════════════════════════════════════════════════════════════
$total_reports = 0;
$active_lost   = 0;
$items_claimed   = 0;
$table_error   = null;
$recent_reports = false;

try {
    $r = $conn->query("SELECT COUNT(id) AS c FROM items");
    if ($r) $total_reports = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE type='lost' AND status='active'");
    if ($r) $active_lost = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE status='found'");
    if ($r) $items_found = $r->fetch_assoc()['c'];
    
    $r = $conn->query("SELECT COUNT(id) AS c FROM feedback");
    if ($r) $total_feedback = $r->fetch_assoc()['c'];
    
    $r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE status='claimed'");
    if ($r) $items_claimed = $r->fetch_assoc()['c'];

    $recent_reports = $conn->query("
        SELECT  i.id,
                i.item_name,
                i.description,
                i.location,
                i.type,
                i.status,
                i.date_lost_found,
                i.created_at,
                u.name AS reporter_name
        FROM    items i
        LEFT JOIN users u ON i.user_email = u.email
        ORDER BY i.created_at DESC
        LIMIT 20
    ");

} catch (mysqli_sql_exception $e) {
    $table_error = $e->getMessage();
}

$totalUsers  = 0;

try {
    $result     = $conn->query("SELECT * FROM users ORDER BY id DESC");
    $totalUsers = $result->num_rows;
} catch (mysqli_sql_exception $e) {
    $table_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — FindIt</title>
    <link rel="icon" type="image/x-icon" href="../images/findIconWithBG.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════ -->
<?php include '../admin/sidebar.dashboard.php'?>

<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════ -->
<div class="dashboard-main">

    <!-- Top bar -->
    <header class="dashboard-topbar">
        <span class="topbar-title">System Overview</span>
        <div class="topbar-right">
            <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            <a href="../logout.php" class="topbar-logout">
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
                    <span class="stat-label">Total Reports</span>
                    <span class="stat-value"><?= $total_reports ?></span>
                    <span class="stat-sub">All time submissions</span>
                </div>
                <div class="stat-icon green"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Active Lost Items</span>
                    <span class="stat-value"><?= $active_lost ?></span>
                    <span class="stat-sub">Awaiting recovery</span>
                </div>
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Claimed Items</span>
                    <span class="stat-value"><?= $items_claimed ?></span>
                    <span class="stat-sub">Successfully recovered</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value"><?= $totalUsers ?></span>
                    <span class="stat-sub">All registered accounts</span>
                </div>
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Total Feedback</span>
                    <span class="stat-value"><?= $total_feedback ?></span>
                    <span class="stat-sub">All user submissions</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-comment-dots"></i></div>
            </div>
        </div>

        <!-- Table card -->
        <?php include '../admin/recent-reports.table.php'?>
        <!-- Table card END -->
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════ -->
<?php include '../admin/edit-modal.dashboard.php'?>

<!-- ══════════════════════════════════════════════════════════
     DELETE MODAL
══════════════════════════════════════════════════════════ -->
<?php include '../admin/delete-modal.dashboard.php'?>

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script src="../js/AdminDashboard.js">
</script>
</body>
</html>
<?php $conn->close(); ?>