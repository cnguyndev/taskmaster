<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Truy cập bị từ chối.';
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$adminId = $_SESSION['user_id'];
$userIdToUpdate = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$newUsername = trim($_POST['username'] ?? '');
$newEmail = trim($_POST['email'] ?? '');
$newIsAdmin = filter_var($_POST['is_admin'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
$newPassword = trim($_POST['new_password'] ?? '');


if ($userIdToUpdate === false || $userIdToUpdate <= 0) {
    $response['message'] = 'ID người dùng không hợp lệ.';
    echo json_encode($response);
    exit;
}
if (empty($newUsername) || empty($newEmail)) {
    $response['message'] = 'Tên người dùng và email không được để trống.';
    echo json_encode($response);
    exit;
}
if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Email không hợp lệ.';
    echo json_encode($response);
    exit;
}
if ($newIsAdmin === null) {
    $response['message'] = 'Quyền admin không hợp lệ.';
    echo json_encode($response);
    exit;
}
if (!empty($newPassword) && strlen($newPassword) < 6) {
    $response['message'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    echo json_encode($response);
    exit;
}
if ($userIdToUpdate === $adminId && $newIsAdmin == 0) {
    $response['message'] = 'Bạn không thể tự bỏ quyền quản trị của chính mình.';
    echo json_encode($response);
    exit;
}


try {
    $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id_to_update");
    $stmtCheckUser->execute(['username' => $newUsername, 'user_id_to_update' => $userIdToUpdate]);
    if ($stmtCheckUser->fetch()) {
        $response['message'] = "Tên người dùng '$newUsername' đã được sử dụng.";
        echo json_encode($response);
        exit;
    }
    $stmtCheckEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id_to_update");
    $stmtCheckEmail->execute(['email' => $newEmail, 'user_id_to_update' => $userIdToUpdate]);
    if ($stmtCheckEmail->fetch()) {
        $response['message'] = "Email '$newEmail' đã được sử dụng.";
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE users SET username = :username, email = :email, is_admin = :is_admin";
    $params = [
        'username' => $newUsername,
        'email' => $newEmail,
        'is_admin' => $newIsAdmin,
        'user_id' => $userIdToUpdate
    ];

    if (!empty($newPassword)) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql .= ", password_hash = :password_hash";
        $params['password_hash'] = $newPasswordHash;
    }
    $sql .= " WHERE id = :user_id";

    $stmtUpdate = $pdo->prepare($sql);
    if ($stmtUpdate->execute($params)) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật thông tin người dùng thành công!';
        $response['user'] = ['id' => $userIdToUpdate, 'username' => $newUsername, 'email' => $newEmail, 'is_admin' => $newIsAdmin];
    } else {
        $response['message'] = 'Lỗi khi cập nhật người dùng.';
    }
} catch (PDOException $e) {
    error_log("Admin Update User PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}
echo json_encode($response);
exit;
