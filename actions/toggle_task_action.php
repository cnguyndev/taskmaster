<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yêu cầu đăng nhập.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['task_id']) || !isset($_POST['is_completed'])) {
    $response['message'] = 'Yêu cầu không hợp lệ. Thiếu task_id hoặc is_completed.';
    error_log("Toggle Task Action Error: Invalid request. POST: " . print_r($_POST, true));
    echo json_encode($response);
    exit;
}

$taskId = filter_var($_POST['task_id'], FILTER_VALIDATE_INT);
$isCompletedInput = $_POST['is_completed'];
$isCompletedDatabaseValue = ($isCompletedInput == '1' || $isCompletedInput === true || $isCompletedInput == 1) ? 1 : 0;
$userId = $_SESSION['user_id'];

if ($taskId === false || $taskId <= 0) {
    $response['message'] = 'ID công việc không hợp lệ.';
    error_log("Toggle Task Action Error: Invalid task_id. Raw: " . $_POST['task_id'] . " Filtered: " . $taskId);
    echo json_encode($response);
    exit;
}

try {
    $stmtOld = $pdo->prepare("SELECT content, is_completed FROM tasks WHERE id = :task_id AND user_id = :user_id");
    $stmtOld->execute(['task_id' => $taskId, 'user_id' => $userId]);
    $oldTaskData = $stmtOld->fetch();

    if (!$oldTaskData) {
        $response['message'] = 'Công việc không tồn tại hoặc bạn không có quyền thay đổi.';
        error_log("Toggle Task Action Error: Task not found or permission denied. Task ID: $taskId, User ID: $userId");
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tasks SET is_completed = :is_completed, updated_at = NOW() WHERE id = :task_id AND user_id = :user_id");
    if ($stmt->execute(['is_completed' => $isCompletedDatabaseValue, 'task_id' => $taskId, 'user_id' => $userId])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Cập nhật trạng thái công việc thành công.';
            log_task_action(
                $pdo,
                $userId,
                $taskId,
                'status_changed',
                ['content' => $oldTaskData['content'], 'is_completed' => (int)$oldTaskData['is_completed']],
                ['content' => $oldTaskData['content'], 'is_completed' => $isCompletedDatabaseValue]
            );
        } else {
            $response['message'] = 'Không có thay đổi hoặc không thể cập nhật.';
        }
    } else {
        $response['message'] = 'Lỗi khi cập nhật trạng thái công việc.';
        error_log("Toggle Task Action Error: Execute failed. PDO errorInfo: " . print_r($stmt->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Toggle Task PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("Toggle Task Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống.';
}

echo json_encode($response);
exit;
