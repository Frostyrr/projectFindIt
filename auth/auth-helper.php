<?php

// Ensure session is running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require a user to be logged in and have a specific role.
 * Example usage: requireRole('admin');
 */
function requireRole($requiredRole) {
    // If they aren't logged in at all
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?error=not_logged_in");
        exit();
    }

    // If they are logged in but lack the required role
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header("Location: browse.php?error=unauthorized");
        exit();
    }
}

/**
 * Simple boolean check if the current user is an admin.
 * Useful for hiding/showing buttons in the UI.
 */
function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}
?>