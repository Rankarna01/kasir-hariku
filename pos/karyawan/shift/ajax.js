document.addEventListener('alpine:init', () => {
    Alpine.data('employeeShiftApp', () => ({
        viewMode: 'roster', // 'roster' | 'table'
        isLoading: false,
        isSaving: false,

        // Data arrays
        users: [],
        warehouses: [],
        shifts: [],
        schedules: [],
        stats: { total_scheduled: 0, today_present: 0, today_scheduled: 0, off_count: 0 },

        // Filters
        selectedWarehouse: 0,
        searchQuery: '',
        statusFilter: 'all',

        // Current Week Range (Monday - Sunday)
        startDate: '',
        endDate: '',
        weekDays: [],

        // Modal Add / Assign
        showAddModal: false,
        formData: {
            user_id: '',
            warehouse_id: 1,
            shift_id: '',
            schedule_date: new Date().toISOString().split('T')[0],
            status: 'scheduled',
            notes: ''
        },

        // Modal Quick Status
        showStatusModal: false,
        activeSchedule: null,
        statusUpdateVal: 'scheduled',
        statusUpdateNotes: '',

        // Modal Copy Week
        showCopyModal: false,

        async init() {
            this.setThisWeek();
            await this.fetchMasterData();
            await this.fetchSchedules();
        },

        // Week navigation helpers
        setThisWeek() {
            const now = new Date();
            const day = now.getDay();
            // Monday is day 1, Sunday is day 7 (0 in JS)
            const diffToMonday = (day === 0 ? -6 : 1) - day;
            const monday = new Date(now);
            monday.setDate(now.getDate() + diffToMonday);
            
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);

            this.startDate = monday.toISOString().split('T')[0];
            this.endDate = sunday.toISOString().split('T')[0];
            this.buildWeekDays(monday);
        },

        prevWeek() {
            const m = new Date(this.startDate);
            m.setDate(m.getDate() - 7);
            const s = new Date(m);
            s.setDate(m.getDate() + 6);
            this.startDate = m.toISOString().split('T')[0];
            this.endDate = s.toISOString().split('T')[0];
            this.buildWeekDays(m);
            this.fetchSchedules();
        },

        nextWeek() {
            const m = new Date(this.startDate);
            m.setDate(m.getDate() + 7);
            const s = new Date(m);
            s.setDate(m.getDate() + 6);
            this.startDate = m.toISOString().split('T')[0];
            this.endDate = s.toISOString().split('T')[0];
            this.buildWeekDays(m);
            this.fetchSchedules();
        },

        thisWeek() {
            this.setThisWeek();
            this.fetchSchedules();
        },

        buildWeekDays(mondayDate) {
            const dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            const todayStr = new Date().toISOString().split('T')[0];
            const arr = [];

            for (let i = 0; i < 7; i++) {
                const d = new Date(mondayDate);
                d.setDate(mondayDate.getDate() + i);
                const dateStr = d.toISOString().split('T')[0];
                const day = d.getDate();
                const month = d.toLocaleDateString('id-ID', { month: 'short' });

                arr.push({
                    name: dayNames[i],
                    date: dateStr,
                    dateFormatted: `${day} ${month}`,
                    isToday: dateStr === todayStr
                });
            }
            this.weekDays = arr;
        },

        async fetchMasterData() {
            try {
                const res = await fetch('logic.php?action=get_master_data');
                const data = await res.json();
                if (data.status === 'success') {
                    this.users = data.users || [];
                    this.warehouses = data.warehouses || [];
                    this.shifts = data.shifts || [];
                    if (this.warehouses.length > 0) {
                        this.formData.warehouse_id = this.warehouses[0].id;
                    }
                }
            } catch (err) {
                console.error("Gagal load master data:", err);
            }
        },

        async fetchSchedules() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_schedules&start_date=${this.startDate}&end_date=${this.endDate}&warehouse_id=${this.selectedWarehouse}&nocache=${Date.now()}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.schedules = data.schedules || [];
                    this.stats = data.stats || { total_scheduled: 0, today_present: 0, today_scheduled: 0, off_count: 0 };
                }
            } catch (err) {
                console.error("Gagal load jadwal:", err);
            } finally {
                this.isLoading = false;
            }
        },

        // Helper to locate a schedule for a specific user and date
        getSchedule(userId, dateStr) {
            return this.schedules.find(s => parseInt(s.user_id) === parseInt(userId) && s.schedule_date === dateStr) || null;
        },

        // Filtered schedules for Table View
        get filteredSchedules() {
            let list = this.schedules;
            if (this.statusFilter !== 'all') {
                list = list.filter(s => s.status === this.statusFilter);
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(s => 
                    (s.employee_name && s.employee_name.toLowerCase().includes(q)) ||
                    (s.shift_name && s.shift_name.toLowerCase().includes(q)) ||
                    (s.store_name && s.store_name.toLowerCase().includes(q))
                );
            }
            return list;
        },

        openAddModal() {
            this.formData = {
                user_id: this.users.length > 0 ? this.users[0].id : '',
                warehouse_id: this.warehouses.length > 0 ? this.warehouses[0].id : 1,
                shift_id: this.shifts.length > 0 ? this.shifts[0].id : '',
                schedule_date: new Date().toISOString().split('T')[0],
                status: 'scheduled',
                notes: ''
            };
            this.showAddModal = true;
        },

        quickAssign(userId, dateStr) {
            this.formData = {
                user_id: userId,
                warehouse_id: this.warehouses.length > 0 ? this.warehouses[0].id : 1,
                shift_id: this.shifts.length > 0 ? this.shifts[0].id : '',
                schedule_date: dateStr,
                status: 'scheduled',
                notes: ''
            };
            this.showAddModal = true;
        },

        async saveSchedule() {
            if (!this.formData.user_id || !this.formData.shift_id || !this.formData.schedule_date) {
                Swal.fire('Perhatian', 'Semua kolom wajib diisi!', 'warning');
                return;
            }

            this.isSaving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'save_schedule');
                fd.append('user_id', this.formData.user_id);
                fd.append('warehouse_id', this.formData.warehouse_id);
                fd.append('shift_id', this.formData.shift_id);
                fd.append('schedule_date', this.formData.schedule_date);
                fd.append('status', this.formData.status);
                fd.append('notes', this.formData.notes);

                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    this.showAddModal = false;
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message, timer: 1500, showConfirmButton: false });
                    await this.fetchSchedules();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal menyimpan penugasan shift.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        openQuickStatusModal(schedule) {
            if (!schedule) return;
            this.activeSchedule = schedule;
            this.statusUpdateVal = schedule.status || 'scheduled';
            this.statusUpdateNotes = schedule.notes || '';
            this.showStatusModal = true;
        },

        async saveStatusUpdate() {
            if (!this.activeSchedule) return;

            try {
                const fd = new FormData();
                fd.append('action', 'update_status');
                fd.append('id', this.activeSchedule.id);
                fd.append('status', this.statusUpdateVal);
                fd.append('notes', this.statusUpdateNotes);

                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    this.showStatusModal = false;
                    Swal.fire({ icon: 'success', title: 'Status Diperbarui', timer: 1200, showConfirmButton: false });
                    await this.fetchSchedules();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal update status.', 'error');
            }
        },

        async deleteSchedule(id) {
            const confirm = await Swal.fire({
                title: 'Hapus Jadwal Shift?',
                text: 'Penugasan shift karyawan pada tanggal ini akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            });

            if (confirm.isConfirmed) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete_schedule');
                    fd.append('id', id);

                    const res = await fetch('logic.php', { method: 'POST', body: fd });
                    const result = await res.json();

                    if (result.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Terhapus', text: result.message, timer: 1200, showConfirmButton: false });
                        await this.fetchSchedules();
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal memproses aksi.', 'error');
                }
            }
        },

        openCopyModal() {
            this.showCopyModal = true;
        },

        async submitCopyWeek() {
            // Source week is previous week of current startDate
            const curStart = new Date(this.startDate);
            const prevStart = new Date(curStart);
            prevStart.setDate(curStart.getDate() - 7);
            const prevStartStr = prevStart.toISOString().split('T')[0];

            try {
                const fd = new FormData();
                fd.append('action', 'copy_week');
                fd.append('source_week_start', prevStartStr);
                fd.append('target_week_start', this.startDate);

                const res = await fetch('logic.php', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status === 'success') {
                    this.showCopyModal = false;
                    Swal.fire('Sukses', result.message, 'success');
                    await this.fetchSchedules();
                } else {
                    Swal.fire('Pemberitahuan', result.message, 'info');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menyalin jadwal.', 'error');
            }
        },

        exportExcel() {
            window.location.href = `logic.php?action=export_excel&start_date=${this.startDate}&end_date=${this.endDate}&warehouse_id=${this.selectedWarehouse}`;
        },

        // Formatters & UI helpers
        formatDateRange(start, end) {
            if (!start || !end) return '';
            const s = new Date(start);
            const e = new Date(end);
            return s.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' - ' +
                   e.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        getStatusCardClass(st) {
            switch(st) {
                case 'present': return 'bg-emerald-50/70 border-emerald-200 text-emerald-900';
                case 'completed': return 'bg-slate-50 border-slate-200 text-slate-800';
                case 'off': return 'bg-amber-50/50 border-amber-200 text-amber-900';
                case 'absent': return 'bg-rose-50/60 border-rose-200 text-rose-900';
                case 'swapped': return 'bg-purple-50/70 border-purple-200 text-purple-900';
                default: return 'bg-blue-50/70 border-blue-200 text-blue-900';
            }
        },

        getStatusBadgeClass(st) {
            switch(st) {
                case 'present': return 'bg-emerald-200/70 text-emerald-800';
                case 'completed': return 'bg-slate-200 text-slate-700';
                case 'off': return 'bg-amber-200/70 text-amber-800';
                case 'absent': return 'bg-rose-200/70 text-rose-800';
                case 'swapped': return 'bg-purple-200/70 text-purple-800';
                default: return 'bg-blue-200/70 text-blue-800';
            }
        },

        getStatusLabel(st) {
            switch(st) {
                case 'present': return 'Hadir';
                case 'completed': return 'Selesai';
                case 'off': return 'Libur';
                case 'absent': return 'Izin / Sakit';
                case 'swapped': return 'Tukar Shift';
                default: return 'Terjadwal';
            }
        }
    }));
});
