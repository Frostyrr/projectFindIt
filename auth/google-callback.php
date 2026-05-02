<?php
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    header("Location: /auth/login.php");
    exit();
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();

// check user
$stmt = $conn->prepare("SELECT * FROM users WHERE oauth_uid = ? AND oauth_provider = 'google'");
$stmt->bind_param("s", $userInfo->id);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($result->num_rows == 0) {
    // New user: insert them and explicitly set role to 'user'
    $stmt = $conn->prepare("
        INSERT INTO users (oauth_provider, oauth_uid, name, email, picture, role)
        VALUES ('google', ?, ?, ?, ?, 'user')
    ");

    $stmt->bind_param(
        "ssss",
        $userInfo->id,
        $userInfo->name,
        $userInfo->email,
        $userInfo->picture
    );

    $stmt->execute();
    
    // Grab the newly created database ID and set the default role
    $db_id = $stmt->insert_id;
    $db_role = 'user';
} else {
    // Existing user: grab their ID and role from the database
    // (Assuming your primary key column is named 'id')
    $db_id = $user['id'];
    $db_role = $user['role'];
}

// Keep your existing UI session data intact
$_SESSION['user'] = [
    'name' => $userInfo->name,
    'email' => $userInfo->email,
    'picture' => $userInfo->picture
];

// Add the RBAC security variables to the session
$_SESSION['user_id'] = $db_id;
$_SESSION['role'] = $db_role;

header("Location: ../index.php");
exit();