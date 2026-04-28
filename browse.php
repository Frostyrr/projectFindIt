<?php
session_start();
include 'db.php';

// Initialize search and filter variables
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';

// Base query
$sql = "SELECT * FROM items WHERE status = 'active'";

// Append conditions if filters are applied
if (!empty($search_query)) {
    $sql .= " AND item_name LIKE '%$search_query%'";
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(date_lost_found) = '$date_filter'";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Items - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css"> <link rel="stylesheet" href="css/browse.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'login.php'; ?>

    <div class="browse-container">
        
        <div class="search-section">
            <h2>Browse Reported Items</h2>
            <form method="GET" action="browse.php" class="search-filter-form">

                <div class="search-box">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search item name..." 
                        value="<?= htmlspecialchars($search_query) ?>"
                    >

                    <button type="submit" class="search-icon-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 24 24">
                            <path d="M10 2a8 8 0 105.293 14.293l4.707 4.707 1.414-1.414-4.707-4.707A8 8 0 0010 2zm0 2a6 6 0 110 12A6 6 0 0110 4z"/>
                        </svg>
                    </button>

                </div>

            </form>
        </div>

        <div class="reports-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="report-card" onclick="goToDetails(<?= $row['id'] ?>)">
                        <div class="card-image-container">
                            <?php if (!empty($row['image_path'])): ?>
                                <img src="<?= htmlspecialchars($row['image_path']) ?>" class="card-image" alt="Item Image">
                            <?php else: ?>
                                <div class="card-image placeholder-image">No Image Available</div>
                            <?php endif; ?>
                            
                            <div class="card-badges">
                                <?php if (strtolower($row['status']) === 'found'): ?>
                                    <span class="badge status-badge found" style="background-color: #3d7a54;">Found</span>
                                <?php else: ?>
                                    <span class="badge status-badge <?= strtolower($row['type']) ?>" 
                                          style="background-color: <?= strtolower($row['type']) === 'lost' ? '#d9534f' : '#3d7a54' ?>;">
                                        <?= ucfirst(htmlspecialchars($row['type'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="card-meta">
                                <span class="meta-item">📅 <?= !empty($row['date_lost_found']) ? date('M d, Y', strtotime($row['date_lost_found'])) : date('M d, Y', strtotime($row['created_at'])) ?></span>
                            </div>
                            
                            <h3 class="card-title"><?= htmlspecialchars($row['item_name']) ?></h3>
                            <p class="card-desc"><?= htmlspecialchars($row['description']) ?></p>
                            
                            <hr class="card-divider">
                            
                            <div class="card-footer">
                                <span class="card-location">📍 <?= htmlspecialchars($row['location']) ?></span>
                                <a href="item_details.php?id=<?= $row['id'] ?>" class="view-details-btn">VIEW DETAILS</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No items found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/loginModal.js"></script>
    <script src="js/goToDetails.js"></script>
    <script src="js/DropDown.js"></script>

</body>
</html>