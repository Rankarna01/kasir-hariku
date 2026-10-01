<?php
// config/database.php

require_once __DIR__ . '/env.php';

$host = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
$user = function_exists('env') ? env('DB_USER', 'root') : 'root';
$pass = function_exists('env') ? env('DB_PASS', '') : '';
$dbname = function_exists('env') ? env('DB_NAME', 'db_pos_ku') : 'db_pos_ku';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback otomatis jika database di .env tidak ditemukan
    $altDb = ($dbname === 'db_pos_ku') ? 'kasir-hariku' : 'db_pos_ku';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$altDb;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $dbname = $altDb;
    } catch (PDOException $e2) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_warehouse_stocks (
        id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, warehouse_id INT NOT NULL, stock INT NOT NULL DEFAULT 0, UNIQUE KEY unique_prod_wh (product_id, warehouse_id)
    )");
    $pdo->exec("INSERT IGNORE INTO product_warehouse_stocks (product_id, warehouse_id, stock) SELECT id, stock, 1 FROM products");
} catch (Exception $e) {}

// Auto-migration: Perbesar kapasitas kolom price & online_price ke DECIMAL(15,2)
// agar tidak terjadi "Numeric value out of range (1264)" saat input harga >= 100.000.000 (100 juta)
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM products LIKE 'price'")->fetch();
    if ($colCheck && strpos(strtolower($colCheck['Type'] ?? ''), 'decimal(10') !== false) {
        $pdo->exec("ALTER TABLE products MODIFY COLUMN price DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE products MODIFY COLUMN online_price DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE products MODIFY COLUMN modal_price DECIMAL(15,2) DEFAULT 0.00");
    }
} catch (Exception $e) {}

try {
    $colCheckDetails = $pdo->query("SHOW COLUMNS FROM sale_details_pos LIKE 'price'")->fetch();
    if ($colCheckDetails && strpos(strtolower($colCheckDetails['Type'] ?? ''), 'decimal(10') !== false) {
        $pdo->exec("ALTER TABLE sale_details_pos MODIFY COLUMN price DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sale_details_pos MODIFY COLUMN subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    }
} catch (Exception $e) {}

try {
    $colCheckSales = $pdo->query("SHOW COLUMNS FROM sales_pos LIKE 'total_amount'")->fetch();
    if ($colCheckSales && strpos(strtolower($colCheckSales['Type'] ?? ''), 'decimal(10') !== false) {
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN dp_amount DECIMAL(15,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN change_amount DECIMAL(15,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN shipping_cost DECIMAL(15,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN payment_fee_amount DECIMAL(15,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE sales_pos MODIFY COLUMN cancelled_amount DECIMAL(15,2) DEFAULT 0.00");
    }
} catch (Exception $e) {}
?>
