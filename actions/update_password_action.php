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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['current_password']) || !isset($_POST['new_password'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$currentPassword = $_POST['current_password'];
$newPassword = $_POST['new_password'];

if (empty($currentPassword) || empty($newPassword)) {
    $response['message'] = 'Vui lòng điền đầy đủ các trường mật khẩu.';
    echo json_encode($response);
    exit;
}
if (strlen($newPassword) < 6) {
    $response['message'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    echo json_encode($response);
    exit;
}

try {
    $stmtUser = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id");
    $stmtUser->execute(['user_id' => $userId]);
    $user = $stmtUser->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        $response['message'] = 'Mật khẩu hiện tại không chính xác.';
        echo json_encode($response);
        exit;
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
    if ($stmtUpdate->execute(['password_hash' => $newPasswordHash, 'user_id' => $userId])) {
        $response['success'] = true;
        $response['message'] = 'Đổi mật khẩu thành công!';
    } else {
        $response['message'] = 'Lỗi khi đổi mật khẩu.';
        error_log("Update Password Error: Execute failed. PDO errorInfo: " . print_r($stmtUpdate->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Update Password PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL.';
}
echo json_encode($response);
exit;
