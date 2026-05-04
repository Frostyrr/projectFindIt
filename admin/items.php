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
        $type        = in_array($_POST['type']   ?? '', ['lost','found'])              ? $_POST['type']   : 'lost';
        $status      = in_array($_POST['status'] ?? '', ['active','recovered']) ? $_POST['status'] : 'active';
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
$items_found   = 0;
$table_error   = null;
$recent_reports = false;

try {
    $r = $conn->query("SELECT COUNT(id) AS c FROM items");
    if ($r) $total_reports = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE type='lost' AND status='active'");
    if ($r) $active_lost = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE status='found'");
    if ($r) $items_found = $r->fetch_assoc()['c'];

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
        LIMIT 10
    ");

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
    <link rel="stylesheet" href="../css/pagination.dashboard.css">
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

        <!-- Table card -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>List of items</h2>
            </div>

            <?php if ($table_error): ?>
                <div class="alert alert-error" style="margin:18px 26px 0">
                    <i class="fas fa-circle-exclamation"></i>
                    <strong>Database error:</strong> <?= htmlspecialchars($table_error) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Reported By</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_reports && $recent_reports->num_rows > 0): ?>
                            <?php while ($row = $recent_reports->fetch_assoc()): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><span class="item-id">#<?= $row['id'] ?></span></td>
                                <td class="td-item-name"><?= htmlspecialchars($row['item_name']) ?></td>
                                <td><?= htmlspecialchars($row['reporter_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td>
                                    <span class="badge <?= strtolower($row['type']) ?>">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= strtolower($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="action-group">
                                        <!-- View -->
                                        <a href="../item_details.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View item">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Edit — passes row data as JSON attributes -->
                                        <button type="button"
                                                class="btn-action btn-edit"
                                                title="Edit item"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>"
                                                data-desc="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>"
                                                data-location="<?= htmlspecialchars($row['location'], ENT_QUOTES) ?>"
                                                data-type="<?= $row['type'] ?>"
                                                data-status="<?= $row['status'] ?>"
                                                data-date="<?= $row['date_lost_found'] ?? '' ?>"
                                                onclick="openEdit(this)">
                                            <i class="fas fa-pen"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button type="button"
                                                class="btn-action btn-delete"
                                                title="Delete item"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>"
                                                onclick="openDelete(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="td-empty">No submissions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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