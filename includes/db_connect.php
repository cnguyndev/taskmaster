<?php
$host = 'localhost';
$db   = 'taskmaster_db'; 
$user = 'root';          
$pass = '';              
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.");
}
