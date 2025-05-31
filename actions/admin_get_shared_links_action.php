<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi.', 'links' => [], 'totalPages' => 0, 'currentPage' => 1, 'totalItems' => 0];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Truy cập bị từ chối.';
    echo json_encode($response);
    exit;
}

$itemsPerPage = filter_var($_GET['limit'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['default' => 15, 'min_range' => 5]]);
$currentPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM shared_links");
    $totalItems = (int)$countStmt->fetchColumn();
    $response['totalItems'] = $totalItems;
    $response['totalPages'] = $totalItems > 0 ? ceil($totalItems / $itemsPerPage) : 0;

    if ($currentPage > $response['totalPages'] && $response['totalPages'] > 0) {
        $currentPage = $response['totalPages'];
    }
    $offset = ($currentPage - 1) * $itemsPerPage;
    $response['currentPage'] = $currentPage;

    $stmt = $pdo->prepare("
        SELECT sl.id, sl.slug, sl.created_at, sl.is_deleted, sl.deleted_on, sl.password_hash IS NOT NULL as has_password,
               u.username as creator_username, tl.name as task_list_name
        FROM shared_links sl
        JOIN users u ON sl.creator_user_id = u.id
        JOIN task_lists tl ON sl.list_id = tl.id
        ORDER BY sl.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['links'] = array_map(function ($link) {
        $link['id'] = (int)$link['id'];
        $link['is_deleted'] = (int)$link['is_deleted'];
        $link['has_password'] = (int)$link['has_password'];
        return $link;
    }, $links);
    $response['success'] = true;
    $response['message'] = 'Lấy danh sách link chia sẻ thành công.';
} catch (PDOException $e) {
    error_log("Admin Get Shared Links PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL khi lấy link chia sẻ.';
}
echo json_encode($response);
exit;
