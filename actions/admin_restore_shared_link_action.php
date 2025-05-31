<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';
$response = ['success' => false, 'message' => 'Lỗi không xác định khi khôi phục liên kết.'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Truy cập bị từ chối. Bạn không có quyền thực hiện hành động này.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['shared_link_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ. Thiếu thông tin ID liên kết.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$sharedLinkId = filter_var($_POST['shared_link_id'], FILTER_VALIDATE_INT);

if ($sharedLinkId === false || $sharedLinkId <= 0) {
    $response['message'] = 'ID liên kết không hợp lệ.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE shared_links SET is_deleted = FALSE, deleted_on = NULL WHERE id = :link_id AND is_deleted = TRUE");

    if ($stmt->execute(['link_id' => $sharedLinkId])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Đã khôi phục liên kết thành công.';
        } else {
            $checkStmt = $pdo->prepare("SELECT id, is_deleted FROM shared_links WHERE id = :link_id");
            $checkStmt->execute(['link_id' => $sharedLinkId]);
            $linkExists = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$linkExists) {
                $response['message'] = 'Không tìm thấy liên kết này trong hệ thống.';
                http_response_code(404);
            } elseif ($linkExists['is_deleted'] == FALSE) {
                $response['message'] = 'Liên kết này không ở trong thùng rác (đã được khôi phục trước đó hoặc chưa từng bị xóa).';
            } else {
                $response['message'] = 'Không thể khôi phục liên kết. Có thể do lỗi không xác định.';
                http_response_code(500);
            }
        }
    } else {
        $response['message'] = 'Lỗi khi thực thi yêu cầu khôi phục trên cơ sở dữ liệu.';
        error_log("Admin Restore Link Error: Execute failed. PDO errorInfo: " . print_r($stmt->errorInfo(), true));
        http_response_code(500);
    }
} catch (PDOException $e) {
    error_log("Admin Restore Link PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi khôi phục liên kết. Vui lòng thử lại sau.';
    http_response_code(500);
} catch (Exception $e) {
    error_log("Admin Restore Link Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn. Vui lòng thử lại sau.';
    http_response_code(500);
}

echo json_encode($response);
exit;
