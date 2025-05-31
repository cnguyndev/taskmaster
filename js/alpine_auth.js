document.addEventListener('alpine:init', () => {
    Alpine.data('authForm', () => ({
        loginData: {
            email: '',
            password: ''
        },
        registerData: {
            username: '',
            email: '',
            password: '',
            confirmPassword: ''
        },
        isLoading: false, 
        authMessage: { 
            text: '',
            type: '' 
        },

        async loginUser() {
            this.isLoading = true;
            this.authMessage = { text: '', type: '' };

            if (!this.loginData.email || !this.loginData.password) {
                this.authMessage = { text: 'Vui lòng nhập đầy đủ email và mật khẩu.', type: 'error' };
                this.isLoading = false;
                return;
            }

            const formData = new FormData();
            formData.append('email', this.loginData.email);
            formData.append('password', this.loginData.password);

            try {
                const response = await fetch('actions/login_process.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json' 
                    }
                });

                if (!response.ok) {
                    let errorMsg = `Lỗi HTTP: ${response.status} ${response.statusText}`;
                    try {
                        const errorResult = await response.json();
                        errorMsg = errorResult.message || errorMsg;
                    } catch (e) {
                        const rawErrorText = await response.text();
                        console.error("Raw server error (login):", rawErrorText);
                        errorMsg = `Lỗi từ server (${response.status}). Vui lòng thử lại.`;
                    }
                    this.authMessage = { text: errorMsg, type: 'error' };
                    this.isLoading = false;
                    return;
                }

                const result = await response.json();
                console.log('Login server response:', result); 

                if (result && result.success) {
                    this.authMessage = { text: result.message || 'Đăng nhập thành công! Đang chuyển hướng...', type: 'success' };
                    window.location.href = result.redirect_url || 'app.php';
                } else {
                    this.authMessage = { text: result.message || 'Email hoặc mật khẩu không đúng.', type: 'error' };
                }
            } catch (error) {
                console.error('Lỗi JavaScript khi đăng nhập:', error);
                this.authMessage = { text: 'Lỗi kết nối hoặc xử lý yêu cầu. Vui lòng thử lại.', type: 'error' };
            } finally {
                this.isLoading = false;
            }
        },

        async registerUser() {
            this.isLoading = true;
            this.authMessage = { text: '', type: '' };

            if (!this.registerData.username || !this.registerData.email || !this.registerData.password || !this.registerData.confirmPassword) {
                this.authMessage = { text: 'Vui lòng điền đầy đủ các trường bắt buộc.', type: 'error' };
                this.isLoading = false;
                return;
            }
            if (this.registerData.password !== this.registerData.confirmPassword) {
                this.authMessage = { text: 'Mật khẩu xác nhận không khớp.', type: 'error' };
                this.isLoading = false;
                return;
            }
            if (this.registerData.password.length < 6) {
                this.authMessage = { text: 'Mật khẩu phải có ít nhất 6 ký tự.', type: 'error' };
                this.isLoading = false;
                return;
            }

            const formData = new FormData();
            formData.append('username', this.registerData.username);
            formData.append('email', this.registerData.email);
            formData.append('password', this.registerData.password);

            try {
                const response = await fetch('actions/register_process.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    let errorMsg = `Lỗi HTTP: ${response.status} ${response.statusText}`;
                    try {
                        const errorResult = await response.json();
                        errorMsg = errorResult.message || errorMsg;
                    } catch (e) {
                        const rawErrorText = await response.text();
                        console.error("Raw server error (register):", rawErrorText);
                        errorMsg = `Lỗi từ server (${response.status}). Vui lòng thử lại.`;
                    }
                    this.authMessage = { text: errorMsg, type: 'error' };
                    this.isLoading = false;
                    return;
                }

                const result = await response.json();
                console.log('Register server response:', result); 

                if (result && result.success) {
                    this.authMessage = { text: result.message || 'Đăng ký thành công! Vui lòng đăng nhập.', type: 'success' };

                    this.registerData = { username: '', email: '', password: '', confirmPassword: '' };
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2500);
                } else {
                    this.authMessage = { text: result.message || 'Đăng ký thất bại. Email hoặc tên người dùng có thể đã tồn tại.', type: 'error' };
                }
            } catch (error) {
                console.error('Lỗi JavaScript khi đăng ký:', error);
                this.authMessage = { text: 'Lỗi kết nối hoặc xử lý yêu cầu. Vui lòng thử lại.', type: 'error' };
            } finally {
                this.isLoading = false;
            }
        }
    }));
});