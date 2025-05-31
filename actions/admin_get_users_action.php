<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định.', 'users' => [], 'totalPages' => 0, 'currentPage' => 1];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Truy cập bị từ chối.';
    echo json_encode($response);
    exit;
}

$itemsPerPage = filter_var($_GET['limit'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['default' => 15, 'min_range' => 5]]);
$currentPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = (int)$countStmt->fetchColumn();
    $response['totalPages'] = $totalUsers > 0 ? ceil($totalUsers / $itemsPerPage) : 0;

    if ($currentPage > $response['totalPages'] && $response['totalPages'] > 0) {
        $currentPage = $response['totalPages'];
    }
    $offset = ($currentPage - 1) * $itemsPerPage;
    $response['currentPage'] = $currentPage;

    $stmt = $pdo->prepare("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['users'] = array_map(function ($user) {
        $user['is_admin'] = (int)$user['is_admin'];
        return $user;
    }, $users);
    $response['success'] = true;
    $response['message'] = 'Lấy danh sách người dùng thành công.';
} catch (PDOException $e) {
    error_log("Admin Get Users PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi CSDL.';
}
echo json_encode($response);
exit;
