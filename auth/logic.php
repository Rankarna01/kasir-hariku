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
            // Set Session
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_role'] = $user['role_name'];
            $_SESSION['pos_name'] = $user['name'];
            $_SESSION['pos_warehouse_id'] = $user['warehouse_id'] ?? null;
            $_SESSION['pos_store_name'] = $user['store_name'] ?? 'Semua Outlet (Global)';
            $_SESSION['pos_store_code'] = $user['store_code'] ?? 'GLOBAL';

            // Setup URL
            $full_base_url = BASE_URL;

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