<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    header("Location: login.php");
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

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("
        INSERT INTO users (oauth_provider, oauth_uid, name, email, picture)
        VALUES ('google', ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $userInfo->id,
        $userInfo->name,
        $userInfo->email,
        $userInfo->picture
    );

    $stmt->execute();
}

$_SESSION['user'] = [
    'name' => $userInfo->name,
    'email' => $userInfo->email,
    'picture' => $userInfo->picture
];

header("Location: index.php");
exit();