<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../config/database.php';

header('Content-Type: application/json');

// 1. Auto-create table pos_registered_devices if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pos_registered_devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_token VARCHAR(64) NOT NULL UNIQUE,
            device_name VARCHAR(100) NOT NULL,
            warehouse_id INT DEFAULT 1,
            registered_ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_active_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure pos_settings has defaults
    $pdo->exec("INSERT IGNORE INTO pos_settings (setting_key, setting_value) VALUES ('enable_device_restriction', '0'), ('device_reg_passcode', '889900')");
} catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';

// Helper to get setting
function getPosSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM pos_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

if ($action === 'check_status') {
    echo json_encode([
        'status' => 'success',
        'is_restricted' => false,
        'is_valid' => true,
        'message' => 'Pembatasan perangkat dinonaktifkan.'
    ]);
    exit;
}

// ----------------------------------------------------
// 2. REGISTER DEVICE (Mendaftarkan Perangkat dengan Sandi)
// ----------------------------------------------------
if ($action === 'register_device') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { $data = $_POST; }

    $device_name = trim($data['device_name'] ?? '');
    $passcode = trim($data['passcode'] ?? '');
    $warehouse_id = intval($data['warehouse_id'] ?? 1);

    if (empty($device_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama Perangkat wajib diisi!']);
        exit;
    }

    if (empty($passcode)) {
        echo json_encode(['status' => 'error', 'message' => 'Sandi Aktivasi wajib diisi!']);
        exit;
    }

    $master_passcode = getPosSetting($pdo, 'device_reg_passcode', '889900');

    if ($passcode !== $master_passcode) {
        echo json_encode(['status' => 'error', 'message' => 'Sandi Aktivasi salah! Silakan tanyakan ke Admin/Owner.']);
        exit;
    }

    try {
        $device_token = bin2hex(random_bytes(32));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO pos_registered_devices (device_token, device_name, warehouse_id, registered_ip, user_agent, is_active, last_active_at, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$device_token, $device_name, $warehouse_id, $ip, $ua]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Perangkat berhasil didaftarkan dan diaktifkan!',
            'device_token' => $device_token,
            'device_name' => $device_name,
            'warehouse_id' => $warehouse_id
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftarkan perangkat: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
