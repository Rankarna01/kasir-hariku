document.addEventListener('alpine:init', () => {
    Alpine.data('arusKasApp', () => ({
        isLoading: false,
        isSaving: false,
        startDate: '',
        endDate: '',
        activePreset: 'today',
        searchQuery: '',
        
        summary: {
            total_keluar: 0,
            total_today: 0,
            count_keluar: 0,
            avg_keluar: 0
        },
        
        history: [],

        showModal: false,
        form: {
            nominalDisplay: '',
            nominal: 0,
            keterangan: ''
        },

        quickNominals: [5000, 10000, 20000, 50000, 100000, 200000],
        quickTags: [
            'Beli Es Batu',
            'Plastik & Kresek',
            'Lakban & ATK Toko',
            'Gas Elpiji Toko',
            'Air Galon',
            'Kebersihan & Tissue',
            'Uang Pecahan Kecil',
            'Konsumsi Toko'
        ],

        init() {
            this.setPreset('today');
        },

        // Helper untuk format YYYY-MM-DD
        formatYMD(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        setPreset(preset) {
            this.activePreset = preset;
            const now = new Date();

            if (preset === 'today') {
                this.startDate = this.formatYMD(now);
                this.endDate = this.formatYMD(now);
            } else if (preset === 'yesterday') {
                const yest = new Date();
                yest.setDate(yest.getDate() - 1);
                this.startDate = this.formatYMD(yest);
                this.endDate = this.formatYMD(yest);
            } else if (preset === '7days') {
                const past7 = new Date();
                past7.setDate(past7.getDate() - 6);
                this.startDate = this.formatYMD(past7);
                this.endDate = this.formatYMD(now);
            } else if (preset === 'this_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                this.startDate = this.formatYMD(firstDay);
                this.endDate = this.formatYMD(now);
            }

            this.fetchData();
        },

        onCustomDateChange() {
            this.activePreset = 'custom';
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_arus_kas&start_date=${encodeURIComponent(this.startDate)}&end_date=${encodeURIComponent(this.endDate)}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    this.summary = data.summary;
                    this.history = data.data || [];
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: data.message || 'Terjadi kesalahan sistem'
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Koneksi Error',
                    text: 'Tidak dapat terhubung ke server.'
                });
            } finally {
                this.isLoading = false;
            }
        },

        get filteredHistory() {
            if (!this.searchQuery.trim()) {
                return this.history;
            }
            const query = this.searchQuery.toLowerCase().trim();
            return this.history.filter(item => {
                const ket = (item.keterangan || '').toLowerCase();
                const user = (item.user_name || '').toLowerCase();
                const shift = (item.shift_name || '').toLowerCase();
                const nominalStr = String(item.nominal || '');
                return ket.includes(query) || user.includes(query) || shift.includes(query) || nominalStr.includes(query);
            });
        },

        openModal() {
            this.form.nominal = 0;
            this.form.nominalDisplay = '';
            this.form.keterangan = '';
            this.showModal = true;
            this.$nextTick(() => {
                const input = document.getElementById('inputNominalKas');
                if (input) input.focus();
            });
        },

        closeModal() {
            this.showModal = false;
        },

        setQuickNominal(amount) {
            this.form.nominal = amount;
            this.form.nominalDisplay = this.formatRupiah(amount);
        },

        addQuickTag(tag) {
            if (!this.form.keterangan) {
                this.form.keterangan = tag;
            } else if (!this.form.keterangan.includes(tag)) {
                this.form.keterangan = `${this.form.keterangan}, ${tag}`;
            }
        },

        onNominalInput(event) {
            let val = event.target.value.replace(/[^0-9]/g, '');
            let num = parseInt(val, 10);
            if (isNaN(num) || num <= 0) {
                this.form.nominal = 0;
                this.form.nominalDisplay = '';
            } else {
                this.form.nominal = num;
                this.form.nominalDisplay = this.formatRupiah(num);
            }
        },

        async saveData() {
            if (!this.form.nominal || this.form.nominal <= 0) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Nominal Kosong',
                    text: 'Silakan isi nominal pengeluaran lebih dari Rp 0',
                    confirmButtonColor: '#e11d48'
                });
            }
            if (!this.form.keterangan || !this.form.keterangan.trim()) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Keterangan Kosong',
                    text: 'Silakan isi keperluan / rincian pengeluaran kas',
                    confirmButtonColor: '#e11d48'
                });
            }

            this.isSaving = true;
            try {
                const formData = new URLSearchParams();
                formData.append('action', 'save_arus_kas');
                formData.append('nominal', this.form.nominal);
                formData.append('keterangan', this.form.keterangan.trim());

                const response = await fetch('logic.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                
                const data = await response.json();
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dicatat!',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    this.showModal = false;
                    this.fetchData();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Gagal menyimpan pengeluaran kas',
                        confirmButtonColor: '#e11d48'
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error Sistem',
                    text: 'Terjadi kesalahan sistem saat menyimpan data.'
                });
            } finally {
                this.isSaving = false;
            }
        },

        deleteItem(item) {
            Swal.fire({
                title: 'Hapus Pengeluaran Ini?',
                html: `
                    <div class="text-sm text-slate-600 mt-2">
                        Nominal: <strong class="text-rose-600 text-base">Rp ${this.formatRupiah(item.nominal)}</strong><br>
                        Keperluan: <em>"${item.keterangan}"</em><br>
                        Waktu: ${this.formatDateTime(item.created_at).full}
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fa-solid fa-trash-can mr-1"></i> Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        formData.append('action', 'delete_arus_kas');
                        formData.append('id', item.id);

                        const response = await fetch('logic.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });
                        const res = await response.json();

                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            this.fetchData();
                        } else {
                            Swal.fire('Gagal', res.message || 'Tidak dapat menghapus data', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Gagal memproses penghapusan', 'error');
                    }
                }
            });
        },

        printVoucher(item) {
            const timeObj = this.formatDateTime(item.created_at);
            const printWindow = window.open('', '_blank', 'width=380,height=550');
            if (!printWindow) {
                alert('Pop-up terblokir oleh browser. Harap izinkan pop-up.');
                return;
            }

            const html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Bukti Pengeluaran Kas #${item.id}</title>
                    <style>
                        @page { size: 80mm auto; margin: 5mm; }
                        body {
                            font-family: 'Courier New', Courier, monospace;
                            font-size: 12px;
                            line-height: 1.35;
                            margin: 0;
                            padding: 10px;
                            color: #000;
                        }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .font-bold { font-weight: bold; }
                        .divider { border-top: 1px dashed #000; margin: 8px 0; }
                        .table-info { width: 100%; border-collapse: collapse; margin-top: 6px; }
                        .table-info td { vertical-align: top; padding: 2px 0; font-size: 11px; }
                        .nominal-box {
                            border: 2px solid #000;
                            padding: 8px;
                            margin: 10px 0;
                            text-align: center;
                            font-size: 16px;
                            font-weight: bold;
                        }
                        .signature-area {
                            margin-top: 25px;
                            display: flex;
                            justify-content: space-between;
                            font-size: 10px;
                            text-align: center;
                        }
                        .signature-box { width: 45%; }
                        .sign-line { margin-top: 40px; border-bottom: 1px solid #000; }
                    </style>
                </head>
                <body>
                    <div class="text-center font-bold" style="font-size: 15px;">LOVE CAKES & BAKERY</div>
                    <div class="text-center" style="font-size: 11px;">BUKTI PENGELUARAN KAS KECIL</div>
                    <div class="text-center" style="font-size: 10px;">(PETTY CASH VOUCHER)</div>
                    <div class="divider"></div>

                    <table class="table-info">
                        <tr>
                            <td width="35%">No. Bukti</td>
                            <td width="5%">:</td>
                            <td class="font-bold">#PC-${String(item.id).padStart(5, '0')}</td>
                        </tr>
                        <tr>
                            <td>Waktu</td>
                            <td>:</td>
                            <td>${timeObj.full}</td>
                        </tr>
                        <tr>
                            <td>Petugas / Kasir</td>
                            <td>:</td>
                            <td>${item.user_name || '-'}</td>
                        </tr>
                        <tr>
                            <td>Shift</td>
                            <td>:</td>
                            <td>${item.shift_name || '-'}</td>
                        </tr>
                    </table>

                    <div class="divider"></div>

                    <div style="font-size: 11px;">
                        <span class="font-bold">Keperluan / Keterangan:</span><br>
                        <div style="margin-top: 3px; padding-left: 2px;">${item.keterangan}</div>
                    </div>

                    <div class="nominal-box">
                        Rp ${this.formatRupiah(item.nominal)}
                    </div>

                    <div class="signature-area">
                        <div class="signature-box">
                            <div>Diserahkan / Kasir</div>
                            <div class="sign-line"></div>
                            <div style="margin-top:2px;">( ${item.user_name || 'Kasir'} )</div>
                        </div>
                        <div class="signature-box">
                            <div>Penerima / Toko</div>
                            <div class="sign-line"></div>
                            <div style="margin-top:2px;">( .................... )</div>
                        </div>
                    </div>

                    <div class="divider" style="margin-top: 25px;"></div>
                    <div class="text-center" style="font-size: 9px; font-style: italic;">Dicetak pada ${new Date().toLocaleString('id-ID')}</div>

                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 800);
                        };
                    </script>
                </body>
                </html>
            `;

            printWindow.document.open();
            printWindow.document.write(html);
            printWindow.document.close();
        },

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID').format(number || 0);
        },

        formatDateTime(dateStr) {
            if (!dateStr) return { date: '-', time: '-', full: '-' };
            const dt = new Date(dateStr.replace(/-/g, '/'));
            const d = dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const t = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
            return { date: d, time: t, full: `${d} ${t}` };
        }
    }));
});
