<?php
session_start();
include 'db.php';

// Security check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php");
    exit;
}

$item_id = intval($_GET['id']);
$user_email = $_SESSION['user']['email'];

// Fetch the existing item to pre-fill the form (Ensuring it belongs to the user)
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND user_email = ?");
$stmt->bind_param("is", $item_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Item not found or you do not have permission to edit it.");
}
$item = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $date_found = $_POST['date_found'];
    $contact_info = $_POST['contact_info'];
    $status = $_POST['status']; // Allow changing status (e.g., active -> found)
    
    $image_path = $item['image_path']; // Default to keeping the old image

    // If a new image was uploaded
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_tmp  = $_FILES['item_image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['item_image']['name']);
        $new_image_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file_tmp, $new_image_path)) {
            $image_path = $new_image_path;
        }
    }

    $update_stmt = $conn->prepare("UPDATE items SET item_name=?, description=?, location=?, date_lost_found=?, image_path=?, contact_info=?, status=? WHERE id=? AND user_email=?");
    $update_stmt->bind_param("sssssssss", $item_name, $description, $location, $date_found, $image_path, $contact_info, $status, $item_id, $user_email);
    
    if ($update_stmt->execute()) {
        header("Location: profile.php?msg=updated");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report - FindIt</title>
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/report.css"> </head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>

    <div class="form-container" style="margin-top: 100px;">
        <div class="form-card">
            <h2>Edit Report</h2>
            <p class="form-subtitle">Update the details of your reported item.</p>
            <hr class="form-divider">

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="status">Current Status</label>
                    <select id="status" name="status" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px;">
                        <option value="active" <?= $item['status'] == 'active' ? 'selected' : '' ?>>Active (Still Lost/Found)</option>
                        <option value="found" <?= $item['status'] == 'found' ? 'selected' : '' ?>>Resolved / Returned</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($item['description']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($item['location']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_found">Date</label>
                        <input type="date" id="date_found" name="date_found" value="<?= htmlspecialchars($item['date_lost_found']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Image</label>
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" style="max-height: 100px; display: block; margin-bottom: 10px; border-radius: 8px;">
                    <?php else: ?>
                        <p style="font-size: 14px; color: #666;">No image uploaded previously.</p>
                    <?php endif; ?>

                    <label for="item_image">Upload New Image (Optional)</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*" hidden>
                    <label for="item_image" class="file-upload-box">
                        <div class="file-upload-content">
                            <span class="upload-title">Click to upload new image</span>
                        </div>
                    </label>
                    <span id="file-name" class="file-name">No file chosen</span>
                </div>

                <div class="form-group">
                    <label for="contact_info">Contact Info</label>
                    <input type="text" id="contact_info" name="contact_info" value="<?= htmlspecialchars($item['contact_info']) ?>" required>
                </div>

                <button type="submit" class="btn primary" style="width: 100%; margin-top: 10px;">Save Changes</button>
                <a href="profile.php" class="btn secondary" style="width: 100%; display:block; text-align:center; margin-top: 10px; box-sizing: border-box; text-decoration: none;">Cancel</a>
            </form>
        </div>
    </div>

    <script src="js/fileUpload.js"></script>
    <script src="js/DropDown.js"></script>
</body>
</html>