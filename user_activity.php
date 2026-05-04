<?php
if (isset($_SESSION['user']['id'])) {

    if (!isset($_SESSION['last_activity_update']) || 
        time() - $_SESSION['last_activity_update'] > 60) {

        $id = $_SESSION['user']['id'];

        $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $_SESSION['last_activity_update'] = time();
    }
}
?>