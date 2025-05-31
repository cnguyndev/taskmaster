<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yêu cầu đăng nhập.';
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['new_username'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$newUsername = trim($_POST['new_username']);

if (empty($newUsername)) {
    $response['message'] = 'Tên người dùng mới không được để trống.';
    echo json_encode($response);
    exit;
}
if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
    $response['message'] = 'Tên người dùng phải từ 3 đến 50 ký tự.';
    echo json_encode($response);
    exit;
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
    $stmtCheck->execute(['username' => $newUsername, 'user_id' => $userId]);
    if ($stmtCheck->fetch()) {
        $response['message'] = 'Tên người dùng này đã được sử dụng.';
        echo json_encode($response);
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE users SET username = :username WHERE id = :user_id");
    if ($stmtUpdate->execute(['username' => $newUsername, 'user_id' => $userId])) {
        $_SESSION['username'] = $newUsername;
        $response['success'] = true;
        $response['message'] = 'Cập nhật tên người dùng thành công!';
        $response['newUsername'] = $newUsername;
    } else {
        $response['message'] = 'Lỗi khi cập nhật tên người dùng.';
        error_log("Update Username Error: Execute failed. PDO errorInfo: " . print_r($stmtUpdate->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Update Username PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}
echo json_encode($response);
exit;
