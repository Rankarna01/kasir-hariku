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
                $app_dir = basename(dirname(__DIR__));
                $folder = $is_localhost ? '/' . $app_dir . '/' : '/';
                $full_base_url = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $folder;
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
                    WHERE sh.user_id = ? AND sh.status = 'open'
                    ORDER BY sh.id DESC LIMIT 1
                ");
                $stmtShift->execute([$user['id']]);
                $openShift = $stmtShift->fetch(PDO::FETCH_ASSOC);

                if (!$openShift) {
                    // Kasir belum membuka shift -> Minta pemilihan shift dari data dinamis master_shifts_pos
                    $stmtM = $pdo->query("SELECT id, shift_name, start_time, end_time FROM master_shifts_pos WHERE is_active = 1 ORDER BY start_time ASC");
                    $masterShifts = $stmtM->fetchAll(PDO::FETCH_ASSOC);

                    $currentTime = date('H:i:s');
                    foreach ($masterShifts as &$ms) {
                        if ($ms['start_time'] <= $ms['end_time']) {
                            $ms['is_current'] = ($currentTime >= $ms['start_time'] && $currentTime <= $ms['end_time']);
                        } else {
                            $ms['is_current'] = ($currentTime >= $ms['start_time'] || $currentTime <= $ms['end_time']);
                        }
                    }
                    unset($ms);

                    $stmtCash = $pdo->query("SELECT setting_value FROM pos_settings WHERE setting_key = 'default_start_cash' LIMIT 1");
                    $defCash = floatval($stmtCash ? $stmtCash->fetchColumn() : 0);

                    if (ob_get_length()) ob_clean();
                    echo json_encode([
                        'status' => 'need_shift',
                        'message' => 'Kredensial valid. Silakan pilih shift kerja Anda.',
                        'data' => $userData,
                        'shifts' => $masterShifts,
                        'default_start_cash' => $defCash
                    ]);
                    exit;
                } else {
                    // Sudah punya shift yang sedang berjalan
                    $_SESSION['pos_active_shift_id'] = $openShift['shift_id'];
                    $_SESSION['pos_active_shift_name'] = $openShift['shift_name'] ?? 'Shift Aktif';
                    $_SESSION['pos_shift_history_id'] = $openShift['id'];
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
} elseif ($action === 'confirm_shift_login') {
    if (empty($_SESSION['pos_user_id'])) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak ditemukan. Silakan login kembali!']);
        exit;
    }

    $user_id = intval($_SESSION['pos_user_id']);
    $shift_id = intval($_POST['shift_id'] ?? 0);
    $start_cash = floatval($_POST['start_cash'] ?? 0);
    $warehouse_id = intval($_SESSION['pos_warehouse_id'] ?? 1) ?: 1;

    if ($shift_id <= 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Silakan pilih salah satu shift kerja!']);
        exit;
    }

    try {
        $stmtShiftInfo = $pdo->prepare("SELECT shift_name FROM master_shifts_pos WHERE id = ?");
        $stmtShiftInfo->execute([$shift_id]);
        $shiftName = $stmtShiftInfo->fetchColumn() ?: 'Shift Kasir';

        // Periksa apakah sudah ada record open shift sebelumnya
        $stmtCheck = $pdo->prepare("SELECT id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmtCheck->execute([$user_id]);
        $existingHistory = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingHistory) {
            $history_id = $existingHistory['id'];
            $stmtUp = $pdo->prepare("UPDATE shifts_history_pos SET shift_id = ?, start_cash = ?, warehouse_id = ? WHERE id = ?");
            $stmtUp->execute([$shift_id, $start_cash, $warehouse_id, $history_id]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO shifts_history_pos (user_id, shift_id, start_time, start_cash, status, warehouse_id) VALUES (?, ?, NOW(), ?, 'open', ?)");
            $stmtIns->execute([$user_id, $shift_id, $start_cash, $warehouse_id]);
            $history_id = $pdo->lastInsertId();
        }

        $_SESSION['pos_active_shift_id'] = $shift_id;
        $_SESSION['pos_active_shift_name'] = $shiftName;
        $_SESSION['pos_shift_history_id'] = $history_id;

        $full_base_url = defined('BASE_URL') ? BASE_URL : '';
        if (empty($full_base_url)) {
            $is_localhost = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
            $app_dir = basename(dirname(__DIR__));
            $folder = $is_localhost ? '/' . $app_dir . '/' : '/';
            $full_base_url = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $folder;
        }

        $redirect_url = rtrim($full_base_url, '/') . '/pos/kasir/';

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Shift ' . $shiftName . ' berhasil dibuka!',
            'redirect' => $redirect_url,
            'shift_name' => $shiftName
        ]);
    } catch (Exception $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuka shift: ' . $e->getMessage()]);
    }
    exit;
} elseif ($action === 'get_master_shifts') {
    try {
        $stmtM = $pdo->query("SELECT id, shift_name, start_time, end_time FROM master_shifts_pos WHERE is_active = 1 ORDER BY start_time ASC");
        $masterShifts = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        $currentTime = date('H:i:s');
        foreach ($masterShifts as &$ms) {
            if ($ms['start_time'] <= $ms['end_time']) {
                $ms['is_current'] = ($currentTime >= $ms['start_time'] && $currentTime <= $ms['end_time']);
            } else {
                $ms['is_current'] = ($currentTime >= $ms['start_time'] || $currentTime <= $ms['end_time']);
            }
        }
        unset($ms);
        echo json_encode(['status' => 'success', 'shifts' => $masterShifts]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
    exit;
}
?>