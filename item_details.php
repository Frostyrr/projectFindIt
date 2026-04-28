<?php
session_start();
include 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: browse.php");
    exit();
}

$item_id = intval($_GET['id']);

// Handle "Mark as Found" POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_found'])) {
    if (isset($_SESSION['user']['email'])) {
        $current_user_email = $_SESSION['user']['email'];

        $check_stmt = $conn->prepare("SELECT user_email FROM items WHERE id = ?");
        $check_stmt->bind_param("i", $item_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($row = $check_result->fetch_assoc()) {
            if ($row['user_email'] === $current_user_email) {
                $update_stmt = $conn->prepare("UPDATE items SET status = 'found' WHERE id = ?");
                $update_stmt->bind_param("i", $item_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $check_stmt->close();

        header("Location: item_details.php?id=" . $item_id);
        exit();
    }
}

// Fetch item
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $item_not_found = true;
} else {
    $item           = $result->fetch_assoc();
    $item_not_found = false;
    $is_owner       = isset($_SESSION['user']['email']) && $_SESSION['user']['email'] === $item['user_email'];
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

                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="index.php">Home</a>
                    <span class="breadcrumb-sep">›</span>
                    <a href="browse.php">Browse</a>
                    <span class="breadcrumb-sep">›</span>
                    <span><?= htmlspecialchars($item['item_name']) ?></span>
                </div>

                <!-- Main card -->
                <div class="item-details-card">

                    <!-- Image -->
                    <div class="item-image-section">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= htmlspecialchars($item['image_path']) ?>"
                                 alt="<?= htmlspecialchars($item['item_name']) ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <span>No Image Available</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="item-info-section">

                        <div class="item-header">
                            <?php if ($current_status === 'found'): ?>
                                <span class="badge status-badge found">Found &amp; Claimed</span>
                            <?php else: ?>
                                <span class="badge status-badge <?= strtolower(htmlspecialchars($item['type'])) ?>">
                                    <?= ucfirst(htmlspecialchars($item['type'])) ?>
                                </span>
                            <?php endif; ?>
                            <span class="date-posted">
                                Posted <?= date('F j, Y', strtotime($item['created_at'])) ?>
                            </span>
                        </div>

                        <h1 class="item-title"><?= htmlspecialchars($item['item_name']) ?></h1>

                        <!-- Description -->
                        <div class="info-group">
                            <h3>Description</h3>
                            <p class="item-description">
                                <?= nl2br(htmlspecialchars($item['description'])) ?>
                            </p>
                        </div>

                        <!-- Meta grid -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">
                                    📍 Location <?= ucfirst(htmlspecialchars($item['type'])) ?>
                                </span>
                                <span class="info-value"><?= htmlspecialchars($item['location']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">📅 Date</span>
                                <span class="info-value">
                                    <?= !empty($item['date_lost_found'])
                                        ? date('F j, Y', strtotime($item['date_lost_found']))
                                        : 'Not specified' ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">🔖 Status</span>
                                <span class="info-value status-<?= $current_status ?>">
                                    <?= $current_status === 'found'
                                        ? 'Found / Claimed'
                                        : ucfirst(htmlspecialchars($item['status'] ?? 'Active')) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Contact + action -->
                        <div class="action-buttons">

                            <div class="contact-info-box">
                                <h3>Contact Reporter</h3>
                                <p class="contact-value">
                                    📞 <?= htmlspecialchars($item['contact_info'] ?? $item['user_email']) ?>
                                </p>
                            </div>

                            <?php if ($is_owner && $current_status !== 'found'): ?>
                                <form method="POST" action=""
                                      onsubmit="return confirm('Mark this item as found / claimed?');">
                                    <button type="submit" name="mark_found" class="btn primary">
                                        ✓ Mark as Found
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>

                    </div><!-- /item-info-section -->
                </div><!-- /item-details-card -->

            <?php endif; ?>
        </div>
    </div>

    <script src="js/loginModal.js"></script>
    <script src="js/DropDown.js"></script>
</body>
</html>