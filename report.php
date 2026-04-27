<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email = $_SESSION['user']['email'];
    $type = 'lost';
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $date_lost = $_POST['date_lost'];
    $contact_info = $_POST['contact_info'];
    
    // Manual file upload handling
    $image_path = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Ensure directory exists
        }
        
        $file_tmp = $_FILES['item_image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['item_image']['name']);
        $image_path = $upload_dir . $file_name;
        
        move_uploaded_file($file_tmp, $image_path);
    }

    $stmt = $conn->prepare("INSERT INTO items (user_email, type, item_name, description, location, date_lost_found, image_path, contact_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $user_email, $type, $item_name, $description, $location, $date_lost, $image_path, $contact_info);
    
    if ($stmt->execute()) {
        header("Location: browse.php?msg=lost_reported");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Lost Item - FindIt</title>
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="form-container">
        <h2>Report a Lost Item</h2>
        <form action="find_lost.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>Where did you lose it?</label>
                <input type="text" name="location" required>
            </div>
            <div class="form-group">
                <label>Date Lost</label>
                <input type="date" name="date_lost" required>
            </div>
            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" name="item_image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Contact Info (Email/Phone)</label>
                <input type="text" name="contact_info" value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" required>
            </div>
            <button type="submit" class="btn primary">Submit Report</button>
        </form>
    </div>
</body>
</html>