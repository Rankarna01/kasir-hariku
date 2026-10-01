document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        loginRole: 'admin', // 'admin' atau 'pegawai'
        username: '',
        password: '',
        showPass: false,
        isLoading: false,

        init() {
            // Pengecekan sesi online/offline pintar
            if (window.dbAuth) {
                window.dbAuth.getItem('user_session').then(user => {
                    if (user && !navigator.onLine) {
                        window.location.href = '../pos/kasir/'; 
                    }
                });
            }
        },

        setLoginRole(role) {
            this.loginRole = role;
            // Bersihkan field bila berganti role agar tidak tertukar
            this.username = '';
            this.password = '';
        },

        async doLogin() {
            if (!navigator.onLine) {
                Swal.fire('Offline!', 'Koneksi internet wajib menyala untuk proses login.', 'warning');
                return;
            }

            this.isLoading = true;

            try {
                const formData = new FormData();
                formData.append('action', 'login_pos');
                formData.append('username', this.username);
                formData.append('password', this.password);
                formData.append('target_role', this.loginRole);

                const response = await fetch('logic.php', { method: 'POST', body: formData });
                const rawText = await response.text(); 

                try {
                    const result = JSON.parse(rawText);

                    if (result.status === 'success') {
                        if (window.dbAuth && result.data) {
                            await window.dbAuth.setItem('user_session', result.data);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Login Berhasil!',
                            text: 'Mengarahkan ke Sistem...',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                        }).then(() => {
                            window.location.href = result.redirect; 
                        });
                    } else {
                        Swal.fire('Gagal Masuk', result.message || 'Username atau password salah', 'error');
                    }
                } catch (parseError) {
                    console.error("❌ ERROR DARI SERVER (Format bukan JSON):", rawText);
                    Swal.fire('Error Server', 'Terjadi kesalahan sistem. Cek Console browser.', 'error');
                }
            } catch (error) {
                console.error("Koneksi Error:", error);
                Swal.fire('Error Jaringan', 'Gagal terhubung ke server! Periksa koneksi Anda.', 'error');
            } finally {
                this.isLoading = false;
            }
        }
    }));
});