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
        $status      = in_array($_POST['status'] ?? '', ['active','found','resolved']) ? $_POST['status'] : 'active';
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
        LIMIT 20
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════ -->
<aside class="admin-sidebar">
    <a href="../index.php" class="sidebar-brand">
        <div class="sidebar-brand-icon">🔍</div>
        <div>
            <div class="sidebar-brand-text">FindIt</div>
            <div class="sidebar-brand-sub">Admin Panel</div>
        </div>
    </a>
    <nav class="sidebar-nav">
        <div class="sidebar-label">Main</div>
        <a href="dashboard.php" class="sidebar-link active"><i class="fas fa-chart-pie"></i> Overview</a>
        <a href="items.php"     class="sidebar-link"><i class="fas fa-box-open"></i> All Items</a>
        <a href="users.php"     class="sidebar-link"><i class="fas fa-users"></i> Users</a>
        <div class="sidebar-label" style="margin-top:10px">Content</div>
        <a href="../browse.php" class="sidebar-link" target="_blank"><i class="fas fa-arrow-up-right-from-square"></i> View Site</a>
        <a href="../report.php" class="sidebar-link"><i class="fas fa-flag"></i> Report Item</a>
        <div class="sidebar-label" style="margin-top:10px">Settings</div>
        <a href="settings.php"  class="sidebar-link"><i class="fas fa-gear"></i> Settings</a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
                <span class="sidebar-user-role">Administrator</span>
            </div>
        </div>
    </div>
</aside>

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
                    <span class="stat-label">Items Found</span>
                    <span class="stat-value"><?= $items_found ?></span>
                    <span class="stat-sub">Successfully recovered</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-circle-check"></i></div>
            </div>
        </div>

        <!-- Table card -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Recent Submissions</h2>
                <span>Latest 20 reports</span>
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
<div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal">
        <div class="modal-header">
            <h3 id="editModalTitle"><i class="fas fa-pen"></i> Edit Item <span id="edit_id_label"></span></h3>
            <button class="modal-close" onclick="closeModal('editModal')" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="dashboard.php" id="editForm">
            <input type="hidden" name="action"  value="edit">
            <input type="hidden" name="item_id" id="edit_item_id">

            <div class="modal-body">

                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label for="edit_item_name">Item Name <span class="req">*</span></label>
                        <input type="text" id="edit_item_name" name="item_name"
                               placeholder="e.g. Brown Leather Backpack" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_location">Location <span class="req">*</span></label>
                        <input type="text" id="edit_location" name="location"
                               placeholder="e.g. Gaisano Mall" required>
                    </div>
                </div>

                <div class="modal-form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                              placeholder="Colour, size, brand, distinguishing features…"></textarea>
                </div>

                <div class="modal-form-row three-col">
                    <div class="modal-form-group">
                        <label for="edit_type">Type</label>
                        <select id="edit_type" name="type">
                            <option value="lost">Lost</option>
                            <option value="found">Found</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="found">Found</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label for="edit_date">Date Lost / Found</label>
                        <input type="date" id="edit_date" name="date_lost_found">
                    </div>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer">
                <button type="button" class="modal-btn-cancel" onclick="closeModal('editModal')">
                    Cancel
                </button>
                <button type="submit" class="modal-btn-primary">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     DELETE MODAL
══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 id="deleteModalTitle"><i class="fas fa-trash"></i> Confirm Delete</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="dashboard.php" id="deleteForm">
            <input type="hidden" name="action"  value="delete">
            <input type="hidden" name="item_id" id="delete_item_id">

            <div class="modal-body">
                <div class="delete-warning-icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <p class="delete-warning">
                    You are about to permanently delete:<br>
                    <strong id="delete_item_name"></strong><br>
                    <span>This will also remove the uploaded image. This cannot be undone.</span>
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn-cancel" onclick="closeModal('deleteModal')">
                    Cancel
                </button>
                <button type="submit" class="modal-btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script src="../js/AdminDashboard.js">
</script>
</body>
</html>
<?php $conn->close(); ?>