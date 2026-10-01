<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// AMBIL FILTER OUTLET JIKA AKTIF
$active_wh_id = isset($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

if ($action === 'get_arus_kas') {
    $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // Validasi format tanggal sederhana
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $end_date = date('Y-m-d');

    try {
        $wh_condition = "";
        $params_summary = [$start_date, $end_date];
        $params_today = [date('Y-m-d')];
        $params_list = [$start_date, $end_date];

        if ($active_wh_id > 0) {
            $wh_condition = " AND (p.warehouse_id = ? OR (p.warehouse_id IS NULL AND sh.warehouse_id = ?)) ";
            $params_summary[] = $active_wh_id;
            $params_summary[] = $active_wh_id;
            $params_today[] = $active_wh_id;
            $params_today[] = $active_wh_id;
            $params_list[] = $active_wh_id;
            $params_list[] = $active_wh_id;
        }

        // Summary Pengeluaran Kas (Periode Terpilih)
        $sql_sum = "
            SELECT 
                COALESCE(SUM(p.nominal), 0) AS total_keluar,
                COUNT(p.id) AS count_keluar,
                COALESCE(AVG(p.nominal), 0) AS avg_keluar
            FROM petty_cash_pos p
            LEFT JOIN shifts_history_pos sh ON p.shift_history_id = sh.id
            WHERE p.jenis = 'keluar' AND DATE(p.created_at) BETWEEN ? AND ?
            $wh_condition
        ";
        $stmt_out = $pdo->prepare($sql_sum);
        $stmt_out->execute($params_summary);
        $sum_data = $stmt_out->fetch(PDO::FETCH_ASSOC);

        // Summary Hari Ini (Today)
        $sql_today = "
            SELECT COALESCE(SUM(p.nominal), 0) AS total_today
            FROM petty_cash_pos p
            LEFT JOIN shifts_history_pos sh ON p.shift_history_id = sh.id
            WHERE p.jenis = 'keluar' AND DATE(p.created_at) = ?
            $wh_condition
        ";
        $stmt_today = $pdo->prepare($sql_today);
        $stmt_today->execute($params_today);
        $today_val = $stmt_today->fetchColumn();

        // Mutasi Detail (Hanya Pengeluaran Kas)
        $sql_list = "
            SELECT 
                p.id,
                p.warehouse_id,
                p.user_id,
                p.shift_history_id,
                p.jenis,
                p.nominal,
                p.keterangan,
                p.created_at,
                COALESCE(u.name, 'Petugas / Kasir') AS user_name,
                COALESCE(s.shift_name, 'Non-Shift') AS shift_name,
                COALESCE(w.name, 'Outlet Utama') AS warehouse_name
            FROM petty_cash_pos p
            LEFT JOIN users_pos u ON p.user_id = u.id
            LEFT JOIN shifts_history_pos sh ON p.shift_history_id = sh.id
            LEFT JOIN master_shifts_pos s ON sh.shift_id = s.id
            LEFT JOIN warehouses w ON (p.warehouse_id = w.id OR (p.warehouse_id IS NULL AND sh.warehouse_id = w.id))
            WHERE p.jenis = 'keluar' AND DATE(p.created_at) BETWEEN ? AND ?
            $wh_condition
            ORDER BY p.created_at DESC, p.id DESC
        ";
        $stmt_list = $pdo->prepare($sql_list);
        $stmt_list->execute($params_list);
        $history = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'summary' => [
                'total_keluar' => (float)($sum_data['total_keluar'] ?? 0),
                'total_today'  => (float)($today_val ?? 0),
                'count_keluar' => (int)($sum_data['count_keluar'] ?? 0),
                'avg_keluar'   => round((float)($sum_data['avg_keluar'] ?? 0))
            ],
            'data' => $history
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mengambil data pengeluaran kas: ' . $e->getMessage()
        ]);
        exit;
    }
}

if ($action === 'save_arus_kas') {
    $nominal = floatval(str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $_POST['nominal'] ?? '0'));
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    if ($nominal <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Nominal pengeluaran harus lebih besar dari Rp 0!']);
        exit;
    }

    if (empty($keterangan)) {
        echo json_encode(['status' => 'error', 'message' => 'Keterangan pengeluaran wajib diisi!']);
        exit;
    }

    $user_id = intval($_SESSION['pos_user_id'] ?? 0);
    $warehouse_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : null;
    
    // Cek shift aktif user
    $shift_history_id = 0;
    try {
        $stmt_shift = $pdo->prepare("SELECT id, warehouse_id FROM shifts_history_pos WHERE user_id = ? AND status = 'open' ORDER BY start_time DESC LIMIT 1");
        $stmt_shift->execute([$user_id]);
        $shift = $stmt_shift->fetch(PDO::FETCH_ASSOC);
        if ($shift) {
            $shift_history_id = intval($shift['id']);
            if (empty($warehouse_id) && !empty($shift['warehouse_id'])) {
                $warehouse_id = intval($shift['warehouse_id']);
            }
        }
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            INSERT INTO petty_cash_pos (warehouse_id, user_id, shift_history_id, jenis, nominal, keterangan, created_at) 
            VALUES (?, ?, ?, 'keluar', ?, ?, NOW())
        ");
        $stmt->execute([$warehouse_id, $user_id, $shift_history_id, $nominal, $keterangan]);
        
        $inserted_id = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Pengeluaran kas sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' berhasil dicatat!',
            'data' => [
                'id' => $inserted_id,
                'nominal' => $nominal,
                'keterangan' => $keterangan,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pengeluaran: ' . $e->getMessage()]);
        exit;
    }
}

if ($action === 'delete_arus_kas') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID catatan tidak valid!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM petty_cash_pos WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Catatan pengeluaran kas berhasil dihapus!']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
exit;
