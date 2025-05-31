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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$isAdmin = filter_var($_POST['is_admin'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0, 'max_range' => 1]]);


if (empty($username) || empty($email) || empty($password)) {
    $response['message'] = 'Tên người dùng, email và mật khẩu là bắt buộc.';
    echo json_encode($response);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Email không hợp lệ.';
    echo json_encode($response);
    exit;
}
if (strlen($password) < 6) {
    $response['message'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    echo json_encode($response);
    exit;
}
if ($isAdmin === null) {
    $response['message'] = 'Quyền admin không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $stmtCheck->execute(['email' => $email, 'username' => $username]);
    if ($stmtCheck->fetch()) {
        $response['message'] = 'Email hoặc tên người dùng đã tồn tại.';
        echo json_encode($response);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmtInsert = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin, created_at) VALUES (:username, :email, :password_hash, :is_admin, NOW())");
    if ($stmtInsert->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'is_admin' => $isAdmin
    ])) {
        $response['success'] = true;
        $response['message'] = 'Tạo người dùng mới thành công!';
    } else {
        $response['message'] = 'Lỗi khi tạo người dùng.';
    }
} catch (PDOException $e) {
    error_log("Admin Create User PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}
echo json_encode($response);
exit;
