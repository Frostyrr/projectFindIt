<?php
session_start();

include 'db.php';

$sql = "SELECT * FROM items WHERE type = 'lost' AND status = 'active' ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css">

</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>
    <div class="hero">
        
        <div class="hero-text">
            <h2>Find your</h2>
            <h1>Lost Items</h1>
            <p>A dedicated digital space to report, track, and recover lost items with ease.</p>
        </div>
        <div class="hero-buttons">
            <a href="browse.php" class="btn primary">Find lost item</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="report.php" class="btn secondary">Report lost item</a>
            <?php else: ?>
                <a href="#" onclick="openModal()" class="btn secondary">Report lost item</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="recent-reports-section">
        <div class="reports-container">
            <div class="reports-header">
                <div class="reports-title">
                    <h2>Recently Reported</h2>
                    <p>Help someone find their lost items today.</p>
                </div>
                <a href="browse.php" class="view-all-btn">View All Items</a>
            </div>

            <?php include 'recent-reports.php'; ?>

        </div>
    </section>

    <script src="js/loginModal.js"></script>
</body>
</html>