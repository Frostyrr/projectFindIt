<?php
include 'db.php';
session_start();

if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "INSERT INTO users(email, password) VALUES ('$email', '$password')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $_SESSION['user'] = $email;
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
    <link rel="icon" type="image/x-icon" href="images/findIconWithBG.png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-card">
        <form action="" method="POST">
            <h2>Create Account</h2>
            <hr class="divider">
            <label for="email">Email Address</label>
            <input type="text" name="email" id="email" placeholder="Enter email address" required>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter a password" required>
            <button type="submit" class="button primary" name="register">Register</button>
            <p>Already have an account?<a href="index.php">Login</a></p>
        </form>
    </div>
</body>
</html>