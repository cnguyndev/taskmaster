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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['shared_link_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}
$sharedLinkId = filter_var($_POST['shared_link_id'], FILTER_VALIDATE_INT);
if ($sharedLinkId === false || $sharedLinkId <= 0) {
    $response['message'] = 'ID liên kết không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE shared_links SET is_deleted = TRUE, deleted_on = NOW() WHERE id = :link_id AND is_deleted = FALSE");
    if ($stmt->execute(['link_id' => $sharedLinkId])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Đã chuyển link vào thùng rác.';
        } else {
            $response['message'] = 'Link không tồn tại hoặc đã ở trong thùng rác.';
        }
    } else {
        $response['message'] = 'Lỗi khi xử lý.';
    }
} catch (PDOException $e) {
    error_log("Admin Trash Link: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL.';
}
echo json_encode($response);
exit;
