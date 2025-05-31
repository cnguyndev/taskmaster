<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];
require_once 'includes/db_connect.php';
$pageTitle = "Bảng Công Việc - TaskMaster Pro";
$isAppPageLayout = true;
$stmtLists = $pdo->prepare("SELECT id, name FROM task_lists WHERE user_id = :user_id ORDER BY name ASC");
$stmtLists->execute(['user_id' => $userId]);
$userTaskLists = $stmtLists->fetchAll(PDO::FETCH_ASSOC);
$userTaskLists = array_map(function ($list) {
    $list['id'] = (int)$list['id'];
    return $list;
}, $userTaskLists);
$selectedListId = null;
$selectedListName = "TaskMaster Pro";
$tasksForCurrentPage = [];
$totalPages = 0;
$currentPage = 1;
$itemsPerPage = 10;
$currentListIdParam = null;
if (isset($_GET['list_id']) && filter_var($_GET['list_id'], FILTER_VALIDATE_INT)) {
    $currentListIdParam = (int)$_GET['list_id'];
} elseif (!empty($userTaskLists)) {
    $currentListIdParam = $userTaskLists[0]['id'];
    if (!isset($_GET['list_id'])) {
        header('Location: app.php?list_id=' . $currentListIdParam);
        exit;
    }
}
if ($currentListIdParam !== null) {
    $stmtCheckList = $pdo->prepare("SELECT id, name FROM task_lists WHERE id = :list_id AND user_id = :user_id");
    $stmtCheckList->execute(['list_id' => $currentListIdParam, 'user_id' => $userId]);
    $currentList = $stmtCheckList->fetch(PDO::FETCH_ASSOC);
    if ($currentList) {
        $selectedListId = (int)$currentList['id'];
        $selectedListName = $currentList['name'];
        $pageTitle = htmlspecialchars($selectedListName) . " - TaskMaster Pro";
        $currentPage = (isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) && (int)$_GET['page'] > 0) ? (int)$_GET['page'] : 1;
        $stmtTotalTasks = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE list_id = :list_id AND user_id = :user_id");
        $stmtTotalTasks->execute(['list_id' => $selectedListId, 'user_id' => $userId]);
        $totalItems = (int)$stmtTotalTasks->fetchColumn();
        $totalPages = $totalItems > 0 ? ceil($totalItems / $itemsPerPage) : 0;
        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $itemsPerPage;
        $stmtTasks = $pdo->prepare("SELECT id, content, is_completed, created_at FROM tasks WHERE list_id = :list_id AND user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmtTasks->bindParam(':list_id', $selectedListId, PDO::PARAM_INT);
        $stmtTasks->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtTasks->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmtTasks->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmtTasks->execute();
        $tasksFromDb = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);
        $tasksForCurrentPage = array_map(function ($task) {
            $task['id'] = (int)$task['id'];
            $task['is_completed'] = isset($task['is_completed']) ? (int)$task['is_completed'] : 0;
            return $task;
        }, $tasksFromDb);
    } else {
        $selectedListId = null;
        $selectedListName = !empty($userTaskLists) ? "Lỗi: Không tìm thấy danh sách" : "Tạo danh sách đầu tiên";
        $pageTitle = "Lỗi - TaskMaster Pro";
    }
} elseif (empty($userTaskLists)) {
    $selectedListId = null;
    $selectedListName = "Bắt đầu bằng việc tạo danh sách";
    $pageTitle = "Tạo danh sách - TaskMaster Pro";
} else {
    $selectedListId = null;
    $selectedListName = "Chọn một danh sách";
    $pageTitle = "Chọn danh sách - TaskMaster Pro";
}
?>
<?php include('includes/layout_header.php') ?>
<style>
    .sidebar-transition {
        transition: width 0.3s ease-in-out, transform 0.3s ease-in-out;
    }

    .content-pusher {
        transition: margin-left 0.3s ease-in-out;
    }

    @media (min-width: 1024px) {
        body.sidebar-desktop-open .content-pusher {
            margin-left: 18rem;
        }

        body:not(.sidebar-desktop-open) .content-pusher {
            margin-left: 0;
        }

        body.admin-sidebar-desktop-open .admin-content-pusher {
            margin-left: 16rem;
        }
    }

    .task-item-actions button {
        transition: background-color 0.2s ease-in-out;
    }
</style>
<aside x-show="isSidebarOpen"
    @click.outside="if (!isDesktop && isSidebarOpen) { console.log('Clicked outside sidebar on mobile, closing.'); isSidebarOpen = false; }"
    x-transition:enter="transition ease-in-out duration-300"
    x-transition:enter-start="-translate-x-full lg:translate-x-0 lg:w-0"
    x-transition:enter-end="translate-x-0 lg:w-72"
    x-transition:leave="transition ease-in-out duration-200"
    x-transition:leave-start="translate-x-0 lg:w-72"
    x-transition:leave-end="-translate-x-full lg:translate-x-0 lg:w-0"
    class="bg-white shadow-2xl p-5 space-y-4 fixed inset-y-0 left-0 z-40 no-scrollbar overflow-y-auto flex flex-col print:hidden sidebar-transition lg:w-72"
    aria-label="Main Navigation Sidebar">

    <div class="relative z-10 flex-grow flex flex-col" @click.stop>
        <div class="flex items-center justify-between mb-6">
            <a href="index.php" class="text-2xl font-bold text-indigo-600 flex items-center">
                <svg class="w-8 h-8 mr-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                TaskMaster
            </a>
            <button @click="console.log('Inner X button clicked'); isSidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600 p-1 rounded-full hover:bg-slate-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <button @click="$dispatch('open-modal', { type: 'createList' }); if (!isDesktop && isSidebarOpen) { console.log('Create List clicked'); isSidebarOpen = false; }" class="w-full flex items-center justify-center mb-5 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 transition-colors shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i> Tạo Danh Sách Mới
        </button>

        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 px-1">Danh sách của bạn</h3>
        <nav class="space-y-1 flex-grow overflow-y-auto no-scrollbar pr-1" aria-label="Task Lists">
            <template x-if="userTaskLists.length === 0">
                <p class="text-sm text-slate-500 px-3 py-2">Chưa có danh sách nào.</p>
            </template>
            <template x-for="list in userTaskLists" :key="list.id">
                <a :href="'app.php?list_id=' + list.id"
                    class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-md transition-all duration-150 ease-in-out"
                    :class="selectedListId == list.id ? 'bg-indigo-100 text-indigo-700 shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    @click="if (!isDesktop && isSidebarOpen) { console.log('Task list link clicked:', list.name); isSidebarOpen = false; }">
                    <i class="fas fa-list-ul mr-3 w-5 text-center" :class="selectedListId == list.id ? 'text-indigo-500' : 'text-slate-400 group-hover:text-slate-500'"></i>
                    <span class="truncate" x-text="list.name"></span>
                </a>
            </template>
        </nav>
    </div>

    <div class="mt-auto space-y-2 border-t border-slate-200 pt-4 relative z-10" @click.stop>
        <template x-if="selectedListId">
            <button @click="$dispatch('open-modal', { type: 'shareList', context: { listId: selectedListId, listName: currentListName } }); if (!isDesktop && isSidebarOpen) { console.log('Share List clicked'); isSidebarOpen = false; }" class="w-full flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow-md transition text-sm">
                <i class="fas fa-share-alt mr-2"></i> Chia sẻ List này
            </button>
        </template>
        <a href="logout.php" @click="if (!isDesktop && isSidebarOpen) { console.log('Logout clicked'); isSidebarOpen = false; }" class="w-full flex items-center justify-center bg-red-500 hover:bg-red-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow-md transition text-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
        </a>
    </div>
</aside>

<div class="flex-1 flex flex-col content-pusher">
    <?php include 'includes/site_navigation.php'; ?>
    <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
        <template x-if="selectedListId">
            <div>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800" x-text="currentListName"></h1>
                </div>
                <form @submit.prevent="addTaskToList" class="mb-8 bg-white p-4 sm:p-5 rounded-xl shadow-lg">
                    <div class="flex items-center space-x-3">
                        <input type="text" x-model="newTaskContent" :placeholder="'Thêm công việc mới vào \'' + currentListName + '\'...'"
                            class="flex-grow px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition-shadow focus:shadow-md"
                            x-ref="newTaskInput">
                        <button type="submit" :disabled="isLoadingAddTask" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 disabled:opacity-50 transition-all hover:shadow-lg active:scale-95">
                            <span x-show="!isLoadingAddTask"><i class="fas fa-plus mr-1 sm:mr-2"></i>Thêm</span>
                            <span x-show="isLoadingAddTask"><svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg></span>
                        </button>
                    </div>
                    <p x-show="addTaskMessage.text" class="text-xs mt-2 transition-opacity duration-300" :class="addTaskMessage.type === 'error' ? 'text-red-500' : 'text-green-500'" x-text="addTaskMessage.text"></p>
                </form>

                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div x-show="isLoadingTasks && tasks.length === 0" class="p-8 text-center text-slate-500">Đang tải...</div>
                    <div x-show="!isLoadingTasks && tasks.length === 0 && selectedListId" class="p-8 text-center text-slate-500">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        Danh sách <strong x-text="currentListName"></strong> hiện chưa có công việc nào.
                    </div>
                    <ul x-show="tasks.length > 0" class="divide-y divide-slate-200">
                        <template x-for="task in tasks" :key="task.id">
                            <li class="p-4 flex items-center justify-between hover:bg-slate-50/70 group transition-colors duration-150"
                                :class="{ 'bg-amber-100 hover:bg-amber-200': task.is_completed == 1, 'hover:bg-slate-50/70': task.is_completed != 1 }">

                                <div class="flex items-center flex-1 min-w-0">
                                    <input type="checkbox"
                                        :checked="task.is_completed == 1"
                                        @change.prevent="toggleTask(task)"
                                        class="h-5 w-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-400 focus:ring-offset-1 cursor-pointer flex-shrink-0">

                                    <div class="ml-3 flex-1 min-w-0" @dblclick="startEditTask(task)" x-ref="taskContentWrapper_task_id">
                                        <template x-if="editingTask && editingTask.id === task.id">
                                            <form @submit.prevent="saveTaskEdit(task.id)" class="flex items-center w-full">
                                                <input type="text" x-model="editingTask.content"
                                                    class="text-sm text-slate-700 border-indigo-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full py-1 px-2"
                                                    x-ref="editInput"
                                                    @keydown.escape.prevent="cancelTaskEdit()"
                                                    @blur="saveTaskEditOnBlur(task.id)"
                                                    x-init="$nextTick(() => $el.focus())">
                                                </input>
                                            </form>
                                        </template>
                                        <template x-if="!editingTask || editingTask.id !== task.id">
                                            <span class="text-slate-700 break-words text-sm cursor-pointer"
                                                :class="{'line-through text-slate-500': task.is_completed == 1, 'text-slate-700': task.is_completed != 1}"
                                                x-text="task.content">
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <div class="task-item-actions flex items-center space-x-1 ml-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click.stop="startEditTask(task)" title="Sửa nội dung"
                                        class="text-slate-400 hover:text-indigo-500 p-1.5 rounded-full hover:bg-indigo-100/70"
                                        x-show="!editingTask || editingTask.id !== task.id">
                                        <i class="fas fa-pencil-alt fa-sm"></i>
                                    </button>
                                    <button @click.stop="saveTaskEdit(task.id)" title="Lưu"
                                        class="text-slate-400 hover:text-green-500 p-1.5 rounded-full hover:bg-green-100/70"
                                        x-show="editingTask && editingTask.id === task.id">
                                        <i class="fas fa-check fa-sm"></i>
                                    </button>
                                    <button @click.stop="cancelTaskEdit()" title="Hủy sửa"
                                        class="text-slate-400 hover:text-orange-500 p-1.5 rounded-full hover:bg-orange-100/70"
                                        x-show="editingTask && editingTask.id === task.id">
                                        <i class="fas fa-times fa-sm"></i>
                                    </button>

                                    <button @click.stop="$dispatch('open-modal', { type: 'history', context: { taskId: task.id, taskContent: task.content } })" title="Lịch sử" class="text-slate-400 hover:text-blue-500 p-1.5 rounded-full hover:bg-blue-100/70">
                                        <i class="fas fa-history fa-sm"></i>
                                    </button>
                                    <button @click.stop="deleteTask(task.id)" title="Xóa" class="text-slate-400 hover:text-red-500 p-1.5 rounded-full hover:bg-red-100/70">
                                        <i class="fas fa-trash-alt fa-sm"></i>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-8 flex justify-center items-center space-x-1 sm:space-x-2" aria-label="Pagination">
                        <a href="?list_id=<?php echo $selectedListId; ?>&page=<?php echo max(1, $currentPage - 1); ?>" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 <?php echo ($currentPage <= 1) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">Trước</a>
                        <?php $range = 1;
                        $startRange = max(1, $currentPage - $range);
                        $endRange = min($totalPages, $currentPage + $range);
                        if ($startRange > 1) echo '<a href="?list_id=' . $selectedListId . '&page=1" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">1</a>';
                        if ($startRange > 2) echo '<span class="px-3 py-2 sm:px-4 text-slate-500">...</span>';
                        for ($i = $startRange; $i <= $endRange; $i++): ?>
                            <a href="?list_id=<?php echo $selectedListId; ?>&page=<?php echo $i; ?>" class="px-3 py-2 sm:px-4 border rounded-lg text-sm font-medium <?php echo ($i == $currentPage) ? 'bg-indigo-600 text-white border-indigo-600 z-10' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-300'; ?>"><?php echo $i; ?></a>
                        <?php endfor;
                        if ($endRange < $totalPages) echo '<span class="px-3 py-2 sm:px-4 text-slate-500">...</span>';
                        if ($endRange < $totalPages && $totalPages > ($currentPage + $range)) echo '<a href="?list_id=' . $selectedListId . '&page=' . $totalPages . '" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">' . $totalPages . '</a>';
                        ?>
                        <a href="?list_id=<?php echo $selectedListId; ?>&page=<?php echo min($totalPages, $currentPage + 1); ?>" class="px-3 py-2 sm:px-4 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 <?php echo ($currentPage >= $totalPages) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">Sau</a>
                    </nav>
                <?php endif; ?>
            </div>
        </template>
        <template x-if="!selectedListId && userTaskLists.length === 0">
            <div class="text-center py-12 flex flex-col items-center justify-center h-full">
                <svg class="w-20 h-20 text-slate-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h2 class="text-2xl font-semibold text-slate-700 mb-2">Chào mừng bạn!</h2>
                <p class="text-slate-500 mb-6">Hãy bắt đầu bằng cách tạo danh sách công việc đầu tiên của bạn.</p>
                <button @click="$dispatch('open-modal', { type: 'createList' })" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 transition-colors text-base">
                    <i class="fas fa-plus mr-2"></i>Tạo Danh Sách Đầu Tiên
                </button>
            </div>
        </template>
        <template x-if="!selectedListId && userTaskLists.length > 0">
            <div class="text-center py-12 flex flex-col items-center justify-center h-full">
                <svg class="w-20 h-20 text-slate-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h2 class="text-2xl font-semibold text-slate-700 mb-2">Chọn một danh sách</h2>
                <p class="text-slate-500">Vui lòng chọn một danh sách từ menu bên trái để xem công việc, hoặc tạo một danh sách mới.</p>
            </div>
        </template>
    </main>
    <footer class="bg-slate-800 text-slate-300 py-6 text-center text-sm print:hidden mt-auto">
        <div class="container mx-auto px-6">
            <p>&copy; <?php echo date("Y"); ?> TaskMaster Pro. All rights reserved.</p>
        </div>
    </footer>
</div>

<div x-show="modals.createList" @keydown.escape.window="$dispatch('close-modal', { type: 'createList' })" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4" x-cloak>
    <div @click.away="$dispatch('close-modal', { type: 'createList' })" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-5 transform transition-all" x-show="modals.createList" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-slate-800">Tạo Danh Sách Mới</h3><button @click="$dispatch('close-modal', { type: 'createList' })" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form @submit.prevent="handleCreateTaskList()">
            <div><label for="modalNewListName" class="block text-sm font-medium text-slate-700 mb-1">Tên danh sách</label><input type="text" id="modalNewListName" x-ref="newListNameInput" x-model="newListName" required class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div>
            <div><label for="modalNewListDescription" class="block text-sm font-medium text-slate-700 mb-1 mt-3">Mô tả (tùy chọn)</label><textarea id="modalNewListDescription" x-model="newListDescription" rows="3" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea></div>
            <p x-show="createListMessage.text" class="text-xs mt-2" :class="createListMessage.type === 'error' ? 'text-red-500' : 'text-green-500'" x-text="createListMessage.text"></p>
            <div class="flex justify-end space-x-3 pt-4"><button type="button" @click="$dispatch('close-modal', { type: 'createList' })" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Hủy</button><button type="submit" :disabled="isProcessingCreateList" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50"><span x-show="!isProcessingCreateList">Tạo</span><span x-show="isProcessingCreateList">Đang tạo...</span></button></div>
        </form>
    </div>
</div>
<div x-show="modals.shareList" @keydown.escape.window="$dispatch('close-modal', { type: 'shareList' })" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4" x-cloak>
    <div @click.away="$dispatch('close-modal', { type: 'shareList' })" class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5 transform transition-all" x-show="modals.shareList" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-slate-800">Chia sẻ: "<span x-text="shareContext.listName"></span>"</h3><button @click="$dispatch('close-modal', { type: 'shareList' })" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <div>
            <label for="customLinkModalInput" class="block text-sm font-medium text-slate-700 mb-1">Liên kết tùy chỉnh (VD: my-tasks)</label>
            <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-sm">
                    <?php
                    $appDir = dirname($_SERVER['PHP_SELF']);
                    echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . ($appDir == '/' ? '' : $appDir);
                    ?>/view_shared.php?s=
                </span>
                <input type="text" id="customLinkModalInput" x-ref="customLinkModalInput" x-model="shareOptions.customSlug" @input.debounce.500ms="checkCustomLink"
                    class="flex-1 block w-full rounded-none rounded-r-md border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <p class="text-xs mt-1" :class="shareOptions.feedbackClass" x-text="shareOptions.feedbackText"></p>
        </div>
        <div>
            <label class="flex items-center">
                <input type="checkbox" x-model="shareOptions.usePassword" class="form-checkbox h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                <span class="ml-2 text-sm text-slate-700">Đặt mật khẩu</span>
            </label>
        </div>
        <div x-show="shareOptions.usePassword">
            <label for="sharePasswordModal" class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
            <input type="password" name="sharePasswordModal" id="sharePasswordModal" x-model="shareOptions.password"
                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm px-3 py-2 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div x-show="generatedShareLink" class="mt-4 p-3 bg-slate-100 rounded-md">
            <p class="text-sm font-medium text-slate-800">Liên kết chia sẻ:</p>
            <div class="flex items-center mt-1">
                <input type="text" :value="generatedShareLink" readonly x-ref="generatedLinkInput" class="flex-grow px-3 py-2 border border-slate-300 rounded-l-md bg-slate-50 text-sm">
                <button @click="copyToClipboard($refs.generatedLinkInput)" class="px-3 py-2 bg-indigo-500 text-white rounded-r-md hover:bg-indigo-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">Sao chép</button>
            </div>
            <p x-text="copyStatusMessage.text" class="text-xs mt-1" :class="copyStatusMessage.type === 'error' ? 'text-red-500' : 'text-green-600'"></p>
        </div>
        <div class="flex justify-end space-x-3 pt-2">
            <button type="button" @click="$dispatch('close-modal', { type: 'shareList' })" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">Hủy</button>
            <button @click="generateShareLink()" :disabled="isProcessingShare" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <span x-show="!isProcessingShare">Tạo</span>
                <span x-show="isProcessingShare">Đang tạo...</span>
            </button>
        </div>
    </div>
</div>
<div x-show="modals.history" @keydown.escape.window="$dispatch('close-modal', { type: 'history' })" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 flex items-center justify-center p-4" x-cloak>
    <div @click.away="$dispatch('close-modal', { type: 'history' })" class="bg-white rounded-lg shadow-xl w-full max-w-xl p-6 space-y-5 transform transition-all" x-show="modals.history" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-slate-800">Lịch sử: "<span x-text="currentTaskForHistory.content"></span>"</h3><button @click="$dispatch('close-modal', { type: 'history' })" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <div class="max-h-80 overflow-y-auto space-y-3 pr-2 no-scrollbar">
            <p x-show="isLoadingHistory" class="text-slate-500">Đang tải...</p>
            <p x-show="!isLoadingHistory && editHistory.length === 0" class="text-slate-500">Không có lịch sử.</p><template x-for="entry in editHistory" :key="entry.id">
                <div class="p-3 bg-slate-50 rounded-md border border-slate-200">
                    <p class="text-sm text-slate-700" x-html="formatHistoryEntry(entry)"></p>
                    <p class="text-xs text-slate-500 mt-1">Thời gian: <span x-text="new Date(entry.created_at).toLocaleString('vi-VN')"></span></p><button x-show="entry.can_restore" @click="restoreTaskVersion(entry.id, currentTaskForHistory.id)" :disabled="isProcessingRestore" class="mt-2 px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded disabled:opacity-50"><span x-show="!isProcessingRestore">Khôi phục</span><span x-show="isProcessingRestore">...</span></button>
                </div>
            </template>
        </div>
        <div class="flex justify-end pt-2"><button @click="$dispatch('close-modal', { type: 'history' })" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Đóng</button></div>
    </div>
</div>

<script src="js/alpine_app.js"></script>
</body>

</html>