document.addEventListener('alpine:init', () => {
    Alpine.data('employeeReportApp', () => ({
        isLoading: false,
        startDate: '',
        endDate: '',
        currentPreset: 'thisMonth',

        stats: {
            total_employees: 0,
            total_shifts: 0,
            total_hours: 0,
            total_transactions: 0,
            total_omset: 0,
            avg_basket_size: 0,
            total_selisih: 0
        },

        employees: [],
        searchQuery: '',
        roleFilter: 'all',

        // Detail Modal
        showDetailModal: false,
        selectedEmployee: null,

        async init() {
            this.setPreset('thisMonth');
            await this.fetchReport();
        },

        setPreset(preset) {
            this.currentPreset = preset;
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth();
            const d = now.getDate();

            if (preset === 'today') {
                const todayStr = now.toISOString().split('T')[0];
                this.startDate = todayStr;
                this.endDate = todayStr;
            } else if (preset === 'yesterday') {
                const yesterday = new Date(now);
                yesterday.setDate(d - 1);
                const yStr = yesterday.toISOString().split('T')[0];
                this.startDate = yStr;
                this.endDate = yStr;
            } else if (preset === '7days') {
                const prev7 = new Date(now);
                prev7.setDate(d - 6);
                this.startDate = prev7.toISOString().split('T')[0];
                this.endDate = now.toISOString().split('T')[0];
            } else if (preset === 'thisMonth') {
                const firstDay = new Date(y, m, 1);
                this.startDate = firstDay.toISOString().split('T')[0];
                this.endDate = now.toISOString().split('T')[0];
            } else if (preset === 'lastMonth') {
                const firstDayLastMonth = new Date(y, m - 1, 1);
                const lastDayLastMonth = new Date(y, m, 0);
                this.startDate = firstDayLastMonth.toISOString().split('T')[0];
                this.endDate = lastDayLastMonth.toISOString().split('T')[0];
            }
        },

        async fetchReport() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_report&start_date=${this.startDate}&end_date=${this.endDate}&nocache=${Date.now()}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.stats = data.stats || {};
                    this.employees = data.employees || [];
                } else {
                    console.error(data.message);
                }
            } catch (err) {
                console.error("Gagal memuat laporan:", err);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredEmployees() {
            let list = this.employees;
            if (this.roleFilter !== 'all') {
                const rf = this.roleFilter.toLowerCase();
                list = list.filter(e => e.role_name && e.role_name.toLowerCase().includes(rf));
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(e => 
                    (e.name && e.name.toLowerCase().includes(q)) ||
                    (e.username && e.username.toLowerCase().includes(q)) ||
                    (e.store_name && e.store_name.toLowerCase().includes(q))
                );
            }
            return list;
        },

        openDetailModal(emp) {
            this.selectedEmployee = emp;
            this.showDetailModal = true;
        },

        printPdf() {
            if (!this.startDate || !this.endDate) return;
            window.open(`print_pdf.php?start_date=${this.startDate}&end_date=${this.endDate}`, '_blank');
        },

        exportExcel() {
            if (!this.startDate || !this.endDate) return;
            window.location.href = `logic.php?action=export_excel&start_date=${this.startDate}&end_date=${this.endDate}`;
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        getInitials(name) {
            if (!name) return 'K';
            const parts = name.trim().split(' ');
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
    }));
});
