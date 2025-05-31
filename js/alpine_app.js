document.addEventListener('alpine:init', () => {
    Alpine.data('taskApp', (initialTasks, currentUserId, currentListId, currentListNameProp, initialTaskLists) => ({
        // === STATE ===
        tasks: initialTasks || [],
        userTaskLists: initialTaskLists || [],

        newTaskContent: '',
        isLoadingAddTask: false,
        isLoadingTasks: false,
        addTaskMessage: { text: '', type: '' },

        userId: currentUserId,
        selectedListId: currentListId,
        currentListName: currentListId ? currentListNameProp : (initialTaskLists.length > 0 ? 'Chọn một danh sách' : 'Tạo danh sách đầu tiên của bạn'),

        isSidebarOpen: false,
        isDesktop: false,

        modals: {
            createList: false,
            shareList: false,
            history: false,
        },
        globalModalOpenState: false,

        newListName: '',
        newListDescription: '',
        isProcessingCreateList: false,
        createListMessage: { text: '', type: '' },

        shareContext: { listId: null, listName: '' },
        shareOptions: {
            customSlug: '',
            usePassword: false,
            password: '',
            feedbackText: '',
            feedbackClass: 'text-sm'
        },
        generatedShareLink: '',
        copyStatusMessage: { text: '', type: '' },
        isProcessingShare: false,

        currentTaskForHistory: { id: null, content: '' },
        editHistory: [],
        isLoadingHistory: false,
        isProcessingRestore: false,

        editingTask: null,
        originalEditingTaskContent: '',
        isProcessingUpdateTask: false,

        // === LIFECYCLE & WATCHERS ===
        init() {
            console.log('Alpine app initialized. Selected List ID:', this.selectedListId, "Name:", this.currentListName, "User ID:", this.userId);
            this.isDesktop = window.innerWidth >= 1024;

            this.isSidebarOpen = this.isDesktop;
            console.log("Initial sidebar state - isSidebarOpen:", this.isSidebarOpen, "isDesktop:", this.isDesktop);


            this.$watch('modals', (newModalsState) => {
                this.globalModalOpenState = Object.values(newModalsState).some(isOpen => isOpen);
            });

            window.addEventListener('resize', () => {
                const previouslyDesktop = this.isDesktop;
                this.isDesktop = window.innerWidth >= 1024;

                if (this.isDesktop && !previouslyDesktop) {
                    this.isSidebarOpen = true;
                } else if (!this.isDesktop && previouslyDesktop) {
                    this.isSidebarOpen = false;
                }
                console.log("Resized - isSidebarOpen:", this.isSidebarOpen, "isDesktop:", this.isDesktop);
            });
            console.log("Initial User Task Lists:", this.userTaskLists);
        },

        // === MODAL MANAGEMENT ===
        handleOpenModal(detail) {
            console.log('taskApp: handleOpenModal received, Detail:', JSON.parse(JSON.stringify(detail)));
            if (detail && detail.type && this.modals.hasOwnProperty(detail.type)) {
                Object.keys(this.modals).forEach(modalType => { this.modals[modalType] = false; }); // Đóng tất cả trước
                this.modals[detail.type] = true; // Mở modal yêu cầu

                if (detail.type === 'createList') {
                    console.log("taskApp: Opening createList modal.");
                    this.newListName = ''; this.newListDescription = ''; this.createListMessage = { text: '', type: '' };
                    this.$nextTick(() => { this.$refs.newListNameInput?.focus(); console.log("taskApp: Focused newListNameInput"); });
                } else if (detail.type === 'shareList' && detail.context) {
                    console.log("taskApp: Opening shareList modal with context:", detail.context);
                    this.setShareContext(detail.context);
                    this.$nextTick(() => { this.$refs.customLinkModalInput?.focus(); console.log("taskApp: Focused customLinkModalInput"); });
                } else if (detail.type === 'history' && detail.context) {
                    console.log("taskApp: Opening history modal, fetching history for task:", detail.context.taskId);
                    this.fetchEditHistory(detail.context.taskId, detail.context.taskContent);
                }
            } else { console.warn('taskApp: Unknown or invalid modal type to open:', detail?.type); }
        },
        handleCloseModal(detail) {
            console.log('taskApp: handleCloseModal received, Detail:', detail);
            if (detail && detail.type && this.modals.hasOwnProperty(detail.type)) {
                this.modals[detail.type] = false;
            } else { console.warn('taskApp: Unknown or invalid modal type to close:', detail?.type); }
        },

        // === TASK LIST MANAGEMENT ===
        async handleCreateTaskList() {
            console.log('taskApp: handleCreateTaskList CALLED. List name:', this.newListName); // DEBUG
            if (!this.newListName.trim()) {
                this.createListMessage = { text: 'Tên danh sách không được để trống.', type: 'error' };
                this.$refs.newListNameInput?.focus(); return;
            }
            this.isProcessingCreateList = true; this.createListMessage = { text: 'Đang tạo...', type: 'info' };
            const formData = new FormData();
            formData.append('name', this.newListName.trim());
            formData.append('description', this.newListDescription.trim());
            try {
                const response = await fetch('actions/create_list_action.php', { method: 'POST', body: formData });
                if (!response.ok) { const txt = await response.text(); console.error('Server error (create_list_action.php):', response.status, txt); this.createListMessage = { text: `Lỗi server: ${response.status}.`, type: 'error' }; throw new Error(`Server error: ${response.status}`); }
                const result = await response.json();
                console.log('Create list server response:', result);
                if (result.success && result.list) {
                    this.modals.createList = false;
                    alert('Tạo danh sách thành công! Trang sẽ làm mới.');
                    window.location.href = `app.php?list_id=${result.list.id}`;
                } else {
                    this.createListMessage = { text: result.message || 'Lỗi không xác định từ server.', type: 'error' };
                }
            } catch (e) {
                console.error('Lỗi JavaScript khi tạo danh sách:', e);
                if (!this.createListMessage.text || this.createListMessage.type === 'info') {
                    this.createListMessage = { text: 'Lỗi kết nối hoặc phản hồi không đúng. ' + e.message, type: 'error' };
                }
            } finally { this.isProcessingCreateList = false; }
        },

        // === TASK MANAGEMENT ===
        async addTaskToList() {
            console.log('taskApp: addTaskToList CALLED.'); // DEBUG
            if (!this.selectedListId) { this.addTaskMessage = { text: 'Vui lòng chọn danh sách.', type: 'error' }; console.error("addTaskToList Error: selectedListId is invalid:", this.selectedListId); return; }
            if (!this.newTaskContent.trim()) { this.addTaskMessage = { text: 'Nội dung công việc không được trống.', type: 'error' }; this.$refs.newTaskInput?.focus(); return; }
            this.isLoadingAddTask = true; this.addTaskMessage = { text: 'Đang thêm...', type: 'info' };
            console.log(`Attempting to add task: "${this.newTaskContent}" to list ID: ${this.selectedListId}`);
            const formData = new FormData(); formData.append('content', this.newTaskContent.trim()); formData.append('list_id', this.selectedListId);
            try {
                const response = await fetch('actions/add_task_action.php', { method: 'POST', body: formData });
                const responseText = await response.clone().text(); console.log("Raw response from add_task_action.php:", responseText); // DEBUG RAW TEXT
                if (!response.ok) { console.error(`Server Error (add_task_action.php) ${response.status}:`, responseText); this.addTaskMessage = { text: `Lỗi server: ${response.status}. Xem Console.`, type: 'error' }; this.isLoadingAddTask = false; return; }
                const result = await response.json();
                console.log('Add task server response (JSON):', result);
                if (result && result.success && result.task) {
                    this.tasks.unshift(result.task); this.newTaskContent = '';
                    this.addTaskMessage = { text: 'Thêm công việc thành công!', type: 'success' };
                    setTimeout(() => this.addTaskMessage = { text: '', type: '' }, 3000);
                } else { this.addTaskMessage = { text: result.message || 'Lỗi khi thêm công việc từ server.', type: 'error' }; }
            } catch (error) { console.error('Lỗi JavaScript trong addTaskToList:', error); if (!this.addTaskMessage.text || this.addTaskMessage.type === 'info') { this.addTaskMessage = { text: 'Lỗi kết nối hoặc định dạng phản hồi. ' + error.message, type: 'error' }; } }
            finally { this.isLoadingAddTask = false; }
        },
        async toggleTask(task) {
            console.log("Toggling task:", task.id, "current state:", task.is_completed);
            const originalCompleted = task.is_completed;
            const newCompletedState = (originalCompleted == 1 || originalCompleted === true) ? 0 : 1;
            const taskIndex = this.tasks.findIndex(t => t.id === task.id);
            if (taskIndex > -1) {
                this.tasks[taskIndex].is_completed = newCompletedState; // Optimistic update
            }
            const formData = new FormData();
            formData.append('task_id', task.id);
            formData.append('is_completed', newCompletedState);
            try {
                const response = await fetch('actions/toggle_task_action.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorText = await response.text(); throw new Error(`Server error ${response.status}: ${errorText}`); }
                const result = await response.json();
                if (!result.success) {
                    if (taskIndex > -1) this.tasks[taskIndex].is_completed = originalCompleted; // Rollback
                    alert(result.message || 'Lỗi cập nhật trạng thái.');
                }
            } catch (e) {
                if (taskIndex > -1) this.tasks[taskIndex].is_completed = originalCompleted; // Rollback
                alert('Lỗi hệ thống khi cập nhật trạng thái: ' + e.message);
                console.error("Toggle task JS error:", e);
            }
        },
        async deleteTask(taskId) {
            if (!confirm('Bạn chắc chắn muốn xóa công việc này?')) return;
            const originalTasks = JSON.parse(JSON.stringify(this.tasks));
            this.tasks = this.tasks.filter(t => t.id !== taskId);
            const formData = new FormData(); formData.append('task_id', taskId);
            try {
                const response = await fetch('actions/delete_task_action.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorText = await response.text(); throw new Error(`Server error ${response.status}: ${errorText}`); }
                const result = await response.json();
                if (!result.success) {
                    this.tasks = originalTasks;
                    alert(result.message || 'Lỗi xóa công việc.');
                } else {
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentPageNum = parseInt(urlParams.get('page') || '1');
                    if (this.tasks.length === 0 && currentPageNum > 0) {
                        const prevPage = Math.max(1, currentPageNum - 1);
                        if (currentPageNum > 1 || (currentPageNum === 1 && this.tasks.length === 0)) {
                            window.location.href = `app.php?list_id=${this.selectedListId}${prevPage > 1 ? '&page=' + prevPage : ''}`;
                        } else if (this.tasks.length === 0 && currentPageNum === 1) {
                        }
                    }
                }
            } catch (e) { this.tasks = originalTasks; alert('Lỗi hệ thống: ' + e.message); console.error("Delete task JS error:", e); }
        },

        startEditTask(task) {
            if (this.editingTask && this.editingTask.id !== task.id) { this.cancelTaskEdit(true); }
            this.originalEditingTaskContent = task.content.trim();
            this.editingTask = { id: task.id, content: task.content.trim() };
            this.$nextTick(() => {
                const inputEl = this.$refs[`editInput_${task.id}`];
                if (inputEl) inputEl.focus();
            });
        },
        cancelTaskEdit(silent = false) {
            if (this.editingTask) {
                if (!silent && this.editingTask.content !== this.originalEditingTaskContent) {
                    if (!confirm('Thay đổi chưa lưu. Hủy bỏ?')) return;
                }
                this.editingTask = null; this.originalEditingTaskContent = '';
            }
        },
        async saveTaskEdit(taskId) {
            if (!this.editingTask || this.editingTask.id !== taskId) return;
            const newContent = this.editingTask.content.trim();
            if (newContent === this.originalEditingTaskContent) { this.editingTask = null; return; }
            if (!newContent) { alert('Nội dung không được trống.'); return; }

            this.isProcessingUpdateTask = true;
            const formData = new FormData();
            formData.append('task_id', taskId); formData.append('content', newContent);
            try {
                const response = await fetch('actions/update_task_content_action.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorText = await response.text(); throw new Error(`Server error ${response.status}: ${errorText.substring(0, 150)}`); }
                const result = await response.json();
                if (result.success && result.task) {
                    const taskIndex = this.tasks.findIndex(t => t.id === taskId);
                    if (taskIndex > -1) { this.tasks[taskIndex].content = result.task.content; }
                    this.editingTask = null;
                } else { alert(result.message || 'Lỗi cập nhật nội dung.'); }
            } catch (e) { alert('Lỗi hệ thống: ' + e.message); }
            finally { this.isProcessingUpdateTask = false; }
        },
        saveTaskEditOnBlur(taskId, event) {
            if (event.relatedTarget && (event.relatedTarget.hasAttribute('data-edit-action'))) {
                return;
            }
            if (this.editingTask && this.editingTask.id === taskId) {
                if (this.editingTask.content.trim() && this.editingTask.content.trim() !== this.originalEditingTaskContent) {
                    this.saveTaskEdit(taskId);
                } else if (!this.editingTask.content.trim()) {
                    this.cancelTaskEdit(true);
                } else {
                    this.editingTask = null;
                }
            }
        },

        // === SHARE LIST MODAL ===
        setShareContext(context) {
            if (!context || context.listId === undefined || context.listName === undefined) {
                console.error("setShareContext Error: Invalid context received.", context);
                alert("Lỗi: Không thể mở modal chia sẻ do thiếu thông tin danh sách.");
                this.modals.shareList = false;
                return;
            }
            console.log("Setting share context. List ID:", context.listId, "List Name:", context.listName);
            this.shareContext.listId = context.listId;
            this.shareContext.listName = context.listName;
            this.shareOptions.customSlug = '';
            this.shareOptions.usePassword = false;
            this.shareOptions.password = '';
            this.shareOptions.feedbackText = '';
            this.shareOptions.feedbackClass = 'text-sm';
            this.generatedShareLink = '';
            this.copyStatusMessage = { text: '', type: '' };
        },

        async checkCustomLink() {
            this.shareOptions.feedbackText = 'Đang kiểm tra...';
            this.shareOptions.feedbackClass = 'text-sm text-slate-500';
            const slug = this.shareOptions.customSlug.trim();

            if (!slug) {
                this.shareOptions.feedbackText = '';
                return;
            }
            console.log("Checking custom slug:", slug);

            try {
                const response = await fetch(`actions/check_slug_action.php?slug=${encodeURIComponent(slug)}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`Server Error (check_slug_action.php) ${response.status}:`, errorText);
                    this.shareOptions.feedbackText = `Lỗi server: ${response.status}.`;
                    this.shareOptions.feedbackClass = 'text-sm text-red-500';
                    return;
                }

                const result = await response.json();
                console.log("Check slug result:", result);

                if (result.exists) {
                    this.shareOptions.feedbackText = result.message || 'Liên kết này đã được sử dụng.';
                    this.shareOptions.feedbackClass = 'text-sm text-red-500';
                } else {
                    this.shareOptions.feedbackText = result.message || 'Liên kết này có thể sử dụng!';
                    this.shareOptions.feedbackClass = 'text-sm text-green-500';
                }
                if (result.message && result.message.toLowerCase().includes("không hợp lệ")) {
                    this.shareOptions.feedbackClass = 'text-sm text-red-500';
                }
            } catch (e) {
                console.error("Lỗi JavaScript khi kiểm tra slug:", e);
                this.shareOptions.feedbackText = 'Lỗi kết nối hoặc phản hồi không đúng khi kiểm tra slug.';
                this.shareOptions.feedbackClass = 'text-sm text-red-500';
            }
        },

        async generateShareLink() {
            if (!this.shareContext.listId) {
                alert('Lỗi: Không xác định được danh sách để chia sẻ. Vui lòng đóng modal và thử lại.');
                console.error("generateShareLink Error: shareContext.listId is null or invalid. Value:", this.shareContext.listId);
                return;
            }
            if (this.shareOptions.customSlug.trim() && this.shareOptions.feedbackClass.includes('text-red-500')) {
                alert('Vui lòng sửa lỗi ở liên kết tùy chỉnh trước khi tạo.');
                this.$refs.customLinkModalInput?.focus();
                return;
            }

            this.isProcessingShare = true;
            this.generatedShareLink = '';
            this.copyStatusMessage = { text: '', type: '' };

            const formData = new FormData();
            formData.append('list_id', this.shareContext.listId);
            if (this.shareOptions.customSlug.trim()) {
                formData.append('custom_slug', this.shareOptions.customSlug.trim());
            }
            if (this.shareOptions.usePassword && this.shareOptions.password.trim()) {
                formData.append('password', this.shareOptions.password.trim());
            } else {
                formData.append('password', '');
            }

            console.log("Đang tạo link chia sẻ cho list ID:", this.shareContext.listId, "Với các tùy chọn:", Object.fromEntries(formData));

            try {
                const response = await fetch('actions/create_share_action.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`Server Error (create_share_action.php) ${response.status}:`, errorText);
                    this.shareOptions.feedbackText = `Lỗi server: ${response.status}.`;
                    this.shareOptions.feedbackClass = "text-sm text-red-500";
                    alert(`Lỗi từ server: ${response.status}. Xem Console để biết thêm chi tiết.`);
                    this.isProcessingShare = false;
                    return;
                }
                const result = await response.json();
                console.log('Phản hồi từ server (generateShareLink):', result);

                if (result.success && result.share_url) {
                    this.generatedShareLink = result.share_url;
                    this.shareOptions.feedbackText = "Đã tạo link thành công!";
                    this.shareOptions.feedbackClass = "text-sm text-green-500";
                } else {
                    this.shareOptions.feedbackText = result.message || 'Lỗi không xác định khi tạo liên kết từ server.';
                    this.shareOptions.feedbackClass = "text-sm text-red-500";
                    alert(result.message || 'Lỗi không xác định khi tạo liên kết từ server.');
                }
            } catch (error) {
                console.error('Lỗi JavaScript khi tạo link chia sẻ:', error);
                this.shareOptions.feedbackText = 'Lỗi kết nối hoặc định dạng phản hồi không đúng khi tạo link. ' + error.message.substring(0, 100) + '...';
                this.shareOptions.feedbackClass = "text-sm text-red-500";
                alert('Lỗi kết nối hoặc xử lý yêu cầu. Vui lòng kiểm tra Console.');
            } finally {
                this.isProcessingShare = false;
            }
        },

        copyToClipboard(elementToCopyFromOrText) {
            let textToCopy = '';
            if (typeof elementToCopyFromOrText === 'string') {
                textToCopy = elementToCopyFromOrText;
            } else if (elementToCopyFromOrText && typeof elementToCopyFromOrText.value !== 'undefined') {
                elementToCopyFromOrText.select();
                elementToCopyFromOrText.setSelectionRange(0, 99999);
                textToCopy = elementToCopyFromOrText.value;
            }

            if (!textToCopy) { this.copyStatusMessage = { text: 'Không có gì để sao chép.', type: 'error' }; return; }
            if (!navigator.clipboard) {
                try {
                    const textArea = document.createElement("textarea");
                    textArea.value = textToCopy;
                    textArea.style.position = "fixed"; textArea.style.left = "-9999px";
                    document.body.appendChild(textArea);
                    textArea.focus(); textArea.select();
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textArea);
                    this.copyStatusMessage = successful ? { text: 'Đã sao chép (fallback)!', type: 'success' } : { text: 'Sao chép (fallback) thất bại.', type: 'error' };
                } catch (err) { this.copyStatusMessage = { text: 'Lỗi sao chép (fallback).', type: 'error' }; }
                setTimeout(() => this.copyStatusMessage = { text: '', type: '' }, 3000); return;
            }
            navigator.clipboard.writeText(textToCopy).then(() => {
                this.copyStatusMessage = { text: 'Đã sao chép!', type: 'success' };
                setTimeout(() => this.copyStatusMessage = { text: '', type: '' }, 3000);
            }).catch(err => { this.copyStatusMessage = { text: 'Lỗi sao chép: ' + err.message, type: 'error' }; });
        },
        // === HISTORY MODAL ===
        async fetchEditHistory(taskId, taskContent) {
            this.currentTaskForHistory = { id: taskId, content: taskContent };
            this.isLoadingHistory = true;
            this.editHistory = [];
            console.log(`Fetching history for task ID: ${taskId}`);

            try {
                const response = await fetch(`actions/get_history_action.php?task_id=${taskId}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`Server Error (get_history_action.php) ${response.status}:`, errorText);
                    this.editHistory = [{ id: Date.now(), action_description: `Lỗi tải lịch sử: ${response.status}`, created_at: new Date().toISOString(), can_restore: false }];
                    throw new Error(`Server responded with ${response.status}`);
                }
                const result = await response.json();
                console.log('Get history response:', result);

                if (result.success && Array.isArray(result.history)) {
                    this.editHistory = result.history.map(entry => ({
                        ...entry,
                        can_restore: entry.action !== 'deleted' && entry.action !== 'created_share_link' && entry.action !== 'restored'
                    }));
                    if (this.editHistory.length === 0) {
                        console.log("Không có lịch sử nào được tìm thấy cho task này.");
                    }
                } else {
                    console.error('Lỗi tải lịch sử từ server:', result.message);
                    this.editHistory = [{ id: Date.now(), action_description: result.message || 'Không thể tải lịch sử.', created_at: new Date().toISOString(), can_restore: false }];
                }
            } catch (error) {
                console.error('Lỗi JavaScript khi tải lịch sử:', error);
                if (this.editHistory.length === 0) {
                    this.editHistory = [{ id: Date.now(), action_description: 'Lỗi kết nối hoặc định dạng phản hồi không đúng.', created_at: new Date().toISOString(), can_restore: false }];
                }
            } finally {
                this.isLoadingHistory = false;
            }
        },

        formatHistoryEntry(entry) {
            if (entry.action_description) {
                return entry.action_description;
            }
            let desc = `Hành động: ${entry.action || 'Không rõ'}`;
            return desc;
        },

        async restoreTaskVersion(historyEntryId, taskId) {
            if (!confirm('Bạn có chắc chắn muốn khôi phục công việc về phiên bản này? Hành động này sẽ tải lại trang.')) return;
            this.isProcessingRestore = true;
            const formData = new FormData();
            formData.append('history_id', historyEntryId);
            formData.append('task_id', taskId);
            try {
                const response = await fetch('actions/restore_history_action.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server error ${response.status}: ${errorText}`);
                }
                const result = await response.json();
                if (result.success) {
                    alert('Khôi phục công việc thành công! Trang sẽ được tải lại.');
                    this.modals.history = false;
                    window.location.reload();
                } else {
                    alert(result.message || 'Lỗi khi khôi phục công việc.');
                }
            } catch (error) {
                console.error('Lỗi JavaScript khi khôi phục:', error);
                alert('Lỗi hệ thống khi khôi phục. ' + error.message);
            } finally {
                this.isProcessingRestore = false;
            }
        }
    }));
});