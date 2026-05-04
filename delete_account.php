<?php
session_start();
require_once 'db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete all items belonging to user
$stmt_items = $conn->prepare("DELETE FROM items WHERE user_id = ?");
$stmt_items->bind_param("i", $user_id);
$stmt_items->execute();
$stmt_items->close();

// Delete user account
$stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->close();

// Clear session
session_unset();
session_destroy();

// Redirect
header("Location: index.php?msg=account_deleted");
exit();
?>