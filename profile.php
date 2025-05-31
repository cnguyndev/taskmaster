<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Thông tin cá nhân - TaskMaster Pro";
$isAppPageLayout = false;

require_once 'includes/db_connect.php';

$userId = (int)$_SESSION['user_id'];
$currentUser = null;
$activeSharedLinks = [];
$trashedSharedLinks = [];

ob_start();

try {
    $stmtUser = $pdo->prepare("SELECT username, email FROM users WHERE id = :user_id");
    $stmtUser->execute(['user_id' => $userId]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        error_log("Profile Page Critical Error: User data not found for user_id: " . $userId);
        throw new Exception("Lỗi: Không thể tải thông tin người dùng.");
    }

    $stmtActiveLinks = $pdo->prepare("
        SELECT sl.id, sl.slug, sl.created_at, sl.password_hash IS NOT NULL as has_password, tl.name as task_list_name
        FROM shared_links sl
        JOIN task_lists tl ON sl.list_id = tl.id
        WHERE sl.creator_user_id = :user_id AND sl.is_deleted = FALSE
        ORDER BY sl.created_at DESC
    ");
    $stmtActiveLinks->execute(['user_id' => $userId]);
    $activeSharedLinks = $stmtActiveLinks->fetchAll(PDO::FETCH_ASSOC);

    $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmtTrashedLinks = $pdo->prepare("
        SELECT sl.id, sl.slug, sl.created_at, sl.deleted_on, tl.name as task_list_name
        FROM shared_links sl
        JOIN task_lists tl ON sl.list_id = tl.id
        WHERE sl.creator_user_id = :user_id AND sl.is_deleted = TRUE AND sl.deleted_on >= :seven_days_ago
        ORDER BY sl.deleted_on DESC
    ");
    $stmtTrashedLinks->execute(['user_id' => $userId, 'seven_days_ago' => $sevenDaysAgo]);
    $trashedSharedLinks = $stmtTrashedLinks->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Profile Page - Exception during data fetch: " . $e->getMessage());
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo "<!DOCTYPE html><html><head><title>Lỗi</title></head><body>";
    echo "<h1>Đã có lỗi xảy ra khi tải trang.</h1>";
    echo "<p>Vui lòng thử lại sau. Nếu lỗi tiếp diễn, liên hệ quản trị viên.</p>";
    echo "</body></html>";
    exit;
}

ob_end_flush();
?>

<?php include('includes/layout_header.php');
include 'includes/site_navigation.php'; ?>
<main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div x-data="userProfileApp(
        '<?php echo htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES); ?>',
        '<?php echo htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES); ?>',
        <?php echo htmlspecialchars(json_encode($activeSharedLinks), ENT_QUOTES, 'UTF-8'); ?>,
        <?php echo htmlspecialchars(json_encode($trashedSharedLinks), ENT_QUOTES, 'UTF-8'); ?>
    )" x-init="console.log('Alpine userProfileApp initialized with: ', currentUsername, currentEmail, activeSharedLinks, trashedSharedLinks)">

        <h1 class="text-3xl font-bold text-slate-800 mb-10 text-center sm:text-left">Thông Tin Cá Nhân</h1>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-slate-300/50 transition-shadow">
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-3">Đổi Tên Hiển Thị</h2>
                    <form @submit.prevent="updateUsername" class="space-y-4">
                        <div> <label for="username" class="block text-sm font-medium text-slate-600 mb-1">Tên người dùng mới</label> <input type="text" id="username" x-model="forms.username.newUsername" required minlength="3" maxlength="50" class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <p x-show="messages.username" :class="messageType.username === 'success' ? 'text-green-600' : 'text-red-600'" x-text="messages.username" class="text-sm"></p> <button type="submit" :disabled="loading.username" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md transition duration-150 disabled:opacity-60 shadow-md hover:shadow-lg"> <span x-show="!loading.username"><i class="fas fa-save mr-2"></i>Lưu Tên Mới</span> <span x-show="loading.username" class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg> Đang lưu...</span> </button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-slate-300/50 transition-shadow">
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-3">Đổi Địa Chỉ Email</h2>
                    <div class="mb-3 p-3 bg-amber-50 border-l-4 border-amber-400 text-amber-800 text-sm rounded-md"> <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Lưu ý:</strong> Việc xác thực email mới chưa được triển khai. </div>
                    <form @submit.prevent="updateEmail" class="space-y-4">
                        <div> <label for="newEmail" class="block text-sm font-medium text-slate-600 mb-1">Email mới</label> <input type="email" id="newEmail" x-model="forms.email.newEmail" required class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <div> <label for="currentPasswordForEmail" class="block text-sm font-medium text-slate-600 mb-1">Mật khẩu hiện tại (xác nhận)</label> <input type="password" id="currentPasswordForEmail" x-model="forms.email.currentPassword" required class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <p x-show="messages.email" :class="messageType.email === 'success' ? 'text-green-600' : 'text-red-600'" x-text="messages.email" class="text-sm"></p> <button type="submit" :disabled="loading.email" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md transition duration-150 disabled:opacity-60 shadow-md hover:shadow-lg"> <span x-show="!loading.email"><i class="fas fa-envelope mr-2"></i>Lưu Email Mới</span> <span x-show="loading.email" class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg> Đang lưu...</span> </button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-slate-300/50 transition-shadow">
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-3">Đổi Mật Khẩu</h2>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div> <label for="currentPassword" class="block text-sm font-medium text-slate-600 mb-1">Mật khẩu hiện tại</label> <input type="password" id="currentPassword" x-model="forms.password.currentPassword" required class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <div> <label for="newPassword" class="block text-sm font-medium text-slate-600 mb-1">Mật khẩu mới (ít nhất 6 ký tự)</label> <input type="password" id="newPassword" x-model="forms.password.newPassword" required minlength="6" class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <div> <label for="confirmNewPassword" class="block text-sm font-medium text-slate-600 mb-1">Xác nhận mật khẩu mới</label> <input type="password" id="confirmNewPassword" x-model="forms.password.confirmNewPassword" required class="w-full px-3 py-2.5 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"> </div>
                        <p x-show="messages.password" :class="messageType.password === 'success' ? 'text-green-600' : 'text-red-600'" x-text="messages.password" class="text-sm"></p> <button type="submit" :disabled="loading.password" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md transition duration-150 disabled:opacity-60 shadow-md hover:shadow-lg"> <span x-show="!loading.password"><i class="fas fa-key mr-2"></i>Đổi Mật Khẩu</span> <span x-show="loading.password" class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg> Đang lưu...</span> </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg hover:shadow-slate-300/50 transition-shadow">
                <h2 class="text-xl font-semibold text-slate-700 mb-1 border-b pb-3">Quản Lý Liên Kết Chia Sẻ</h2>
                <p x-show="messages.links" :class="messageType.links === 'success' ? 'text-green-600' : 'text-red-600'" x-text="messages.links" class="text-sm my-3 p-2 rounded-md" :class="messageType.links === 'success' ? 'bg-green-50' : 'bg-red-50'"></p>
                <div class="mb-4 border-b border-slate-200">
                    <nav class="-mb-px flex space-x-6" aria-label="Tabs"> <button @click="activeTab = 'activeLinks'" :class="{ 'border-indigo-500 text-indigo-600 font-semibold': activeTab === 'activeLinks', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'activeLinks' }" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm transition-all"> Đang hoạt động (<span x-text="activeSharedLinks.length"></span>) </button> <button @click="activeTab = 'trashedLinks'" :class="{ 'border-indigo-500 text-indigo-600 font-semibold': activeTab === 'trashedLinks', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'trashedLinks' }" class="whitespace-nowrap py-3 px-1 border-b-2 text-sm transition-all"> Thùng rác (<span x-text="trashedSharedLinks.length"></span>) <span class="ml-1 text-xs text-slate-400">(lưu 7 ngày)</span> </button> </nav>
                </div>
                <div x-show="activeTab === 'activeLinks'" x-transition> <template x-if="activeSharedLinks.length === 0">
                        <p class="text-slate-500 text-sm py-4 text-center">Chưa có liên kết hoạt động.</p>
                    </template>
                    <ul class="space-y-3"> <template x-for="link in activeSharedLinks" :key="link.id">
                            <li class="p-3.5 border border-slate-200 rounded-lg hover:shadow-md transition-shadow bg-slate-50/50">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center">
                                    <div class="flex-grow min-w-0">
                                        <p class="font-medium text-indigo-700 truncate" x-text="link.task_list_name"></p> <a :href="'view_shared.php?s=' + link.slug" target="_blank" class="text-xs text-slate-500 hover:text-indigo-600 hover:underline break-all block" x-text="'/view_shared.php?s=' + link.slug"></a>
                                        <p class="text-xs text-slate-400 mt-1.5"> <i class="far fa-clock fa-fw"></i> Tạo: <span x-text="new Date(link.created_at).toLocaleDateString('vi-VN')"></span> <span x-show="link.has_password == 1" class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full font-medium"><i class="fas fa-lock fa-xs mr-1"></i>Có MK</span> </p>
                                    </div>
                                    <div class="mt-3 sm:mt-0 sm:ml-4 flex-shrink-0"> <button @click="trashSharedLink(link.id)" title="Chuyển vào thùng rác" class="text-slate-500 hover:text-red-600 p-2 rounded-full hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-300"> <i class="fas fa-trash-alt fa-fw"></i> </button> </div>
                                </div>
                            </li>
                        </template> </ul>
                </div>
                <div x-show="activeTab === 'trashedLinks'" x-transition> <template x-if="trashedSharedLinks.length === 0">
                        <p class="text-slate-500 text-sm py-4 text-center">Thùng rác trống.</p>
                    </template>
                    <ul class="space-y-3"> <template x-for="link in trashedSharedLinks" :key="link.id">
                            <li class="p-3.5 border border-slate-200 bg-slate-100 rounded-lg opacity-80">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center">
                                    <div class="flex-grow min-w-0">
                                        <p class="font-medium text-slate-500 line-through truncate" x-text="link.task_list_name"></p>
                                        <p class="text-xs text-slate-400 break-all" x-text="'/view_shared.php?s=' + link.slug"></p>
                                        <p class="text-xs text-slate-400 mt-1.5"> <i class="far fa-trash-alt fa-fw"></i> Xóa lúc: <span x-text="new Date(link.deleted_on).toLocaleDateString('vi-VN')"></span> (Còn <strong x-text="daysUntilPermanentDelete(link.deleted_on)"></strong> ngày) </p>
                                    </div>
                                    <div class="mt-3 sm:mt-0 sm:ml-4 flex-shrink-0 flex space-x-2"> <button @click="restoreSharedLink(link.id)" title="Khôi phục" class="text-slate-500 hover:text-green-600 p-2 rounded-full hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-green-300"> <i class="fas fa-undo-alt fa-fw"></i> </button> <button @click="deleteSharedLinkPermanently(link.id)" title="Xóa vĩnh viễn" class="text-slate-500 hover:text-red-700 p-2 rounded-full hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-400"> <i class="fas fa-times-circle fa-fw"></i> </button> </div>
                                </div>
                            </li>
                        </template> </ul>
                    <p class="mt-6 text-xs text-slate-500 italic"><i class="fas fa-info-circle mr-1"></i> Các liên kết trong thùng rác sẽ tự động bị xóa vĩnh viễn khỏi hệ thống sau 7 ngày.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$customJs = ['js/alpine_profile.js'];
include 'includes/layout_footer.php';
?>