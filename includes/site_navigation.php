<?php
$currentFile = basename($_SERVER['PHP_SELF']); // Cần thiết nếu có logic dựa trên file hiện tại
?>
<header class="bg-white shadow-sm sticky top-0 z-30 print:hidden">
    <div class="container mx-auto px-4 sm:px-6 py-3 flex justify-between items-center">
        <div class="flex items-center">
            <?php
            if ($isAppPageLayout):
            ?>
                <button @click="$dispatch('toggle-sidebar'); console.log('Mobile toggle button clicked');"
                    title="Mở/Đóng Menu"
                    class="text-slate-500 hover:text-slate-700 p-2 -ml-2 rounded-md focus:outline-none hover:bg-slate-100 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <button @click="$dispatch('toggle-sidebar'); console.log('Desktop toggle button clicked');"
                    title="Mở/Đóng Menu"
                    class="text-slate-500 hover:text-slate-700 p-2 rounded-md focus:outline-none hover:bg-slate-100 hidden lg:block">
                    <svg class="w-6 h-6" x-show="$store.app?.isSidebarOpen !== false" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
            <?php endif; ?>

            <a href="index.php" class="text-xl sm:text-2xl font-bold text-indigo-600 <?php if ($isAppPageLayout) echo 'ml-3'; ?>">
                <i class="fas fa-check-double mr-1 text-indigo-500"></i> TaskMaster
            </a>
        </div>
        <div class="flex items-center space-x-2 sm:space-x-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($currentFile !== 'profile.php'): ?>
                    <a href="profile.php" title="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" class="text-slate-600 hover:text-indigo-600 p-2 text-sm rounded-full hover:bg-slate-100 flex items-center">
                        <i class="fas fa-user-circle text-lg sm:mr-1"></i>
                        <span class="hidden sm:inline ml-1"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </a>
                <?php else: ?>
                    <span class="text-slate-700 text-sm flex items-center p-2">
                        <i class="fas fa-user-circle text-lg sm:mr-1 text-indigo-500"></i>
                        <span class="hidden sm:inline font-medium ml-1"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </span>
                <?php endif; ?>

                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $currentFile !== 'admin.php'): ?>
                    <a href="admin.php" title="Trang quản trị" class="text-purple-600 hover:text-purple-800 p-2 font-medium text-sm rounded-full hover:bg-purple-50">
                        <i class="fas fa-user-shield"></i> <span class="hidden sm:inline ml-1">Admin</span>
                    </a>
                <?php endif; ?>
                <?php
                if (basename($_SERVER['PHP_SELF']) === 'profile.php'): ?>
                    <a href="app.php" title="Bảng công việc" class="text-indigo-600 hover:text-indigo-800 p-2 font-medium text-sm rounded-full hover:bg-indigo-50">
                        <i class="fas fa-th-list"></i> <span class="hidden sm:inline ml-1">Bảng CV</span>
                    </a>
                <?php endif; ?>
                <a href="logout.php" title="Đăng xuất" class="text-red-500 hover:text-red-700 p-2 font-medium text-sm rounded-full hover:bg-red-50 flex items-center">
                    <i class="fas fa-sign-out-alt sm:mr-1"></i>
                    <span class="hidden sm:inline ml-1">Đăng xuất</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="text-slate-600 hover:text-indigo-600 px-3 py-2 text-sm rounded-md hover:bg-slate-100">Đăng nhập</a>
                <a href="register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md text-sm transition-colors hover:shadow-lg">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>