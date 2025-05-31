document.addEventListener('alpine:init', () => {
    console.log("alpine_profile.js: Event 'alpine:init' fired.");

    Alpine.data('userProfileApp', (currentUsername, currentEmail, initialActiveLinks, initialTrashedLinks) => {
        console.log("Alpine.data('userProfileApp') CALLED with initial data:",
            currentUsername, currentEmail,
            JSON.parse(JSON.stringify(initialActiveLinks || [])),
            JSON.parse(JSON.stringify(initialTrashedLinks || []))
        );

        return {
            // === STATE ===
            forms: {
                username: { newUsername: currentUsername || '' },
                email: { newEmail: currentEmail || '', currentPassword: '' },
                password: { currentPassword: '', newPassword: '', confirmNewPassword: '' }
            },
            messages: { username: '', email: '', password: '', links: '' },
            messageType: { username: '', email: '', password: '', links: '' },
            loading: {
                username: false,
                email: false,
                password: false,
                links: false
            },
            activeSharedLinks: initialActiveLinks || [],
            trashedSharedLinks: initialTrashedLinks || [],
            activeTab: 'activeLinks',

            // === LIFECYCLE & WATCHERS ===
            init() {
                console.log("userProfileApp component: init() method CALLED.");
                console.log("State after init - Username:", this.forms.username.newUsername);
                console.log("State after init - Active Links:", this.activeSharedLinks.length);
                Object.keys(this.messages).forEach(key => {
                    this.$watch(`messages.${key}`, (newValue) => {
                        if (newValue) {
                            setTimeout(() => {
                                this.messages[key] = '';
                                this.messageType[key] = '';
                            }, 5000);
                        }
                    });
                });
                console.log("userProfileApp component: init() method FINISHED.");
            },

            // === UPDATE USERNAME ===
            async updateUsername() {
                console.log("updateUsername called. newUsername:", this.forms.username.newUsername);
                this.loading.username = true; this.messages.username = ''; this.messageType.username = '';
                if (!this.forms.username.newUsername.trim()) {
                    this.messages.username = 'Tên người dùng không được để trống.';
                    this.messageType.username = 'error'; this.loading.username = false; return;
                }
                const formData = new FormData(); formData.append('new_username', this.forms.username.newUsername.trim());
                try {
                    const response = await fetch('actions/update_username_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Update username server response:", result);
                    this.messages.username = result.message; this.messageType.username = result.success ? 'success' : 'error';
                    if (result.success && result.newUsername) {
                        if (confirm(result.message + " Tải lại trang để cập nhật!")) {
                            window.location.reload();
                        }
                    }
                } catch (e) {
                    console.error("Update username JS error:", e);
                    this.messages.username = e.message.includes("Server error") ? e.message : 'Lỗi kết nối hoặc xử lý.'; this.messageType.username = 'error';
                } finally { this.loading.username = false; }
            },

            // === UPDATE EMAIL ===
            async updateEmail() {
                console.log("updateEmail called. newEmail:", this.forms.email.newEmail);
                this.loading.email = true; this.messages.email = ''; this.messageType.email = '';
                if (!this.forms.email.newEmail.trim() || !this.forms.email.currentPassword.trim()) {
                    this.messages.email = 'Nhập email mới và mật khẩu hiện tại.'; this.messageType.email = 'error';
                    this.loading.email = false; return;
                }
                if (!/^\S+@\S+\.\S+$/.test(this.forms.email.newEmail.trim())) {
                    this.messages.email = 'Định dạng email mới không hợp lệ.'; this.messageType.email = 'error';
                    this.loading.email = false; return;
                }
                const formData = new FormData();
                formData.append('new_email', this.forms.email.newEmail.trim());
                formData.append('current_password', this.forms.email.currentPassword);
                try {
                    const response = await fetch('actions/update_email_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Update email server response:", result);
                    this.messages.email = result.message; this.messageType.email = result.success ? 'success' : 'error';
                    if (result.success) { this.forms.email.currentPassword = ''; }
                } catch (e) { console.error("Update email JS error:", e); if (!this.messages.email) { this.messages.email = e.message.includes("Server error") ? e.message : 'Lỗi kết nối.'; this.messageType.email = 'error'; } }
                finally { this.loading.email = false; }
            },

            // === UPDATE PASSWORD ===
            async updatePassword() {
                console.log("updatePassword called.");
                this.loading.password = true; this.messages.password = ''; this.messageType.password = '';
                if (!this.forms.password.currentPassword || !this.forms.password.newPassword || !this.forms.password.confirmNewPassword) {
                    this.messages.password = 'Điền đủ các trường mật khẩu.'; this.messageType.password = 'error';
                    this.loading.password = false; return;
                }
                if (this.forms.password.newPassword.length < 6) {
                    this.messages.password = 'Mật khẩu mới ít nhất 6 ký tự.'; this.messageType.password = 'error';
                    this.loading.password = false; return;
                }
                if (this.forms.password.newPassword !== this.forms.password.confirmNewPassword) {
                    this.messages.password = 'Mật khẩu mới không khớp.'; this.messageType.password = 'error';
                    this.loading.password = false; return;
                }
                const formData = new FormData();
                formData.append('current_password', this.forms.password.currentPassword);
                formData.append('new_password', this.forms.password.newPassword);
                try {
                    const response = await fetch('actions/update_password_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Update password server response:", result);
                    this.messages.password = result.message; this.messageType.password = result.success ? 'success' : 'error';
                    if (result.success) { this.forms.password = { currentPassword: '', newPassword: '', confirmNewPassword: '' }; }
                } catch (e) { console.error("Update password JS error:", e); if (!this.messages.password) { this.messages.password = e.message.includes("Server error") ? e.message : 'Lỗi kết nối.'; this.messageType.password = 'error'; } }
                finally { this.loading.password = false; }
            },

            // === SHARED LINK MANAGEMENT ===
            async trashSharedLink(linkId) {
                console.log("trashSharedLink called for ID:", linkId);
                if (!confirm('Chuyển liên kết này vào thùng rác?')) return;
                this.loading.links = true; this.messages.links = ''; this.messageType.links = '';
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/trash_shared_link_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Trash link response:", result);
                    this.messages.links = result.message; this.messageType.links = result.success ? 'success' : 'error';
                    if (result.success) {
                        const linkToMoveIndex = this.activeSharedLinks.findIndex(link => link.id === linkId);
                        if (linkToMoveIndex > -1) {
                            const linkToMove = this.activeSharedLinks.splice(linkToMoveIndex, 1)[0];
                            linkToMove.deleted_on = new Date().toISOString();
                            this.trashedSharedLinks.unshift(linkToMove);
                        }
                    }
                } catch (e) { console.error("Trash link JS error:", e); if (!this.messages.links) { this.messages.links = e.message.includes("Server error") ? e.message : 'Lỗi kết nối.'; this.messageType.links = 'error'; } }
                finally { this.loading.links = false; }
            },

            async restoreSharedLink(linkId) {
                console.log("restoreSharedLink called for ID:", linkId);
                if (!confirm('Khôi phục liên kết này?')) return;
                this.loading.links = true; this.messages.links = ''; this.messageType.links = '';
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/restore_shared_link_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Restore link response:", result);
                    this.messages.links = result.message; this.messageType.links = result.success ? 'success' : 'error';
                    if (result.success) {
                        const linkToRestoreIndex = this.trashedSharedLinks.findIndex(link => link.id === linkId);
                        if (linkToRestoreIndex > -1) {
                            const linkToRestore = this.trashedSharedLinks.splice(linkToRestoreIndex, 1)[0];
                            linkToRestore.deleted_on = null;
                            this.activeSharedLinks.unshift(linkToRestore);
                        }
                    }
                } catch (e) { console.error("Restore link JS error:", e); if (!this.messages.links) { this.messages.links = e.message.includes("Server error") ? e.message : 'Lỗi kết nối.'; this.messageType.links = 'error'; } }
                finally { this.loading.links = false; }
            },

            async deleteSharedLinkPermanently(linkId) {
                console.log("deleteSharedLinkPermanently called for ID:", linkId);
                if (!confirm('XÓA VĨNH VIỄN liên kết này? Không thể hoàn tác.')) return;
                this.loading.links = true; this.messages.links = ''; this.messageType.links = '';
                const formData = new FormData(); formData.append('shared_link_id', linkId);
                try {
                    const response = await fetch('actions/delete_shared_link_permanently_action.php', { method: 'POST', body: formData });
                    if (!response.ok) { const txt = await response.text(); throw new Error(`Server error ${response.status}: ${txt.substring(0, 100)}`); }
                    const result = await response.json();
                    console.log("Delete permanently response:", result);
                    this.messages.links = result.message; this.messageType.links = result.success ? 'success' : 'error';
                    if (result.success) {
                        this.trashedSharedLinks = this.trashedSharedLinks.filter(link => link.id !== linkId);
                    }
                } catch (e) { console.error("Delete perm. link JS error:", e); if (!this.messages.links) { this.messages.links = e.message.includes("Server error") ? e.message : 'Lỗi kết nối.'; this.messageType.links = 'error'; } }
                finally { this.loading.links = false; }
            },

            daysUntilPermanentDelete(deletedOnStr) {
                if (!deletedOnStr) return 'N/A';
                const deletedDate = new Date(deletedOnStr);
                const sevenDaysLater = new Date(deletedDate.getTime() + 7 * 24 * 60 * 60 * 1000);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                sevenDaysLater.setHours(23, 59, 59, 999);
                const diffTime = sevenDaysLater - today;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                return diffDays >= 0 ? diffDays : 0;
            }
        };
    });

}); 