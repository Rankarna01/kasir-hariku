document.addEventListener('alpine:init', () => {
    Alpine.data('loginApp', () => ({
        loginRole: 'admin', // 'admin' atau 'pegawai'
        username: '',
        password: '',
        showPass: false,
        isLoading: false,

        // Pop-up pemilihan shift dinamis untuk Pegawai
        showShiftModal: false,
        masterShifts: [],
        selectedShiftId: null,
        startCash: 0,
        startCashFormatted: '0',
        loggedInUser: null,
        isLoadingShift: false,

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

        formatRupiah(val) {
            const num = parseInt(val, 10);
            if (isNaN(num)) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        updateCashInput(val) {
            const raw = String(val).replace(/[^0-9]/g, '');
            const num = parseInt(raw, 10) || 0;
            this.startCash = num;
            this.startCashFormatted = this.formatRupiah(num);
        },

        setCashQuick(amount) {
            this.startCash = amount;
            this.startCashFormatted = this.formatRupiah(amount);
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

                    // Kasus 1: Pegawai butuh pemilihan Shift Operasional Dinamis
                    if (result.status === 'need_shift') {
                        this.loggedInUser = result.data || {};
                        this.masterShifts = result.shifts || [];

                        // Pilih otomatis shift yang sedang berlangsung atau urutan pertama
                        const curShift = this.masterShifts.find(s => s.is_current);
                        this.selectedShiftId = curShift ? curShift.id : (this.masterShifts[0]?.id || null);

                        const defCash = result.default_start_cash || 0;
                        this.startCash = defCash;
                        this.startCashFormatted = this.formatRupiah(defCash);

                        // Tampilkan Pop-Up Pemilihan Shift Dinamis!
                        this.showShiftModal = true;
                        return;
                    }

                    // Kasus 2: Login berhasil langsung (Admin atau Pegawai yang sudah buka shift)
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
        },

        async confirmShift() {
            if (!this.selectedShiftId) {
                Swal.fire('Peringatan', 'Silakan pilih salah satu shift kerja terlebih dahulu!', 'warning');
                return;
            }

            this.isLoadingShift = true;

            try {
                const formData = new FormData();
                formData.append('action', 'confirm_shift_login');
                formData.append('shift_id', this.selectedShiftId);
                formData.append('start_cash', this.startCash);

                const response = await fetch('logic.php', { method: 'POST', body: formData });
                const rawText = await response.text();

                try {
                    const result = JSON.parse(rawText);

                    if (result.status === 'success') {
                        if (window.dbAuth && this.loggedInUser) {
                            await window.dbAuth.setItem('user_session', this.loggedInUser);
                        }

                        this.showShiftModal = false;

                        Swal.fire({
                            icon: 'success',
                            title: 'Shift Berhasil Dibuka!',
                            text: result.message || 'Mengarahkan ke Mesin Kasir...',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                        }).then(() => {
                            window.location.href = result.redirect;
                        });
                    } else {
                        Swal.fire('Gagal Buka Shift', result.message || 'Terjadi kesalahan.', 'error');
                    }
                } catch (parseError) {
                    console.error("❌ ERROR DARI SERVER (Konfirmasi Shift):", rawText);
                    Swal.fire('Error Server', 'Format respon server tidak valid.', 'error');
                }
            } catch (error) {
                console.error("Koneksi Error:", error);
                Swal.fire('Error Jaringan', 'Gagal membuka shift kasir.', 'error');
            } finally {
                this.isLoadingShift = false;
            }
        }
    }));
});