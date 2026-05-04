<?php
session_start();
require_once '../db.php';

// Security check
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    
    $item_id = (int) $_POST['item_id'];
    $user_email = $_SESSION['user']['email'];
    
    // First verify the item belongs to this user
    $check_stmt = $conn->prepare("SELECT id FROM items WHERE id = ? AND user_email = ?");
    $check_stmt->bind_param("is", $item_id, $user_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Item doesn't exist or doesn't belong to user
        $check_stmt->close();
        
        // Use the redirect parameter if provided, otherwise go to profile
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'profile.php';
        header("Location: ../" . $redirect . "?error=delete_failed");
        exit();
    }
    $check_stmt->close();
    
    // Delete the item
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $item_id, $user_email);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        
        // Use the redirect parameter if provided, otherwise go to profile
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'profile.php';
        header("Location: ../" . $redirect . "?msg=item_deleted");
        exit();
    } else {
        $stmt->close();
        
        // Use the redirect parameter if provided, otherwise go to profile
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'profile.php';
        header("Location: ../" . $redirect . "?error=delete_failed");
        exit();
    }
    
} else {
    header("Location: ../profile.php");
    exit();
}
?>