<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['pos_user_id']) || !in_array($_SESSION['pos_role'], ['admin', 'superadmin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Admin/Superadmin.']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

if (isset($_GET['action']) && $_GET['action'] == 'reset') {
    $password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['pos_user_id'];
    
    // Verifikasi password admin
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users_pos WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Password yang dimasukkan salah.']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal verifikasi password: ' . $e->getMessage()]);
        exit;
    }

    // Buat tabel log jika belum ada (antisipasi pertama kali dijalankan)
    $sql_create = "CREATE TABLE IF NOT EXISTS `reset_logs_pos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `username` VARCHAR(255) DEFAULT NULL,
        `reset_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `ip_address` VARCHAR(50) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    try {
        $pdo->exec($sql_create);
    } catch (PDOException $e) {}

    // Daftar tabel yang akan di TRUNCATE
    $tables = [
        'sales_pos',
        'sale_details_pos',
        'sale_payments_pos',
        'sale_cancellations_pos',
        'sale_cancellation_items_pos',
        'shifts_history_pos',
        'petty_cash_pos',
        'inventory_history_pos',
        'opname_history_pos',
        'product_mutations',
        'product_outs',
        'purchase_orders',
        'purchase_order_details',
        'purchase_order_payments',
        'purchase_payments',
        'purchase_requests',
        'stok_opname',
        'stok_opname_details',
        'stok_opname_keys',
        'productions',
        'production_details',
        'bom_requests',
        'bom_request_details',
        'barang_masuk',
        'barang_keluar',
        'system_logs'
    ];

    try {
        // Matikan foreign key checks agar truncate bisa jalan
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        $truncated = [];
        foreach ($tables as $table) {
            // Cek apakah tabel ada sebelum truncate untuk mencegah error jika tabel dihapus sebelumnya
            $result = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() > 0) {
                $pdo->exec("TRUNCATE TABLE `$table`");
                $truncated[] = $table;
            }
        }
        
        // Nyalakan kembali foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        // Catat ke log
        $username = $_SESSION['pos_username'] ?? 'Unknown Admin';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $desc = "Berhasil mengosongkan " . count($truncated) . " tabel transaksi (TRUNCATE).";
        
        $stmt_log = $pdo->prepare("INSERT INTO reset_logs_pos (user_id, username, ip_address, description) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([$user_id, $username, $ip_address, $desc]);

        echo json_encode(['status' => 'success', 'message' => 'Data transaksi berhasil direset.']);
        
    } catch (PDOException $e) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo json_encode(['status' => 'error', 'message' => 'Error saat mereset data: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
