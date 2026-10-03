// pos/assets/rawbt_printer.js
// Shared Engine untuk Cetak Struk via RawBT (Android Driver Service) & Browser Print

const RawBtPrinter = {
    // 1. Format ESC/POS
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
        const showLogo = options.show_logo !== 0;
        const showCashier = options.show_cashier !== 0;
        const showInvoice = options.show_invoice !== 0;
        const showNotes = options.show_notes !== 0;
        const showTaxOngkir = options.show_tax_ongkir !== 0;

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

            const qty = item.qty || 1;
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
            addRow('Bayar (' + trx.payment_method.toUpperCase() + ')', 'Rp ' + Number(trx.pay_amount || trx.total_amount || 0).toLocaleString('id-ID'));
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
        text += '\n\n\n\n' + CMD_CUT;

        return text;
    },

    // 2. Base64 Converter
    toBase64(str) {
        return btoa(unescape(encodeURIComponent(str)));
    },

    // 3. Dispatch Cetak ke RawBT
    print(trx, options = {}) {
        const deviceName = localStorage.getItem('pos_device_name') || 'Tablet Kasir';
        const mode = localStorage.getItem('pos_print_mode') || 'rawbt';
        const rawText = this.buildEscPos(trx, options);
        const base64 = this.toBase64(rawText);

        // Catat log
        this.logPrint(trx.sale_id || null, trx.invoice_no, deviceName, mode, 'success', 'Cetak via RawBT');

        // Buka RawBT Intent
        window.location.href = `rawbt:base64,${base64}`;
    },

    // 4. Log ke Server
    async logPrint(saleId, invoiceNo, deviceName, mode, status, note) {
        try {
            const fd = new FormData();
            if (saleId) fd.append('sale_id', saleId);
            fd.append('invoice_no', invoiceNo || 'TRX-POS');
            fd.append('device_name', deviceName);
            fd.append('mode', mode);
            fd.append('status', status);
            fd.append('note', note);

            // Path relatif menuju logic.php pengaturan printer
            await fetch('../pengaturan/printer/logic.php?action=log_print', { method: 'POST', body: fd });
        } catch(e) {}
    }
};

window.RawBtPrinter = RawBtPrinter;
