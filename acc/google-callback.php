<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../vendor/autoload.php';
require_once 'db.php'; // Include your DB connection
require_once 'load_env.php';
loadEnv(__DIR__ . '../.env');

session_start();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIR_URL']);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];

        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        // Save user info in session
        $_SESSION['user_name'] = $userInfo->name;
        $_SESSION['user_email'] = $userInfo->email;
        $_SESSION['user_picture'] = $userInfo->picture;
        $_SESSION['google_id'] = $userInfo->id;

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$userInfo->id]);
        $user = $stmt->fetch();

        if (!$user) {
            // Insert new user
            $insert = $pdo->prepare("INSERT INTO users (google_id, name, email, picture) VALUES (?, ?, ?, ?)");
            $insert->execute([
                $userInfo->id,
                $userInfo->name,
                $userInfo->email,
                $userInfo->picture
            ]);
        }

        header('Location: ../?login=1');
        exit;
    }
}
header('Location: ../');
exit;
