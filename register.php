<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập, chuyển hướng đến app.php
if (isset($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

// Các biến cho layout_header.php
$pageTitle = "Đăng ký - TaskMaster Pro";
$metaDescription = "Tạo tài khoản TaskMaster Pro mới để bắt đầu quản lý công việc hiệu quả."; // Thêm meta description nếu muốn
$isAppPageLayout = false; // Trang này không dùng layout của app (không có sidebar app)
$bodyClasses = 'h-full bg-slate-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8'; // Class body đặc thù
$customJs = ['js/alpine_auth.js']; // JS cho form xác thực

// Include phần đầu của layout chung
// Đảm bảo file này đã được tạo và chứa từ <!DOCTYPE html> đến trước <main> hoặc nội dung chính
// và nó sẽ include 'includes/site_navigation.php' (header cũ của bạn) nếu $currentFile không nằm trong $noNavPages
// Trang register.php nằm trong $noNavPages nên site_navigation sẽ không được hiển thị.
include 'includes/layout_header.php';
?>

<div class="max-w-md w-full space-y-8 p-8 sm:p-10 bg-white shadow-2xl rounded-xl transform transition-all hover:shadow-slate-400/50">
    <div>
        <a href="index.php" class="flex justify-center">
            <svg class="h-12 w-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </a>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900">
            Tạo tài khoản mới
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Hoặc <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                đăng nhập nếu bạn đã có tài khoản
            </a>
        </p>
    </div>
    <form class="mt-8 space-y-6" @submit.prevent="registerUser" id="registerForm">
        <div class="rounded-md shadow-sm -space-y-px">
            <div>
                <label for="username" class="sr-only">Tên người dùng</label>
                <input id="username" name="username" type="text" x-model="registerData.username" autocomplete="username" required
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Tên người dùng">
            </div>
            <div>
                <label for="email-address-register" class="sr-only">Địa chỉ email</label>
                <input id="email-address-register" name="email" type="email" x-model="registerData.email" autocomplete="email" required
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Địa chỉ email">
            </div>
            <div>
                <label for="password-register" class="sr-only">Mật khẩu</label>
                <input id="password-register" name="password" type="password" x-model="registerData.password" autocomplete="new-password" required minlength="6"
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Mật khẩu (ít nhất 6 ký tự)">
            </div>
            <div>
                <label for="confirm-password" class="sr-only">Xác nhận mật khẩu</label>
                <input id="confirm-password" name="confirmPassword" type="password" x-model="registerData.confirmPassword" autocomplete="new-password" required
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Xác nhận mật khẩu">
            </div>
        </div>

        <template x-if="authMessage.text">
            <div class="p-3 rounded-md text-sm"
                :class="{ 'bg-red-50 text-red-700': authMessage.type === 'error', 'bg-green-50 text-green-700': authMessage.type === 'success' }">
                <p x-text="authMessage.text"></p>
            </div>
        </template>

        <div>
            <button type="submit" :disabled="isLoading"
                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-70 transition-opacity">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3" x-show="isLoading">
                    <svg class="animate-spin h-5 w-5 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                <span x-text="isLoading ? 'Đang xử lý...' : 'Đăng ký'"></span>
            </button>
        </div>
    </form>
    <div class="text-sm text-center mt-4">
        <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500">
            &larr; Quay lại trang chủ
        </a>
    </div>
</div>
<?php
// Include phần cuối của layout chung
include 'includes/layout_footer.php';
?>