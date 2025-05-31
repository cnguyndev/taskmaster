<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.', 'history' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yêu cầu đăng nhập.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['task_id'])) {
    $response['message'] = 'Thiếu ID công việc.';
    echo json_encode($response);
    exit;
}

$taskId = filter_var($_GET['task_id'], FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];

if ($taskId === false || $taskId <= 0) {
    $response['message'] = 'ID công việc không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmtCheckTask = $pdo->prepare("SELECT id FROM tasks WHERE id = :task_id AND user_id = :user_id");
    $stmtCheckTask->execute(['task_id' => $taskId, 'user_id' => $userId]);
    if (!$stmtCheckTask->fetch()) {
        $response['message'] = 'Bạn không có quyền xem lịch sử của công việc này hoặc công việc không tồn tại.';
        error_log("Get History Error: Permission denied or task not found for task ID {$taskId}, user ID {$userId}");
        echo json_encode($response);
        exit;
    }

    $stmtHistory = $pdo->prepare("SELECT id, task_id, user_id, action, action_description, old_data_json, new_data_json, created_at
                                  FROM task_edit_history
                                  WHERE task_id = :task_id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                  ORDER BY created_at DESC LIMIT 50");
    $stmtHistory->execute(['task_id' => $taskId]);
    $historyEntries = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['history'] = $historyEntries;
} catch (PDOException $e) {
    error_log("Get History PDOException for task_id {$taskId}: " . $e->getMessage());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi lấy lịch sử.';
} catch (Exception $e) {
    error_log("Get History Exception for task_id {$taskId}: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn khi lấy lịch sử.';
}

echo json_encode($response);
exit;
