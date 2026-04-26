<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="home.php" class="nav-brand">
                <img src="images/findIcon.png">FindIt
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse.php">Browse</a></li>
                
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="hero">
        
        <div class="hero-text">
            <h2>Find your</h2>
            <h1>Lost Items</h1>
            <p>A dedicated digital space to report, track, and recover lost items with ease.</p>
        </div>
        <div class="hero-buttons">
           <a href="find_lost.php" class="btn primary">Find lost</a>
            <a href="found_item.php" class="btn secondary">Found item</a>
        </div>
    </div>
</body>
</html>