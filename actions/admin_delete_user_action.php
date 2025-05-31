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
$userIdToDelete = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);

if ($userIdToDelete === false || $userIdToDelete <= 0) {
    $response['message'] = 'ID người dùng không hợp lệ.';
    echo json_encode($response);
    exit;
}

if ($userIdToDelete === $adminId) {
    $response['message'] = 'Bạn không thể tự xóa chính mình.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmtDeleteUser = $pdo->prepare("DELETE FROM users WHERE id = :user_id_to_delete");
    if ($stmtDeleteUser->execute(['user_id_to_delete' => $userIdToDelete])) {
        if ($stmtDeleteUser->rowCount() > 0) {
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Xóa người dùng thành công.';
        } else {
            $pdo->rollBack();
            $response['message'] = 'Không tìm thấy người dùng để xóa.';
        }
    } else {
        $pdo->rollBack();
        $response['message'] = 'Lỗi khi xóa người dùng.';
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin Delete User PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
}
echo json_encode($response);
exit;
