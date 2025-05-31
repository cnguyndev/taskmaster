<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định khi tạo liên kết.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Yêu cầu đăng nhập để thực hiện hành động này.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['list_id'])) {
    $response['message'] = 'Yêu cầu không hợp lệ hoặc thiếu ID danh sách.';
    echo json_encode($response);
    exit;
}

$creatorUserId = $_SESSION['user_id'];
$listId = filter_var($_POST['list_id'], FILTER_VALIDATE_INT);
$customSlug = trim($_POST['custom_slug'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($listId === false || $listId <= 0) {
    $response['message'] = 'ID danh sách không hợp lệ.';
    echo json_encode($response);
    exit;
}

try {
    $stmtCheckListOwner = $pdo->prepare("SELECT id, name FROM task_lists WHERE id = :list_id AND user_id = :user_id");
    $stmtCheckListOwner->execute(['list_id' => $listId, 'user_id' => $creatorUserId]);
    $listData = $stmtCheckListOwner->fetch();
    if (!$listData) {
        $response['message'] = 'Bạn không có quyền chia sẻ danh sách này hoặc danh sách không tồn tại.';
        error_log("Create Share Link Error: Permission denied or list not found. List ID: $listId, User ID: $creatorUserId");
        echo json_encode($response);
        exit;
    }

    $slugToUse = $customSlug;

    if (empty($customSlug)) {
        $isUnique = false;
        while (!$isUnique) {
            $slugToUse = bin2hex(random_bytes(5));
            $stmtCheckRandSlug = $pdo->prepare("SELECT id FROM shared_links WHERE slug = :slug");
            $stmtCheckRandSlug->execute(['slug' => $slugToUse]);
            if (!$stmtCheckRandSlug->fetch()) {
                $isUnique = true;
            }
        }
    } else {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $customSlug)) {
            $response['message'] = 'Liên kết tùy chỉnh chứa ký tự không hợp lệ.';
            echo json_encode($response);
            exit;
        }
        if (strlen($customSlug) < 3 || strlen($customSlug) > 50) {
            $response['message'] = 'Liên kết tùy chỉnh phải từ 3 đến 50 ký tự.';
            echo json_encode($response);
            exit;
        }
        $stmtCheckExistSlug = $pdo->prepare("SELECT id FROM shared_links WHERE slug = :slug");
        $stmtCheckExistSlug->execute(['slug' => $customSlug]);
        if ($stmtCheckExistSlug->fetch()) {
            $response['message'] = 'Liên kết tùy chỉnh này đã được sử dụng. Vui lòng chọn tên khác.';
            echo json_encode($response);
            exit;
        }
    }

    $passwordHash = null;
    if (!empty($password)) {
        if (strlen($password) < 4) {
            $response['message'] = 'Mật khẩu chia sẻ phải có ít nhất 4 ký tự.';
            echo json_encode($response);
            exit;
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    $stmtInsert = $pdo->prepare("INSERT INTO shared_links (creator_user_id, list_id, slug, password_hash, created_at) VALUES (:creator_user_id, :list_id, :slug, :password_hash, NOW())");
    if ($stmtInsert->execute([
        'creator_user_id' => $creatorUserId,
        'list_id' => $listId,
        'slug' => $slugToUse,
        'password_hash' => $passwordHash
    ])) {
        $response['success'] = true;
        $response['message'] = 'Tạo liên kết chia sẻ thành công!';

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $projectBaseDir = dirname($_SERVER['PHP_SELF'], 2);
        if ($projectBaseDir === '/' || $projectBaseDir === '\\') {
            $projectBaseDir = '';
        }
        $response['share_url'] = $protocol . $host . $projectBaseDir . "/view_shared.php?s=" . $slugToUse;
    } else {
        $response['message'] = 'Không thể lưu liên kết chia sẻ vào CSDL.';
        error_log("Create Share Link Error: Insert failed. PDO errorInfo: " . print_r($stmtInsert->errorInfo(), true));
    }
} catch (PDOException $e) {
    error_log("Create Share Link PDOException: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
    $response['message'] = 'Lỗi cơ sở dữ liệu khi tạo liên kết: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("Create Share Link Exception: " . $e->getMessage());
    $response['message'] = 'Lỗi hệ thống không mong muốn: ' . $e->getMessage();
}

echo json_encode($response);
exit;
