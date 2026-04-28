<?php
session_start();
require_once 'db.php';

// Security check
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['user']['email'];

// Delete all items reported by this user 
$stmt_items = $conn->prepare("DELETE FROM items WHERE user_email = ?");
$stmt_items->bind_param("s", $user_email);
$stmt_items->execute();
$stmt_items->close();

//Delete the user from the users table
$stmt_user = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt_user->bind_param("s", $user_email);
$stmt_user->execute();
$stmt_user->close();

session_unset();
session_destroy();

// Redirect to homepage
header("Location: index.php?msg=account_deleted");
exit();
?>