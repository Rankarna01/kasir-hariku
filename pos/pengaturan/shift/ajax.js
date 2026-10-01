document.addEventListener('alpine:init', () => {
    Alpine.data('shiftPanelApp', () => ({
        activeTab: 'master', // 'master' | 'live' | 'history'
        shifts: [],
        liveShifts: [],
        historyData: [],
        stats: { total_master: 0, total_active: 0, total_live: 0 },
        isLoading: false,
        isSaving: false,

        // Modal Add/Edit Master
        showModal: false,
        formData: { id: '', shift_name: '', start_time: '', end_time: '', is_active: 1 },

        // Modal Force Close Live Shift
        showForceModal: false,
        selectedLiveShift: null,
        forceCloseCash: 0,

        // History Filter
        historyFilter: {
            startDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            endDate: new Date().toISOString().split('T')[0]
        },

        async init() {
            await this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_panel_data&nocache=${Date.now()}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.shifts = data.shifts || [];
                    this.liveShifts = data.live_shifts || [];
                    this.stats = data.stats || { total_master: 0, total_active: 0, total_live: 0 };
                } else {
                    console.error(data.message);
                }
            } catch (err) {
                console.error("Gagal memuat data:", err);
            } finally {
                this.isLoading = false;
            }
        },

        openModal(shift = null) {
            if (shift) {
                this.formData = {
                    id: shift.id,
                    shift_name: shift.shift_name,
                    start_time: shift.start_time ? shift.start_time.substring(0, 5) : '',
                    end_time: shift.end_time ? shift.end_time.substring(0, 5) : '',
                    is_active: shift.is_active !== undefined ? shift.is_active : 1
                };
            } else {
                this.formData = { id: '', shift_name: '', start_time: '08:00', end_time: '16:00', is_active: 1 };
            }
            this.showModal = true;
        },

        async saveShift() {
            if (!this.formData.shift_name || !this.formData.start_time || !this.formData.end_time) {
                Swal.fire('Peringatan', 'Harap isi semua kolom dengan benar!', 'warning');
                return;
            }

            this.isSaving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'save');
                fd.append('id', this.formData.id || '');
                fd.append('shift_name', this.formData.shift_name);
                fd.append('start_time', this.formData.start_time);
                fd.append('end_time', this.formData.end_time);
                fd.append('is_active', this.formData.is_active);

                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    this.showModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    await this.fetchData();
                } else {
                    Swal.fire('Gagal', result.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        async toggleStatus(id, newStatus) {
            try {
                const fd = new FormData();
                fd.append('action', 'toggle_status');
                fd.append('id', id);
                fd.append('status', newStatus);

                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await res.json();
                if (result.status === 'success') {
                    await this.fetchData();
                }
            } catch (e) {
                console.error(e);
            }
        },

        async deleteShift(id) {
            const confirm = await Swal.fire({
                title: 'Hapus Master Shift?',
                text: 'Shift ini akan dinonaktifkan dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal'
            });

            if (confirm.isConfirmed) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);

                    const res = await fetch('logic.php', { method: 'POST', body: fd });
                    const result = await res.json();
                    if (result.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message, timer: 1200, showConfirmButton: false });
                        await this.fetchData();
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal memproses aksi.', 'error');
                }
            }
        },

        openForceCloseModal(liveShift) {
            this.selectedLiveShift = liveShift;
            this.forceCloseCash = 0;
            this.showForceModal = true;
        },

        async submitForceClose() {
            if (!this.selectedLiveShift) return;

            const confirm = await Swal.fire({
                title: 'Konfirmasi Tutup Paksa?',
                text: `Sesi kasir ${this.selectedLiveShift.kasir_name} akan diakhiri secara manual oleh Admin.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Tutup Sekarang!',
                cancelButtonText: 'Batal'
            });

            if (confirm.isConfirmed) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'force_close_shift');
                    fd.append('shift_history_id', this.selectedLiveShift.id);
                    fd.append('end_cash', this.forceCloseCash);

                    const res = await fetch('logic.php', { method: 'POST', body: fd });
                    const result = await res.json();

                    if (result.status === 'success') {
                        this.showForceModal = false;
                        Swal.fire('Tutup Shift Berhasil', result.message, 'success');
                        await this.fetchData();
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal memproses tutup shift.', 'error');
                }
            }
        },

        async fetchHistory() {
            try {
                const res = await fetch(`logic.php?action=get_history&start_date=${this.historyFilter.startDate}&end_date=${this.historyFilter.endDate}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.historyData = data.history || [];
                }
            } catch (e) {
                console.error("Gagal memuat riwayat:", e);
            }
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        formatTime(t) {
            if (!t) return '-';
            return t.substring(0, 5);
        },

        formatDateTime(dt) {
            if (!dt) return '-';
            const d = new Date(dt);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                   d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
    }));
});