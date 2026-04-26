<?php
include 'db.php';

if (!isset($_GET['id'])) {
    header("location: index.php");
    exit();
} else {
    $id = $_GET['id'];
}

if (isset($_POST['update'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "UPDATE `users` SET email='$email', password='$password' WHERE id = $id";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("location: dashboard.php");
    }
}

$sql = "SELECT * FROM `users` WHERE id = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-card">
        <form action="" method="POST">
            <h2>Update Account</h2>
            <hr class="divider">
            <label for="email">Email Address</label>
            <input type="text" name="email" id="email" placeholder="Enter a new email address" value="<?php echo $row['email']; ?>">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter a new password">
            <button type="submit" class="button primary" name="update">Update</button>
            <button type="button" class="button secondary" onclick="window.location.href='dashboard.php'">Cancel</button>

        </form>
    </div>
</body>
</html>