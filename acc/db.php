<?php

require_once __DIR__ . '/../load_env.php';
loadEnv(__DIR__ . '/../.env');

$host = 'localhost';           // Your MySQL host
$port = 3306;                  // Change this to your custom port (default is 3306)
$db   = $_ENV['DB_DB'];       // Your database name
$user = $_ENV['DB_USER'];       // Your MySQL username
$pass = $_ENV['DB_PASS'];       // Your MySQL password


try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}
