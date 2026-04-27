<?php
session_start();
include 'db.php'; // Ensure DB connection is included

// Check if an ID was provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to browse page if no ID is provided
    header("Location: browse.php");
    exit();
}

$item_id = intval($_GET['id']);

// Handle "Mark as Found" POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_found'])) {
    if (isset($_SESSION['user']['email'])) {
        $current_user_email = $_SESSION['user']['email'];
        
        // Verify that the logged-in user is the owner of this item
        $check_stmt = $conn->prepare("SELECT user_email FROM items WHERE id = ?");
        $check_stmt->bind_param("i", $item_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($row = $check_result->fetch_assoc()) {
            if ($row['user_email'] === $current_user_email) {
                // Update the status to 'found'
                $update_stmt = $conn->prepare("UPDATE items SET status = 'found' WHERE id = ?");
                $update_stmt->bind_param("i", $item_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $check_stmt->close();
        
        // Refresh the page to show the updated badge
        header("Location: item_details.php?id=" . $item_id);
        exit();
    }
}

// Fetch the item details using a prepared statement for security
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the item exists
if ($result->num_rows === 0) {
    $item_not_found = true;
} else {
    $item = $result->fetch_assoc();
    $item_not_found = false;
    
    // Determine if the current logged-in user is the one who reported this item
    $is_owner = isset($_SESSION['user']['email']) && $_SESSION['user']['email'] === $item['user_email'];
    $current_status = strtolower($item['status'] ?? 'active');
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/item_details.css"> 
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>
    
    <div class="details-page-container">
        <div class="details-content">

            <?php if ($item_not_found): ?>
                <div class="error-container">
                    <h2>Item Not Found</h2>
                    <p>The item you are looking for does not exist or has been removed.</p>
                    <a href="browse.php" class="btn primary">Back to Browse</a>
                </div>
            <?php else: ?>

            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; 
                <a href="browse.php">Browse</a> &gt; 
                <span><?= htmlspecialchars($item['item_name']) ?></span>
            </div>
        

            <div class="item-details-card">
                <div class="item-image-section">
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <?php else: ?>
                        <div class="placeholder-image">
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="item-info-section">
                    <div class="item-header">
                        <?php if ($current_status === 'found'): ?>
                            <span class="badge status-badge found">Found & Claimed</span>
                        <?php else: ?>
                            <span class="badge status-badge <?= strtolower(htmlspecialchars($item['type'])) ?>">
                                <?= ucfirst(htmlspecialchars($item['type'])) ?>
                            </span>
                        <?php endif; ?>
                        <span class="date-posted">Posted: <?= date('F j, Y', strtotime($item['created_at'])) ?></span>
                    </div>

                    <h1 class="item-title"><?= htmlspecialchars($item['item_name']) ?></h1>
                    
                    <div class="info-group">
                        <h3>Description</h3>
                        <p class="item-description"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">📍 Location <?= ucfirst(htmlspecialchars($item['type'])) ?>:</span>
                            <span class="info-value"><?= htmlspecialchars($item['location']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📅 Date:</span>
                            <span class="info-value">
                                <?= !empty($item['date_lost_found']) ? date('F j, Y', strtotime($item['date_lost_found'])) : 'Not specified' ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">🔖 Status:</span>
                            <span class="info-value status-<?= $current_status ?>">
                                <?= $current_status === 'found' ? 'Found / Claimed' : ucfirst(htmlspecialchars($item['status'] ?? 'Active')) ?>
                            </span>
                        </div>
                    </div>

                    <div class="action-section">
                        <div class="contact-info-box" style="background: #f1f8f4; padding: 15px; border-radius: 8px; border: 1px solid #d4ebd9; margin-bottom: 20px;">
                            <h3 style="margin-top: 0; font-size: 14px; color: #3d7a54; text-transform: uppercase;">Contact Reporter</h3>
                            <p style="margin: 5px 0 0 0; font-size: 16px; font-weight: 500; color: #222;">
                                📞 <?= htmlspecialchars($item['contact_info'] ?? $item['user_email']) ?>
                            </p>
                        </div>

                        <?php if ($is_owner && $current_status !== 'found'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to mark this item as found/claimed?');">
                                <button type="submit" name="mark_found" class="btn primary" style="width: 100%; background-color: #3d7a54;">
                                    ✓ Mark as Found / Claimed
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="js/loginModal.js"></script>
</body>
</html>