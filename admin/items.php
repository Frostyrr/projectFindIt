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
$search     = trim($_GET['search']  ?? '');
$filter_type   = $_GET['type']   ?? '';
$filter_status = $_GET['status'] ?? '';

// ════════════════════════════════════════════════════════════
//  PAGINATION
// ════════════════════════════════════════════════════════════
$limit  = 15;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ════════════════════════════════════════════════════════════
//  FETCH DATA
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
$count_sql  = "SELECT COUNT(i.id) AS c FROM items i LEFT JOIN users u ON i.user_email = u.email $where";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_items / $limit));
$count_stmt->close();

// Items
$data_sql  = "
    SELECT  i.id, i.item_name, i.description, i.location, i.type,
            i.status, i.date_lost_found, i.created_at,
            u.name AS reporter_name
    FROM    items i
    LEFT JOIN users u ON i.user_email = u.email
    $where
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
";
$data_stmt = $conn->prepare($data_sql);
$all_types  = $types . "ii";
$all_params = array_merge($params, [$limit, $offset]);
$data_stmt->bind_param($all_types, ...$all_params);
$data_stmt->execute();
$items = $data_stmt->get_result();
$data_stmt->close();

// Stats for top cards
$r = $conn->query("SELECT COUNT(id) AS c FROM items"); $total_all   = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE type='lost' AND status='active'"); $active_lost = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(id) AS c FROM items WHERE status='found'"); $items_found = $r->fetch_assoc()['c'];
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
    <style>
        /* ── Filter bar ── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .filter-bar form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
        }

        .filter-input {
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            color: var(--ink-900);
            background: var(--surface);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .filter-input:focus {
            border-color: var(--green-500);
            box-shadow: 0 0 0 3px rgba(61,122,84,.11);
        }

        .filter-input.search { flex: 1; min-width: 180px; }

        .filter-select {
            padding: 9px 32px 9px 13px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            color: var(--ink-900);
            background: var(--surface);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%238c9990' d='M5 6L0 0h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
            outline: none;
            transition: border-color var(--transition);
        }

        .filter-select:focus { border-color: var(--green-500); }

        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            background: var(--green-900);
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12.5px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: background var(--transition);
            white-space: nowrap;
        }

        .filter-btn:hover { background: var(--green-700); }

        .filter-reset {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-300);
            text-decoration: none;
            padding: 9px 4px;
            transition: color var(--transition);
            white-space: nowrap;
        }

        .filter-reset:hover { color: var(--red-500); }

        /* ── Pagination ── */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 20px 26px;
            border-top: 1px solid var(--border);
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--ink-600);
            text-decoration: none;
            background: var(--surface);
            transition: background var(--transition), border-color var(--transition), color var(--transition);
        }

        .page-link:hover {
            background: var(--green-100);
            border-color: var(--green-300);
            color: var(--green-700);
        }

        .page-link.active {
            background: var(--green-900);
            border-color: var(--green-900);
            color: #fff;
        }

        .page-link.disabled {
            opacity: .35;
            pointer-events: none;
        }

        /* ── Results summary ── */
        .results-summary {
            font-size: 12.5px;
            color: var(--ink-300);
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include '../admin/sidebar.dashboard.php'; ?>

<div class="dashboard-main">

    <!-- Top bar -->
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

        <!-- Flash -->
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

        <!-- Table card -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Items List</h2>
                <span class="results-summary"><?= $total_items ?> result<?= $total_items !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Filter bar -->
            <div style="padding: 18px 26px 0;">
                <div class="filter-bar">
                    <form method="GET" action="">
                        <input  type="text"
                                name="search"
                                class="filter-input search"
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
            </div>

            <!-- Table -->
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
                        <?php if ($items && $items->num_rows > 0): ?>
                            <?php while ($row = $items->fetch_assoc()): ?>
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
                                        <a href="../item_details.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View item">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                            <tr><td colspan="8" class="td-empty">
                                <i class="fas fa-box-open" style="font-size:24px; display:block; margin-bottom:10px; color:var(--ink-300)"></i>
                                No items found<?= ($search || $filter_type || $filter_status) ? ' matching your filters' : '' ?>.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php
                    // Build query string preserving filters
                    $qs = http_build_query(array_filter([
                        'search' => $search,
                        'type'   => $filter_type,
                        'status' => $filter_status,
                    ]));
                    $qs = $qs ? "&$qs" : '';
                ?>
                <div class="pagination">
                    <a href="?page=<?= $page - 1 ?><?= $qs ?>"
                       class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i === 1 || $i === $total_pages || abs($i - $page) <= 2): ?>
                            <a href="?page=<?= $i ?><?= $qs ?>"
                               class="page-link <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php elseif (abs($i - $page) === 3): ?>
                            <span class="page-link" style="border:none; background:none; color:var(--ink-300)">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <a href="?page=<?= $page + 1 ?><?= $qs ?>"
                       class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        </div><!-- /table-card -->
    </div><!-- /dashboard-body -->
</div><!-- /dashboard-main -->

<?php include '../admin/edit-modal.dashboard.php'; ?>
<?php include '../admin/delete-modal.dashboard.php'; ?>

<script src="../js/AdminDashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>