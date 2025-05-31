<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định khi cập nhật công việc.'];

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

if (!isset($_POST['task_id']) || !isset($_POST['content'])) {
    $response['message'] = 'Dữ liệu không đủ. Thiếu task_id hoặc content.';
    error_log("Update Task Content Error: Missing task_id or content. POST: " . print_r($_POST, true));
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$taskId = filter_var($_POST['task_id'], FILTER_VALIDATE_INT);
$newContent = trim((string)$_POST['content']);

if ($taskId === false || $taskId <= 0) {
    $response['message'] = 'ID công việc không hợp lệ.';
    echo json_encode($response);
    exit;
}

if (empty($newContent)) {
    $response['message'] = 'Nội dung công việc không được để trống.';
    echo json_encode($response);
    exit;
}
if (strlen($newContent) > 65535) {
    $response['message'] = 'Nội dung công việc quá dài.';
    echo json_encode($response);
    exit;
}

try {
    $stmtOld = $pdo->prepare("SELECT content, is_completed, list_id FROM tasks WHERE id = :task_id AND user_id = :user_id");
    $stmtOld->execute(['task_id' => $taskId, 'user_id' => $userId]);
    $oldTaskData = $stmtOld->fetch();

    if (!$oldTaskData) {
        $response['message'] = 'Công việc không tồn tại hoặc bạn không có quyền chỉnh sửa.';
        error_log("Update Task Content Error: Task not found or permission denied. Task ID: $taskId, User ID: $userId");
        echo json_encode($response);
        exit;
    }

    if ($oldTaskData['content'] === $newContent) {
        $response['success'] = true;
        $response['message'] = 'Nội dung công việc không thay đổi.';
        $response['task'] = [
            'id' => (int)$taskId,
            'content' => $newContent,
            'is_completed' => (int)$oldTaskData['is_completed'],
            'list_id' => (int)$oldTaskData['list_id'],
            'user_id' => (int)$userId
        ];
        echo json_encode($response);
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE tasks SET content = :content, updated_at = NOW() WHERE id = :task_id AND user_id = :user_id");
    $updateResult = $stmtUpdate->execute([
        'content' => $newContent,
        'task_id' => $taskId,
        'user_id' => $userId
    ]);

    if ($updateResult && $stmtUpdate->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Cập nhật nội dung công việc thành công.';
        $response['task'] = [
            'id' => (int)$taskId,
            'content' => $newContent,
            'is_completed' => (int)$oldTaskData['is_completed'],
            'list_id' => (int)$oldTaskData['list_id'],
            'user_id' => (int)$userId
        ];
        log_task_action(
            $pdo,
            $userId,
            $taskId,
            'content_updated',
            ['content' => $oldTaskData['content'], 'is_completed' => (int)$oldTaskData['is_completed']],
            ['content' => $newContent, 'is_completed' => (int)$oldTaskData['is_completed']]
        );
    } else {
        $response['message'] = 'Không có thay đổi nào được thực hiện hoặc lỗi khi cập nhật.';
        error_log("Update Task Content Warning/Error: rowCount is 0 or update failed. PDO errorInfo: " . print_r($stmtUpdate->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Update Task Content PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    $response['message'] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("Update Task Content Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn.';
}

echo json_encode($response);
exit;
