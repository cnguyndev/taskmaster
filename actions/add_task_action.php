<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);


$response = ['success' => false, 'message' => 'Lỗi không xác định khi thêm công việc.'];


try {
    require_once '../includes/db_connect.php';
    require_once '../includes/functions.php';

    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Yêu cầu đăng nhập để thực hiện hành động này.';
        echo json_encode($response);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Phương thức yêu cầu không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    if (!isset($_POST['content']) || !isset($_POST['list_id'])) {
        $response['message'] = 'Dữ liệu không đầy đủ. Vui lòng cung cấp nội dung và ID danh sách.';
        echo json_encode($response);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $listId = filter_var($_POST['list_id'], FILTER_VALIDATE_INT);
    $content = trim((string)$_POST['content']);

    if (empty($content)) {
        $response['message'] = 'Nội dung công việc không được để trống.';
        echo json_encode($response);
        exit;
    }
    if ($listId === false || $listId <= 0) {
        $response['message'] = 'ID danh sách không hợp lệ: ' . htmlspecialchars($_POST['list_id'] ?? 'NOT PROVIDED');
        echo json_encode($response);
        exit;
    }


    $stmtCheckList = $pdo->prepare("SELECT id FROM task_lists WHERE id = :list_id AND user_id = :user_id");
    $stmtCheckList->execute(['list_id' => $listId, 'user_id' => $userId]);
    if (!$stmtCheckList->fetch()) {
        $response['message'] = 'Danh sách không tồn tại hoặc bạn không có quyền thêm công việc vào danh sách này.';
        echo json_encode($response);
        exit;
    }


    $stmtInsertTask = $pdo->prepare("INSERT INTO tasks (user_id, list_id, content, is_completed, created_at, updated_at) VALUES (:user_id, :list_id, :content, 0, NOW(), NOW())");
    $stmtInsertTask->execute([
        'user_id' => $userId,
        'list_id' => $listId,
        'content' => $content
    ]);
    $newTaskId = $pdo->lastInsertId();

    if ($newTaskId) {
        $response['success'] = true;
        $response['message'] = 'Thêm công việc thành công!';
        $response['task'] = [
            'id' => (int)$newTaskId,
            'user_id' => (int)$userId,
            'list_id' => (int)$listId,
            'content' => $content,
            'is_completed' => 0,
            'created_at' => date("Y-m-d H:i:s")
        ];
    } else {
        $response['message'] = 'Không thể thêm công việc vào cơ sở dữ liệu. Vui lòng thử lại.';
        error_log("Add Task Error: Failed to insert task into DB. PDO errorInfo: " . print_r($stmtInsertTask->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Add Task - PDOException: " . $e->getMessage() . " (Code: " . $e->getCode() . ") - Trace: " . $e->getTraceAsString());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi thêm công việc. Vui lòng liên hệ quản trị viên.';
} catch (Throwable $t) {
    error_log("Add Task - Throwable: " . $t->getMessage() . " - Trace: " . $t->getTraceAsString());
    $response['message'] = 'Lỗi hệ thống không mong muốn khi thêm công việc. Vui lòng liên hệ quản trị viên.';
}


if (!headers_sent()) {
}
echo json_encode($response);
exit;
