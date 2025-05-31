<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

$pageTitle = "Đăng nhập - TaskMaster Pro";
$metaDescription = "Đăng nhập vào TaskMaster Pro để quản lý công việc của bạn.";
$isAppPageLayout = false;
$bodyClasses = 'h-full bg-slate-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8';
$customJs = ['js/alpine_auth.js'];

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
            Đăng nhập tài khoản
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Hoặc <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                tạo tài khoản mới
            </a>
        </p>
    </div>
    <form class="mt-8 space-y-6" @submit.prevent="loginUser" id="loginForm">
        <input type="hidden" name="remember" value="true">
        <div class="rounded-md shadow-sm -space-y-px">
            <div>
                <label for="email-address" class="sr-only">Địa chỉ email</label>
                <input id="email-address" name="email" type="email" x-model="loginData.email" autocomplete="email" required
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Địa chỉ email">
            </div>
            <div>
                <label for="password" class="sr-only">Mật khẩu</label>
                <input id="password" name="password" type="password" x-model="loginData.password" autocomplete="current-password" required
                    class="appearance-none rounded-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-500 text-slate-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                    placeholder="Mật khẩu">
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember-me" name="remember-me" type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                <label for="remember-me" class="ml-2 block text-sm text-slate-900">
                    Ghi nhớ tôi
                </label>
            </div>

            <div class="text-sm">
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Quên mật khẩu?
                </a>
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
                <span x-text="isLoading ? 'Đang xử lý...' : 'Đăng nhập'"></span>
            </button>
        </div>
    </form>
    <div class="text-sm text-center mt-4">
        <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500">
            &larr; Quay lại trang chủ
        </a>
    </div>
</div>
<?php include 'includes/layout_footer.php'; ?>