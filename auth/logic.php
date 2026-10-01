<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0);
    session_start();
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php'; 

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action === 'login_pos') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $target_role = trim($_POST['target_role'] ?? ''); // 'admin' or 'pegawai'

    if (empty($username) || empty($password)) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password wajib diisi!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name, w.name as store_name, w.code as store_code 
            FROM users_pos u 
            JOIN roles_pos r ON u.role_id = r.id 
            LEFT JOIN warehouses w ON u.warehouse_id = w.id 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $role_name_lower = strtolower($user['role_name'] ?? '');
            $is_cashier = in_array($role_name_lower, ['kasir', 'cashier']);

            // Validasi tab target role jika ditentukan
            if ($target_role === 'admin' && $is_cashier) {
                if (ob_get_length()) ob_clean();
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Akun ini terdaftar sebagai Pegawai / Kasir. Silakan pilih tab "Pegawai (Kasir)" untuk masuk.'
                ]);
                exit;
            }

            if ($target_role === 'pegawai' && !$is_cashier) {
                if (ob_get_length()) ob_clean();
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Akun ini adalah Administrator / Backoffice. Silakan pilih tab "Administrator" untuk masuk.'
                ]);
                exit;
            }

            // Set Session
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_role'] = $user['role_name'];
            $_SESSION['pos_name'] = $user['name'];
            $_SESSION['pos_warehouse_id'] = $user['warehouse_id'] ?? null;
            $_SESSION['pos_store_name'] = $user['store_name'] ?? 'Semua Outlet (Global)';
            $_SESSION['pos_store_code'] = $user['store_code'] ?? 'GLOBAL';

            // Setup URL
            $full_base_url = defined('BASE_URL') ? BASE_URL : '';
            if (empty($full_base_url)) {
                $is_localhost = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
                $folder = $is_localhost ? '/pos-lovecakes/' : '/';
                $full_base_url = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $folder;
            }

            // 🎯 REDIRECT BERDASARKAN ROLE
            $role_name_lower = strtolower($user['role_name'] ?? '');
            if (in_array($role_name_lower, ['kasir', 'cashier'])) {
                $redirect_url = rtrim($full_base_url, '/') . '/pos/kasir/'; 
            } else {
                $redirect_url = rtrim($full_base_url, '/') . '/pos/dashboard/'; 
            }

            // Data untuk PWA (Offline)
            $userData = [
                'id' => $user['id'], 
                'username' => $user['username'], 
                'name' => $user['name'], 
                'role' => $user['role_name']
            ];

            // 🎯 Jika Login sebagai Pegawai (Kasir), periksa status shift aktif
            if ($is_cashier) {
                $stmtShift = $pdo->prepare("
                    SELECT sh.id, sh.shift_id, ms.shift_name, ms.start_time, ms.end_time 
                    FROM shifts_history_pos sh
                    LEFT JOIN master_shifts_pos ms ON sh.shift_id = ms.id
                    WHERE sh.user_id = ? AND sh.status = 'open' AND sh.shift_id > 0
                    ORDER BY sh.id DESC LIMIT 1
                ");
                $stmtShift->execute([$user['id']]);
                $openShift = $stmtShift->fetch(PDO::FETCH_ASSOC);

                if ($openShift) {
                    $_SESSION['pos_active_shift_id'] = $openShift['shift_id'];
                    $_SESSION['pos_active_shift_name'] = $openShift['shift_name'] ?? 'Shift Aktif';
                    $_SESSION['pos_shift_history_id'] = $openShift['id'];
                } else {
                    unset($_SESSION['pos_active_shift_id']);
                    unset($_SESSION['pos_active_shift_name']);
                    unset($_SESSION['pos_shift_history_id']);
                }
            }

            // REDIRECT BERDASARKAN ROLE
            if ($is_cashier) {
                $redirect_url = rtrim($full_base_url, '/') . '/pos/kasir/'; 
            } else {
                $redirect_url = rtrim($full_base_url, '/') . '/pos/dashboard/'; 
            }

            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Login berhasil!', 
                'redirect' => $redirect_url,
                'data' => $userData
            ]);
        } else {
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Username atau Password salah!']);
        }
    } catch (PDOException $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
    }
    exit;
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
    exit;
}
?>