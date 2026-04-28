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
    $date_found = $_POST['date_found'];
    $contact_info = $_POST['contact_info'];
    
    $image_path = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_tmp  = $_FILES['item_image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['item_image']['name']);
        $image_path = $upload_dir . $file_name;
        move_uploaded_file($file_tmp, $image_path);
    }

    $stmt = $conn->prepare("INSERT INTO items (user_email, type, item_name, description, location, date_lost_found, image_path, contact_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $user_email, $type, $item_name, $description, $location, $date_found, $image_path, $contact_info);
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>

    <div class="form-container">
        <div class="form-card">

            <h2>Report a Lost Item</h2>
            <p class="form-subtitle">Fill in the details below to help others identify the item.</p>
            <hr class="form-divider">

            <form action="" method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" placeholder="e.g. Brown Leather Backpack" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe colour, size, brand, distinguishing features…" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">LOCATION FOUND</label>
                        <input type="text" id="location" name="location" placeholder="e.g. CSUCC Athenaeum" required>
                    </div>
                    <div class="form-group">
                        <label for="date_found">Date Found</label>
                        <input type="date" id="date_found" name="date_found" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="item_image">Upload Image</label>

                    <input type="file" id="item_image" name="item_image" accept="image/*" hidden>

                    <label for="item_image" class="file-upload-box">
                        <div class="file-upload-content">
                            <span class="upload-title">Click to upload or drag file here</span>
                            <span class="file-hint">JPG, PNG or WEBP — max 5 MB</span>
                        </div>
                    </label>

                    <span id="file-name" class="file-name">No file chosen</span>
                </div>

                <div class="form-group">
                    <label for="contact_info">Contact Info</label>
                    <input type="text" id="contact_info" name="contact_info" placeholder="Email or phone number"
                           value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" required>
                </div>

                <button type="submit" class="btn primary">Submit Report</button>

            </form>
        </div>
    </div>

    <script src="js/loginModal.js"></script>
    <script src="js/fileUpload.js"></script>
    <script src="js/DropDown.js"></script>

</body>
</html>