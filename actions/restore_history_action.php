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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['history_id']) || !isset($_POST['task_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ. Thiếu history_id hoặc task_id.';
    echo json_encode($response);
    exit;
}

$historyId = filter_var($_POST['history_id'], FILTER_VALIDATE_INT);
$taskId = filter_var($_POST['task_id'], FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];

if ($historyId === false || $taskId === false || $historyId <= 0 || $taskId <= 0) {
    $response['message'] = 'Dữ liệu ID không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtCheckTask = $pdo->prepare("SELECT content, is_completed, list_id FROM tasks WHERE id = :task_id AND user_id = :user_id");
    $stmtCheckTask->execute(['task_id' => $taskId, 'user_id' => $userId]);
    $currentTaskData = $stmtCheckTask->fetch(PDO::FETCH_ASSOC);
    if (!$currentTaskData) {
        $response['message'] = 'Bạn không có quyền khôi phục công việc này hoặc công việc không tồn tại.';
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }

    $stmtHistory = $pdo->prepare("SELECT action, old_data_json, new_data_json FROM task_edit_history WHERE id = :history_id AND task_id = :task_id");
    $stmtHistory->execute(['history_id' => $historyId, 'task_id' => $taskId]);
    $historyEntry = $stmtHistory->fetch(PDO::FETCH_ASSOC);

    if (!$historyEntry) {
        $response['message'] = 'Không tìm thấy bản ghi lịch sử hợp lệ để khôi phục.';
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }

    $dataToRestoreJson = null;
    if ($historyEntry['action'] === 'deleted' && !empty($historyEntry['old_data_json'])) {
        $dataToRestoreJson = $historyEntry['old_data_json'];
    } elseif (!empty($historyEntry['new_data_json'])) {
        $dataToRestoreJson = $historyEntry['new_data_json'];
    } elseif (!empty($historyEntry['old_data_json']) && $historyEntry['action'] !== 'created') {
        $dataToRestoreJson = $historyEntry['old_data_json'];
    }


    if (!$dataToRestoreJson) {
        $response['message'] = 'Không có dữ liệu đầy đủ để khôi phục từ bản ghi lịch sử này.';
        error_log("Restore Error: No valid JSON data in history_id $historyId for task_id $taskId");
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }
    $dataToRestore = json_decode($dataToRestoreJson, true);

    if (!is_array($dataToRestore)) {
        $response['message'] = 'Dữ liệu lịch sử bị lỗi định dạng.';
        error_log("Restore Error: Failed to decode JSON data for history_id $historyId. JSON: " . $dataToRestoreJson);
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }

    $newContent = $dataToRestore['content'] ?? $currentTaskData['content'];
    $newIsCompleted = array_key_exists('is_completed', $dataToRestore) ? (int)$dataToRestore['is_completed'] : (int)$currentTaskData['is_completed'];

    $stmtUpdate = $pdo->prepare("UPDATE tasks SET content = :content, is_completed = :is_completed, updated_at = NOW() WHERE id = :task_id AND user_id = :user_id");
    $updateResult = $stmtUpdate->execute([
        'content' => $newContent,
        'is_completed' => $newIsCompleted,
        'task_id' => $taskId,
        'user_id' => $userId
    ]);

    if ($updateResult) {
        log_task_action(
            $pdo,
            $userId,
            $taskId,
            'restored',
            ['content' => $currentTaskData['content'], 'is_completed' => (int)$currentTaskData['is_completed']],
            ['content' => $newContent, 'is_completed' => $newIsCompleted, 'restored_from_history_id' => $historyId]
        );
        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Khôi phục công việc thành công!';
    } else {
        $pdo->rollBack();
        $response['message'] = 'Lỗi khi cập nhật công việc trong quá trình khôi phục.';
        error_log("Restore Task Error: Update execute failed. PDO errorInfo: " . print_r($stmtUpdate->errorInfo(), true));
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Restore History PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Restore History Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống.';
}

echo json_encode($response);
exit;
