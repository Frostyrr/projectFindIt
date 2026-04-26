<?php
include 'db.php';
session_start();

$sql = "SELECT * FROM `users`";
$result = mysqli_query($conn, $sql);

if (!isset($_SESSION['user'])) {
    header("location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <div class="table-card">
            <div class="card-header">
                <h2>User Management</h2>
                <a href="logout.php" class="btn logout">Sign out</a>
            </div>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Actions</th>
                </tr>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <tr>
                    <td class="th-primary"><?= $row['id'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td><?= $row['password'] ?></td>
                    <td>
                        <a href="update.php?id=<?= $row['id'] ?>" class="btn primary">Edit</a>
                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn secondary">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>

            </table>
            <?php else: ?>
                <p>No data found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>