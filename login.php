<?php
include 'db.php';
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if ($password === $user['password']) { 
            $_SESSION['user'] = $username;
            header("Location: dashboard.php");
            exit();
        }
    }

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-card">
        <form action="" method="POST">
            <h2>Welcome!</h2>
            <hr class="divider">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" required>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
            <button type="submit" class="button primary" name="login">Login</button>
            <button type="button" class="button secondary" onclick="window.location.href='register.php'">Register</button>
        </form>
    </div>
</body>
</html>