<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email = $_SESSION['user']['email'];
    $type = $_POST['type']; // Now getting type from form
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $date_found = $_POST['date_found'];
    $contact_info = $_POST['contact_info'];
    
    // Validate type
    if (!in_array($type, ['lost', 'found'])) {
        $type = 'lost'; // Default to lost if invalid
    }
    
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
        $redirect_msg = $type === 'lost' ? 'lost_reported' : 'found_reported';
        header("Location: browse.php?msg=$redirect_msg");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Item - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/report.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    <?php include 'auth/login.php'; ?>

    <div class="form-container">
        <div class="form-card">

            <h2>Report an Item</h2>
            <p class="form-subtitle">Fill in the details below to help others identify the item.</p>
            <hr class="form-divider">

            <form action="" method="POST" enctype="multipart/form-data">

                <!-- Type Selection -->
                <div class="form-group">
                    <label>Report Type</label>
                    <div class="type-selector">
                        <label class="type-option">
                            <input type="radio" name="type" value="lost" checked>
                            <span class="type-label lost">
                                <span class="material-symbols-outlined">search</span>
                                Lost Item
                            </span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="type" value="found">
                            <span class="type-label found">
                                <span class="material-symbols-outlined">check_circle</span>
                                Found Item
                            </span>
                        </label>
                    </div>
                </div>

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
                        <label for="location" id="location-label">Location Lost</label>
                        <input type="text" id="location" name="location" placeholder="e.g. CSUCC Athenaeum" required>
                    </div>
                    <div class="form-group">
                        <label for="date_found" id="date-label">Date Lost</label>
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

    <script>
        // Update labels based on type selection
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const type = this.value;
                document.getElementById('location-label').textContent = 
                    type === 'lost' ? 'Location Lost' : 'Location Found';
                document.getElementById('date-label').textContent = 
                    type === 'lost' ? 'Date Lost' : 'Date Found';
                document.querySelector('.form-card h2').textContent = 
                    type === 'lost' ? 'Report a Lost Item' : 'Report a Found Item';
                document.querySelector('.form-subtitle').textContent = 
                    type === 'lost' ? 'Fill in the details below to help others identify what you lost.' : 
                    'Fill in the details below to help the owner find their item.';
            });
        });
    </script>

    <script src="js/loginModal.js"></script>
    <script src="js/fileUpload.js"></script>
    <script src="js/DropDown.js"></script>
    <script src="js/navbar.js"></script>

</body>
</html>