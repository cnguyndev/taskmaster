<?php
if (session_status() === PHP_SESSION_NONE) {
}

function log_task_action($pdo, $userId, $taskId, $action, $oldData = null, $newData = null, $customDescription = null)
{
    if (!$pdo || !$userId || !$taskId) { 
        error_log("log_task_action: Missing pdo, userId, or taskId. UserID: $userId, TaskID: $taskId");
        return false;
    }

    $actionDescription = $customDescription;

    if ($actionDescription === null) {

        $taskContentForDesc = "";
        if ($action === 'created' && isset($newData['content'])) {
            $taskContentForDesc = $newData['content'];
            $actionDescription = "Đã tạo công việc: \"" . htmlspecialchars(mb_substr($taskContentForDesc, 0, 50)) . (mb_strlen($taskContentForDesc) > 50 ? "..." : "") . "\"";
        } elseif ($action === 'deleted' && isset($oldData['content'])) {
            $taskContentForDesc = $oldData['content'];
            $actionDescription = "Đã xóa công việc: \"" . htmlspecialchars(mb_substr($taskContentForDesc, 0, 50)) . (mb_strlen($taskContentForDesc) > 50 ? "..." : "") . "\"";
        } elseif ($action === 'status_changed' && isset($newData['is_completed'])) {
            $newStatus = ($newData['is_completed'] == 1) ? "Hoàn thành" : "Chưa hoàn thành";
            $actionDescription = "Đổi trạng thái thành: $newStatus";
        } elseif ($action === 'content_updated' && isset($newData['content']) && isset($oldData['content'])) {
            $actionDescription = "Cập nhật nội dung từ: \"" . htmlspecialchars(mb_substr($oldData['content'], 0, 30)) . "...\" thành \"" . htmlspecialchars(mb_substr($newData['content'], 0, 30)) . "...\"";
        } elseif ($action === 'restored') {
            $actionDescription = "Đã khôi phục công việc từ lịch sử.";
            if (isset($newData['restored_from_history_id'])) {
                $actionDescription .= " (ID lịch sử: " . $newData['restored_from_history_id'] . ")";
            }
        } else {
            $actionDescription = "Hành động: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $action)));
            if (isset($newData['content'])) $actionDescription .= " - \"" . htmlspecialchars(mb_substr($newData['content'], 0, 30)) . "...\"";
        }
    }

    $oldDataJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
    $newDataJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO task_edit_history (task_id, user_id, action, action_description, old_data_json, new_data_json, created_at) VALUES (:task_id, :user_id, :action, :action_description, :old_data_json, :new_data_json, NOW())");
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'action' => $action,
            'action_description' => $actionDescription,
            'old_data_json' => $oldDataJson,
            'new_data_json' => $newDataJson
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("History Logging PDOException for task_id {$taskId}, action {$action}: " . $e->getMessage());
        return false;
    }
}
