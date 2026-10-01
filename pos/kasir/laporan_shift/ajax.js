function getLocalDateStr(offsetDays = 0) {
    const d = new Date();
    if (offsetDays !== 0) {
        d.setDate(d.getDate() + offsetDays);
    }
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('shiftReportApp', () => ({
        shifts: [],
        isLoading: false,
        currentPage: 1,
        totalPages: 1,
        totalData: 0,
        activeQuickFilter: 'today',
        filters: { 
            search: '',
            startDate: getLocalDateStr(0),
            endDate: getLocalDateStr(0)
        },
        summary: {
            total_shifts: 0,
            open_shifts: 0,
            closed_shifts: 0,
            total_system: 0,
            total_actual: 0,
            total_difference: 0
        },

        showModal: false,
        isDetailLoading: false,
        activeShift: null,
        activeTransactions: [],
        activeSettlements: [],
        activePettyCash: [],

        init() { 
            this.fetchShifts(); 
        },

        setQuickFilter(type) {
            this.activeQuickFilter = type;
            const now = new Date();
            if (type === 'today') {
                this.filters.startDate = getLocalDateStr(0);
                this.filters.endDate = getLocalDateStr(0);
            } else if (type === 'yesterday') {
                this.filters.startDate = getLocalDateStr(-1);
                this.filters.endDate = getLocalDateStr(-1);
            } else if (type === '7days') {
                this.filters.startDate = getLocalDateStr(-6);
                this.filters.endDate = getLocalDateStr(0);
            } else if (type === 'this_month') {
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                this.filters.startDate = `${year}-${month}-01`;
                this.filters.endDate = getLocalDateStr(0);
            } else if (type === 'all') {
                this.filters.startDate = '';
                this.filters.endDate = '';
            }
            this.applyFilter();
        },

        formatRupiah(number) {
            if (!number) return '0';
            return new Intl.NumberFormat('id-ID').format(Math.round(number));
        },

        formatDate(datetime) {
            if (!datetime) return '-';
            const d = new Date(datetime);
            if (isNaN(d.getTime())) return datetime;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + 
                   d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        },

        formatDateOnly(datetime) {
            if (!datetime) return '-';
            const d = new Date(datetime);
            if (isNaN(d.getTime())) return datetime;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatTimeOnly(datetime) {
            if (!datetime) return '-';
            const d = new Date(datetime);
            if (isNaN(d.getTime())) return datetime;
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':') + ' WIB';
        },

        async fetchShifts() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({ 
                    action: 'get_shifts', 
                    page: this.currentPage, 
                    search: this.filters.search,
                    start_date: this.filters.startDate,
                    end_date: this.filters.endDate
                });
                const response = await fetch(`logic.php?${params.toString()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.shifts = result.data || [];
                    this.currentPage = result.pagination.current_page || 1;
                    this.totalPages = result.pagination.total_pages || 1;
                    this.totalData = result.pagination.total_data || 0;
                    if (result.summary) {
                        this.summary = result.summary;
                    }
                }
            } catch (error) { 
                console.error('Fetch error:', error); 
            } finally { 
                this.isLoading = false; 
            }
        },

        applyFilter() { 
            this.currentPage = 1; 
            this.fetchShifts(); 
        },

        nextPage() { 
            if (this.currentPage < this.totalPages) { 
                this.currentPage++; 
                this.fetchShifts(); 
            } 
        },

        prevPage() { 
            if (this.currentPage > 1) { 
                this.currentPage--; 
                this.fetchShifts(); 
            } 
        },

        async openDetail(shift) {
            this.activeShift = shift;
            this.activeTransactions = [];
            this.activeSettlements = [];
            this.activePettyCash = [];
            this.showModal = true;
            this.isDetailLoading = true;

            try {
                const response = await fetch(`logic.php?action=get_detail&id=${shift.id}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.activeShift = result.shift;
                    this.activeTransactions = result.transactions || [];
                    this.activeSettlements  = result.settlements || [];
                    this.activePettyCash    = result.petty_cash || [];
                } else {
                    alert(result.message || 'Gagal memuat detail shift.');
                    this.showModal = false;
                }
            } catch (error) {
                console.error('Detail fetch error:', error);
                this.showModal = false;
            } finally {
                this.isDetailLoading = false;
            }
        },

        printShift(shiftId) {
            const w = 450, h = 680;
            const left = (window.screen.width - w) / 2;
            const top = (window.screen.height - h) / 2;
            window.open(`../print_shift.php?id=${shiftId}`, '_blank', `width=${w},height=${h},top=${top},left=${left},scrollbars=yes`);
        }
    }));
});