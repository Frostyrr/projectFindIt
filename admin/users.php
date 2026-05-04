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
//  POST HANDLER — delete_user action (PRG pattern)
// ════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'delete_user' && $user_id > 0) {

        // Prevent admin from deleting their own account here
        if ($user_id === (int)$_SESSION['user_id']) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'You cannot delete your own account from here.'
            ];
            header("Location: users.php");
            exit();
        }

        try {
            // 1. Get the user's email (needed to delete their items)
            $s = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
            $s->bind_param("i", $user_id);
            $s->execute();
            $target = $s->get_result()->fetch_assoc();
            $s->close();

            if (!$target) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => "User #$user_id not found."];
                header("Location: users.php");
                exit();
            }

            $target_email = $target['email'];
            $target_name  = $target['name'];

            // 2. Delete image files on disk before removing DB rows
            $s = $conn->prepare("SELECT image_path FROM items WHERE user_email = ?");
            $s->bind_param("s", $target_email);
            $s->execute();
            $images = $s->get_result();
            $s->close();

            while ($img = $images->fetch_assoc()) {
                if (!empty($img['image_path'])) {
                    $full_path = '../' . ltrim($img['image_path'], '/');
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
            }

            // 3. Delete all items belonging to this user
            $s = $conn->prepare("DELETE FROM items WHERE user_email = ?");
            $s->bind_param("s", $target_email);
            $s->execute();
            $s->close();

            // 4. Delete the user record
            $s = $conn->prepare("DELETE FROM users WHERE id = ?");
            $s->bind_param("i", $user_id);
            $s->execute();
            $s->close();

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "Account of \"$target_name\" (#{$user_id}) has been deleted."
            ];

        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => "Delete failed: " . $e->getMessage()
            ];
        }

        header("Location: users.php");
        exit();
    }
}

// ════════════════════════════════════════════════════════════
//  FLASH MESSAGE
// ════════════════════════════════════════════════════════════
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ════════════════════════════════════════════════════════════
//  FETCH DATA
// ════════════════════════════════════════════════════════════
$table_error = null;
$result      = false;
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
    <title>Users — FindIt Admin</title>
    <link rel="icon" type="image/x-icon" href="../images/findIconWithBG.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../admin/sidebar.dashboard.php'; ?>

<div class="dashboard-main">

    <header class="dashboard-topbar">
        <span class="topbar-title">User Management</span>
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
                <h2>List of Users</h2>
                <span><?= $totalUsers ?> total</span>
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
                            <th>OAuth</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><span class="item-id">#<?= $row['id'] ?></span></td>
                                <td><?= ucwords(htmlspecialchars($row['oauth_provider'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= ucwords(htmlspecialchars($row['role'])) ?></td>
                                <td>
                                    <div class="action-group">
                                        <!-- View profile -->
                                        <a href="../profile.php?id=<?= $row['id'] ?>"
                                           class="btn-action" title="View profile">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Delete (disabled for own account) -->
                                        <?php if ($row['id'] !== (int)$_SESSION['user_id']): ?>
                                            <button type="button"
                                                    class="btn-action btn-delete"
                                                    title="Delete user"
                                                    onclick="openDeleteUser(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn-action"
                                                    title="Cannot delete your own account"
                                                    disabled
                                                    style="opacity:0.3;cursor:not-allowed;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- ════════════════════════════════════════════════════════
     DELETE USER MODAL
════════════════════════════════════════════════════════ -->
<div id="deleteUserModal"
     style="display:none;position:fixed;inset:0;z-index:1000;
            background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:440px;width:90%;
                box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 8px;font-size:1.1rem;color:#dc2626;">Delete User Account</h3>
        <p style="color:#555;font-size:0.9rem;margin:0 0 6px;line-height:1.5;">
            Permanently delete the account of <strong id="deleteUserName"></strong>?
            All their reports will also be removed.
        </p>
        <p style="color:#888;font-size:0.8rem;margin:0 0 24px;">⚠ This action cannot be undone.</p>
        <form method="POST" action="users.php">
            <input type="hidden" name="action"  value="delete_user">
            <input type="hidden" name="user_id" id="deleteUserId">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button"
                        onclick="closeDeleteUser()"
                        style="padding:9px 20px;border-radius:8px;border:1px solid #ddd;
                               background:#f5f5f5;cursor:pointer;font-size:0.875rem;font-weight:600;">
                    Cancel
                </button>
                <button type="submit"
                        style="padding:9px 20px;border-radius:8px;border:none;
                               background:#dc2626;color:#fff;cursor:pointer;
                               font-size:0.875rem;font-weight:600;">
                    Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeleteUser(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteUserModal').style.display = 'flex';
    }
    function closeDeleteUser() {
        document.getElementById('deleteUserModal').style.display = 'none';
    }
    document.getElementById('deleteUserModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>

<script src="../js/AdminDashboard.js"></script>
</body>
</html>
<?php $conn->close(); ?>