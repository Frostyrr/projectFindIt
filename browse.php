<?php
session_start();
include 'db.php';

// Fetch all active items, newest first
$sql = "SELECT * FROM items WHERE status = 'active' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Items - FindIt</title>
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/browse.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="feed-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <?php if ($row['image_path']): ?>
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" class="item-image" alt="Item Image">
                    <?php else: ?>
                        <div class="item-image" style="display:flex; align-items:center; justify-content:center; color:#999;">No Image</div>
                    <?php endif; ?>
                    
                    <div class="item-details">
                        <span class="badge <?= $row['type'] ?>"><?= strtoupper($row['type']) ?></span>
                        <h3><?= htmlspecialchars($row['item_name']) ?></h3>
                        <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($row['date_lost_found']) ?></p>
                        <p class="description">
                            <?= htmlspecialchars($row['description']) ?>
                        </p>
                        <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                        <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_info']) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No items reported yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>