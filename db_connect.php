<?php
$host = 'localhost';
$db   = 'Spark1';      //  
$user = 'root';        // MAMP default user
$pass = 'root';        // MAMP default password (
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // easier results
    PDO::ATTR_EMULATE_PREPARES   => false,                  // safer
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

