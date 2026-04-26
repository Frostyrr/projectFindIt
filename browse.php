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
    <style>
        .feed-container { max-width: 1200px; margin: 100px auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .item-card { border: 1px solid #ddd; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .item-image { width: 100%; height: 200px; object-fit: cover; background: #f8f8f8; }
        .item-details { padding: 15px; }
        .badge { padding: 5px 10px; border-radius: 15px; color: white; font-size: 12px; font-weight: bold; }
        .badge.lost { background: #d9534f; }
        .badge.found { background: #5cb85c; }
    </style>
</head>
<body>
    
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
                        <h3 style="margin: 10px 0;"><?= htmlspecialchars($row['item_name']) ?></h3>
                        <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($row['date_lost_found']) ?></p>
                        <p style="font-size: 14px; color: #555; margin-top: 10px;"><?= htmlspecialchars($row['description']) ?></p>
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