<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yêu cầu đăng nhập.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['task_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}

$taskId = filter_var($_POST['task_id'], FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];

if ($taskId === false || $taskId <= 0) {
    $response['message'] = 'ID công việc không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmtOld = $pdo->prepare("SELECT content, is_completed, list_id FROM tasks WHERE id = :task_id AND user_id = :user_id");
    $stmtOld->execute(['task_id' => $taskId, 'user_id' => $userId]);
    $deletedTaskData = $stmtOld->fetch();

    if (!$deletedTaskData) {
        $response['message'] = 'Công việc không tồn tại hoặc bạn không có quyền xóa.';
        echo json_encode($response);
        exit;
    }

    $pdo->beginTransaction();

    $stmtDeleteHistory = $pdo->prepare("DELETE FROM task_edit_history WHERE task_id = :task_id");
    $stmtDeleteHistory->execute(['task_id' => $taskId]);

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id");
    if ($stmt->execute(['task_id' => $taskId, 'user_id' => $userId])) {
        if ($stmt->rowCount() > 0) {
            log_task_action($pdo, $userId, $taskId, 'deleted', $deletedTaskData, null);
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Xóa công việc thành công.';
        } else {
            $pdo->rollBack();
            $response['message'] = 'Không thể xóa công việc (không tìm thấy hoặc không có quyền).';
        }
    } else {
        $pdo->rollBack();
        $response['message'] = 'Lỗi khi xóa công việc.';
        error_log("Delete Task Action Error: Execute failed. PDO errorInfo: " . print_r($stmt->errorInfo(), true));
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Delete Task PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Delete Task Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống.';
}

echo json_encode($response);
exit;
