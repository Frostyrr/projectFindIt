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
    // Check if user is admin - admins can edit any item
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            die("Item not found.");
        }
    } else {
        die("Item not found or you do not have permission to edit it.");
    }
}
$item = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name   = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $date_found  = $_POST['date_found'];
    $contact_info = trim($_POST['contact_info']);
    $type        = $_POST['type']; // Added type field
    $status      = $_POST['status']; // Allow changing status
    
    // Validate required fields
    $errors = [];
    if (empty($item_name)) $errors[] = "Item name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($location)) $errors[] = "Location is required.";
    if (empty($date_found)) $errors[] = "Date is required.";
    if (!in_array($type, ['lost', 'found'])) $type = $item['type']; // Keep original if invalid
    if (!in_array($status, ['active', 'claimed', 'resolved'])) $status = $item['status']; // Keep original if invalid
    
    $image_path = $item['image_path']; // Default to keeping the old image

    // If a new image was uploaded
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['item_image']['tmp_name']);
        finfo_close($file_info);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
        } elseif ($_FILES['item_image']['size'] > 5 * 1024 * 1024) { // 5MB max
            $errors[] = "File size too large. Maximum 5MB allowed.";
        } else {
            $file_tmp  = $_FILES['item_image']['tmp_name'];
            $file_ext  = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
            $new_image_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file_tmp, $new_image_path)) {
                // Delete old image if it exists
                if (!empty($item['image_path']) && file_exists($item['image_path'])) {
                    unlink($item['image_path']);
                }
                $image_path = $new_image_path;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    // If no errors, update the database
    if (empty($errors)) {
        $update_stmt = $conn->prepare(
            "UPDATE items 
             SET item_name = ?, 
                 description = ?, 
                 location = ?, 
                 date_lost_found = ?, 
                 image_path = ?, 
                 contact_info = ?, 
                 type = ?,
                 status = ? 
             WHERE id = ? AND user_email = ?"
        );
        
        $update_stmt->bind_param(
            "ssssssssis", 
            $item_name, 
            $description, 
            $location, 
            $date_found, 
            $image_path, 
            $contact_info,
            $type,
            $status, 
            $item_id, 
            $user_email
        );
        
        if ($update_stmt->execute()) {
            header("Location: profile.php?msg=updated");
            exit();
        } else {
            $errors[] = "Update failed: " . $conn->error;
        }
    }
}

// Function to format date for input field
$date_value = !empty($item['date_lost_found']) ? date('Y-m-d', strtotime($item['date_lost_found'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/report.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <?php include 'auth/login.php'; ?>
    <?php endif; ?>

    <div class="form-container">
        <div class="form-card">
            <h2>Edit Report</h2>
            <p class="form-subtitle">Update the details of your <?= htmlspecialchars($item['type']) ?> item report.</p>
            <hr class="form-divider">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- Type Selection -->
                <div class="form-group">
                    <label>Report Type</label>
                    <div class="type-selector">
                        <label class="type-option">
                            <input type="radio" name="type" value="lost" <?= $item['type'] === 'lost' ? 'checked' : '' ?>>
                            <span class="type-label lost">
                                <span class="material-symbols-outlined">search</span>
                                Lost Item
                            </span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="type" value="found" <?= $item['type'] === 'found' ? 'checked' : '' ?>>
                            <span class="type-label found">
                                <span class="material-symbols-outlined">check_circle</span>
                                Found Item
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">Current Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="claimed" <?= $item['status'] === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                        <option value="resolved" <?= $item['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>

                <!-- Item Name -->
                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" 
                           value="<?= htmlspecialchars($item['item_name']) ?>" required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($item['description']) ?></textarea>
                </div>

                <!-- Location and Date -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="location" id="location-label">
                            <?= $item['type'] === 'lost' ? 'Location Lost' : 'Location Found' ?>
                        </label>
                        <input type="text" id="location" name="location" 
                               value="<?= htmlspecialchars($item['location']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_found" id="date-label">
                            <?= $item['type'] === 'lost' ? 'Date Lost' : 'Date Found' ?>
                        </label>
                        <input type="date" id="date_found" name="date_found" 
                               value="<?= $date_value ?>" required>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label>Current Image</label>
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" 
                             alt="Current item image"
                             style="max-height: 120px; display: block; margin-bottom: 10px; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <?php else: ?>
                        <p style="font-size: 14px; color: #666; margin-bottom: 10px;">No image uploaded previously.</p>
                    <?php endif; ?>

                    <label for="item_image">Upload New Image (Optional)</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*" hidden>
                    <label for="item_image" class="file-upload-box">
                        <div class="file-upload-content">
                            <span class="upload-title">Click to upload new image or drag file here</span>
                            <span class="file-hint">JPG, PNG or WEBP — max 5 MB</span>
                        </div>
                    </label>
                    <span id="file-name" class="file-name">No file chosen</span>
                </div>

                <!-- Contact Info -->
                <div class="form-group">
                    <label for="contact_info">Contact Info</label>
                    <input type="text" id="contact_info" name="contact_info" 
                           value="<?= htmlspecialchars($item['contact_info']) ?>" required>
                </div>

                <!-- Buttons -->
                <div class="form-actions" style="display: flex; gap: 12px; margin-top: 20px;">
                    <button type="submit" class="btn primary" style="flex: 1;">Save Changes</button>
                    <a href="profile.php" class="btn secondary" style="flex: 1; text-align: center; text-decoration: none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update labels based on type selection
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const type = this.value;
                document.getElementById('location-label').textContent = 
                    type === 'lost' ? 'Location Lost' : 'Location Found';
                document.getElementById('date-label').textContent = 
                    type === 'lost' ? 'Date Lost' : 'Date Found';
            });
        });
    </script>

    <script src="js/loginModal.js"></script>
    <script src="js/fileUpload.js"></script>
    <script src="js/DropDown.js"></script>

</body>
</html>