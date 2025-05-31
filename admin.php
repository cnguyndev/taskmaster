<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Truy cập bị từ chối.");
}
require_once 'includes/db_connect.php';
$pageTitle = "Quản Trị Viên - TaskMaster Pro";
$currentAdminUserId = (int)$_SESSION['user_id'];

$userItemsPerPage = 15;
$userCurrentPage = isset($_GET['page_user']) && filter_var($_GET['page_user'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['page_user'] : 1;
$userStmtTotal = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = (int)$userStmtTotal->fetchColumn();
$userTotalPages = $totalUsers > 0 ? ceil($totalUsers / $userItemsPerPage) : 0;
if ($userCurrentPage > $userTotalPages && $userTotalPages > 0) $userCurrentPage = $userTotalPages;
$userOffset = ($userCurrentPage - 1) * $userItemsPerPage;
$stmtUsers = $pdo->prepare("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmtUsers->bindParam(':limit', $userItemsPerPage, PDO::PARAM_INT);
$stmtUsers->bindParam(':offset', $userOffset, PDO::PARAM_INT);
$stmtUsers->execute();
$usersForCurrentPage = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
$usersForCurrentPage = array_map(function ($user) {
    $user['id'] = (int)$user['id'];
    $user['is_admin'] = (int)$user['is_admin'];
    return $user;
}, $usersForCurrentPage);

$sharedLinksForCurrentPage = [];
$linkCurrentPage = 1;
$linkTotalPages = 0;
$linkItemsPerPage = 15;

$currentViewPHP = $_GET['view'] ?? 'users';
if (!in_array($currentViewPHP, ['dashboard', 'users', 'shared_links'])) {
    $currentViewPHP = 'dashboard';
}

include 'includes/admin_header.php';
?>

<div x-show="isSidebarOpen && !isDesktop"
    @click="console.log('Admin Page Overlay clicked, closing sidebar.'); isSidebarOpen = false;"
    class="fixed inset-0 bg-black/40 z-35 lg:hidden"
    aria-hidden="true"
    x-cloak>
</div>

<aside x-show="isSidebarOpen"
    @keydown.escape.window="if (isSidebarOpen && !isDesktop) { console.log('Admin Sidebar: Escape pressed, closing.'); isSidebarOpen = false; }"
    x-transition:enter="transition ease-in-out duration-300" x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-200"
    x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
    class="w-64 bg-slate-800 text-slate-100 p-5 space-y-6 fixed inset-y-0 left-0 z-40 no-scrollbar overflow-y-auto flex flex-col print:hidden"
    aria-label="Admin Navigation Sidebar"
    x-cloak>

    <div class="relative flex-grow flex flex-col h-full">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-white flex items-center">
                <i class="fas fa-shield-alt mr-2"></i>Admin Panel
            </h2>
            <button @click="console.log('Admin Sidebar: Inner X button clicked, closing.'); isSidebarOpen = false;"
                class="lg:hidden text-slate-300 hover:text-white p-1 rounded-full hover:bg-slate-700"
                aria-label="Đóng menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <nav class="space-y-1 flex-grow">
            <a href="#" @click.prevent="changeAdminView('dashboard'); if(!isDesktop && isSidebarOpen) isSidebarOpen = false;" :class="{'bg-slate-900 text-indigo-300 font-semibold': currentView === 'dashboard', 'text-slate-300 hover:bg-slate-700 hover:text-white': currentView !== 'dashboard'}" class="flex items-center px-3 py-2.5 rounded-md transition-colors text-sm"><i class="fas fa-tachometer-alt w-5 mr-3 text-center"></i> Tổng quan</a>
            <a href="#" @click.prevent="changeAdminView('users'); if(!isDesktop && isSidebarOpen) isSidebarOpen = false;" :class="{'bg-slate-900 text-indigo-300 font-semibold': currentView === 'users', 'text-slate-300 hover:bg-slate-700 hover:text-white': currentView !== 'users'}" class="flex items-center px-3 py-2.5 rounded-md transition-colors text-sm"><i class="fas fa-users w-5 mr-3 text-center"></i> Người dùng</a>
            <a href="#" @click.prevent="changeAdminView('shared_links'); if(!isDesktop && isSidebarOpen) isSidebarOpen = false;" :class="{'bg-slate-900 text-indigo-300 font-semibold': currentView === 'shared_links', 'text-slate-300 hover:bg-slate-700 hover:text-white': currentView !== 'shared_links'}" class="flex items-center px-3 py-2.5 rounded-md transition-colors text-sm"><i class="fas fa-link w-5 mr-3 text-center"></i> Link Chia sẻ</a>
        </nav>

        <div class="mt-auto border-t border-slate-700 pt-4">
            <a href="app.php" @click="if(!isDesktop && isSidebarOpen) isSidebarOpen = false;" class="flex items-center px-3 py-2.5 rounded-md text-indigo-300 hover:bg-slate-700 hover:text-indigo-200 transition-colors text-sm"><i class="fas fa-arrow-left w-5 mr-3 text-center"></i> Về trang Task App</a>
        </div>
    </div>
</aside>

<div class="flex-1 flex flex-col admin-content-pusher" :class="{ 'lg:ml-64': isSidebarOpen && isDesktop }">
    <div class="lg:hidden bg-slate-700 text-white shadow-md sticky top-0 z-20">
        <div class="container mx-auto px-4 sm:px-6 py-3 flex justify-between items-center">
            <button @click="$dispatch('toggle-admin-sidebar'); console.log('Admin mobile toggle (hamburger) clicked');"
                title="Mở/Đóng Menu Admin"
                class="text-slate-300 hover:text-white p-2 -ml-2 rounded-md focus:outline-none hover:bg-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-lg font-semibold">
                Admin Panel
            </h1>
            <div class="w-6"></div>
        </div>
    </div>

    <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
        <template x-if="currentView === 'dashboard'">
            <div x-transition>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-6">Bảng điều khiển Admin</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p>Chào mừng! Chọn mục từ menu.</p>
                </div>
            </div>
        </template>

        <template x-if="currentView === 'users'">
            <div x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Quản Lý Người Dùng</h1><button @click="openCreateUserModal()" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md text-sm flex items-center transition-transform hover:scale-105"><i class="fas fa-plus mr-2"></i> Tạo User Mới</button>
                </div>
                <p x-show="userMessage.text" class="mb-4 p-3 rounded-md text-sm" :class="userMessage.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="userMessage.text" x-transition></p>
                <div class="bg-white shadow-xl rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tên</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Quyền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ngày tạo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200"><template x-if="users.length === 0">
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">Không có người dùng nào.</td>
                                </tr>
                            </template>
                            <template x-for="user in users" :key="user.id">
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900" x-text="user.id"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium" x-text="user.username"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500" x-text="user.email"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span x-text="user.is_admin == 1 ? 'Admin' : 'User'" :class="user.is_admin == 1 ? 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800' : 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800'"></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500" x-text="new Date(user.created_at).toLocaleDateString('vi-VN', { year:'numeric', month: '2-digit', day: '2-digit'})"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2"><button @click="openEditUserModal(user)" class="text-indigo-600 hover:text-indigo-900 hover:bg-indigo-100 p-1.5 rounded-full transition-colors" title="Sửa"><i class="fas fa-edit"></i></button><button @click="deleteUser(user.id, user.username)" class="text-red-600 hover:text-red-900 hover:bg-red-100 p-1.5 rounded-full transition-colors" title="Xóa" :disabled="user.id == currentAdminId"><i class="fas fa-trash-alt"></i></button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <?php if ($userTotalPages > 0): ?> <nav class="mt-6 flex flex-col sm:flex-row justify-between items-center text-sm text-slate-600" aria-label="User Pagination">
                        <div> Trang <strong x-text="userPagination.currentPage"></strong> trên <strong x-text="userPagination.totalPages"></strong> (Tổng: <strong x-text="<?php echo $totalUsers; ?>"></strong> người dùng)</div>
                        <div class="flex space-x-1 mt-3 sm:mt-0"><a href="admin.php?view=users&page_user=<?php echo max(1, $userCurrentPage - 1); ?>" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 <?php echo ($userCurrentPage <= 1) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">Trước</a><?php $range = 1;
                                                                                                                                                                                                                                                                                                                                                            $startRange = max(1, $userCurrentPage - $range);
                                                                                                                                                                                                                                                                                                                                                            $endRange = min($userTotalPages, $userCurrentPage + $range);
                                                                                                                                                                                                                                                                                                                                                            if ($startRange > 1) echo '<a href="admin.php?view=users&page_user=1" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">1</a>';
                                                                                                                                                                                                                                                                                                                                                            if ($startRange > 2) echo '<span class="px-3 py-2 sm:px-4 text-slate-500">...</span>';
                                                                                                                                                                                                                                                                                                                                                            for ($i = $startRange; $i <= $endRange; $i++): ?><a href="admin.php?view=users&page_user=<?php echo $i; ?>" class="px-3 py-2 sm:px-4 border rounded-lg <?php echo ($i == $userCurrentPage) ? 'bg-indigo-600 text-white border-indigo-600 z-10' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-300'; ?>"><?php echo $i; ?></a><?php endfor;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                if ($endRange < $userTotalPages - 1) echo '<span class="px-3 py-2 sm:px-4 text-slate-500">...</span>';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                if ($endRange < $userTotalPages) echo '<a href="admin.php?view=users&page_user=' . $userTotalPages . '" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">' . $userTotalPages . '</a>'; ?><a href="admin.php?view=users&page_user=<?php echo min($userTotalPages, $userCurrentPage + 1); ?>" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 <?php echo ($userCurrentPage >= $userTotalPages) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">Sau</a></div>
                    </nav><?php endif; ?>
            </div>
        </template>

        <template x-if="currentView === 'shared_links'">
            <div x-transition>
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Quản Lý Link Chia Sẻ</h1>
                </div>
                <p x-show="sharedLinkMessage.text" class="mb-4 p-3 rounded-md text-sm" :class="sharedLinkMessage.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="sharedLinkMessage.text" x-transition></p>
                <div x-show="isLoadingSharedLinks" class="text-center py-8 text-slate-500">Đang tải danh sách link...</div>

                <template x-if="!isLoadingSharedLinks && sharedLinks.length === 0">
                    <p class="text-slate-500 text-sm py-4 text-center bg-white p-6 rounded-lg shadow-md">Không có link chia sẻ nào trong hệ thống.</p>
                </template>

                <div class="bg-white shadow-xl rounded-lg overflow-x-auto" x-show="!isLoadingSharedLinks && sharedLinks.length > 0">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">List Gốc</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Người Tạo Link</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Slug</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Có MK</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ngày Tạo Link</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Trạng Thái</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <template x-for="link in sharedLinks" :key="link.id">
                                <tr class="hover:bg-slate-50/50 transition-colors" :class="{'opacity-60 bg-slate-100 line-through': link.is_deleted == 1}">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700" x-text="link.id"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-indigo-700 font-medium" x-text="link.task_list_name"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600" x-text="link.creator_username"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500">
                                        <a :href="'../view_shared.php?s=' + link.slug" target="_blank" class="hover:underline" x-text="link.slug"></a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <i class="fas" :class="link.has_password ? 'fa-check-circle text-green-500' : 'fa-times-circle text-slate-400'"></i>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500" x-text="new Date(link.created_at).toLocaleDateString('vi-VN')"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                        <span x-show="link.is_deleted == 1" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Đã ẩn</span>
                                        <span x-show="link.is_deleted == 0" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium space-x-1">
                                        <template x-if="link.is_deleted == 0">
                                            <button @click="trashSharedLinkAdmin(link.id)" class="text-orange-500 hover:text-orange-700 hover:bg-orange-100 p-1.5 rounded-full" title="Ẩn Link (Thùng rác)"><i class="fas fa-eye-slash"></i></button>
                                        </template>
                                        <template x-if="link.is_deleted == 1">
                                            <button @click="restoreSharedLinkAdmin(link.id)" class="text-green-500 hover:text-green-700 hover:bg-green-100 p-1.5 rounded-full" title="Khôi phục Link"><i class="fas fa-undo"></i></button>
                                        </template>
                                        <button @click="deleteSharedLinkPermanentlyAdmin(link.id)" class="text-red-600 hover:text-red-900 hover:bg-red-100 p-1.5 rounded-full" title="Xóa vĩnh viễn Link"><i class="fas fa-times-circle"></i></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <nav x-show="!isLoadingSharedLinks && sharedLinkPagination.totalPages > 0"
                    class="mt-6 flex flex-col sm:flex-row justify-between items-center text-sm text-slate-600"
                    aria-label="Shared Links Pagination">
                    <div>
                        Trang <strong x-text="sharedLinkPagination.currentPage"></strong> / <strong x-text="sharedLinkPagination.totalPages"></strong>
                        <span x-show="sharedLinkPagination.totalItems > 0">
                            (Tổng: <strong x-text="sharedLinkPagination.totalItems"></strong> links)
                        </span>
                    </div>
                    <div class="flex space-x-1 mt-3 sm:mt-0">
                        <button @click="fetchSharedLinks(Math.max(1, sharedLinkPagination.currentPage - 1))"
                            :disabled="sharedLinkPagination.currentPage <= 1"
                            class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                            Trước
                        </button>

                        <template x-for="page in displayedSharedLinkPages" :key="page + '-' + Math.random()"> <button x-text="page"
                                @click="typeof page === 'number' ? fetchSharedLinks(page) : null"
                                :class="{
                                'bg-indigo-600 text-white border-indigo-600 z-10 pointer-events-none': page === sharedLinkPagination.currentPage,
                                'bg-white text-slate-600 hover:bg-slate-50 border-slate-300': page !== sharedLinkPagination.currentPage && typeof page === 'number',
                                'text-slate-500 cursor-default px-1': typeof page !== 'number' /* Kiểu cho '...' */
                            }"
                                class="px-3 py-2 sm:px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                :disabled="typeof page !== 'number' || page === sharedLinkPagination.currentPage">
                            </button>
                        </template>

                        <button @click="fetchSharedLinks(Math.min(sharedLinkPagination.totalPages, sharedLinkPagination.currentPage + 1))"
                            :disabled="sharedLinkPagination.currentPage >= sharedLinkPagination.totalPages"
                            class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                            Sau
                        </button>
                    </div>
                </nav>
            </div>
        </template>
    </main>
</div>
<div x-show="showEditUserModal" @keydown.escape.window="closeEditUserModal()" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4" x-cloak>
    <div @click.away="closeEditUserModal()" class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5 transform transition-all" x-show="showEditUserModal" x-transition>
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-slate-800">Sửa User: <span class="text-indigo-600" x-text="editingUser.username_original"></span></h3><button @click="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form @submit.prevent="saveUserChanges()" class="space-y-4"><input type="hidden" x-model="editingUser.id">
            <div><label for="editUsernameAdmin" class="block text-sm font-medium text-slate-700 mb-1">Tên người dùng</label><input type="text" id="editUsernameAdmin" x-ref="editUsernameAdminInput" x-model="editingUser.username" required class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="editEmailAdmin" class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" id="editEmailAdmin" x-model="editingUser.email" required class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="editIsAdmin" class="flex items-center"><input type="checkbox" id="editIsAdmin" x-model="editingUser.is_admin" :true-value="1" :false-value="0" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" :disabled="editingUser.id == currentAdminId"><span class="ml-2 text-sm text-slate-700">Đặt làm Quản trị viên <template x-if="editingUser.id == currentAdminId"><span class="text-xs text-red-500 ml-1">(Không thể tự bỏ quyền)</span></template></span></label></div>
            <div><label for="editPasswordAdmin" class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu mới (để trống nếu không đổi)</label><input type="password" id="editPasswordAdmin" x-model="editingUser.new_password" minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <p x-show="editModalMessage.text" class="text-xs" :class="editModalMessage.type === 'error' ? 'text-red-500' : 'text-green-500'" x-text="editModalMessage.text"></p>
            <div class="flex justify-end space-x-3 pt-2"><button type="button" @click="closeEditUserModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Hủy</button><button type="submit" :disabled="isProcessingUserUpdate" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50"><span>Lưu</span></button></div>
        </form>
    </div>
</div>
<div x-show="showCreateUserModal" @keydown.escape.window="closeCreateUserModal()" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4" x-cloak>
    <div @click.away="closeCreateUserModal()" class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5 transform transition-all" x-show="showCreateUserModal" x-transition>
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-slate-800">Tạo Người Dùng Mới</h3><button @click="closeCreateUserModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form @submit.prevent="createNewUser()" class="space-y-4">
            <div><label for="newUsernameAdminInput" class="block text-sm font-medium text-slate-700 mb-1">Tên người dùng</label><input type="text" id="newUsernameAdminInput" x-ref="newUsernameAdminInput" x-model="newUser.username" required class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="newEmailUserAdmin" class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" id="newEmailUserAdmin" x-model="newUser.email" required class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="newPasswordUserAdmin" class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu (ít nhất 6 ký tự)</label><input type="password" id="newPasswordUserAdmin" x-model="newUser.password" required minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="newConfirmPasswordAdmin" class="block text-sm font-medium text-slate-700 mb-1">Xác nhận Mật khẩu</label><input type="password" id="newConfirmPasswordAdmin" x-model="newUser.confirm_password" required class="w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm"></div>
            <div><label for="newIsAdminUser" class="flex items-center"><input type="checkbox" id="newIsAdminUser" x-model="newUser.is_admin" :true-value="1" :false-value="0" class="h-4 w-4 text-indigo-600 border-slate-300 rounded"><span class="ml-2 text-sm text-slate-700">Đặt làm Quản trị viên</span></label></div>
            <p x-show="createModalMessage.text" class="text-xs" :class="createModalMessage.type === 'error' ? 'text-red-500' : 'text-green-500'" x-text="createModalMessage.text"></p>
            <div class="flex justify-end space-x-3 pt-2"><button type="button" @click="closeCreateUserModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Hủy</button><button type="submit" :disabled="isProcessingUserCreate" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50"><span>Tạo</span></button></div>
        </form>
    </div>
</div>
<?php
$adminCustomJs = ['js/alpine_admin.js'];
include 'includes/admin_footer.php';
?>