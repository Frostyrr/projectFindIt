<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// --- Pagination Logic ---
$limit = 9; // Show 9 items per page (2 rows of 3)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total items for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM items WHERE user_email = ?");
$count_stmt->bind_param("s", $user_email);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_items = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Fetch items for the current page
$stmt = $conn->prepare("SELECT * FROM items WHERE user_email = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $user_email, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

?>