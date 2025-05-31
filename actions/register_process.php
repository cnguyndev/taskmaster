<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định khi xử lý đăng ký.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức không hợp lệ.';
    echo json_encode($response);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($email) || empty($password)) {
    $response['message'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Địa chỉ email không hợp lệ.';
    echo json_encode($response);
    exit;
}

if (strlen($username) < 3 || strlen($username) > 50) {
    $response['message'] = 'Tên người dùng phải từ 3 đến 50 ký tự.';
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) {
    $response['message'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    echo json_encode($response);
    exit;
}

try {
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
    $stmtCheck->execute(['email' => $email, 'username' => $username]);
    if ($stmtCheck->fetch()) {
        $response['message'] = 'Email hoặc tên người dùng này đã được sử dụng. Vui lòng chọn thông tin khác.';
        echo json_encode($response);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin, created_at) VALUES (:username, :email, :password_hash, 0, NOW())");
    if ($insertStmt->execute(['username' => $username, 'email' => $email, 'password_hash' => $passwordHash])) {
        $response['success'] = true;
        $response['message'] = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
    } else {
        $response['message'] = 'Đăng ký không thành công do lỗi từ CSDL.';
        error_log("Register Process Error: Failed to insert user. PDO errorInfo: " . print_r($insertStmt->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Register Process PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    if ($e->getCode() == 23000) {
        $response['message'] = 'Email hoặc tên người dùng đã tồn tại (Lỗi CSDL).';
    } else {
        $response['message'] = 'Lỗi cơ sở dữ liệu khi đăng ký.';
    }
} catch (Exception $e) {
    error_log("Register Process Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn khi đăng ký.';
}

if (!headers_sent()) {
}
echo json_encode($response);
exit;
