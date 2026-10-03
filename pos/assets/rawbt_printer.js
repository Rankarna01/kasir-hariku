// pos/assets/rawbt_printer.js
// Shared Engine Arsitektur Cetak Struk via RawBT (Android) & Fallback Browser Print

const RawBtPrinter = {
    // 1. Deteksi Sistem Operasi (Android vs Non-Android)
    isAndroid() {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        return /android/i.test(ua);
    },

    // 2. Format ESC/POS Byte Stream (58mm / 32 Karakter atau 80mm / 48 Karakter)
    buildEscPos(trx, options = {}) {
        const paperWidth = options.paperWidth || localStorage.getItem('pos_paper_width') || '58mm';
        const maxCols = paperWidth === '80mm' ? 48 : 32;
        const divider = '-'.repeat(maxCols);
        const doubleDivider = '='.repeat(maxCols);

        const ESC = '\x1B';
        const GS = '\x1D';
        const CMD_INIT = ESC + '@';
        const CMD_ALIGN_LEFT = ESC + 'a\x00';
        const CMD_ALIGN_CENTER = ESC + 'a\x01';
        const CMD_ALIGN_RIGHT = ESC + 'a\x02';
        const CMD_BOLD_ON = ESC + 'E\x01';
        const CMD_BOLD_OFF = ESC + 'E\x00';
        const CMD_DOUBLE_ON = GS + '!\x11';
        const CMD_DOUBLE_OFF = GS + '!\x00';
        const CMD_CUT = GS + 'V\x41\x00';

        const storeName = options.store_name || 'AYAM GORENG HARIKU';
        const storeAddress = options.store_address || '';
        const storePhone = options.store_phone || '';
        const footer = options.receipt_footer || 'Terima Kasih Atas Kunjungan Anda!';
        const showLogo = options.show_logo !== 0 && options.show_logo !== '0';
        const showCashier = options.show_cashier !== 0 && options.show_cashier !== '0';
        const showInvoice = options.show_invoice !== 0 && options.show_invoice !== '0';
        const showNotes = options.show_notes !== 0 && options.show_notes !== '0';
        const showTaxOngkir = options.show_tax_ongkir !== 0 && options.show_tax_ongkir !== '0';

        let text = CMD_INIT;

        // Header Toko
        text += CMD_ALIGN_CENTER;
        if (showLogo) {
            text += CMD_BOLD_ON + CMD_DOUBLE_ON + storeName + '\n' + CMD_DOUBLE_OFF + CMD_BOLD_OFF;
        } else {
            text += CMD_BOLD_ON + storeName + '\n' + CMD_BOLD_OFF;
        }

        if (storeAddress) text += storeAddress + '\n';
        if (storePhone) text += 'Telp: ' + storePhone + '\n';
        text += divider + '\n';

        // Info Transaksi
        text += CMD_ALIGN_LEFT;
        if (showInvoice && trx.invoice_no) {
            text += 'No. Trx : ' + trx.invoice_no + '\n';
        }
        text += 'Waktu   : ' + (trx.date || new Date().toLocaleString('id-ID')) + '\n';
        if (showCashier && trx.cashier_name) {
            text += 'Kasir   : ' + trx.cashier_name + '\n';
        }
        if (trx.customer_name && trx.customer_name !== 'Pelanggan Umum') {
            text += 'Pelanggan: ' + trx.customer_name + '\n';
        }

        text += divider + '\n';

        // Item List
        const items = trx.items || [];
        items.forEach(item => {
            const name = (item.name || item.product_name || 'Item').substring(0, maxCols);
            text += CMD_BOLD_ON + name + CMD_BOLD_OFF + '\n';

            const qty = parseInt(item.qty || 1, 10);
            const price = Number(item.price || 0);
            const subtotal = Number(item.subtotal || (qty * price));

            const qtyPriceStr = `${qty} x ${price.toLocaleString('id-ID')}`;
            const subtotalStr = subtotal.toLocaleString('id-ID');
            const spaces = Math.max(1, maxCols - qtyPriceStr.length - subtotalStr.length);
            text += qtyPriceStr + ' '.repeat(spaces) + subtotalStr + '\n';
        });

        text += divider + '\n';

        // Summary baris
        const addRow = (label, val, isBold = false) => {
            const spaces = Math.max(1, maxCols - label.length - val.length);
            if (isBold) text += CMD_BOLD_ON;
            text += label + ' '.repeat(spaces) + val + '\n';
            if (isBold) text += CMD_BOLD_OFF;
        };

        if (trx.subtotal) {
            addRow('Subtotal', 'Rp ' + Number(trx.subtotal).toLocaleString('id-ID'));
        }
        if (trx.discount && trx.discount > 0) {
            addRow('Diskon', '-Rp ' + Number(trx.discount).toLocaleString('id-ID'));
        }
        if (showTaxOngkir && trx.ongkir && trx.ongkir > 0) {
            addRow('Ongkir / Tambahan', 'Rp ' + Number(trx.ongkir).toLocaleString('id-ID'));
        }

        text += doubleDivider + '\n';
        addRow('TOTAL', 'Rp ' + Number(trx.total_amount || 0).toLocaleString('id-ID'), true);
        text += doubleDivider + '\n';

        if (trx.payment_method) {
            addRow('Bayar (' + String(trx.payment_method).toUpperCase() + ')', 'Rp ' + Number(trx.pay_amount || trx.total_amount || 0).toLocaleString('id-ID'));
        }
        if (trx.change_amount !== undefined) {
            addRow('Kembali', 'Rp ' + Number(trx.change_amount).toLocaleString('id-ID'));
        }

        if (showNotes && trx.notes) {
            text += divider + '\n';
            text += 'Catatan: ' + trx.notes + '\n';
        }

        // Footer
        text += divider + '\n' + CMD_ALIGN_CENTER;
        text += footer + '\n';
        const deviceName = localStorage.getItem('pos_device_name') || 'Tablet Kasir';
        text += 'Dicetak via ' + deviceName + '\n';
        text += '\n\n\n\n' + CMD_CUT;

        return text;
    },

    // 3. Base64 UTF-8 Converter
    toBase64(str) {
        return btoa(unescape(encodeURIComponent(str)));
    },

    // 4. Intent URL Standard Android Chrome dengan Fallback Play Store
    getIntentUrl(base64Data) {
        const playStoreFallback = encodeURIComponent('https://play.google.com/store/apps/details?id=ru.a402d.rawbtprinter');
        return `intent:base64,${base64Data}#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;S.browser_fallback_url=${playStoreFallback};end;`;
    },

    // Helper URL struk kasir agar tidak pernah 404 dari sub-folder manapun
    getReceiptUrl(invoice) {
        const inv = encodeURIComponent(invoice || '');
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            return BASE_URL.replace(/\/+$/, '') + `/pos/kasir/print_receipt.php?invoice=${inv}&auto_print_usb=1`;
        }
        const posIndex = window.location.pathname.indexOf('/pos/');
        if (posIndex !== -1) {
            const basePath = window.location.pathname.substring(0, posIndex);
            return window.location.origin + basePath + `/pos/kasir/print_receipt.php?invoice=${inv}&auto_print_usb=1`;
        }
        return `/sistem-kasir/kasir-hariku/pos/kasir/print_receipt.php?invoice=${inv}&auto_print_usb=1`;
    },

    // 5. DISPATCHER UTAMA CETAK (Langsung kirim ke RawBT tanpa popup window baru)
    print(trx, options = {}) {
        // Simpan struk terakhir untuk opsi cetak ulang
        localStorage.setItem('pos_last_receipt', JSON.stringify(trx));

        const mode = localStorage.getItem('pos_print_mode') || 'rawbt';

        // Hanya jika kasir memilih mode 'browser' secara manual, buka window print browser
        if (mode === 'browser') {
            return this.printViaBrowser(trx, options, false);
        }

        // DEFAULT JALUR RAWBT: Langsung kirim perintah cetak tanpa buka tab/window baru
        return this.printViaRawBT(trx, options);
    },

    // 6. JALUR UTAMA RAWBT (Kirim ke Aplikasi RawBT di Background)
    printViaRawBT(trx, options = {}) {
        const deviceName = localStorage.getItem('pos_device_name') || 'Tablet Kasir';
        const rawText = this.buildEscPos(trx, options);
        const base64 = this.toBase64(rawText);

        // Catat log awal ke server
        this.logPrint(trx.sale_id || null, trx.invoice_no, deviceName, 'rawbt', 'success', 'Perintah cetak dikirim ke RawBT');

        // Kirim perintah cetak via URI scheme / Intent (TIDAK membuka window/tab baru)
        if (this.isAndroid()) {
            let hasPageBlurred = false;
            const blurHandler = () => { hasPageBlurred = true; };
            const visibilityHandler = () => { if (document.hidden) hasPageBlurred = true; };

            window.addEventListener('blur', blurHandler, { once: true });
            document.addEventListener('visibilitychange', visibilityHandler, { once: true });

            const intentUrl = this.getIntentUrl(base64);
            window.location.href = intentUrl;

            // Timer Heuristik hanya di Android (2.5 detik)
            setTimeout(() => {
                window.removeEventListener('blur', blurHandler);
                document.removeEventListener('visibilitychange', visibilityHandler);

                if (!hasPageBlurred && !document.hidden) {
                    this.handleRawBtNotResponding(trx, options);
                }
            }, 2500);
        } else {
            // Pada non-Android (misal PC/Mac testing), panggil URI rawbt: standard tanpa buka tab baru
            window.location.href = `rawbt:base64,${base64}`;
        }

        // Tampilkan Toast Notifikasi Pasca-Cetak di pojok bawah
        this.showPostPrintToast(trx, options);
        return true;
    },

    // 7. PENANGANAN RAWBT TIDAK MERESPONS / BELUM TERPASANG
    handleRawBtNotResponding(trx, options = {}) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            icon: 'warning',
            title: 'RawBT Belum Terpasang / Tidak Merespons',
            html: `
                <div class="text-left text-xs space-y-2.5 p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 leading-relaxed">
                    <p><b>Peringatan:</b> Aplikasi RawBT Print Service tampaknya belum terpasang atau belum dibuka di perangkat Android ini.</p>
                    <p>Pilih tindakan di bawah agar kasir tetap bisa mencetak struk transaksi:</p>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="fa-solid fa-print mr-1"></i> Cetak via Browser (Darurat)',
            denyButtonText: '<i class="fa-brands fa-google-play mr-1"></i> Buka Play Store',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#FF3870',
            denyButtonColor: '#0284c7'
        }).then((result) => {
            if (result.isConfirmed) {
                // Fallback instan ke Browser Print
                this.printViaBrowser(trx, options);
            } else if (result.isDenied) {
                // Buka Google Play Store
                window.open('https://play.google.com/store/apps/details?id=ru.a402d.rawbtprinter', '_blank');
            }
        });
    },

    // 8. LAPIS 1: TOAST SETELAH CETAK ("Struk Tidak Keluar?" & "Cetak Ulang")
    showPostPrintToast(trx, options = {}) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'info',
            title: 'Struk Dikirim ke Printer!',
            html: `
                <div class="text-[11px] text-slate-500 mb-2">No. ${trx.invoice_no || 'Struk'}</div>
                <div class="flex gap-2">
                    <button id="swal-rawbt-reprint" class="px-2.5 py-1 bg-[#FF3870] hover:bg-rose-600 text-white rounded-lg text-[11px] font-bold shadow-xs">Cetak Ulang</button>
                    <button id="swal-rawbt-stuck" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-[11px] font-bold">Struk tidak keluar?</button>
                </div>
            `,
            showConfirmButton: false,
            timer: 12000,
            timerProgressBar: true,
            didOpen: (toast) => {
                const btnReprint = toast.querySelector('#swal-rawbt-reprint');
                const btnStuck = toast.querySelector('#swal-rawbt-stuck');

                if (btnReprint) {
                    btnReprint.addEventListener('click', () => {
                        Swal.close();
                        this.print(trx, options);
                    });
                }
                if (btnStuck) {
                    btnStuck.addEventListener('click', () => {
                        Swal.close();
                        this.handleStrukTidakKeluar(trx, options);
                    });
                }
            }
        });
    },

    // 9. LAPIS 1 (LANJUTAN): PENANGANAN KASUS "STRUK TIDAK KELUAR"
    handleStrukTidakKeluar(trx, options = {}) {
        const deviceName = localStorage.getItem('pos_device_name') || 'Tablet Kasir';
        
        // 1. Catat log kegagalan ke server
        this.logPrint(trx.sale_id || null, trx.invoice_no, deviceName, 'rawbt', 'reported_stuck', 'Kasir melaporkan struk tidak keluar pada printer.');

        // 2. Naikkan counter gagal beruntun (Lapis 3)
        let fails = parseInt(localStorage.getItem('pos_consecutive_print_fails') || '0', 10) + 1;
        localStorage.setItem('pos_consecutive_print_fails', fails.toString());

        // 3. Tampilkan Checklist Troubleshooting
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Checklist Printer Kasir',
                html: `
                    <div class="text-left text-xs space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200">
                        <div class="font-bold text-slate-800 mb-1">Periksa 4 hal berikut:</div>
                        <p class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[10px]">1</span> Daya printer thermal menyala (LED indikator hidup).</p>
                        <p class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[10px]">2</span> Kertas thermal tidak habis dan posisi tidak terbalik.</p>
                        <p class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[10px]">3</span> Bluetooth tablet terhubung ke printer (iWare).</p>
                        <p class="flex items-center gap-2"><span class="w-4 h-4 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[10px]">4</span> Buka aplikasi RawBT di tablet, pastikan status "Ready" (hijau).</p>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fa-solid fa-rotate-right mr-1"></i> Coba Cetak Ulang RawBT',
                denyButtonText: '<i class="fa-solid fa-print mr-1"></i> Cetak via Browser (Darurat)',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#FF3870',
                denyButtonColor: '#0284c7'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.printViaRawBT(trx, options);
                } else if (result.isDenied) {
                    this.printViaBrowser(trx, options);
                }
            });
        }
    },

    // 10. JALUR CADANGAN: BROWSER PRINT (Window.print / Popup)
    printViaBrowser(trx, options = {}, isAutoNonAndroid = false) {
        const deviceName = localStorage.getItem('pos_device_name') || 'PC Kasir';
        const invoice = trx.invoice_no || 'TRX-POS';

        this.logPrint(trx.sale_id || null, invoice, deviceName, 'browser', 'success', isAutoNonAndroid ? 'Cetak otomatis Browser Print (Non-Android)' : 'Cetak via Browser Print');

        const url = this.getReceiptUrl(invoice);

        const win = window.open(url, '_blank', 'width=420,height=650');
        if (!win) {
            // Popup blocker aktif -> Fallback ke tab langsung atau alert
            window.location.href = url;
        }

        if (typeof Swal !== 'undefined' && isAutoNonAndroid) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: 'Mencetak via Browser Print',
                text: 'Perangkat terdeteksi non-Android (PC/Laptop).',
                timer: 3500,
                showConfirmButton: false
            });
        }

        return true;
    },

    // 11. LAPIS 2: TES CETAK HARIAN SAAT KASIR PERTAMA KALI DIBUKA
    async checkAndPromptDailyTest() {
        const todayStr = new Date().toISOString().split('T')[0];
        const lastTestedDate = localStorage.getItem('pos_daily_test_date');

        if (lastTestedDate === todayStr) {
            return; // Sudah dites hari ini
        }

        if (typeof Swal === 'undefined') return;

        const result = await Swal.fire({
            title: 'Tes Cetak Printer Hari Ini',
            text: 'Kasir baru dibuka hari ini. Apakah Anda ingin melakukan tes cetak struk untuk memastikan printer thermal & RawBT siap transaksi?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-receipt mr-1"></i> Ya, Tes Cetak',
            cancelButtonText: 'Lewati / Nanti',
            confirmButtonColor: '#FF3870'
        });

        localStorage.setItem('pos_daily_test_date', todayStr);

        if (result.isConfirmed) {
            const sampleTrx = {
                invoice_no: 'TEST-HARIAN-' + Math.floor(100 + Math.random() * 900),
                date: new Date().toLocaleString('id-ID'),
                cashier_name: 'Tes Harian',
                customer_name: 'Tes Sistem Cetak',
                subtotal: 10000,
                discount: 0,
                ongkir: 0,
                total_amount: 10000,
                pay_amount: 10000,
                change_amount: 0,
                payment_method: 'CASH',
                items: [
                    { name: 'Tes Cetak Struk Harian POS', qty: 1, price: 10000, subtotal: 10000 }
                ]
            };

            this.print(sampleTrx);

            // Konfirmasi hasil cetak fisik
            setTimeout(async () => {
                const conf = await Swal.fire({
                    title: 'Apakah Struk Keluar?',
                    text: 'Periksa printer thermal Anda. Apakah kertas struk berhasil tercetak dengan baik?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Berhasil!',
                    cancelButtonText: 'Tidak Keluar',
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#EF4444'
                });

                if (conf.isConfirmed) {
                    localStorage.setItem('pos_last_test_time', Date.now().toString());
                    localStorage.setItem('pos_last_test_status', 'success');
                    localStorage.setItem('pos_consecutive_print_fails', '0');
                    Swal.fire({ icon: 'success', title: 'Printer Siap!', text: 'Status printer berhasil diverifikasi untuk transaksi hari ini.', timer: 2000, showConfirmButton: false });
                } else {
                    localStorage.setItem('pos_last_test_time', Date.now().toString());
                    localStorage.setItem('pos_last_test_status', 'failed');
                    this.handleStrukTidakKeluar(sampleTrx);
                }
            }, 3000);
        }
    },

    // 12. LAPIS 3: CEK KEGAGALAN BERUNTUN DARI PRINT LOG
    checkConsecutiveFailures() {
        const fails = parseInt(localStorage.getItem('pos_consecutive_print_fails') || '0', 10);
        return fails;
    },

    resetConsecutiveFailures() {
        localStorage.setItem('pos_consecutive_print_fails', '0');
    },

    // 13. PENCATATAN LOG KE SERVER (Endpoint logic.php)
    async logPrint(saleId, invoiceNo, deviceName, mode, status, note = '') {
        try {
            const fd = new FormData();
            if (saleId) fd.append('sale_id', saleId);
            fd.append('invoice_no', invoiceNo || 'TRX-POS');
            fd.append('device_name', deviceName || 'Tablet Kasir');
            fd.append('mode', mode || 'rawbt');
            fd.append('status', status || 'success');
            fd.append('note', note || '');

            let endpoint = '../pengaturan/printer/logic.php?action=log_print';
            if (typeof BASE_URL !== 'undefined') {
                endpoint = BASE_URL + 'pos/pengaturan/printer/logic.php?action=log_print';
            } else if (window.location.pathname.includes('/pengaturan/printer/')) {
                endpoint = 'logic.php?action=log_print';
            }

            await fetch(endpoint, { method: 'POST', body: fd });
        } catch(e) {
            // Offline / silent fail
        }
    }
};

window.RawBtPrinter = RawBtPrinter;
