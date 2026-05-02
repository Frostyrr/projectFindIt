<?php
session_start();
// Fixed the file path issue here as well
require_once '../db.php'; 

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
        // 3. Delete the item ONLY if it belongs to the logged-in user using MySQLi
        // Assuming your database connection variable in db.php is named $conn
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
        
        // "ii" means we are passing two Integers ($item_id and $user_id)
        $stmt->bind_param("ii", $item_id, $user_id);
        $stmt->execute();

        // Check if any rows were actually deleted
        if ($stmt->affected_rows > 0) {
            // Success
            header("Location: profile.php?msg=item_deleted");
        } else {
            // Failed (item didn't exist or didn't belong to them)
            header("Location: profile.php?error=delete_failed");
        }
        
        $stmt->close();
        exit();

    } catch (Exception $e) {
        // Log the error in a real app, but for now display it to help you debug
        die("Database error during deletion: " . $e->getMessage());
    }
} else {
    // Redirect if accessed directly via URL
    header("Location: profile.php");
    exit();
}
?>