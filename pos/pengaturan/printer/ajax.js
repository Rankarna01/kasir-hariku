// pos/pengaturan/printer/ajax.js
// Sistem Integrasi Web POS & RawBT Driver Service Android

document.addEventListener('alpine:init', () => {
    Alpine.data('printerApp', () => ({
        // 1. STATE PERANGKAT (Lokal di localStorage)
        deviceName: 'Tablet Kasir 1',
        printMode: 'rawbt', // 'rawbt' | 'browser' | 'manual'
        paperWidth: '58mm', // '58mm' (32 char) | '80mm' (48 char)
        autoPrintOnPay: true,
        lastTestTime: null,
        lastTestStatus: '',
        lastReceiptInvoice: '',

        // 2. STATE PENGATURAN STRUK (Database)
        storeSettings: {
            store_name: 'AYAM GORENG HARIKU',
            store_address: '',
            store_phone: '',
            receipt_footer: 'Terima Kasih Atas Kunjungan Anda!',
            show_logo: 1,
            show_cashier: 1,
            show_invoice: 1,
            show_notes: 1,
            show_tax_ongkir: 1
        },

        // 3. STATE LOG CETAK (Halaman Admin)
        printLogs: [],
        logStats: {
            total_print: 0,
            total_success: 0,
            total_stuck: 0,
            total_failed: 0
        },

        // 4. UI STATE
        isLoading: false,
        isTesting: false,
        showRawbtModal: false,
        showTroubleshootModal: false,
        activeTab: 'printer', // 'printer' | 'receipt' | 'logs'
        isAndroidDevice: false,

        async init() {
            this.detectEnvironment();
            this.loadLocalDeviceSettings();
            await this.loadStoreSettings();
            await this.loadPrintLogs();
        },

        // Deteksi apakah perangkat menggunakan Android
        detectEnvironment() {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            this.isAndroidDevice = /android/i.test(ua);
        },

        // 1. LOAD & SAVE PENGATURAN PERANGKAT (localStorage)
        loadLocalDeviceSettings() {
            this.deviceName = localStorage.getItem('pos_device_name') || (this.isAndroidDevice ? 'Tablet Android Kasir' : 'PC/Tablet Kasir');
            this.printMode = localStorage.getItem('pos_print_mode') || (this.isAndroidDevice ? 'rawbt' : 'browser');
            this.paperWidth = localStorage.getItem('pos_paper_width') || '58mm';
            this.autoPrintOnPay = localStorage.getItem('pos_auto_print_on_pay') !== '0';
            
            const savedTest = localStorage.getItem('pos_last_test_time');
            this.lastTestTime = savedTest ? parseInt(savedTest) : null;
            this.lastTestStatus = localStorage.getItem('pos_last_test_status') || '';
            
            const lastTrx = localStorage.getItem('pos_last_receipt');
            if (lastTrx) {
                try {
                    const parsed = JSON.parse(lastTrx);
                    this.lastReceiptInvoice = parsed.invoice_no || '';
                } catch(e) {}
            }
        },

        saveDeviceSettings() {
            localStorage.setItem('pos_device_name', this.deviceName.trim());
            localStorage.setItem('pos_print_mode', this.printMode);
            localStorage.setItem('pos_paper_width', this.paperWidth);
            localStorage.setItem('pos_auto_print_on_pay', this.autoPrintOnPay ? '1' : '0');

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Pengaturan Perangkat Disimpan!',
                showConfirmButton: false,
                timer: 1800
            });
        },

        // 2. LOAD & SAVE PENGATURAN STRUK (Database)
        async loadStoreSettings() {
            try {
                const res = await fetch('logic.php?action=get_settings');
                const result = await res.json();
                if (result.status === 'success' && result.data) {
                    this.storeSettings = {
                        store_name: result.data.store_name || 'AYAM GORENG HARIKU',
                        store_address: result.data.store_address || '',
                        store_phone: result.data.store_phone || '',
                        receipt_footer: result.data.receipt_footer || 'Terima Kasih Atas Kunjungan Anda!',
                        show_logo: parseInt(result.data.show_logo ?? 1),
                        show_cashier: parseInt(result.data.show_cashier ?? 1),
                        show_invoice: parseInt(result.data.show_invoice ?? 1),
                        show_notes: parseInt(result.data.show_notes ?? 1),
                        show_tax_ongkir: parseInt(result.data.show_tax_ongkir ?? 1)
                    };
                }
            } catch (e) {
                console.error("Gagal load store settings:", e);
            }
        },

        async saveReceiptSettings() {
            this.isLoading = true;
            try {
                const formData = new FormData();
                formData.append('store_name', this.storeSettings.store_name);
                formData.append('store_address', this.storeSettings.store_address);
                formData.append('store_phone', this.storeSettings.store_phone);
                formData.append('receipt_footer', this.storeSettings.receipt_footer);
                formData.append('show_logo', this.storeSettings.show_logo);
                formData.append('show_cashier', this.storeSettings.show_cashier);
                formData.append('show_invoice', this.storeSettings.show_invoice);
                formData.append('show_notes', this.storeSettings.show_notes);
                formData.append('show_tax_ongkir', this.storeSettings.show_tax_ongkir);

                const res = await fetch('logic.php?action=save_receipt_settings', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Format struk toko berhasil diperbarui ke database.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menyimpan pengaturan struk.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        // 3. LOAD PRINT LOGS (Database)
        async loadPrintLogs() {
            try {
                const res = await fetch('logic.php?action=get_print_logs');
                const result = await res.json();
                if (result.status === 'success' && result.data) {
                    this.printLogs = result.data.logs || [];
                    this.logStats = result.data.stats || this.logStats;
                }
            } catch (e) {
                console.error("Gagal load print logs:", e);
            }
        },

        // Catat Log ke Database
        async logPrintToServer(saleId, invoiceNo, status, note = '') {
            try {
                const fd = new FormData();
                if (saleId) fd.append('sale_id', saleId);
                fd.append('invoice_no', invoiceNo);
                fd.append('device_name', this.deviceName);
                fd.append('mode', this.printMode);
                fd.append('status', status);
                fd.append('note', note);

                await fetch('logic.php?action=log_print', { method: 'POST', body: fd });
                this.loadPrintLogs();
            } catch(e) {}
        },

        // 4. FORMAT TANGGAL & STATUS TES TERAKHIR
        get formattedLastTest() {
            if (!this.lastTestTime) return 'Belum pernah dilakukan tes cetak';
            const diffSec = Math.floor((Date.now() - this.lastTestTime) / 1000);
            
            let timeStr = '';
            if (diffSec < 60) timeStr = 'Baru saja';
            else if (diffSec < 3600) timeStr = `${Math.floor(diffSec / 60)} menit yang lalu`;
            else if (diffSec < 86400) timeStr = `${Math.floor(diffSec / 3600)} jam yang lalu`;
            else timeStr = `${Math.floor(diffSec / 86400)} hari yang lalu`;

            if (this.lastTestStatus === 'success') {
                return `Tes terakhir berhasil (${timeStr})`;
            } else {
                return `Tes terakhir gagal (${timeStr})`;
            }
        },

        get isTestOldOrMissing() {
            if (!this.lastTestTime) return 'missing';
            const diffDays = (Date.now() - this.lastTestTime) / (1000 * 3600 * 24);
            if (diffDays >= 7) return 'old';
            return 'ok';
        },

        // 5. HELPER FORMAT ESC/POS BYTE (32 Karakter untuk 58mm / 48 Karakter untuk 80mm)
        buildReceiptBytes(trx, paperWidth = '58mm') {
            const maxCols = paperWidth === '80mm' ? 48 : 32;
            const divider = '-'.repeat(maxCols);
            const doubleDivider = '='.repeat(maxCols);

            // Perintah ESC/POS Standar
            const ESC = '\x1B';
            const GS = '\x1D';
            const CMD_INIT = ESC + '@';
            const CMD_ALIGN_LEFT = ESC + 'a\x00';
            const CMD_ALIGN_CENTER = ESC + 'a\x01';
            const CMD_ALIGN_RIGHT = ESC + 'a\x02';
            const CMD_BOLD_ON = ESC + 'E\x01';
            const CMD_BOLD_OFF = ESC + 'E\x00';
            const CMD_DOUBLE_ON = GS + '!\x11'; // Double width & height
            const CMD_DOUBLE_OFF = GS + '!\x00'; // Normal font
            const CMD_CUT = GS + 'V\x41\x00'; // Cut paper

            let text = CMD_INIT;

            // Header Toko
            text += CMD_ALIGN_CENTER;
            if (this.storeSettings.show_logo) {
                text += CMD_BOLD_ON + CMD_DOUBLE_ON + (this.storeSettings.store_name || 'AYAM GORENG HARIKU') + '\n' + CMD_DOUBLE_OFF + CMD_BOLD_OFF;
            } else {
                text += CMD_BOLD_ON + (this.storeSettings.store_name || 'AYAM GORENG HARIKU') + '\n' + CMD_BOLD_OFF;
            }

            if (this.storeSettings.store_address) {
                text += this.storeSettings.store_address + '\n';
            }
            if (this.storeSettings.store_phone) {
                text += 'Telp: ' + this.storeSettings.store_phone + '\n';
            }

            text += divider + '\n';

            // Info Transaksi
            text += CMD_ALIGN_LEFT;
            if (this.storeSettings.show_invoice) {
                text += 'No. Trx : ' + (trx.invoice_no || 'TRX-SAMPLE') + '\n';
            }
            text += 'Tanggal : ' + (trx.date || new Date().toLocaleString('id-ID')) + '\n';
            if (this.storeSettings.show_cashier && trx.cashier_name) {
                text += 'Kasir   : ' + trx.cashier_name + '\n';
            }
            if (trx.customer_name && trx.customer_name !== 'Pelanggan Umum') {
                text += 'Pelanggan: ' + trx.customer_name + '\n';
            }

            text += divider + '\n';

            // Items
            const items = trx.items || [];
            items.forEach(item => {
                const name = (item.name || item.product_name || 'Item').substring(0, maxCols);
                text += CMD_BOLD_ON + name + CMD_BOLD_OFF + '\n';

                const qtyPrice = `${item.qty || 1} x ${this.formatNumber(item.price || 0)}`;
                const subtotal = this.formatNumber(item.subtotal || ((item.qty || 1) * (item.price || 0)));
                const spaces = Math.max(1, maxCols - qtyPrice.length - subtotal.length);
                text += qtyPrice + ' '.repeat(spaces) + subtotal + '\n';
            });

            text += divider + '\n';

            // Summary (Subtotal, Diskon, Ongkir/Pajak, Total)
            const addSummaryLine = (label, val, isBold = false) => {
                const spaces = Math.max(1, maxCols - label.length - val.length);
                if (isBold) text += CMD_BOLD_ON;
                text += label + ' '.repeat(spaces) + val + '\n';
                if (isBold) text += CMD_BOLD_OFF;
            };

            if (trx.subtotal) {
                addSummaryLine('Subtotal', 'Rp ' + this.formatNumber(trx.subtotal));
            }
            if (trx.discount && trx.discount > 0) {
                addSummaryLine('Diskon', '-Rp ' + this.formatNumber(trx.discount));
            }
            if (this.storeSettings.show_tax_ongkir && trx.ongkir && trx.ongkir > 0) {
                addSummaryLine('Ongkir / Tambahan', 'Rp ' + this.formatNumber(trx.ongkir));
            }

            text += doubleDivider + '\n';
            addSummaryLine('TOTAL', 'Rp ' + this.formatNumber(trx.total_amount || 0), true);
            text += doubleDivider + '\n';

            if (trx.payment_method) {
                addSummaryLine('Bayar (' + trx.payment_method.toUpperCase() + ')', 'Rp ' + this.formatNumber(trx.pay_amount || trx.total_amount || 0));
            }
            if (trx.change_amount !== undefined) {
                addSummaryLine('Kembali', 'Rp ' + this.formatNumber(trx.change_amount));
            }

            if (this.storeSettings.show_notes && trx.notes) {
                text += divider + '\n';
                text += 'Catatan: ' + trx.notes + '\n';
            }

            // Footer
            text += divider + '\n' + CMD_ALIGN_CENTER;
            text += (this.storeSettings.receipt_footer || 'Terima Kasih Atas Kunjungan Anda!') + '\n';
            text += 'Dicetak via ' + this.deviceName + '\n';
            text += '\n\n\n\n' + CMD_CUT;

            return text;
        },

        // Konversi String ke Base64 UTF-8 Aman
        toBase64(string) {
            return btoa(unescape(encodeURIComponent(string)));
        },

        // 6. FUNGSI UTAMA CETAK (PRINT DISPATCHER)
        printReceiptData(trx) {
            // Simpan sebagai struk terakhir
            localStorage.setItem('pos_last_receipt', JSON.stringify(trx));
            this.lastReceiptInvoice = trx.invoice_no || '';

            if (this.printMode === 'rawbt') {
                return this.sendToRawBT(trx);
            } else if (this.printMode === 'browser') {
                return this.fallbackBrowserPrint(trx);
            } else {
                // Manual Mode: Buka Dialog Struk
                this.fallbackBrowserPrint(trx);
            }
        },

        // Kirim ESC/POS ke RawBT Android Driver
        sendToRawBT(trx) {
            const rawText = this.buildReceiptBytes(trx, this.paperWidth);
            const base64Data = this.toBase64(rawText);
            const rawbtUrl = `rawbt:base64,${base64Data}`;

            // Catat log sukses terkirim ke driver
            this.logPrintToServer(trx.sale_id || null, trx.invoice_no || 'TRX-SAMPLE', 'success', `Cetak via RawBT (${this.paperWidth})`);

            // Panggil RawBT Intent URL
            window.location.href = rawbtUrl;

            // Tampilkan Toast Konfirmasi 10 Detik
            this.showPrintToast(trx);
            return true;
        },

        // Fallback Browser Print Dialog
        fallbackBrowserPrint(trx) {
            const invoice = trx.invoice_no;
            this.logPrintToServer(trx.sale_id || null, invoice, 'success', 'Cetak via Browser Print');
            window.open(`../../kasir/print_receipt.php?invoice=${invoice}`, '_blank', 'width=450,height=650');
            this.showPrintToast(trx);
            return true;
        },

        // 7. TOAST NOTIFIKASI DENGAN TOMBOL "CETAK ULANG" & "STRUK TIDAK KELUAR?"
        showPrintToast(trx) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: 'Data Struk Dikirim ke Printer!',
                html: `
                    <div class="text-xs text-slate-500 mt-1 mb-2">No. ${trx.invoice_no || 'Struk'}</div>
                    <div class="flex gap-2">
                        <button id="swal-btn-reprint" class="px-2.5 py-1 bg-[#FF3870] text-white rounded text-[11px] font-bold">Cetak Ulang</button>
                        <button id="swal-btn-stuck" class="px-2.5 py-1 bg-slate-200 text-slate-700 rounded text-[11px] font-bold">Struk tidak keluar?</button>
                    </div>
                `,
                showConfirmButton: false,
                timer: 10000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    const btnReprint = toast.querySelector('#swal-btn-reprint');
                    const btnStuck = toast.querySelector('#swal-btn-stuck');

                    if (btnReprint) {
                        btnReprint.addEventListener('click', () => {
                            Swal.close();
                            this.printReceiptData(trx);
                        });
                    }
                    if (btnStuck) {
                        btnStuck.addEventListener('click', () => {
                            Swal.close();
                            this.openTroubleshootModal(trx.invoice_no);
                        });
                    }
                }
            });
        },

        // 8. TEST CETAK
        async runTestPrint() {
            this.isTesting = true;

            const sampleTrx = {
                invoice_no: 'TEST-' + Math.floor(1000 + Math.random() * 9000),
                date: new Date().toLocaleString('id-ID'),
                cashier_name: 'Staf Kasir',
                customer_name: 'Pelanggan Contoh',
                subtotal: 35000,
                discount: 5000,
                ongkir: 0,
                total_amount: 30000,
                pay_amount: 50000,
                change_amount: 20000,
                payment_method: 'CASH',
                items: [
                    { name: 'Ayam Goreng Paha Crispy', qty: 1, price: 20000, subtotal: 20000 },
                    { name: 'Nasi Putih Hariku', qty: 1, price: 7000, subtotal: 7000 },
                    { name: 'Es Teh Manis Jumbo', qty: 1, price: 8000, subtotal: 8000 }
                ]
            };

            try {
                this.printReceiptData(sampleTrx);
                this.lastTestTime = Date.now();
                this.lastTestStatus = 'success';
                localStorage.setItem('pos_last_test_time', this.lastTestTime.toString());
                localStorage.setItem('pos_last_test_status', 'success');
            } catch(e) {
                console.error("Test print error:", e);
                this.lastTestStatus = 'failed';
                localStorage.setItem('pos_last_test_status', 'failed');
            } finally {
                this.isTesting = false;
            }
        },

        // 9. CETAK ULANG STRUK TERAKHIR
        reprintLast() {
            const lastStr = localStorage.getItem('pos_last_receipt');
            if (!lastStr) {
                Swal.fire('Belum Ada Struk', 'Belum ada transaksi terakhir yang tercatat di perangkat ini.', 'info');
                return;
            }
            try {
                const trx = JSON.parse(lastStr);
                this.printReceiptData(trx);
            } catch(e) {
                Swal.fire('Error', 'Data struk terakhir tidak valid.', 'error');
            }
        },

        // 10. MODAL TROUBLESHOOT "STRUK TIDAK KELUAR?"
        openTroubleshootModal(invoiceNo = '') {
            this.showTroubleshootModal = true;
            this.lastReceiptInvoice = invoiceNo || this.lastReceiptInvoice;
        },

        reportPrinterStuck() {
            const invoice = this.lastReceiptInvoice || 'UNKNOWN';
            this.logPrintToServer(null, invoice, 'reported_stuck', 'Kasir melaporkan struk tidak keluar pada printer.');
            this.showTroubleshootModal = false;
            Swal.fire({
                icon: 'warning',
                title: 'Laporan Dicatat!',
                text: 'Kendala struk tidak keluar telah dicatat di Log Cetak Admin untuk ditindaklanjuti.',
                confirmButtonColor: '#FF3870'
            });
        },

        // Helper Format Ribuan
        formatNumber(num) {
            return Number(num || 0).toLocaleString('id-ID');
        }
    }));
});