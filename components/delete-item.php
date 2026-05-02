<?php
session_start();
require_once '/../db.php'; // Ensure this points to your actual database connection file

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// 2. Process the deletion if a POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = filter_var($_POST['item_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    try {
        // 3. Delete the item ONLY if it belongs to the logged-in user
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            // Success
            header("Location: profile.php?msg=item_deleted");
        } else {
            // Failed (item didn't exist or didn't belong to them)
            header("Location: profile.php?error=delete_failed");
        }
        exit();

    } catch (PDOException $e) {
        // Log the error in a real app, but for now display it to help you debug
        die("Database error during deletion: " . $e->getMessage());
    }
} else {
    // Redirect if accessed directly via URL
    header("Location: profile.php");
    exit();
}
?>