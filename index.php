<?php
session_start();
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
            <a href="report.php" class="btn secondary">Report found item</a>
        </div>
    </div>

    <section class="recent-reports">

    </section>

    <script src="js/loginModal.js"></script>
</body>
</html>