<?php
// NYALAKAN X-RAY ERROR
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';

$invoice = $_GET['invoice'] ?? '';

if (empty($invoice)) {
    die("<h3 style='font-family:sans-serif; text-align:center; color:#ef4444;'>Nomor Invoice tidak ditemukan.</h3>");
}

try {
    // 1. Tarik Data Master Transaksi
    $stmtHead = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone FROM sales_pos s LEFT JOIN customers_pos c ON s.customer_id = c.id WHERE s.invoice_no = ?");
    $stmtHead->execute([$invoice]);
    $sale = $stmtHead->fetch(PDO::FETCH_ASSOC);

    if (!$sale) die("<h3 style='font-family:sans-serif; text-align:center;'>Transaksi tidak valid.</h3>");

    // 2. Tarik Data Detail Item
    $stmtDetail = $pdo->prepare("SELECT sd.*, COALESCE(p.name, sd.custom_name, 'Produk') as product_name FROM sale_details_pos sd LEFT JOIN products p ON sd.product_id = p.id WHERE sd.sale_id = ?");
    $stmtDetail->execute([$sale['id']]);
    $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

    // 3. Tarik Pengaturan Toko
    $toko = false;
    try {
        $stmt_toko = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
        $toko = $stmt_toko->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { }

    if (!$toko) {
        $toko = ['store_name' => 'AYAM GORENG HARIKU', 'store_address' => 'Alamat belum diatur', 'store_phone' => '-', 'receipt_footer' => 'Terima Kasih Atas Kunjungan Anda!'];
    }

    $calculated_ongkir = $sale['total_amount'] - ($sale['subtotal'] - $sale['discount_voucher'] - $sale['discount_points'] - $sale['discount_manual'] - ($sale['discount_auto'] ?? 0));
    
    $is_po = !empty($sale['is_po']) ? true : false;
    $channel = !empty($sale['channel']) ? $sale['channel'] : 'Toko';
    $pickup_date = !empty($sale['pickup_date']) ? date('d/m/Y', strtotime($sale['pickup_date'])) : '-';
    $pickup_time = !empty($sale['pickup_time']) ? date('H:i', strtotime($sale['pickup_time'])) : '-';

} catch (Exception $e) {
    die("<h3>⚠️ SYSTEM ERROR</h3><p>" . $e->getMessage() . "</p>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?= htmlspecialchars($invoice) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { 
            margin: 0; 
            size: 58mm auto; 
        }
        * { box-sizing: border-box; }
        body { 
            font-family: 'Courier New', Courier, monospace, 'Arial', sans-serif; 
            margin: 0; 
            padding: 16px 10px; 
            background: #f1f5f9; 
            color: #1e293b;
            font-size: 11px; 
            line-height: 1.35; 
            font-weight: 600;
        }

        /* Container Struk Card untuk Tampilan Layar */
        .receipt-card {
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 5mm 4mm;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            color: #000000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 900; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .divider-double { border-top: 2px dashed #000; margin: 7px 0; }
        
        .store-name { font-size: 13px; font-weight: 900; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .store-info { font-size: 9.5px; line-height: 1.25; margin-bottom: 1px; }
        
        .info-table, .item-table, .summary-table { width: 100%; font-size: 10px; border-collapse: collapse; }
        .info-table td, .summary-table td { padding: 1.5px 0; vertical-align: top; }
        .item-name { font-weight: 800; padding-bottom: 1px; font-size: 10.5px; }
        .item-row td { padding-bottom: 4px; vertical-align: top; }
        
        .barcode-container { margin-top: 8px; text-align: center; }
        .barcode-container svg { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        
        /* TAMPILAN CETAK ASLI THERMAL PAPER */
        @media print { 
            body { 
                background: #ffffff !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                width: 58mm !important;
                max-width: 58mm !important;
            } 
            .receipt-card {
                border-radius: 0 !important;
                box-shadow: none !important;
                border: none !important;
                padding: 2mm 3mm !important;
                width: 58mm !important;
                max-width: 58mm !important;
                margin: 0 !important;
            }
            .no-print { display: none !important; } 
        }

        /* Tombol & Aksi di Layar */
        .action-container {
            width: 58mm;
            max-width: 58mm;
            margin: 12px auto 20px auto;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .btn { 
            padding: 10px 12px; 
            cursor: pointer; 
            border-radius: 10px; 
            font-weight: 800; 
            width: 100%; 
            margin-bottom: 7px; 
            border: none; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 7px; 
            box-sizing: border-box; 
            font-size: 11px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .btn:hover { opacity: 0.94; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        
        .btn-print { background: #2563eb; color: #ffffff; font-size: 12px; padding: 11px 14px; box-shadow: 0 4px 10px rgba(37,99,235,0.25); }
        .btn-bt { background: #6366f1; color: #ffffff; box-shadow: 0 3px 8px rgba(99,102,241,0.2); }
        .btn-usb { background: #059669; color: #ffffff; }
        .btn-serial { background: #0d9488; color: #ffffff; }
        .btn-connect { background: #7c3aed; color: #ffffff; font-weight: 900; box-shadow: 0 4px 12px rgba(124,58,237,0.3); }
        .btn-close { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; margin-top: 8px; }
        
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 800;
        }
        .badge-connected { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-disconnected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        
        .alert-connect {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            color: #92400e;
            font-size: 10px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    
    <!-- CARD STRUK FISIK 58MM -->
    <div class="receipt-card">
        <div class="text-center">
            <div class="store-name"><?= htmlspecialchars($toko['store_name']) ?></div>
            <div class="store-info"><?= htmlspecialchars($toko['store_address']) ?></div>
            <div class="store-info">Telp/WA: <?= htmlspecialchars($toko['store_phone']) ?></div>
        </div>
        
        <div class="divider"></div>
        
        <table class="info-table">
            <tr><td style="width: 42px;">Tgl</td><td>: <?= date('d/m/y H:i', strtotime($sale['created_at'])) ?></td></tr>
            <tr><td>Inv</td><td>: <?= htmlspecialchars($invoice) ?></td></tr>
            <?php 
                $order_type = strtolower($sale['order_type'] ?? '');
                $channel_name = strtoupper($channel);
            ?>
            <?php if($order_type === 'online' || in_array(strtolower($channel), ['grabfood', 'gofood', 'shopeefood', 'travelokaeats', 'wa'])): ?>
                <tr><td>Sumber</td><td>: <strong style="font-weight: 900; font-size: 10.5px;">ONLINE (<?= htmlspecialchars($channel_name) ?>)</strong></td></tr>
                <?php if(!empty($sale['external_order_id'])): ?>
                <tr><td>Order ID</td><td>: <span class="text-bold"><?= htmlspecialchars($sale['external_order_id']) ?></span></td></tr>
                <?php endif; ?>
                <?php if(!empty($sale['driver_name'])): ?>
                <tr><td>Driver</td><td>: <?= htmlspecialchars($sale['driver_name']) ?> (<?= htmlspecialchars($sale['driver_phone'] ?? '-') ?>)</td></tr>
                <?php endif; ?>
            <?php else: ?>
                <tr><td>Kasir</td><td>: <?= htmlspecialchars($sale['cashier_name'] ?? 'Kasir') ?></td></tr>
                <tr><td>Plg</td><td>: <?= htmlspecialchars($sale['customer_name'] ?? 'Umum') ?></td></tr>
                <?php if($is_po): ?>
                <tr><td>Tipe</td><td>: <span style="background:#000; color:#fff; padding:1px 3px; border-radius:2px;">PESANAN PO</span></td></tr>
                <tr><td>Ambil</td><td>: <?= $pickup_date ?> <?= $pickup_time ?></td></tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>
        
        <div class="divider"></div>
        
        <table class="item-table">
            <?php foreach ($items as $item): ?>
                <tr class="item-row">
                    <td colspan="2" class="item-name"><?= htmlspecialchars($item['product_name']) ?></td>
                </tr>
                <tr class="item-row">
                    <td style="padding-left: 5px; color: #222; font-weight: normal;"><?= $item['qty'] ?> x <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td class="text-right text-bold"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="divider"></div>
        
        <table class="summary-table">
            <tr><td>Subtotal</td><td class="text-right"><?= number_format($sale['subtotal'], 0, ',', '.') ?></td></tr>
            <?php if($calculated_ongkir > 0): ?><tr><td>Ongkir</td><td class="text-right"><?= number_format($calculated_ongkir, 0, ',', '.') ?></td></tr><?php endif; ?>
            <?php if($sale['discount_voucher'] > 0): ?><tr><td>Voucher</td><td class="text-right">-<?= number_format($sale['discount_voucher'], 0, ',', '.') ?></td></tr><?php endif; ?>
            <?php if($sale['discount_points'] > 0): ?><tr><td>Poin</td><td class="text-right">-<?= number_format($sale['discount_points'], 0, ',', '.') ?></td></tr><?php endif; ?>
            <?php if($sale['discount_manual'] > 0): ?><tr><td>Disc. Manual</td><td class="text-right">-<?= number_format($sale['discount_manual'], 0, ',', '.') ?></td></tr><?php endif; ?>
            <tr><td class="text-bold" style="font-size: 13px; padding-top: 4px;">TOTAL</td><td class="text-bold text-right" style="font-size: 13px; padding-top: 4px;"><?= number_format($sale['total_amount'], 0, ',', '.') ?></td></tr>
            <tr><td style="padding-top: 4px;"> (<?= strtoupper($sale['payment_method']) ?>)</td><td class="text-right" style="padding-top: 4px;"><?= number_format($sale['amount_paid'], 0, ',', '.') ?></td></tr>
            <?php if(!empty($sale['payment_reference'])): ?>
            <tr><td colspan="2" style="font-size: 9px;">Ref: <?= htmlspecialchars($sale['payment_reference']) ?></td></tr>
            <?php endif; ?>
            <?php if($sale['payment_status'] === 'dp'): ?>
            <tr><td class="text-bold">SISA HUTANG</td><td class="text-bold text-right"><?= number_format($sale['total_amount'] - $sale['dp_amount'], 0, ',', '.') ?></td></tr>
            <?php endif; ?>
            <tr><td>Kembali</td><td class="text-right"><?= number_format($sale['change_amount'], 0, ',', '.') ?></td></tr>
        </table>
        
        <div class="divider-double"></div>
        
        <div class="text-center store-info" style="margin-top: 5px;">
            <p style="margin: 0; font-style: italic;"><?= htmlspecialchars($toko['receipt_footer']) ?></p>
        </div>

        <div class="barcode-container"><svg id="barcode"></svg></div>
    </div>

    <!-- PANDUAN, STATUS PERANGKAT & AKSI PRINT (TIDAK IKUT TERCETAK) -->
    <div class="no-print action-container" id="action-buttons">
        
        <!-- STATUS INTEGRASI PRINTER (DARI PENGATURAN KASIR) -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b;">
                    <i class="fa-solid fa-print text-blue-500 mr-1"></i> Status Printer:
                </span>
                <span id="printer-status-badge" class="badge-status badge-disconnected">
                    <i class="fa-solid fa-circle text-[7px]"></i> Belum Terhubung
                </span>
            </div>
            
            <div id="printer-device-name" style="font-size: 11px; font-weight: 800; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                Belum ada printer terhubung
            </div>
            
            <!-- PESAN PETUNJUK (OTOMATIS HILANG SAAT TERHUBUNG, MUNCUL KEMBALI SAAT PUTUS) -->
            <div id="printer-connect-msg" style="font-size: 10px; color: #94a3b8; font-weight: 600; margin-top: 2px;">
                Klik tombol di bawah untuk menyambungkan printer
            </div>

            <div style="margin-top: 6px; display: flex; align-items: center; justify-content: space-between; font-size: 9.5px; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 6px;">
                <span>Mode: <b id="printer-mode-label">Manual</b></span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="btn-disconnect-printer" onclick="putuskanPrinter()" style="display: none; background: none; border: none; padding: 0; color: #ef4444; font-size: 9.5px; font-weight: 800; cursor: pointer;">
                        <i class="fa-solid fa-link-slash"></i> Putuskan / Ganti
                    </button>
                    <a href="../pengaturan/printer/index.php" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 800;">
                        <i class="fa-solid fa-gear"></i> Pengaturan
                    </a>
                </div>
            </div>
        </div>

        <!-- ALERT KETIKA GAGAL BACA PRINTER + TOMBOL KHUSUS SAMBUNGKAN PRINTER -->
        <div id="alert-connect" class="alert-connect">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 text-sm mt-0.5"></i>
                <div style="flex: 1;">
                    <b style="color: #78350f;">Gagal membaca mesin printer!</b>
                    <p id="alert-connect-msg" style="margin: 2px 0 8px 0; line-height: 1.3;">
                        Pastikan printer thermal <b>iware C58MPC V2</b> Anda menyala dan Bluetooth aktif.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <button onclick="hubungkanPrinterBluetooth(true)" class="btn btn-connect" style="margin: 0; padding: 8px 10px; font-size: 10.5px;">
                            <i class="fa-brands fa-bluetooth"></i> Sambungkan iware Bluetooth
                        </button>
                        <button onclick="hubungkanPrinterUSB(true)" class="btn btn-usb" style="margin: 0; padding: 7px 10px; font-size: 10px;">
                            <i class="fa-solid fa-usb"></i> Sambungkan Kabel USB
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL KHUSUS HUBUNGKAN MESIN PRINTER (OTOMATIS HILANG SAAT TERHUBUNG, MUNCUL KEMBALI SAAT PUTUS) -->
        <button type="button" onclick="hubungkanPrinterBluetooth(false)" class="btn btn-connect" id="btn-quick-connect">
            <i class="fa-brands fa-bluetooth"></i> Sambungkan Mesin Printer (iware)
        </button>

        <!-- TOMBOL AKSI CETAK UTAMA -->
        <button onclick="window.print()" class="btn btn-print">
            <i class="fa-solid fa-print"></i> Cetak / Cetak Ulang Struk
        </button>
        <button onclick="printBluetooth(false)" class="btn btn-bt" id="btn-bt">
            <i class="fa-brands fa-bluetooth"></i> Print Bluetooth (iware C58MPC)
        </button>
        <button onclick="printWebUSB(false)" class="btn btn-usb" id="btn-usb">
            <i class="fa-solid fa-bolt"></i> Print Direct WebUSB (ESC/POS)
        </button>
        <button onclick="printSerial(false)" class="btn btn-serial" id="btn-serial">
            <i class="fa-solid fa-plug"></i> Print Direct WebSerial (COM)
        </button>
        
        <button onclick="window.close()" class="btn btn-close">
            <i class="fa-solid fa-xmark"></i> Tutup Halaman
        </button>

        <!-- TIPS MINIMALIS -->
        <div style="margin-top: 8px; text-align: center; font-size: 9.5px; color: #94a3b8;">
            <i class="fa-solid fa-circle-info"></i> Support printer iware C58MPC V2 & Thermal 58mm
        </div>
    </div>

    <script>
        const receiptData = {
            storeName: <?= json_encode($toko['store_name']) ?>,
            invoice: <?= json_encode($invoice) ?>,
            date: <?= json_encode(date('d/m/y H:i', strtotime($sale['created_at']))) ?>,
            items: <?= json_encode($items) ?>,
            total: "<?= number_format($sale['total_amount'], 0, ',', '.') ?>",
            paid: "<?= number_format($sale['amount_paid'], 0, ',', '.') ?>",
            change: "<?= number_format($sale['change_amount'], 0, ',', '.') ?>",
            footer: <?= json_encode($toko['receipt_footer']) ?>
        };

        // DAFTAR UUID BLE GATT UNTUK IWARE C58MPC V2 & PRINTER THERMAL
        const KNOWN_BLE_SERVICES = [
            '000018f0-0000-1000-8000-00805f9b34fb', // Standard Chinese Thermal Printer
            'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // iware / Pos-58 / Rongta
            '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent UART
            '0000e0ff-0000-1000-8000-00805f9b34fb', // ESC/POS BLE
            '0000ff00-0000-1000-8000-00805f9b34fb', // Feasycom / BLE Serial
            '0000fff0-0000-1000-8000-00805f9b34fb', // Common BLE UART
            '0000ae30-0000-1000-8000-00805f9b34fb', // Zhuhai / iware variant
            '0000fee7-0000-1000-8000-00805f9b34fb'  // Tencent / OEM BLE
        ];

        let activeBtDevice = null;
        let isPrinterConnected = false;

        // MANAJER STATUS KONEKSI (MENGATUR HILANG/MUNCULNYA PESAN DAN TOMBOL SAMBUNGKAN)
        function setPrinterConnectedState(connected, deviceName = '', type = 'bluetooth', deviceObj = null) {
            isPrinterConnected = connected;
            const badge = document.getElementById('printer-status-badge');
            const nameEl = document.getElementById('printer-device-name');
            const msgEl = document.getElementById('printer-connect-msg');
            const quickBtn = document.getElementById('btn-quick-connect');
            const disconnectBtn = document.getElementById('btn-disconnect-printer');
            const alertBox = document.getElementById('alert-connect');
            const modeLabel = document.getElementById('printer-mode-label');

            const autoMode = localStorage.getItem('pos_auto_print_mode') || 'manual';
            if (modeLabel) {
                modeLabel.innerText = autoMode === 'bluetooth' ? 'Otomatis Bluetooth' : (autoMode === 'usb' ? 'Otomatis USB' : 'Manual');
            }

            if (connected) {
                // ✅ 1. KETIKA SUDAH TERHUBUNG:
                if (badge) {
                    badge.className = 'badge-status badge-connected';
                    badge.innerHTML = `<i class="fa-solid fa-circle text-[7px]"></i> Terhubung (${type === 'bluetooth' ? 'Bluetooth' : 'USB'})`;
                }
                if (nameEl) {
                    nameEl.innerHTML = `<span style="color:#15803d;"><i class="fa-${type === 'bluetooth' ? 'brands fa-bluetooth' : 'solid fa-usb'} mr-1"></i> <b>${deviceName || 'iware C58MPC V2'}</b> <span style="font-size:10px; font-weight:700;">(Siap Cetak)</span></span>`;
                }

                // 🌟 OTOMATIS HILANGKAN PESAN DAN TOMBOL SAMBUNGKAN:
                if (msgEl) msgEl.style.display = 'none';
                if (quickBtn) quickBtn.style.display = 'none';
                if (alertBox) alertBox.style.display = 'none';
                if (disconnectBtn) disconnectBtn.style.display = 'inline-block';

                if (deviceObj) {
                    activeBtDevice = deviceObj;
                    deviceObj.removeEventListener('gattserverdisconnected', onGattDisconnected);
                    deviceObj.addEventListener('gattserverdisconnected', onGattDisconnected);
                }
            } else {
                // ⚠️ 2. KETIKA PUTUS ATAU BELUM TERHUBUNG:
                if (badge) {
                    badge.className = 'badge-status badge-disconnected';
                    badge.innerHTML = '<i class="fa-solid fa-circle text-[7px]"></i> Belum Terhubung';
                }
                if (nameEl) {
                    nameEl.innerHTML = '<span style="color:#64748b;">Belum ada printer terhubung</span>';
                }

                // 🌟 OTOMATIS MUNCULKAN KEMBALI PESAN DAN TOMBOL SAMBUNGKAN:
                if (msgEl) {
                    msgEl.innerText = 'Klik tombol di bawah untuk menyambungkan printer';
                    msgEl.style.display = 'block';
                }
                if (quickBtn) quickBtn.style.display = 'flex';
                if (disconnectBtn) disconnectBtn.style.display = 'none';
            }
        }

        // Event listener saat koneksi GATT Bluetooth putus
        function onGattDisconnected(event) {
            console.warn('Printer Bluetooth terputus:', event);
            activeBtDevice = null;
            setPrinterConnectedState(false);
            showConnectAlert('Koneksi ke printer Bluetooth iware C58MPC terputus! Pastikan printer menyala, lalu klik tombol di bawah untuk menyambungkan kembali.');
        }

        // Putuskan printer secara manual
        function putuskanPrinter() {
            if (activeBtDevice && activeBtDevice.gatt && activeBtDevice.gatt.connected) {
                try { activeBtDevice.gatt.disconnect(); } catch(e) {}
            }
            activeBtDevice = null;
            localStorage.removeItem('pos_printer_name');
            localStorage.removeItem('pos_bt_active');
            setPrinterConnectedState(false);
            showConnectAlert('Printer telah diputuskan. Silakan klik tombol di bawah untuk menyambungkan printer baru.');
        }

        // Tampilkan Banner Alert Gagal Baca Printer
        function showConnectAlert(pesan = '') {
            const alertBox = document.getElementById('alert-connect');
            const msgEl = document.getElementById('alert-connect-msg');
            if (pesan) msgEl.innerText = pesan;
            alertBox.style.display = 'block';
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Pastikan tombol sambungkan muncul saat ada alert
            const quickBtn = document.getElementById('btn-quick-connect');
            if (quickBtn) quickBtn.style.display = 'flex';
        }

        function hideConnectAlert() {
            document.getElementById('alert-connect').style.display = 'none';
        }

        // Modal / Trigger Cepat Hubungkan Printer
        function bukaModalKoneksi() {
            showConnectAlert('Pilih metode koneksi untuk printer kasir Anda (iware C58MPC V2):');
        }

        // Cari karakteristik BLE yang bisa menerima data cetak
        async function findBlePrinterCharacteristic(device) {
            if (!device.gatt.connected) {
                await device.gatt.connect();
            }
            const server = device.gatt;

            for (const uuid of KNOWN_BLE_SERVICES) {
                try {
                    const service = await server.getPrimaryService(uuid);
                    if (service) {
                        const chars = await service.getCharacteristics();
                        for (const c of chars) {
                            if (c.properties.write || c.properties.writeWithoutResponse) {
                                return c;
                            }
                        }
                    }
                } catch (e) {}
            }

            try {
                const services = await server.getPrimaryServices();
                for (const s of services) {
                    try {
                        const chars = await s.getCharacteristics();
                        for (const c of chars) {
                            if (c.properties.write || c.properties.writeWithoutResponse) {
                                return c;
                            }
                        }
                    } catch (e) {}
                }
            } catch (e) {}

            throw new Error('Tidak ditemukan port/karakteristik cetak ESC/POS pada printer ini.');
        }

        // Kirim data chunked (64 byte) agar ramah chip Bluetooth iware C58MPC V2
        async function sendChunkedData(characteristic, dataBuffer) {
            const CHUNK_SIZE = 64;
            for (let i = 0; i < dataBuffer.length; i += CHUNK_SIZE) {
                const chunk = dataBuffer.slice(i, i + CHUNK_SIZE);
                if (characteristic.writeValueWithoutResponse) {
                    await characteristic.writeValueWithoutResponse(chunk);
                } else {
                    await characteristic.writeValue(chunk);
                }
                await new Promise(r => setTimeout(r, 25));
            }
        }

        // Fungsi merakit teks ESC/POS mentah untuk Thermal Printer 58mm (32 Kolom)
        function buildEscPosText() {
            let printText = "\x1B\x40"; // ESC @ (Init)
            printText += "\x1B\x61\x01\x1B\x45\x01" + receiptData.storeName + "\n\x1B\x45\x00\x1B\x61\x00";
            printText += "--------------------------------\n";
            printText += "Tgl : " + receiptData.date + "\nInv : " + receiptData.invoice + "\n";
            printText += "--------------------------------\n";
            
            receiptData.items.forEach(i => {
                let pFormat = parseInt(i.price).toLocaleString('id-ID');
                let sFormat = parseInt(i.subtotal).toLocaleString('id-ID');
                printText += i.product_name + "\n";
                let row = i.qty + " x " + pFormat;
                let space = 32 - row.length - sFormat.length;
                printText += row + " ".repeat(space > 0 ? space : 1) + sFormat + "\n";
            });
            
            printText += "--------------------------------\n";
            printText += " ".repeat(Math.max(0, 32 - ("TOTAL: Rp "+receiptData.total).length)) + "TOTAL: Rp " + receiptData.total + "\n";
            printText += " ".repeat(Math.max(0, 32 - ("BAYAR: Rp "+receiptData.paid).length)) + "BAYAR: Rp " + receiptData.paid + "\n";
            printText += " ".repeat(Math.max(0, 32 - ("KEMBALI: Rp "+receiptData.change).length)) + "KEMBALI: Rp " + receiptData.change + "\n";
            printText += "--------------------------------\n";
            printText += "\x1B\x61\x01" + receiptData.footer + "\n\n\n\n";
            printText += "\x1D\x56\x42\x00"; // Potong kertas
            return printText;
        }

        // HUBUNGKAN & SIMPAN PRINTER BLUETOOTH (SEKADAR SAMBUNG ATAU LANGSUNG PRINT)
        async function hubungkanPrinterBluetooth(langsungCetak = true) {
            if (!navigator.bluetooth) {
                alert('Browser ini tidak mendukung Web Bluetooth. Gunakan Google Chrome versi terbaru di PC atau Android.');
                return;
            }
            try {
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: KNOWN_BLE_SERVICES
                });

                const pName = device.name || 'iware C58MPC V2';
                localStorage.setItem('pos_printer_name', pName);
                localStorage.setItem('pos_bt_active', '1');
                localStorage.setItem('pos_auto_print_mode', 'bluetooth');

                // ✅ SAAT TERHUBUNG: OTOMATIS HILANGKAN PESAN & TOMBOL SAMBUNGKAN
                setPrinterConnectedState(true, pName, 'bluetooth', device);
                hideConnectAlert();

                if (langsungCetak) {
                    await printBluetooth(false, device);
                }
            } catch (err) {
                console.error("Gagal menghubungkan Bluetooth:", err);
                setPrinterConnectedState(false);
                if (err.name !== 'NotFoundError') {
                    showConnectAlert("Gagal terhubung ke Bluetooth: " + err.message);
                }
            }
        }

        // HUBUNGKAN & SIMPAN PRINTER USB
        async function hubungkanPrinterUSB(langsungCetak = true) {
            if (!navigator.usb) {
                alert('Browser ini tidak mendukung WebUSB. Silakan cetak secara normal via Cetak Ulang Struk.');
                return;
            }
            try {
                const device = await navigator.usb.requestDevice({ filters: [] });
                const usbName = device.productName || device.manufacturerName || 'Thermal Printer USB';
                localStorage.setItem('pos_usb_printer_name', usbName);
                localStorage.setItem('pos_auto_print_mode', 'usb');
                localStorage.removeItem('pos_bt_active');

                // ✅ SAAT TERHUBUNG: OTOMATIS HILANGKAN PESAN & TOMBOL SAMBUNGKAN
                setPrinterConnectedState(true, usbName, 'usb');
                hideConnectAlert();

                if (langsungCetak) {
                    await printWebUSB(false, device);
                }
            } catch (err) {
                console.error("Gagal menghubungkan USB:", err);
                setPrinterConnectedState(false);
                if (err.name !== 'NotFoundError') {
                    showConnectAlert("Gagal terhubung ke USB: " + err.message);
                }
            }
        }

        // 1. PRINT VIA BLUETOOTH (IWARE C58MPC V2 / GATT)
        async function printBluetooth(isAutoPrint = false, existingDevice = null) {
            const btn = document.getElementById('btn-bt');
            const savedPrinter = localStorage.getItem('pos_printer_name');
            let device = existingDevice;

            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghubungkan iware...';
                btn.disabled = true;
            }

            try {
                if (!device && savedPrinter && navigator.bluetooth.getDevices) {
                    const devices = await navigator.bluetooth.getDevices();
                    device = devices.find(d => d.name === savedPrinter);
                }

                if (!device) {
                    if (isAutoPrint) {
                        if (btn) {
                            btn.innerHTML = '<i class="fa-brands fa-bluetooth"></i> Print Bluetooth (iware C58MPC)';
                            btn.disabled = false;
                        }
                        // ⚠️ SAAT PUTUS / GAGAL BACA: PESAN DAN TOMBOL MUNCUL KEMBALI
                        setPrinterConnectedState(false);
                        showConnectAlert("Printer Bluetooth belum terhubung. Klik tombol di bawah untuk menyambungkan.");
                        return false;
                    }
                    device = await navigator.bluetooth.requestDevice({
                        acceptAllDevices: true,
                        optionalServices: KNOWN_BLE_SERVICES
                    });
                    localStorage.setItem('pos_printer_name', device.name || 'iware C58MPC V2');
                    localStorage.setItem('pos_bt_active', '1');
                }

                const characteristic = await findBlePrinterCharacteristic(device);
                const printText = buildEscPosText();
                const encoder = new TextEncoder();
                await sendChunkedData(characteristic, encoder.encode(printText));
                
                // ✅ SAAT TERHUBUNG DAN BERHASIL: PESAN & TOMBOL HILANG
                setPrinterConnectedState(true, device.name || 'iware C58MPC V2', 'bluetooth', device);
                hideConnectAlert();

                if (btn) { btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Berhasil Dicetak Bluetooth! ✅'; }
                setTimeout(() => { 
                    if (btn) {
                        btn.innerHTML = '<i class="fa-brands fa-bluetooth"></i> Print Bluetooth (iware C58MPC)'; 
                        btn.disabled = false; 
                    }
                }, 2500);
                return true;

            } catch (error) {
                console.error("Gagal Print Bluetooth:", error);
                if (btn) {
                    btn.innerHTML = '<i class="fa-brands fa-bluetooth"></i> Print Bluetooth (iware C58MPC)';
                    btn.disabled = false;
                }
                // ⚠️ SAAT KONEKSI PUTUS: PESAN & TOMBOL MUNCUL KEMBALI
                setPrinterConnectedState(false);
                showConnectAlert('Gagal mengirim data cetak ke Printer Bluetooth (' + (error.message || 'Periksa koneksi printer') + '). Pastikan printer iware dinyalakan.');
                return false;
            }
        }

        // 2. PRINT LANGSUNG VIA WEBUSB (ESC/POS RAW)
        async function printWebUSB(isAutoPrint = false, existingDevice = null) {
            const btn = document.getElementById('btn-usb');
            if (!navigator.usb) {
                if (!isAutoPrint) alert('Browser Anda tidak mendukung WebUSB. Silakan gunakan Google Chrome.');
                return false;
            }
            if (btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mencari Printer USB...'; btn.disabled = true; }

            try {
                let device = existingDevice;
                if (!device && navigator.usb.getDevices) {
                    const devList = await navigator.usb.getDevices();
                    if (devList && devList.length > 0) device = devList[0];
                }
                if (!device) {
                    if (isAutoPrint) return false;
                    device = await navigator.usb.requestDevice({ filters: [] });
                }

                await device.open();
                if (device.configuration === null) await device.selectConfiguration(1);
                
                let interfaceNumber = 0;
                let endpointNumber = 1;
                for (let config of device.configurations) {
                    for (let iface of config.interfaces) {
                        for (let alt of iface.alternates) {
                            for (let ep of alt.endpoints) {
                                if (ep.direction === "out") {
                                    interfaceNumber = iface.interfaceNumber;
                                    endpointNumber = ep.endpointNumber;
                                    break;
                                }
                            }
                        }
                    }
                }

                await device.claimInterface(interfaceNumber);
                const encoder = new TextEncoder();
                const printText = buildEscPosText();
                await device.transferOut(endpointNumber, encoder.encode(printText));
                await device.close();

                // ✅ SAAT TERHUBUNG USB: PESAN & TOMBOL HILANG
                setPrinterConnectedState(true, device.productName || 'USB Thermal', 'usb');
                hideConnectAlert();

                if (btn) { btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Berhasil Dicetak WebUSB! ✅'; }
                setTimeout(() => { 
                    if (btn) { btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Print Direct WebUSB (ESC/POS)'; btn.disabled = false; }
                }, 2000);
                return true;
            } catch (err) {
                console.error("WebUSB Error:", err);
                if (btn) { btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Print Direct WebUSB (ESC/POS)'; btn.disabled = false; }
                setPrinterConnectedState(false);
                if (!isAutoPrint) {
                    showConnectAlert("Gagal mencetak via WebUSB: " + err.message);
                }
                return false;
            }
        }

        // 3. PRINT LANGSUNG VIA WEBSERIAL (COM PORT)
        async function printSerial(isAutoPrint = false, existingPort = null) {
            const btn = document.getElementById('btn-serial');
            if (!navigator.serial) {
                if (!isAutoPrint) alert('Browser Anda tidak mendukung WebSerial.');
                return false;
            }
            if (btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Membuka Serial...'; btn.disabled = true; }

            try {
                let port = existingPort;
                if (!port && navigator.serial.getPorts) {
                    const portList = await navigator.serial.getPorts();
                    if (portList && portList.length > 0) port = portList[0];
                }
                if (!port) {
                    if (isAutoPrint) return false;
                    port = await navigator.serial.requestPort();
                }

                await port.open({ baudRate: 9600 });
                const writer = port.writable.getWriter();
                const encoder = new TextEncoder();
                const printText = buildEscPosText();
                await writer.write(encoder.encode(printText));
                writer.releaseLock();
                await port.close();

                setPrinterConnectedState(true, 'Serial COM Port', 'usb');
                hideConnectAlert();

                if (btn) { btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Berhasil Dicetak Serial! ✅'; }
                setTimeout(() => { 
                    if (btn) { btn.innerHTML = '<i class="fa-solid fa-plug"></i> Print Direct WebSerial (COM)'; btn.disabled = false; }
                }, 2000);
                return true;
            } catch (err) {
                console.error("WebSerial Error:", err);
                if (btn) { btn.innerHTML = '<i class="fa-solid fa-plug"></i> Print Direct WebSerial (COM)'; btn.disabled = false; }
                setPrinterConnectedState(false);
                if (!isAutoPrint) showConnectAlert("Gagal mencetak via WebSerial: " + err.message);
                return false;
            }
        }

        // INISIALISASI HALAMAN & CEK STATUS KONEKSI PRINTER OTOMATIS
        document.addEventListener("DOMContentLoaded", async function() {
            JsBarcode("#barcode", "<?= htmlspecialchars($invoice) ?>", { 
                format: "CODE128", 
                displayValue: true, 
                fontSize: 11, 
                height: 38, 
                width: 1.15 
            });

            // 1. Cek printer yang tersimpan di localStorage
            const savedBt = localStorage.getItem('pos_printer_name');
            const savedUsb = localStorage.getItem('pos_usb_printer_name');
            const btActive = localStorage.getItem('pos_bt_active') === '1';

            // 2. CEK AKTIF KONEKSI BLUETOOTH / DRIVER OTOMATIS
            let isConnectedOnLoad = false;
            if (navigator.bluetooth && navigator.bluetooth.getDevices) {
                try {
                    const devices = await navigator.bluetooth.getDevices();
                    if (devices && devices.length > 0) {
                        const targetDev = (savedBt ? devices.find(d => d.name === savedBt) : null) || devices[0];
                        if (targetDev) {
                            if (targetDev.gatt && targetDev.gatt.connected) {
                                setPrinterConnectedState(true, targetDev.name || 'iware C58MPC V2', 'bluetooth', targetDev);
                                isConnectedOnLoad = true;
                            } else {
                                // Coba sambungkan otomatis jika printer sedang aktif di dekat PC
                                targetDev.gatt.connect().then(() => {
                                    setPrinterConnectedState(true, targetDev.name || 'iware C58MPC V2', 'bluetooth', targetDev);
                                }).catch(() => {
                                    setPrinterConnectedState(false);
                                });
                            }
                        }
                    }
                } catch(e) {
                    console.warn("getDevices check:", e);
                }
            }

            if (!isConnectedOnLoad) {
                setPrinterConnectedState(false);
            }

            // 3. AUTO PRINT SESUAI PENGATURAN KASIR
            const params = new URLSearchParams(window.location.search);
            const isParamBt = params.get('auto_print_bt') === '1';
            const isParamUsb = params.get('auto_print_usb') === '1';
            const savedAutoMode = localStorage.getItem('pos_auto_print_mode') || 'manual';

            const shouldAutoBt = isParamBt || (savedAutoMode === 'bluetooth' && btActive);
            const shouldAutoUsb = isParamUsb || (savedAutoMode === 'usb');

            if (shouldAutoBt) {
                setTimeout(async () => {
                    const success = await printBluetooth(true);
                    if (!success) {
                        showConnectAlert("Mode Otomatis Bluetooth aktif, tetapi printer iware C58MPC belum tersambung. Klik tombol di bawah untuk menyambungkan.");
                    }
                }, 700);
            } else if (shouldAutoUsb) {
                setTimeout(async () => {
                    let printed = false;
                    if (navigator.usb && navigator.usb.getDevices) {
                        try {
                            const devList = await navigator.usb.getDevices();
                            if (devList && devList.length > 0) {
                                printed = await printWebUSB(true, devList[0]);
                            }
                        } catch(e) {}
                    }
                    if (!printed) {
                        window.print();
                    }
                }, 700);
            }
        });
    </script>
</body> 
</html>