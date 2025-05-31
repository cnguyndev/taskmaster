<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';

$response = ['exists' => false, 'message' => 'Lỗi không xác định.'];

if (!isset($_GET['slug'])) {
    $response['message'] = 'Thiếu tham số slug.';
    $response['exists'] = true;
    echo json_encode($response);
    exit;
}

$slug = trim($_GET['slug']);

if (empty($slug)) {
    $response['exists'] = false;
    $response['message'] = '';
    echo json_encode($response);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
    $response['message'] = 'Slug chỉ được chứa chữ cái, số, dấu gạch dưới (_) và gạch ngang (-).';
    $response['exists'] = true;
    echo json_encode($response);
    exit;
}

if (strlen($slug) < 3 || strlen($slug) > 50) {
    $response['message'] = 'Slug phải có độ dài từ 3 đến 50 ký tự.';
    $response['exists'] = true;
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM shared_links WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        $response['exists'] = true;
        $response['message'] = 'Liên kết tùy chỉnh này đã được sử dụng. Vui lòng chọn tên khác.';
    } else {
        $response['exists'] = false;
        $response['message'] = 'Liên kết này có thể sử dụng!';
    }
} catch (PDOException $e) {
    error_log("Check Slug PDOException: " . $e->getMessage());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi kiểm tra liên kết.';
    $response['exists'] = true;
} catch (Exception $e) {
    error_log("Check Slug Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống khi kiểm tra liên kết.';
    $response['exists'] = true;
}

echo json_encode($response);
exit;
