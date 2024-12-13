<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "dbs13455438";

$dsn = "mysql:host=$hostname;dbname=$database;charset=utf8";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $connection = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    exit("Database connection failed: " . $e->getMessage());
}
?>
