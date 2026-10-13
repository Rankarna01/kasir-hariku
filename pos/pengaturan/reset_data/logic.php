<?php
session_start();
header('Content-Type: application/json');

// Validasi otorisasi: harus memiliki otorisasi token rahasia DAN sudah login
if (empty($_SESSION['secret_reset_authorized']) || empty($_SESSION['pos_user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Token otorisasi rahasia tidak valid atau Anda belum login.']);
    exit;
}

// Validasi role: hanya admin/owner yang berhak mengeksekusi
$current_role = strtolower(trim($_SESSION['pos_role'] ?? ''));
if (!in_array($current_role, ['admin', 'owner', 'superadmin', 'backoffice'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Tindakan ini hanya boleh dilakukan oleh Administrator / Owner.']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

if (isset($_GET['action']) && $_GET['action'] == 'reset') {
    $password = $_POST['admin_password'] ?? '';
    $user_id = $_SESSION['pos_user_id'];
    $scope = $_POST['reset_scope'] ?? 'transaksi';
    
    // Verifikasi password admin
    try {
        $stmt_user = $pdo->prepare("SELECT password FROM users_pos WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        $envMasterPass = function_exists('env') ? env('MASTER_ADMIN_PASSWORD', '') : '';

        $isValidPass = false;
        if (!empty($envMasterPass) && $password === $envMasterPass) {
            $isValidPass = true;
        } elseif ($user && password_verify($password, $user['password'])) {
            $isValidPass = true;
        } else {
            // Fallback: periksa dengan akun role admin/superadmin di database
            $stmt_admins = $pdo->query("SELECT password FROM users_pos WHERE role_id IN (SELECT id FROM roles_pos WHERE LOWER(role_name) LIKE '%admin%' OR LOWER(role_name) LIKE '%owner%')");
            while ($adm = $stmt_admins->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $adm['password'])) {
                    $isValidPass = true;
                    break;
                }
            }
        }

        if (!$isValidPass) {
            echo json_encode(['status' => 'error', 'message' => 'Password admin yang dimasukkan salah.']);
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

    // Pengelompokan tabel berdasarkan cakupan reset
    $transaksi_tables = [
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

    $produk_tables = [
        'products',
        'product_warehouse_stocks',
        'categories',
        'recipe_details',
        'saved_custom_items_pos',
        'saved_custom_reguler_pos'
    ];

    $pelanggan_tables = [
        'customers_pos'
    ];

    $tables = [];
    $scope_name = '';

    if ($scope === 'transaksi') {
        $tables = $transaksi_tables;
        $scope_name = 'Data Transaksi & Kasir';
    } elseif ($scope === 'produk') {
        $tables = $produk_tables;
        $scope_name = 'Data Master Produk & Katalog';
    } elseif ($scope === 'pelanggan') {
        $tables = $pelanggan_tables;
        $scope_name = 'Data Master Pelanggan';
    } elseif ($scope === 'total') {
        $tables = array_merge($transaksi_tables, $produk_tables, $pelanggan_tables);
        $scope_name = 'Reset Total Kasir (Go-Live Bersih)';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cakupan reset tidak valid.']);
        exit;
    }

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

        // Khusus Reset Total: Bersihkan akun kasir / staf lain, HANYA SISAKAN 1 AKUN ADMIN
        if ($scope === 'total') {
            try {
                // Identifikasi akun admin utama yang sedang login atau admin pertama
                $stmt_keep_admin = $pdo->prepare("SELECT id FROM users_pos WHERE id = ?");
                $stmt_keep_admin->execute([$user_id]);
                $keep_admin = $stmt_keep_admin->fetch(PDO::FETCH_ASSOC);

                if ($keep_admin) {
                    $keep_id = $keep_admin['id'];
                    $pdo->prepare("DELETE FROM users_pos WHERE id != ?")->execute([$keep_id]);
                } else {
                    // Fallback: simpan admin dengan id terkecil
                    $first_admin = $pdo->query("SELECT id FROM users_pos ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    if ($first_admin) {
                        $pdo->prepare("DELETE FROM users_pos WHERE id != ?")->execute([$first_admin['id']]);
                    }
                }
            } catch (Exception $e_user) {}
        }
        
        // Nyalakan kembali foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        // Catat ke log
        $username = $_SESSION['pos_username'] ?? 'Unknown Admin';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $desc = "[$scope_name] Berhasil mengosongkan " . count($truncated) . " tabel (" . implode(', ', array_slice($truncated, 0, 5)) . (count($truncated) > 5 ? '...' : '') . "). Akun admin tetap dipertahankan.";
        
        $stmt_log = $pdo->prepare("INSERT INTO reset_logs_pos (user_id, username, ip_address, description) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([$user_id, $username, $ip_address, $desc]);

        echo json_encode([
            'status' => 'success', 
            'message' => "$scope_name berhasil direset! (" . count($truncated) . " tabel dikosongkan. 1 Akun Admin aman terjaga)."
        ]);
        
    } catch (PDOException $e) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo json_encode(['status' => 'error', 'message' => 'Error saat mereset data: ' . $e->getMessage()]);
    }

} elseif (isset($_GET['action']) && $_GET['action'] == 'backup_sql') {
    // Fitur Backup Database Lengkap Otomatis (.sql)
    try {
        set_time_limit(300);
        $tables_to_backup = [];
        $stmt_tables = $pdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'");
        while ($row = $stmt_tables->fetch(PDO::FETCH_NUM)) {
            $tables_to_backup[] = $row[0];
        }

        $sql_dump = "-- ============================================================\n";
        $sql_dump .= "-- BACKUP DATABASE SISTEM KASIR HARIKU\n";
        $sql_dump .= "-- Tanggal Backup : " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Diekspor oleh  : " . ($_SESSION['pos_username'] ?? 'Admin') . "\n";
        $sql_dump .= "-- ============================================================\n\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

        foreach ($tables_to_backup as $table) {
            $stmt_create = $pdo->query("SHOW CREATE TABLE `$table`");
            $create_row = $stmt_create->fetch(PDO::FETCH_ASSOC);
            $create_table_sql = $create_row['Create Table'] ?? '';

            $sql_dump .= "\n-- ------------------------------------------------------------\n";
            $sql_dump .= "-- Struktur Tabel `$table`\n";
            $sql_dump .= "-- ------------------------------------------------------------\n";
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_dump .= $create_table_sql . ";\n\n";

            // Data Tabel
            $stmt_data = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $sql_dump .= "-- Data Tabel `$table`\n";
                foreach ($rows as $r) {
                    $cols = array_map(function($c) { return "`" . str_replace("`", "``", $c) . "`"; }, array_keys($r));
                    $vals = array_map(function($val) use ($pdo) {
                        if ($val === null) return "NULL";
                        return $pdo->quote($val);
                    }, array_values($r));
                    $sql_dump .= "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sql_dump .= "\n";
            }
        }

        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $filename = 'backup_kasir_' . date('Ymd_His') . '.sql';

        // Simpan juga salinan ke folder server
        $backup_dir = __DIR__ . '/../../../backups';
        if (!is_dir($backup_dir)) {
            @mkdir($backup_dir, 0755, true);
        }
        @file_put_contents($backup_dir . '/' . $filename, $sql_dump);

        // Langsung kirim file download ke browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($sql_dump));
        echo $sql_dump;
        exit;

    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo "Gagal membuat backup database: " . $e->getMessage();
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
