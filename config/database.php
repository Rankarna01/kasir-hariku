<?php
// config/database.php

require_once __DIR__ . '/env.php';

$host = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
$user = function_exists('env') ? env('DB_USER', 'root') : 'root';
$pass = function_exists('env') ? env('DB_PASS', '') : '';
$dbname = function_exists('env') ? env('DB_NAME', 'kasir-hariku') : 'kasir-hariku';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback otomatis jika database di .env tidak ditemukan
    $altDb = ($dbname === 'sim-kue') ? 'kasir-hariku' : 'sim-kue';
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
?>
