<?php
session_start();
include 'db.php';

// Initialize search and filter variables
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$date_filter  = isset($_GET['date'])   ? $conn->real_escape_string($_GET['date'])   : '';
$type_filter  = isset($_GET['type']) && in_array($_GET['type'], ['lost', 'found']) ? $_GET['type'] : '';

// --- Pagination Setup ---
$limit = 20;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Base condition for all queries
$where_sql = "WHERE status = 'active'";

if (!empty($search_query)) {
    $where_sql .= " AND item_name LIKE '%$search_query%'";
}
if (!empty($date_filter)) {
    $where_sql .= " AND DATE(date_lost_found) = '$date_filter'";
}
if (!empty($type_filter)) {
    $where_sql .= " AND type = '$type_filter'";
}

// Total count
$count_sql    = "SELECT COUNT(*) as total FROM items " . $where_sql;
$count_result = $conn->query($count_sql);
$total_items  = $count_result->fetch_assoc()['total'];
$total_pages  = ceil($total_items / $limit);

// Fetch items
$sql    = "SELECT * FROM items " . $where_sql . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Helper: build pagination links preserving all filters
function getPageLink($pageNum, $search, $date, $type) {
    $params = ['page' => $pageNum];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($date))   $params['date']   = $date;
    if (!empty($type))   $params['type']   = $type;
    return "?" . http_build_query($params);
}

// Helper: build filter button href preserving search/date
function typeLink($type, $search, $date) {
    $params = [];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($date))   $params['date']   = $date;
    if (!empty($type))   $params['type']   = $type;
    return "?" . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Items - FindIt</title>
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">
    <link rel="stylesheet" href="css/home/main.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/recent-reports.css">
    <link rel="stylesheet" href="css/browse.css">
    <link rel="stylesheet" href="css/pages-section.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    <?php include 'auth/login.php'; ?>

    <div class="browse-container">

        <div class="search-section">
            <h2>Browse Items</h2>
            <form method="GET" action="browse.php" class="search-filter-form">

                <div class="search-box">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search item name..."
                        value="<?= htmlspecialchars($search_query) ?>"
                    >
                    <?php if (!empty($date_filter)): ?>
                        <input type="hidden" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                    <?php endif; ?>
                    <?php if (!empty($type_filter)): ?>
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type_filter) ?>">
                    <?php endif; ?>

                    <button type="submit" class="search-icon-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 24 24">
                            <path d="M10 2a8 8 0 105.293 14.293l4.707 4.707 1.414-1.414-4.707-4.707A8 8 0 0010 2zm0 2a6 6 0 110 12A6 6 0 0110 4z"/>
                        </svg>
                    </button>
                </div>

            </form>

            <!-- Lost / Found filter buttons -->
            <div class="type-filter-buttons">
                <a href="<?= typeLink('', $search_query, $date_filter) ?>"
                   class="type-btn <?= $type_filter === '' ? 'active' : '' ?>">All</a>
                <a href="<?= typeLink('lost', $search_query, $date_filter) ?>"
                   class="type-btn lost <?= $type_filter === 'lost' ? 'active' : '' ?>">Lost</a>
                <a href="<?= typeLink('found', $search_query, $date_filter) ?>"
                   class="type-btn found <?= $type_filter === 'found' ? 'active' : '' ?>">Found</a>
            </div>
        </div>

        <div class="reports-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
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
                <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <p>No items found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= getPageLink($page - 1, $search_query, $date_filter, $type_filter) ?>" class="page-link">&laquo; Prev</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page   = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<a href="' . getPageLink(1, $search_query, $date_filter, $type_filter) . '" class="page-link">1</a>';
                    if ($start_page > 2) echo '<span class="page-link" style="border:none; background:transparent;">...</span>';
                }

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="<?= getPageLink($i, $search_query, $date_filter, $type_filter) ?>"
                       class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span class="page-link" style="border:none; background:transparent;">...</span>';
                    echo '<a href="' . getPageLink($total_pages, $search_query, $date_filter, $type_filter) . '" class="page-link">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= getPageLink($page + 1, $search_query, $date_filter, $type_filter) ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <script src="js/loginModal.js"></script>
    <script src="js/goToDetails.js"></script>
    <script src="js/DropDown.js"></script>
</body>
</html>