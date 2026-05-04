<?php
session_start();
require_once '../db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Security check ───────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

// ════════════════════════════════════════════════════════════
//  FLASH MESSAGE (set by the redirect above)
// ════════════════════════════════════════════════════════════
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$table_error = null;

// get all total users
$totalUsers = $result->num_rows;

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
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value"><?= $totalUsers?></span>
                </div>
                <div class="stat-icon green"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-left">
                    <span class="stat-label">Active Users</span>
                    <span class="stat-value"><?= $active_lost ?></span>
                </div>
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <!-- Table card -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>List of users</h2>
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
                            <th>OAuth Provider</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><span class="item-id">#<?= $row['id'] ?></span></td>
                                <td class="td-item-name"><?= ucwords(htmlspecialchars($row['oauth_provider'])) ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= ucwords(htmlspecialchars($row['role'])) ?></td>
                                <td>
                                    <div class="action-group">
                                        <!-- View -->
                                        <a href="../profile.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View user">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="td-empty">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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