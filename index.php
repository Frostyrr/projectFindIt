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
    <title>FindIt — Recover What's Yours</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css">
</head>
<body>

    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>

    <!-- Hero -->
    <div class="hero">

        <div class="hero-text">
            <h2>Find your</h2>
            <h1>Lost Items</h1>
            <p>A dedicated digital space to report, track, and recover lost items with ease.</p>
        </div>

        <div class="hero-buttons">
            <a href="browse.php" class="btn primary">Find Lost Item</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="report.php" class="btn secondary">Report Lost Item</a>
            <?php else: ?>
                <a href="#" onclick="openModal()" class="btn secondary">Report Lost Item</a>
            <?php endif; ?>
        </div>

        <!-- scroll cue -->
        <a href="#recent-reports" class="hero-scroll-cue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 viewBox="0 0 24 24">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
            Scroll
        </a>

    </div>

    <!-- Recent reports -->
    <section class="recent-reports-section" id="recent-reports">
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

    <hr class="section-divider">   

    <section class="community">
        <div class="community-card">
            <p>THE FINAL STEP</p>
            <hr class="divider">
            <h1>Ready to help our community?</h1>
            <p>Help reunite lost items with their owners in just a few clicks.</p>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="report.php" class="btn secondary">Report Lost Item</a>
            <?php else: ?>
                <a href="#" onclick="openModal()" class="btn secondary">Report Lost Item</a>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">

            <div class="footer-brand">
                <h3>FindIt</h3>
                <p>Helping communities reconnect people with their lost belongings.</p>
            </div>

            <div class="footer-links">
                <a href="browse.php">Browse Items</a>
                <a href="report.php">Report Item</a>
                <a href="#recent-reports">Recent Reports</a>
            </div>

            <div class="footer-meta">
                <p>&copy; <?php echo date("Y"); ?> FindIt. All rights reserved.</p>
            </div>

        </div>
    </footer>

    <script src="js/loginModal.js"></script>
    <script src="js/goToDetail.sjs"></script>
    <script>
        // Add .scrolled class to navbar on scroll for elevated shadow
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 10);
        });
    </script>

</body>
</html>