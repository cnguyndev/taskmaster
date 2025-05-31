document.addEventListener('alpine:init', () => {
    console.log("alpine_admin.js: Event 'alpine:init' fired. Registering 'adminPageControls'...");

    Alpine.data('adminPageControls', (
        initialUsers, userCurrentPage, userTotalPages, userItemsPerPage, currentAdminId,
        initialSharedLinks, linkCurrentPage, linkTotalPages, linkItemsPerPage
    ) => {
        console.log("Alpine.data('adminPageControls') factory function CALLED.");
        console.log("Initial Users Data:", { count: initialUsers?.length, userCurrentPage, userTotalPages });
        console.log("Initial Links Data (from PHP, may not be used if AJAX fetches on init):", { count: initialSharedLinks?.length, linkCurrentPage, linkTotalPages });
        console.log("Current Admin ID:", currentAdminId);


        return {
            // === COMMON ADMIN STATE ===
            currentView: 'dashboard',
            isSidebarOpen: false,
            isDesktop: false,
            currentAdminId: currentAdminId,

            // === USER MANAGEMENT STATE ===
            users: initialUsers || [],
            userPagination: {
                currentPage: userCurrentPage || 1,
                totalPages: userTotalPages || 0,
                itemsPerPage: userItemsPerPage || 15,
            },
            userMessage: { text: '', type: '' },
            showEditUserModal: false,
            editingUser: { id: null, username: '', email: '', is_admin: 0, new_password: '', username_original: '', email_original: '' },
            isProcessingUserUpdate: false,
            editModalMessage: { text: '', type: '' },
            showCreateUserModal: false,
            newUser: { username: '', email: '', password: '', confirm_password: '', is_admin: 0 },
            isProcessingUserCreate: false,
            createModalMessage: { text: '', type: '' },

            // === SHARED LINKS MANAGEMENT STATE ===
            sharedLinks: [],
            sharedLinkPagination: {
                currentPage: 1,
                totalPages: 0,
                itemsPerPage: linkItemsPerPage || 15,
                totalItems: 0
            },
            isLoadingSharedLinks: false,
            sharedLinkMessage: { text: '', type: '' },

            get displayedSharedLinkPages() {
                const current = this.sharedLinkPagination.currentPage;
                const total = this.sharedLinkPagination.totalPages;
                const delta = 1;

                const range = [];
                const rangeWithDots = [];
                let l;

                if (total <= 1) return [];
                const pages = [];
                const pagesToShow = 5;
                const sideWidth = Math.floor((pagesToShow - 3) / 2);

                if (total <= pagesToShow) {
                    for (let i = 1; i <= total; i++) {
                        pages.push(i);
                    }
                } else {
                    pages.push(1);

                    if (current > 1 + sideWidth + 1) {
                        pages.push('...');
                    }

                    let startPage = Math.max(2, current - sideWidth);
                    let endPage = Math.min(total - 1, current + sideWidth);

                    if (startPage <= 1 + sideWidth) startPage = 2;
                    if (endPage >= total - sideWidth) endPage = total - 1;


                    for (let i = startPage; i <= endPage; i++) {
                        if (!pages.includes(i)) {
                            pages.push(i);
                        }
                    }

                    if (current < total - sideWidth - 1) {
                        if (!pages.includes('...')) {
                            const lastNumericPage = pages.filter(p => typeof p === 'number').pop();
                            if (lastNumericPage && total - 1 > lastNumericPage) {
                                pages.push('...');
                            }
                        }
                    }

                    if (!pages.includes(total)) {
                        pages.push(total);
                    }
                }
                const finalPages = [];
                let lastPushed = null;
                for (const p of pages) {
                    if (p === '...' && lastPushed === '...') {
                    } else if (typeof p === 'number' && typeof lastPushed === 'number' && p === lastPushed + 1 && finalPages[finalPages.length - 1] === '...') {
                        finalPages.pop();
                        finalPages.push(p);
                    }
                    else {
                        finalPages.push(p);
                    }
                    lastPushed = p;
                }
                const cleanedPages = [];
                for (let i = 0; i < finalPages.length; i++) {
                    if (finalPages[i] === '...' && typeof finalPages[i - 1] === 'number' && typeof finalPages[i + 1] === 'number' && finalPages[i + 1] - finalPages[i - 1] <= 2) {
                        if (finalPages[i + 1] - finalPages[i - 1] === 2 && !cleanedPages.includes(finalPages[i - 1] + 1)) {
                            cleanedPages.push(finalPages[i - 1] + 1);
                        }
                    } else {
                        cleanedPages.push(finalPages[i]);
                    }
                }
                return [...new Set(cleanedPages)];
            },


            // === INITIALIZATION ===
            initAdminPage() {
                console.log("adminPageControls component: initAdminPage() method CALLED.");
                this.isDesktop = window.innerWidth >= 1024;
                this.isSidebarOpen = this.isDesktop;

                const urlParams = new URLSearchParams(window.location.search);
                const viewParam = urlParams.get('view');
                if (viewParam && ['dashboard', 'users', 'shared_links'].includes(viewParam)) {
                    this.currentView = viewParam;
                } else {
                    this.currentView = (this.users.length > 0) ? 'users' : 'dashboard';
                }
                console.log("Admin initial view determined as:", this.currentView);


                this.$watch('userMessage.text', (val) => { if (val) setTimeout(() => this.userMessage = { text: '', type: '' }, 5000); });
                this.$watch('editModalMessage.text', (val) => { if (val) setTimeout(() => this.editModalMessage = { text: '', type: '' }, 5000); });
                this.$watch('createModalMessage.text', (val) => { if (val) setTimeout(() => this.createModalMessage = { text: '', type: '' }, 5000); });
                this.$watch('sharedLinkMessage.text', (val) => { if (val) setTimeout(() => this.sharedLinkMessage = { text: '', type: '' }, 5000); });

                window.addEventListener('resize', () => {
                    const oldDesktop = this.isDesktop;
                    this.isDesktop = window.innerWidth >= 1024;
                    if (this.isDesktop && !oldDesktop) {
                        this.isSidebarOpen = true;
                    } else if (!this.isDesktop && oldDesktop) {
                        this.isSidebarOpen = false;
                    }
                });

                if (this.currentView === 'shared_links') {
                    const pageParam = parseInt(urlParams.get('page_link') || '1');
                    this.fetchSharedLinks(pageParam);
                }
                console.log("adminPageControls component: initAdminPage() method FINISHED.");
            },

            // === VIEW MANAGEMENT ===
            changeAdminView(view) {
                this.currentView = view;
                console.log("Admin view changed to:", view);
                const url = new URL(window.location);
                url.searchParams.set('view', view);
                url.searchParams.delete('page_user');
                url.searchParams.delete('page_link');

                window.history.pushState({}, '', url.toString());

                if (view === 'users') {
                } else if (view === 'shared_links') {
                    this.fetchSharedLinks(1);
                }
            },

            // === USER MANAGEMENT METHODS ===
            openEditUserModal(user) {
                console.log("Opening edit modal for user:", JSON.parse(JSON.stringify(user)));
                this.editingUser = { ...user, new_password: '', username_original: user.username, email_original: user.email };
                this.editModalMessage = { text: '', type: '' };
                this.showEditUserModal = true;
                this.$nextTick(() => this.$refs.editUsernameAdminInput?.focus());
            },
            closeEditUserModal() { this.showEditUserModal = false; },
            async saveUserChanges() {
                this.isProcessingUserUpdate = true; this.editModalMessage = { text: '', type: '' };
                const formData = new FormData();
                formData.append('user_id', this.editingUser.id);
                formData.append('username', this.editingUser.username.trim());
                formData.append('email', this.editingUser.email.trim());
                formData.append('is_admin', this.editingUser.is_admin ? 1 : 0);
                if (this.editingUser.new_password && this.editingUser.new_password.trim() !== '') { formData.append('new_password', this.editingUser.new_password); }
                try {
                    const response = await fetch('actions/admin_update_user_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.editModalMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) {
                        const index = this.users.findIndex(u => u.id === this.editingUser.id);
                        if (index !== -1) {
                            this.users[index].username = this.editingUser.username.trim();
                            this.users[index].email = this.editingUser.email.trim();
                            this.users[index].is_admin = this.editingUser.is_admin ? 1 : 0;
                        }
                        setTimeout(() => { this.closeEditUserModal(); }, 1500);
                    }
                } catch (e) { console.error("Save user changes JS error:", e); this.editModalMessage = { text: e.message.includes("Server error") ? e.message : 'Lỗi kết nối.', type: 'error' }; }
                finally { this.isProcessingUserUpdate = false; }
            },
            async deleteUser(userId, username) {
                if (userId === this.currentAdminId) { alert("Không thể tự xóa chính mình!"); return; }
                if (!confirm(`Xóa người dùng '${username}' (ID: ${userId})? Hành động này sẽ xóa tất cả dữ liệu liên quan của người dùng này.`)) return;
                this.userMessage = { text: 'Đang xóa...', type: 'info' };
                const formData = new FormData(); formData.append('user_id', userId);
                try {
                    const response = await fetch('actions/admin_delete_user_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.userMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) {
                        this.users = this.users.filter(u => u.id !== userId);
                    }
                } catch (e) { console.error("Delete user JS error:", e); this.userMessage = { text: e.message.includes("Server error") ? e.message : 'Lỗi kết nối.', type: 'error' }; }
            },
            openCreateUserModal() {
                this.newUser = { username: '', email: '', password: '', confirm_password: '', is_admin: 0 };
                this.createModalMessage = { text: '', type: '' }; this.showCreateUserModal = true;
                this.$nextTick(() => this.$refs.newUsernameAdminInput?.focus());
            },
            closeCreateUserModal() { this.showCreateUserModal = false; },
            async createNewUser() {
                this.isProcessingUserCreate = true; this.createModalMessage = { text: '', type: '' };
                if (!this.newUser.username.trim() || !this.newUser.email.trim() || !this.newUser.password.trim()) { this.createModalMessage = { text: "Điền đủ username, email, mật khẩu.", type: "error" }; this.isProcessingUserCreate = false; return; }
                if (this.newUser.password !== this.newUser.confirm_password) { this.createModalMessage = { text: "Mật khẩu không khớp.", type: "error" }; this.isProcessingUserCreate = false; return; }
                if (this.newUser.password.length < 6) { this.createModalMessage = { text: "Mật khẩu > 5 ký tự.", type: "error" }; this.isProcessingUserCreate = false; return; }
                const formData = new FormData();
                formData.append('username', this.newUser.username); formData.append('email', this.newUser.email);
                formData.append('password', this.newUser.password); formData.append('is_admin', this.newUser.is_admin ? 1 : 0);
                try {
                    const response = await fetch('actions/admin_create_user_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.createModalMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) {
                        setTimeout(() => { this.closeCreateUserModal(); window.location.reload(); }, 1500);
                    }
                } catch (e) { console.error("Create user JS error:", e); this.createModalMessage = { text: e.message.includes("Server error") ? e.message : 'Lỗi kết nối.', type: 'error' }; }
                finally { this.isProcessingUserCreate = false; }
            },

            // === SHARED LINKS MANAGEMENT METHODS ===
            async fetchSharedLinks(page = 1) {
                this.isLoadingSharedLinks = true;
                this.sharedLinkMessage = { text: '', type: '' };
                console.log(`Admin: Fetching shared links for page ${page}`);

                const url = new URL(window.location);
                url.searchParams.set('page_link', page);
                url.searchParams.set('view', 'shared_links');
                window.history.pushState({}, '', url.toString());

                try {
                    const response = await fetch(`actions/admin_get_shared_links_action.php?page=${page}&limit=${this.sharedLinkPagination.itemsPerPage || 15}`);
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    if (result.success) {
                        this.sharedLinks = result.links;
                        this.sharedLinkPagination.currentPage = result.currentPage;
                        this.sharedLinkPagination.totalPages = result.totalPages;
                        if (typeof result.totalItems !== 'undefined') {
                            this.sharedLinkPagination.totalItems = result.totalItems;
                        }
                        console.log("Admin: Fetched shared links:", this.sharedLinks.length, "Total Pages:", result.totalPages, "Total Items:", this.sharedLinkPagination.totalItems);
                    } else {
                        this.sharedLinkMessage = { text: result.message || "Lỗi tải link chia sẻ.", type: 'error' };
                    }
                } catch (e) {
                    console.error("Admin Fetch shared links JS error:", e);
                    this.sharedLinkMessage = { text: e.message.includes("Server error") ? e.message : 'Lỗi kết nối khi tải link.', type: 'error' };
                } finally {
                    this.isLoadingSharedLinks = false;
                }
            },
            async trashSharedLinkAdmin(linkId) {
                if (!confirm('Chuyển link này vào thùng rác (vô hiệu hóa)?')) return;
                this.sharedLinkMessage = { text: 'Đang xử lý...', type: 'info' };
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/admin_trash_shared_link_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.sharedLinkMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) this.fetchSharedLinks(this.sharedLinkPagination.currentPage);
                } catch (e) { this.sharedLinkMessage = { text: 'Lỗi kết nối.', type: 'error' }; }
            },
            async restoreSharedLinkAdmin(linkId) {
                if (!confirm('Khôi phục link này?')) return;
                this.sharedLinkMessage = { text: 'Đang xử lý...', type: 'info' };
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/admin_restore_shared_link_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.sharedLinkMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) this.fetchSharedLinks(this.sharedLinkPagination.currentPage);
                } catch (e) { this.sharedLinkMessage = { text: 'Lỗi kết nối.', type: 'error' }; }
            },
            async deleteSharedLinkPermanentlyAdmin(linkId) {
                if (!confirm('XÓA VĨNH VIỄN link này? Không thể hoàn tác.')) return;
                this.sharedLinkMessage = { text: 'Đang xóa...', type: 'info' };
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/admin_delete_shared_link_permanently_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Lỗi server ${response.status}: ${txt.substring(0, 150)}`); }
                    const result = await response.json();
                    this.sharedLinkMessage = { text: result.message, type: result.success ? 'success' : 'error' };
                    if (result.success) {
                        if (this.sharedLinks.length === 1 && this.sharedLinkPagination.currentPage > 1) {
                            this.fetchSharedLinks(this.sharedLinkPagination.currentPage - 1);
                        } else {
                            this.fetchSharedLinks(this.sharedLinkPagination.currentPage);
                        }
                    }
                } catch (e) { this.sharedLinkMessage = { text: 'Lỗi kết nối.', type: 'error' }; }
            }
        };
    });

});