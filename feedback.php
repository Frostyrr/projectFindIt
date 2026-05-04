<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$success = false;
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email   = $_SESSION['user']['email'];
    $subject      = $_POST['subject'];
    $category     = $_POST['category'];
    $message      = $_POST['message'];
    $rating       = isset($_POST['rating']) ? intval($_POST['rating']) : null;

    $stmt = $conn->prepare(
        "INSERT INTO feedback (user_email, subject, category, message, rating, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("ssssi", $user_email, $subject, $category, $message, $rating);

    if ($stmt->execute()) {
        header("Location: feedback.php?msg=submitted");
        exit();
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/report.css">
    <link rel="stylesheet" href="css/profile/feedback.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    <?php include 'auth/login.php'; ?>

    <div class="form-container">
        <div class="form-card">

            <h2>Send Feedback</h2>
            <p class="form-subtitle">We'd love to hear your thoughts, suggestions, or report any issues.</p>
            <hr class="form-divider">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'submitted'): ?>
            <div class="alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                Thank you! Your feedback has been submitted successfully.
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert-success" style="background:#fdf2f2; border-color:#f5c6cb; color:#c0392b;">
                <span class="material-symbols-outlined">error</span>
                Something went wrong. Please try again.
            </div>
            <?php endif; ?>

            <form action="" method="POST">

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="" disabled selected>Select a category…</option>
                        <option value="bug">Bug / Technical Issue</option>
                        <option value="suggestion">Feature Suggestion</option>
                        <option value="content">Content / Listing Problem</option>
                        <option value="account">Account & Profile</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Brief summary of your feedback" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5"
                              placeholder="Describe your feedback in detail…"
                              maxlength="1000"
                              oninput="updateCounter(this)" required></textarea>
                    <div class="char-counter" id="char-counter">0 / 1000</div>
                </div>

                <div class="form-group">
                    <label>Overall Experience</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5">
                        <label for="star5" title="Excellent">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4" title="Good">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3" title="Okay">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2" title="Poor">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1" title="Terrible">&#9733;</label>
                    </div>
                    <p class="rating-hint">Optional — tap a star to rate your overall experience.</p>
                </div>

                <button type="submit" class="btn primary">Submit Feedback</button>
                <a href="index.php" class="btn secondary"
                   style="width: 100%; display:block; text-align:center; margin-top: 10px;
                          box-sizing: border-box; text-decoration: none;">
                    Cancel
                </a>

            </form>
        </div>
    </div>

    <script src="js/loginModal.js"></script>
    <script src="js/DropDown.js"></script>
    <script>
        function updateCounter(textarea) {
            const counter = document.getElementById('char-counter');
            const len = textarea.value.length;
            counter.textContent = len + ' / 1000';
            counter.classList.toggle('warn', len > 850);
        }
    </script>
</body>
</html>