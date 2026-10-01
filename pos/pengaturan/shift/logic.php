<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';
require_once '../../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure tables & columns exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS master_shifts_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shift_name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';
$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

// 1. GET ALL MASTER SHIFTS & STATS
if ($action === 'read' || $action === 'get_panel_data') {
    try {
        // Master shifts (all, ordered by start_time)
        $stmt = $pdo->query("SELECT * FROM master_shifts_pos ORDER BY is_active DESC, start_time ASC");
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate work duration for each shift
        foreach ($shifts as &$s) {
            $t1 = strtotime('2000-01-01 ' . $s['start_time']);
            $t2 = strtotime('2000-01-01 ' . $s['end_time']);
            if ($t2 < $t1) { $t2 += 86400; } // cross midnight
            $diffHours = round(($t2 - $t1) / 3600, 1);
            $s['duration_hours'] = $diffHours;
        }
        unset($s);

        // Live Active Shifts (Currently Open)
        $liveFilter = "";
        $liveParams = [];
        if ($wh_id > 0) {
            $liveFilter = " AND (sh.warehouse_id = ? OR (sh.warehouse_id IS NULL AND ? = 1))";
            $liveParams = [$wh_id, $wh_id];
        }

        $stmtLive = $pdo->prepare("
            SELECT 
                sh.id, sh.user_id, sh.shift_id, sh.start_time, sh.start_cash, sh.warehouse_id,
                COALESCE(u.name, 'Kasir') as kasir_name,
                COALESCE(u.username, '') as kasir_username,
                COALESCE(w.name, 'Store 01') as store_name,
                COALESCE(ms.shift_name, 'Reguler / Bebas') as shift_name,
                (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
                 WHERE sp.created_at >= sh.start_time AND sp.created_at <= NOW()) as current_cash_in,
                (SELECT COALESCE(SUM(nominal), 0) FROM petty_cash_pos 
                 WHERE shift_history_id = sh.id AND jenis = 'keluar') as current_petty_cash
            FROM shifts_history_pos sh
            LEFT JOIN users_pos u ON sh.user_id = u.id
            LEFT JOIN warehouses w ON sh.warehouse_id = w.id
            LEFT JOIN master_shifts_pos ms ON sh.shift_id = ms.id
            WHERE sh.status = 'open' $liveFilter
            ORDER BY sh.start_time DESC
        ");
        $stmtLive->execute($liveParams);
        $liveShifts = $stmtLive->fetchAll(PDO::FETCH_ASSOC);

        foreach ($liveShifts as &$ls) {
            $elapsedSeconds = time() - strtotime($ls['start_time']);
            $hours = floor($elapsedSeconds / 3600);
            $mins = floor(($elapsedSeconds % 3600) / 60);
            $ls['elapsed_formatted'] = ($hours > 0 ? "{$hours} jam " : "") . "{$mins} menit";
            $ls['store_name'] = str_ireplace('gudang', 'Store', $ls['store_name']);
        }
        unset($ls);

        // Stats
        $totalMaster = count($shifts);
        $totalActive = 0;
        foreach ($shifts as $s) {
            if (!empty($s['is_active'])) $totalActive++;
        }
        $totalLive = count($liveShifts);

        echo json_encode([
            'status' => 'success',
            'shifts' => $shifts,
            'live_shifts' => $liveShifts,
            'stats' => [
                'total_master' => $totalMaster,
                'total_active' => $totalActive,
                'total_live' => $totalLive
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. SAVE MASTER SHIFT (CREATE / UPDATE)
if ($action === 'save') {
    $id = intval($_POST['id'] ?? 0);
    $shift_name = trim($_POST['shift_name'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama Shift, Jam Mulai, dan Jam Selesai wajib diisi!']);
        exit;
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE master_shifts_pos SET shift_name = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$shift_name, $start_time, $end_time, $is_active, $id]);
            $msg = "Master Shift berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO master_shifts_pos (shift_name, start_time, end_time, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$shift_name, $start_time, $end_time, $is_active]);
            $msg = "Master Shift baru berhasil ditambahkan!";
        }
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. TOGGLE ACTIVE STATUS
if ($action === 'toggle_status') {
    $id = intval($_POST['id'] ?? 0);
    $status = !empty($_POST['status']) ? 1 : 0;
    try {
        $stmt = $pdo->prepare("UPDATE master_shifts_pos SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['status' => 'success', 'message' => $status ? 'Shift diaktifkan!' : 'Shift dinonaktifkan!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 4. DELETE MASTER SHIFT (SOFT DELETE)
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    try {
        // Soft delete agar histori masa lalu tetap terjaga
        $stmt = $pdo->prepare("UPDATE master_shifts_pos SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Shift berhasil dinonaktifkan/dihapus.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit;
}

// 5. FORCE CLOSE SHIFT (ADMIN ACTION FOR ABANDONED SHIFTS)
if ($action === 'force_close_shift') {
    $shift_history_id = intval($_POST['shift_history_id'] ?? 0);
    $end_cash = floatval($_POST['end_cash'] ?? 0);

    if ($shift_history_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID Sesi Shift tidak valid!']);
        exit;
    }

    try {
        // Fetch current shift
        $stmtC = $pdo->prepare("SELECT id, status, user_id FROM shifts_history_pos WHERE id = ?");
        $stmtC->execute([$shift_history_id]);
        $curr = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$curr || $curr['status'] !== 'open') {
            echo json_encode(['status' => 'error', 'message' => 'Shift ini sudah ditutup sebelumnya atau tidak ditemukan.']);
            exit;
        }

        // Close shift
        $stmtClose = $pdo->prepare("UPDATE shifts_history_pos SET status = 'closed', end_time = NOW(), end_cash = ? WHERE id = ?");
        $stmtClose->execute([$end_cash, $shift_history_id]);

        echo json_encode(['status' => 'success', 'message' => 'Sesi shift kasir berhasil dipaksa tutup oleh Admin!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 6. GET SHIFT HISTORY LOG
if ($action === 'get_history') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $filter_wh = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : $wh_id;

    try {
        $wh_cond = "";
        $params = [$start_date, $end_date];
        if ($filter_wh > 0) {
            $wh_cond = " AND (sh.warehouse_id = ? OR (sh.warehouse_id IS NULL AND ? = 1))";
            $params[] = $filter_wh;
            $params[] = $filter_wh;
        }

        $stmt = $pdo->prepare("
            SELECT 
                sh.id, sh.start_time, sh.end_time, sh.start_cash, sh.end_cash, sh.status,
                COALESCE(u.name, 'Kasir') as kasir_name,
                COALESCE(w.name, 'Store 01') as store_name,
                COALESCE(ms.shift_name, 'Reguler / Bebas') as shift_name,
                (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
                 WHERE sp.created_at >= sh.start_time AND sp.created_at <= COALESCE(sh.end_time, NOW())) as total_cash_in,
                (SELECT COALESCE(SUM(nominal), 0) FROM petty_cash_pos 
                 WHERE shift_history_id = sh.id AND jenis = 'keluar') as total_kas_keluar
            FROM shifts_history_pos sh
            LEFT JOIN users_pos u ON sh.user_id = u.id
            LEFT JOIN warehouses w ON sh.warehouse_id = w.id
            LEFT JOIN master_shifts_pos ms ON sh.shift_id = ms.id
            WHERE DATE(sh.start_time) BETWEEN ? AND ? $wh_cond
            ORDER BY sh.start_time DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($history as &$h) {
            $h['store_name'] = str_ireplace('gudang', 'Store', $h['store_name']);
            $expected = floatval($h['start_cash']) + floatval($h['total_cash_in']) - floatval($h['total_kas_keluar']);
            $h['expected_cash'] = $expected;
            $h['selisih'] = $h['status'] === 'closed' ? (floatval($h['end_cash']) - $expected) : 0;
            $h['formatted_start'] = date('d/m/Y H:i', strtotime($h['start_time']));
            $h['formatted_end'] = $h['end_time'] ? date('d/m/Y H:i', strtotime($h['end_time'])) : 'Masih Berjalan';
        }
        unset($h);

        echo json_encode(['status' => 'success', 'history' => $history]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);