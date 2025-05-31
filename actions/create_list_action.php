<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định khi xử lý yêu cầu.'];

try {
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Yêu cầu đăng nhập.';
        echo json_encode($response);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Phương thức không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? null);

    if (empty($name)) {
        $response['message'] = 'Tên danh sách không được để trống.';
        echo json_encode($response);
        exit;
    }
    if (strlen($name) > 255) {
        $response['message'] = 'Tên danh sách quá dài (tối đa 255 ký tự).';
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO task_lists (user_id, name, description, created_at, updated_at) VALUES (:user_id, :name, :description, NOW(), NOW())");
    $stmt->execute([
        'user_id' => $userId,
        'name' => $name,
        'description' => $description
    ]);
    $newListId = $pdo->lastInsertId();

    if ($newListId) {
        $response['success'] = true;
        $response['message'] = 'Tạo danh sách thành công!';
        $response['list'] = [
            'id' => (int)$newListId,
            'name' => $name,
            'description' => $description,
            'user_id' => (int)$userId
        ];
    } else {
        $response['message'] = 'Không thể tạo danh sách vào CSDL.';
        error_log("Create Task List Error: lastInsertId was 0 or false. PDO errorInfo: " . print_r($stmt->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Create Task List PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    if ($e->getCode() == 23000) {
        $response['message'] = 'Tên danh sách này có thể đã được sử dụng. Vui lòng chọn tên khác.';
    } else {
        $response['message'] = 'Lỗi cơ sở dữ liệu khi tạo danh sách.';
    }
} catch (Exception $e) {
    error_log("Create Task List Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn khi tạo danh sách.';
}

if (!headers_sent()) {
}
echo json_encode($response);
exit;
