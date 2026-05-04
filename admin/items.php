<?php
session_start();
require_once '../db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Only admins can access this page ──────────────────────────
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// ── Save a flash message to show after redirect ───────────────
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// ── Handle form submissions (delete / edit) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);

    try {

        // DELETE
        if ($action === 'delete' && $item_id > 0) {
            // Get the image path so we can delete the file too
            $s = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $img = $s->get_result()->fetch_assoc()['image_path'] ?? '';
            $s->close();

            // Delete the image file if it exists
            if ($img && file_exists("../$img")) {
                unlink("../$img");
            }

            // Delete the item from the database
            $s = $conn->prepare("DELETE FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $s->close();

            flash('success', "Item #$item_id deleted.");
        }

        // EDIT
        if ($action === 'edit' && $item_id > 0) {
            $name   = trim($_POST['item_name']   ?? '');
            $desc   = trim($_POST['description'] ?? '');
            $loc    = trim($_POST['location']    ?? '');
            $type   = in_array($_POST['type']   ?? '', ['lost', 'found'])             ? $_POST['type']   : 'lost';
            $status = in_array($_POST['status'] ?? '', ['active', 'found','resolved']) ? $_POST['status'] : 'active';
            $date   = $_POST['date_lost_found'] ?: null;

            if (!$name) {
                flash('error', "Item name cannot be empty.");
            } else {
                $s = $conn->prepare("
                    UPDATE items
                    SET item_name=?, description=?, location=?, type=?, status=?, date_lost_found=?
                    WHERE id=?
                ");
                $s->bind_param("ssssssi", $name, $desc, $loc, $type, $status, $date, $item_id);
                $s->execute();
                $s->close();
                flash('success', "Item #$item_id updated.");
            }
        }

    } catch (mysqli_sql_exception $e) {
        flash('error', "Something went wrong: " . $e->getMessage());
    }

    header("Location: items.php");
    exit();
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

// ── Get and clear the flash message ──────────────────────────
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Search and filter values from the URL ────────────────────
$search = trim($_GET['search'] ?? '');
$ftype  = $_GET['type']        ?? '';
$fstat  = $_GET['status']      ?? '';

// ── Build the WHERE clause based on filters ───────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $like    = "%$search%";
    $where  .= " AND (i.item_name LIKE ? OR i.location LIKE ? OR u.name LIKE ?)";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= "sss";
}
if ($ftype) {
    $where    .= " AND i.type = ?";
    $params[]  = $ftype;
    $types    .= "s";
}
if ($fstat) {
    $where    .= " AND i.status = ?";
    $params[]  = $fstat;
    $types    .= "s";
}

// ── Shared FROM clause used by both queries ───────────────────
$from = "FROM items i LEFT JOIN users u ON i.user_email = u.email $where";

// ── Count total matching items ────────────────────────────────
$cs = $conn->prepare("SELECT COUNT(i.id) AS c $from");
if ($types) $cs->bind_param($types, ...$params);
$cs->execute();
$total_items = $cs->get_result()->fetch_assoc()['c'];
$cs->close();

// ── Fetch all matching items ──────────────────────────────────
$ds = $conn->prepare("SELECT i.*, u.name AS reporter_name $from ORDER BY i.created_at DESC");
if ($types) $ds->bind_param($types, ...$params);
$ds->execute();
$items = $ds->get_result();
$ds->close();

// ── Fetch summary stats ───────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(type='lost' AND status='active') AS active_lost,
        SUM(status='found') AS recovered
    FROM items
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items</title>
    <link rel="icon" type="image/x-icon" href="../images/findIconWithBG.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/items.css">
</head>
<body>

<?php include '../admin/sidebar.dashboard.php'; ?>

<div class="dashboard-main">

    <header class="dashboard-topbar">
        <span class="topbar-title">All Items</span>
        <div class="topbar-right">
            <span class="topbar-date"><?= date('l, F j, Y') ?></span>
            <a href="../auth/logout.php" class="topbar-logout">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <div class="dashboard-body">
        <!-- Flash message (success or error) -->
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
                    <span class="stat-label">Total Items</span>
                    <span class="stat-value"><?= $stats['total'] ?></span>
                    <span class="stat-sub">All submissions</span>
                </div>
                <div class="stat-icon green"><i class="fas fa-box-open"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Active Lost</span>
                    <span class="stat-value"><?= $stats['active_lost'] ?></span>
                    <span class="stat-sub">Awaiting recovery</span>
                </div>
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Recovered</span>
                    <span class="stat-value"><?= $stats['recovered'] ?></span>
                    <span class="stat-sub">Successfully found</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-circle-check"></i></div>
            </div>
        </div>

        <!-- Items list -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Items List</h2>
                <span class="results-summary"><?= $total_items ?> result<?= $total_items !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Search and filter form -->
            <div class="filter-bar">
                <form method="GET" action="">
                    <input type="text"
                           name="search"
                           class="filter-input"
                           placeholder="Search by item, location or reporter…"
                           value="<?= htmlspecialchars($search) ?>">

                    <select name="type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="lost"  <?= $ftype === 'lost'  ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $ftype === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>

                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="active"   <?= $fstat === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="found"    <?= $fstat === 'found'    ? 'selected' : '' ?>>Found</option>
                        <option value="resolved" <?= $fstat === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-magnifying-glass"></i> Search
                    </button>

                    <?php if ($search || $ftype || $fstat): ?>
                        <a href="items.php" class="filter-reset">
                            <i class="fas fa-xmark"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Item cards -->
            <div class="items-grid">
                <?php if ($items && $items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <div class="item-card" id="row-<?= $row['id'] ?>">

                            <!-- Image or placeholder -->
                            <div class="item-card-image">
                                <?php if (!empty($row['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($row['image_path']) ?>"
                                         alt="<?= htmlspecialchars($row['item_name']) ?>">
                                <?php else: ?>
                                    <div class="item-card-placeholder">No Image</div>
                                <?php endif; ?>

                                <!-- Lost/Found and status badges -->
                                <div class="item-card-badges">
                                    <span class="badge <?= strtolower($row['type']) ?>">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                    <span class="badge <?= strtolower($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Card details -->
                            <div class="item-card-body">
                                <span class="item-card-meta">📅 <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                <h3 class="item-card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                                <span class="item-card-reporter">👤 <?= htmlspecialchars($row['reporter_name'] ?? 'Unknown') ?></span>
                                <p class="item-card-desc"><?= htmlspecialchars($row['description'] ?? '') ?></p>

                                <hr class="item-card-divider">

                                <div class="item-card-footer">
                                    <span class="item-card-location">📍 <?= htmlspecialchars($row['location']) ?></span>

                                    <!-- Action buttons: View, Edit, Delete -->
                                    <div class="item-card-actions">
                                        <a href="../item_details.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button"
                                                class="btn-action btn-edit"
                                                title="Edit"
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
                                        <button type="button"
                                                class="btn-action btn-delete"
                                                title="Delete"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>"
                                                onclick="openDelete(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="cards-empty">
                        <i class="fas fa-box-open" style="font-size:32px;"></i>
                        No items found<?= ($search || $ftype || $fstat) ? ' matching your filters' : '' ?>.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include '../admin/edit-modal.dashboard.php'; ?>
<?php include '../admin/delete-modal.dashboard.php'; ?>
<script src="../js/AdminDashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>