<?php
// view_shared.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php'; // $pdo

$slug = trim($_GET['s'] ?? '');
$pageTitle = "Danh sách được chia sẻ";
$sharedLinkData = null;
$tasks = [];
$listName = "Không tìm thấy danh sách";
$creatorUsername = "N/A";
$passwordProtected = false;
$passwordError = '';
$showContent = false; // Cờ để quyết định có hiển thị task không

if (empty($slug)) {
    $listName = "Thiếu thông tin liên kết chia sẻ.";
} else {
    try {
        $stmt = $pdo->prepare("SELECT sl.id as shared_link_id, sl.list_id, sl.password_hash, tl.name as task_list_name, u.username as creator_username
                               FROM shared_links sl
                               JOIN task_lists tl ON sl.list_id = tl.id
                               JOIN users u ON sl.creator_user_id = u.id
                               WHERE sl.slug = :slug AND (sl.expires_at IS NULL OR sl.expires_at > NOW())");
        $stmt->execute(['slug' => $slug]);
        $sharedLinkData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sharedLinkData) {
            $pageTitle = "Chia sẻ: " . htmlspecialchars($sharedLinkData['task_list_name']);
            $listName = htmlspecialchars($sharedLinkData['task_list_name']);
            $creatorUsername = htmlspecialchars($sharedLinkData['creator_username']);

            // Kiểm tra mật khẩu
            if ($sharedLinkData['password_hash']) {
                $passwordProtected = true;
                // Kiểm tra xem mật khẩu đã được submit và lưu trong session cho slug này chưa
                if (isset($_SESSION['shared_access'][$slug]) && $_SESSION['shared_access'][$slug] === true) {
                    $showContent = true;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_password'])) {
                    if (password_verify($_POST['share_password'], $sharedLinkData['password_hash'])) {
                        $_SESSION['shared_access'][$slug] = true; // Lưu vào session
                        $showContent = true;
                        $passwordProtected = false; // Không cần hiện form nữa
                        // Không cần redirect, chỉ cần set $showContent = true
                    } else {
                        $passwordError = 'Mật khẩu không chính xác. Vui lòng thử lại.';
                    }
                }
            } else {
                // Không có mật khẩu, cho phép xem nội dung
                $showContent = true;
            }

            if ($showContent) {
                $stmtTasks = $pdo->prepare("SELECT content, is_completed FROM tasks WHERE list_id = :list_id ORDER BY created_at DESC");
                $stmtTasks->execute(['list_id' => $sharedLinkData['list_id']]);
                $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $pageTitle = "Liên kết không hợp lệ";
            $listName = "Liên kết chia sẻ không hợp lệ, không tồn tại hoặc đã hết hạn.";
        }
    } catch (PDOException $e) {
        error_log("View Shared PDOException: " . $e->getMessage());
        $listName = "Lỗi khi tải danh sách được chia sẻ. Vui lòng thử lại sau.";
        $pageTitle = "Lỗi";
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col items-center justify-center p-4 sm:p-8">
    <div class="max-w-2xl w-full bg-white shadow-2xl rounded-xl p-6 sm:p-8 transform transition-all">
        <div class="mb-6 pb-4 border-b border-slate-200">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-800 text-sm mb-3 inline-block"><i class="fas fa-arrow-left mr-1"></i> Về TaskMaster</a>
            <h1 class="text-2xl sm:text-3xl font-bold text-indigo-700 mb-1"><?php echo $listName; ?></h1>
            <?php if ($sharedLinkData): ?>
                <p class="text-sm text-slate-500">Được chia sẻ bởi: <span class="font-medium"><?php echo $creatorUsername; ?></span></p>
            <?php endif; ?>
        </div>

        <?php if (empty($slug)): ?>
            <div class="p-4 bg-red-50 text-red-700 rounded-md">
                <i class="fas fa-exclamation-triangle mr-2"></i> Không có liên kết nào được cung cấp.
            </div>
        <?php elseif (!$sharedLinkData && !empty($slug)): ?>
            <div class="p-4 bg-red-50 text-red-700 rounded-md">
                <i class="fas fa-times-circle mr-2"></i> <?php echo $listName; // Sẽ là "Liên kết không hợp lệ..." 
                                                            ?>
            </div>
        <?php elseif ($passwordProtected && !$showContent): ?>
            <form method="POST" action="view_shared.php?s=<?php echo htmlspecialchars($slug); ?>" class="space-y-4 py-4">
                <p class="text-sm text-slate-600"><i class="fas fa-lock mr-2 text-slate-500"></i>Danh sách này được bảo vệ bằng mật khẩu. Vui lòng nhập mật khẩu để xem.</p>
                <div>
                    <label for="share_password" class="block text-sm font-medium text-slate-700 sr-only">Mật khẩu:</label>
                    <input type="password" name="share_password" id="share_password" required
                        class="mt-1 block w-full px-4 py-2.5 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Nhập mật khẩu">
                </div>
                <?php if ($passwordError): ?>
                    <p class="text-sm text-red-600"><i class="fas fa-times-circle mr-1"></i> <?php echo $passwordError; ?></p>
                <?php endif; ?>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-opacity-70 transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-key mr-2"></i> Truy cập
                </button>
            </form>
        <?php elseif ($showContent): ?>
            <?php if (!empty($tasks)): ?>
                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 no-scrollbar">
                    <?php foreach ($tasks as $task): ?>
                        <div class="flex items-center p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
                            <input type="checkbox" <?php echo $task['is_completed'] ? 'checked' : ''; ?> disabled
                                class="h-5 w-5 text-indigo-500 border-slate-300 rounded cursor-not-allowed opacity-80 flex-shrink-0">
                            <span class="ml-3 text-slate-700 text-sm break-words <?php echo $task['is_completed'] ? 'line-through text-slate-400' : ''; ?>">
                                <?php echo htmlspecialchars($task['content']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-slate-500 bg-slate-50 rounded-lg">
                    <i class="fas fa-folder-open fa-2x text-slate-400 mb-3"></i>
                    <p>Danh sách này hiện không có công việc nào.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>

</html>