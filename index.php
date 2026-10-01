<?php
session_start();
require_once __DIR__ . '/config/env.php';

// ==========================================
// Cek sesi aktif seperti di Sistem Produksi
// ==========================================
if (isset($_SESSION['pos_user_id'])) {
    // Kalau sudah login, cek role
    if (strtolower($_SESSION['pos_role']) === 'kasir' || strtolower($_SESSION['pos_role']) === 'cashier') {
        header("Location: " . BASE_URL . "pos/kasir/");
    } else {
        header("Location: " . BASE_URL . "pos/dashboard/");
    }
    exit;
} else {
    // Kalau belum login, arahkan ke halaman form login
    header("Location: " . BASE_URL . "auth/");
    exit;
}
?>
