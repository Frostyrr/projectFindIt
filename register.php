<?php
include 'db.php';
session_start();

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "INSERT INTO users(username, password) VALUES ('$username', '$password')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['user'] = $username;
        header("location: login.php");
        exit();
    } else {
        header("location: register.php");
        exit();
    }

    header("location:index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-card">
        <form action="" method="POST">
            <h2>Create Account</h2>
            <hr class="divider">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Choose a username" required>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter a password" required>
            <button type="submit" class="button primary" name="register">Register</button>
            <p>Already have an account?<a href="index.php">Login</a></p>
        </form>
    </div>
</body>
</html>