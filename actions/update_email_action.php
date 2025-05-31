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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['new_email']) || !isset($_POST['current_password'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$newEmail = trim($_POST['new_email']);
$currentPassword = $_POST['current_password'];

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Địa chỉ email mới không hợp lệ.';
    echo json_encode($response);
    exit;
}
if (empty($currentPassword)) {
    $response['message'] = 'Vui lòng nhập mật khẩu hiện tại để xác nhận.';
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

    $stmtCheckEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
    $stmtCheckEmail->execute(['email' => $newEmail, 'user_id' => $userId]);
    if ($stmtCheckEmail->fetch()) {
        $response['message'] = 'Địa chỉ email này đã được sử dụng.';
        echo json_encode($response);
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id");
    if ($stmtUpdate->execute(['email' => $newEmail, 'user_id' => $userId])) {
        $_SESSION['email'] = $newEmail;
        $response['success'] = true;
        $response['message'] = 'Cập nhật email thành công! Bạn có thể cần xác thực email mới (nếu có).';
        $response['newEmail'] = $newEmail;
    } else {
        $response['message'] = 'Lỗi khi cập nhật email.';
        error_log("Update Email Error: Execute failed. PDO errorInfo: " . print_r($stmtUpdate->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Update Email PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL.';
}
echo json_encode($response);
exit;
