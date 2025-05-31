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
    $stmt = $pdo->prepare("UPDATE shared_links SET is_deleted = TRUE, deleted_on = NOW() WHERE id = :link_id AND creator_user_id = :user_id AND is_deleted = FALSE");
    if ($stmt->execute(['link_id' => $sharedLinkId, 'user_id' => $userId])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Đã chuyển liên kết vào thùng rác.';
        } else {
            $response['message'] = 'Không tìm thấy liên kết hoặc liên kết đã ở trong thùng rác.';
        }
    } else {
        $response['message'] = 'Lỗi khi xử lý yêu cầu.';
        error_log("Trash Shared Link Error: Execute failed. PDO errorInfo: " . print_r($stmt->errorInfo(), true) . " UserID: $userId, LinkID: $sharedLinkId");
    }
} catch (PDOException $e) {
    error_log("Trash Shared Link PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL.';
}
echo json_encode($response);
exit;
