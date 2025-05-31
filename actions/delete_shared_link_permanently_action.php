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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['shared_link_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$sharedLinkId = filter_var($_POST['shared_link_id'], FILTER_VALIDATE_INT);

if ($sharedLinkId === false || $sharedLinkId <= 0) {
    $response['message'] = 'ID liên kết không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {

        $stmt = $pdo->prepare("DELETE FROM shared_links WHERE id = :link_id");
        $params = ['link_id' => $sharedLinkId];
    } else {
        $stmt = $pdo->prepare("DELETE FROM shared_links WHERE id = :link_id AND creator_user_id = :user_id");
        $params = ['link_id' => $sharedLinkId, 'user_id' => $userId];
    }

    if ($stmt->execute($params)) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Đã xóa vĩnh viễn liên kết.';
        } else {
            $response['message'] = 'Không tìm thấy liên kết hoặc không có quyền xóa.';
        }
    } else {
        $response['message'] = 'Lỗi khi xử lý yêu cầu xóa.';
        error_log("Delete Shared Link Permanently Error: Execute failed. PDO errorInfo: " . print_r($stmt->errorInfo(), true) . " UserID: $userId, LinkID: $sharedLinkId");
    }
} catch (PDOException $e) {
    error_log("Delete Shared Link Permanently PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL khi xóa liên kết.';
}
echo json_encode($response);
exit;
