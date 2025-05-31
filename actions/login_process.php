<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định khi đăng nhập.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức không hợp lệ.';
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    $response['message'] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Địa chỉ email không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $email;
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['logged_in_time'] = time();

        $response['success'] = true;
        $response['message'] = 'Đăng nhập thành công!';
        $response['redirect_url'] = $user['is_admin'] ? 'admin.php' : 'app.php';
    } else {
        $response['message'] = 'Email hoặc mật khẩu không chính xác.';
    }
} catch (PDOException $e) {
    error_log("Login PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi đăng nhập.';
} catch (Exception $e) {
    error_log("Login Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn khi đăng nhập.';
}

if (!headers_sent()) {
}
echo json_encode($response);
exit;
