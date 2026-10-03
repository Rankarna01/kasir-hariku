<?php
// pos/pengaturan/printer/logic.php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

// 1. AUTO MIGRATION: Pastikan tabel print_logs_pos dan kolom pengaturan struk tersedia
try {
    // Tabel log cetak
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `print_logs_pos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sale_id` INT(11) DEFAULT NULL,
            `invoice_no` VARCHAR(60) NOT NULL,
            `device_name` VARCHAR(100) DEFAULT 'Tablet Kasir',
            `mode` VARCHAR(30) DEFAULT 'rawbt',
            `status` ENUM('success', 'failed', 'reported_stuck') DEFAULT 'success',
            `note` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_invoice` (`invoice_no`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // Kolom toggle struk di store_settings_pos
    $colsCheck = $pdo->query("SHOW COLUMNS FROM store_settings_pos")->fetchAll(PDO::FETCH_COLUMN);
    $neededCols = [
        'show_logo' => 'TINYINT(1) DEFAULT 1',
        'show_cashier' => 'TINYINT(1) DEFAULT 1',
        'show_invoice' => 'TINYINT(1) DEFAULT 1',
        'show_notes' => 'TINYINT(1) DEFAULT 1',
        'show_tax_ongkir' => 'TINYINT(1) DEFAULT 1'
    ];

    foreach ($neededCols as $col => $def) {
        if (!in_array($col, $colsCheck)) {
            $pdo->exec("ALTER TABLE store_settings_pos ADD COLUMN `$col` $def");
        }
    }
} catch (Exception $e) {
    // Silent fail if permission issues
}

$action = $_REQUEST['action'] ?? '';

// ACTION: Ambil Pengaturan Toko & Struk
if ($action === 'get_settings') {
    try {
        $stmt = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1 LIMIT 1");
        $store = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$store) {
            $pdo->exec("INSERT IGNORE INTO store_settings_pos (id, store_name, receipt_footer) VALUES (1, 'AYAM GORENG HARIKU', 'Terima Kasih Atas Kunjungan Anda!')");
            $store = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status' => 'success', 'data' => $store]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ACTION: Simpan Pengaturan Struk (Database)
if ($action === 'save_receipt_settings') {
    try {
        $store_name = trim($_POST['store_name'] ?? '');
        $store_address = trim($_POST['store_address'] ?? '');
        $store_phone = trim($_POST['store_phone'] ?? '');
        $receipt_footer = trim($_POST['receipt_footer'] ?? '');
        $show_logo = isset($_POST['show_logo']) ? (int)$_POST['show_logo'] : 1;
        $show_cashier = isset($_POST['show_cashier']) ? (int)$_POST['show_cashier'] : 1;
        $show_invoice = isset($_POST['show_invoice']) ? (int)$_POST['show_invoice'] : 1;
        $show_notes = isset($_POST['show_notes']) ? (int)$_POST['show_notes'] : 1;
        $show_tax_ongkir = isset($_POST['show_tax_ongkir']) ? (int)$_POST['show_tax_ongkir'] : 1;

        if (empty($store_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama toko tidak boleh kosong!']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE store_settings_pos 
            SET store_name = ?, store_address = ?, store_phone = ?, receipt_footer = ?,
                show_logo = ?, show_cashier = ?, show_invoice = ?, show_notes = ?, show_tax_ongkir = ?
            WHERE id = 1
        ");
        $stmt->execute([
            $store_name, $store_address, $store_phone, $receipt_footer,
            $show_logo, $show_cashier, $show_invoice, $show_notes, $show_tax_ongkir
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Pengaturan struk berhasil disimpan ke database!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ACTION: Catat Log Cetak Struk
if ($action === 'log_print') {
    try {
        $sale_id = !empty($_POST['sale_id']) ? (int)$_POST['sale_id'] : null;
        $invoice_no = trim($_POST['invoice_no'] ?? 'TEST-PRINT');
        $device_name = trim($_POST['device_name'] ?? 'Tablet Kasir');
        $mode = trim($_POST['mode'] ?? 'rawbt');
        $status = trim($_POST['status'] ?? 'success'); // 'success', 'failed', 'reported_stuck'
        $note = trim($_POST['note'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO print_logs_pos (sale_id, invoice_no, device_name, mode, status, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$sale_id, $invoice_no, $device_name, $mode, $status, $note]);

        echo json_encode(['status' => 'success', 'message' => 'Log tercatat']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ACTION: Ambil Riwayat Log Cetak & Ringkasan Kasus "Struk Tidak Keluar"
if ($action === 'get_print_logs') {
    try {
        // 1. Ambil 25 Log Cetak Terakhir
        $stmt = $pdo->query("
            SELECT id, sale_id, invoice_no, device_name, mode, status, note, created_at 
            FROM print_logs_pos 
            ORDER BY id DESC 
            LIMIT 30
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Ringkasan Statistik
        $totalPrint = (int)$pdo->query("SELECT COUNT(*) FROM print_logs_pos")->fetchColumn();
        $totalSuccess = (int)$pdo->query("SELECT COUNT(*) FROM print_logs_pos WHERE status = 'success'")->fetchColumn();
        $totalStuck = (int)$pdo->query("SELECT COUNT(*) FROM print_logs_pos WHERE status = 'reported_stuck'")->fetchColumn();
        $totalFailed = (int)$pdo->query("SELECT COUNT(*) FROM print_logs_pos WHERE status = 'failed'")->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'logs' => $logs,
                'stats' => [
                    'total_print' => $totalPrint,
                    'total_success' => $totalSuccess,
                    'total_stuck' => $totalStuck,
                    'total_failed' => $totalFailed
                ]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// ACTION: Ambil Data Lengkap Struk untuk Cetak Ulang Berdasarkan Invoice
if ($action === 'get_receipt_data') {
    try {
        $invoice = trim($_GET['invoice'] ?? '');
        if (empty($invoice)) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice tidak ditentukan']);
            exit;
        }

        $stmtHead = $pdo->prepare("
            SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.name as cashier_name
            FROM sales_pos s 
            LEFT JOIN customers_pos c ON s.customer_id = c.id 
            LEFT JOIN users_pos u ON s.user_id = u.id
            WHERE s.invoice_no = ?
        ");
        $stmtHead->execute([$invoice]);
        $sale = $stmtHead->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']);
            exit;
        }

        $stmtDetail = $pdo->prepare("
            SELECT sd.*, COALESCE(p.name, sd.custom_name, 'Produk') as product_name 
            FROM sale_details_pos sd 
            LEFT JOIN products p ON sd.product_id = p.id 
            WHERE sd.sale_id = ?
        ");
        $stmtDetail->execute([$sale['id']]);
        $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

        $store = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'sale' => $sale,
                'items' => $items,
                'store' => $store
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
exit;