<?php
session_start();
require_once '../db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Security check ───────────────────────────────────────────
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// ════════════════════════════════════════════════════════════
//  POST HANDLERS (PRG pattern)
// ════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);

    // ── DELETE ───────────────────────────────────────────────
    if ($action === 'delete' && $item_id > 0) {
        try {
            $s = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $img = $s->get_result()->fetch_assoc();
            $s->close();

            if (!empty($img['image_path'])) {
                $full_path = '../' . ltrim($img['image_path'], '/');
                if (file_exists($full_path)) unlink($full_path);
            }

            $s = $conn->prepare("DELETE FROM items WHERE id = ?");
            $s->bind_param("i", $item_id);
            $s->execute();
            $s->close();

            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Item #$item_id has been deleted."];
        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Delete failed: " . $e->getMessage()];
        }
        header("Location: items.php");
        exit();
    }

    // ── EDIT ─────────────────────────────────────────────────
    if ($action === 'edit' && $item_id > 0) {
        $item_name   = trim($_POST['item_name']    ?? '');
        $description = trim($_POST['description']  ?? '');
        $location    = trim($_POST['location']     ?? '');
        $type        = in_array($_POST['type']   ?? '', ['lost','found'])               ? $_POST['type']   : 'lost';
        $status      = in_array($_POST['status'] ?? '', ['active','found','resolved'])  ? $_POST['status'] : 'active';
        $date_lf     = !empty($_POST['date_lost_found']) ? $_POST['date_lost_found'] : null;

        if ($item_name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Item name cannot be empty."];
            header("Location: items.php");
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
            $s->bind_param("ssssssi", $item_name, $description, $location, $type, $status, $date_lf, $item_id);
            $s->execute();
            $s->close();

            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Item #$item_id updated successfully."];
        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Update failed: " . $e->getMessage()];
        }
        header("Location: items.php");
        exit();
    }
}

// ════════════════════════════════════════════════════════════
//  FLASH MESSAGE
// ════════════════════════════════════════════════════════════
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ════════════════════════════════════════════════════════════
//  FILTERS
// ════════════════════════════════════════════════════════════
$search        = trim($_GET['search']  ?? '');
$filter_type   = $_GET['type']   ?? '';
$filter_status = $_GET['status'] ?? '';

// ════════════════════════════════════════════════════════════
//  PAGINATION
// ════════════════════════════════════════════════════════════
$limit  = 12;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ════════════════════════════════════════════════════════════
//  BUILD WHERE CLAUSE
// ════════════════════════════════════════════════════════════
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (i.item_name LIKE ? OR i.location LIKE ? OR u.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= "sss";
}
if ($filter_type !== '') {
    $where   .= " AND i.type = ?";
    $params[] = $filter_type;
    $types   .= "s";
}
if ($filter_status !== '') {
    $where   .= " AND i.status = ?";
    $params[] = $filter_status;
    $types   .= "s";
}

// Total count
$count_stmt = $conn->prepare("SELECT COUNT(i.id) AS c FROM items i LEFT JOIN users u ON i.user_email = u.email $where");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_items / $limit));
$count_stmt->close();

// Items
$data_stmt = $conn->prepare("
    SELECT  i.id, i.item_name, i.description, i.location, i.type,
            i.status, i.date_lost_found, i.created_at, i.image_path,
            u.name AS reporter_name
    FROM    items i
    LEFT JOIN users u ON i.user_email = u.email
    $where
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
");
$all_types  = $types . "ii";
$all_params = array_merge($params, [$limit, $offset]);
$data_stmt->bind_param($all_types, ...$all_params);
$data_stmt->execute();
$items = $data_stmt->get_result();
$data_stmt->close();

// Stats
$r = $conn->query("SELECT COUNT(id) AS c FROM items"); $total_all   = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE type='lost' AND status='active'"); $active_lost = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE status='found'"); $items_found = $r->fetch_assoc()['c'];

function buildQS($page, $search, $type, $status) {
    return '?' . http_build_query(array_filter([
        'page'   => $page,
        'search' => $search,
        'type'   => $type,
        'status' => $status,
    ]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Items — FindIt Admin</title>
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
                    <span class="stat-value"><?= $total_all ?></span>
                    <span class="stat-sub">All submissions</span>
                </div>
                <div class="stat-icon green"><i class="fas fa-box-open"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Active Lost</span>
                    <span class="stat-value"><?= $active_lost ?></span>
                    <span class="stat-sub">Awaiting recovery</span>
                </div>
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Recovered</span>
                    <span class="stat-value"><?= $items_found ?></span>
                    <span class="stat-sub">Successfully found</span>
                </div>
                <div class="stat-icon amber"><i class="fas fa-circle-check"></i></div>
            </div>
        </div>

        <!-- Cards section -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Items List</h2>
                <span class="results-summary"><?= $total_items ?> result<?= $total_items !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Filter bar -->
            <div class="filter-bar">
                <form method="GET" action="">
                    <input type="text"
                           name="search"
                           class="filter-input"
                           placeholder="Search by item, location or reporter…"
                           value="<?= htmlspecialchars($search) ?>">

                    <select name="type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="lost"  <?= $filter_type === 'lost'  ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $filter_type === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>

                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="active"   <?= $filter_status === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="found"    <?= $filter_status === 'found'    ? 'selected' : '' ?>>Found</option>
                        <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-magnifying-glass"></i> Search
                    </button>

                    <?php if ($search || $filter_type || $filter_status): ?>
                        <a href="items.php" class="filter-reset">
                            <i class="fas fa-xmark"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Cards grid -->
            <div class="items-grid">
                <?php if ($items && $items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <div class="item-card" id="row-<?= $row['id'] ?>">

                            <div class="item-card-image">
                                <?php if (!empty($row['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($row['image_path']) ?>"
                                         alt="<?= htmlspecialchars($row['item_name']) ?>">
                                <?php else: ?>
                                    <div class="item-card-placeholder">No Image</div>
                                <?php endif; ?>
                                <div class="item-card-badges">
                                    <span class="badge <?= strtolower($row['type']) ?>">
                                        <?= ucfirst($row['type']) ?>
                                    </span>
                                    <span class="badge <?= strtolower($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="item-card-body">
                                <span class="item-card-meta">📅 <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                <h3 class="item-card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                                <span class="item-card-reporter">👤 <?= htmlspecialchars($row['reporter_name'] ?? 'Unknown') ?></span>
                                <p class="item-card-desc"><?= htmlspecialchars($row['description'] ?? '') ?></p>

                                <hr class="item-card-divider">

                                <div class="item-card-footer">
                                    <span class="item-card-location">📍 <?= htmlspecialchars($row['location']) ?></span>
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
                        No items found<?= ($search || $filter_type || $filter_status) ? ' matching your filters' : '' ?>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="<?= buildQS($page - 1, $search, $filter_type, $filter_status) ?>"
                       class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i === 1 || $i === $total_pages || abs($i - $page) <= 2): ?>
                            <a href="<?= buildQS($i, $search, $filter_type, $filter_status) ?>"
                               class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php elseif (abs($i - $page) === 3): ?>
                            <span class="page-link" style="border:none;background:none;color:var(--ink-300)">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <a href="<?= buildQS($page + 1, $search, $filter_type, $filter_status) ?>"
                       class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../admin/edit-modal.dashboard.php'; ?>
<?php include '../admin/delete-modal.dashboard.php'; ?>
<script src="../js/AdminDashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>