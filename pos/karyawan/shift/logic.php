<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';
require_once '../../../config/auth.php';

// Auto create table employee_shift_schedules_pos
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_shift_schedules_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        warehouse_id INT NOT NULL DEFAULT 1,
        shift_id INT NOT NULL,
        schedule_date DATE NOT NULL,
        status ENUM('scheduled', 'present', 'completed', 'absent', 'off', 'swapped') DEFAULT 'scheduled',
        check_in_time DATETIME NULL,
        check_out_time DATETIME NULL,
        notes VARCHAR(255) NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_date (user_id, schedule_date),
        INDEX (user_id),
        INDEX (warehouse_id),
        INDEX (shift_id),
        INDEX (schedule_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

$action = $_REQUEST['action'] ?? '';
$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;
$admin_user_id = $_SESSION['pos_user_id'] ?? 1;

// 1. GET MASTER DATA (USERS, SHIFTS, WAREHOUSES)
if ($action === 'get_master_data') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        // Active employees / users
        $stmtUsers = $pdo->query("
            SELECT u.id, u.name, u.username, r.role_name, u.warehouse_id,
                   COALESCE(w.name, 'Store 01') as store_name
            FROM users_pos u 
            JOIN roles_pos r ON u.role_id = r.id 
            LEFT JOIN warehouses w ON u.warehouse_id = w.id
            ORDER BY r.role_name ASC, u.name ASC
        ");
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            $u['store_name'] = str_ireplace('gudang', 'Store', $u['store_name']);
        }
        unset($u);

        // Warehouses / Stores
        $stmtW = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY id ASC");
        $warehouses = $stmtW->fetchAll(PDO::FETCH_ASSOC);
        foreach ($warehouses as &$w) {
            $w['name'] = str_ireplace('gudang', 'Store', $w['name']);
        }
        unset($w);

        // Active Master Shifts
        $stmtShifts = $pdo->query("SELECT id, shift_name, start_time, end_time FROM master_shifts_pos WHERE is_active = 1 ORDER BY start_time ASC");
        $shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'users' => $users,
            'warehouses' => $warehouses,
            'shifts' => $shifts
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. GET SCHEDULES (ROSTER / TABLE DATA)
if ($action === 'get_schedules') {
    header('Content-Type: application/json; charset=utf-8');
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
    $end_date   = $_GET['end_date']   ?? date('Y-m-d', strtotime('sunday this week'));
    $filter_wh  = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : $wh_id;
    $filter_user = !empty($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    try {
        $conds = ["es.schedule_date BETWEEN ? AND ?"];
        $params = [$start_date, $end_date];

        if ($filter_wh > 0) {
            $conds[] = "(es.warehouse_id = ? OR (es.warehouse_id IS NULL AND ? = 1))";
            $params[] = $filter_wh;
            $params[] = $filter_wh;
        }

        if ($filter_user > 0) {
            $conds[] = "es.user_id = ?";
            $params[] = $filter_user;
        }

        $whereClause = implode(" AND ", $conds);

        $stmt = $pdo->prepare("
            SELECT 
                es.id, es.user_id, es.warehouse_id, es.shift_id, es.schedule_date, 
                es.status, es.notes, es.check_in_time, es.check_out_time,
                u.name as employee_name, u.username as employee_username, r.role_name,
                COALESCE(w.name, 'Store 01') as store_name,
                ms.shift_name, ms.start_time, ms.end_time
            FROM employee_shift_schedules_pos es
            JOIN users_pos u ON es.user_id = u.id
            LEFT JOIN roles_pos r ON u.role_id = r.id
            LEFT JOIN warehouses w ON es.warehouse_id = w.id
            LEFT JOIN master_shifts_pos ms ON es.shift_id = ms.id
            WHERE $whereClause
            ORDER BY es.schedule_date ASC, ms.start_time ASC, u.name ASC
        ");
        $stmt->execute($params);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($schedules as &$sc) {
            $sc['store_name'] = str_ireplace('gudang', 'Store', $sc['store_name']);
            $sc['formatted_date'] = date('d/m/Y', strtotime($sc['schedule_date']));
            $sc['day_name'] = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ][date('l', strtotime($sc['schedule_date']))] ?? date('l', strtotime($sc['schedule_date']));
            $sc['start_time_short'] = $sc['start_time'] ? substr($sc['start_time'], 0, 5) : '-';
            $sc['end_time_short']   = $sc['end_time']   ? substr($sc['end_time'], 0, 5) : '-';
        }
        unset($sc);

        // Stats calculation
        $today = date('Y-m-d');
        $totalScheduled = count($schedules);
        $todayPresent = 0;
        $todayScheduled = 0;
        $offCount = 0;

        foreach ($schedules as $s) {
            if ($s['schedule_date'] === $today) {
                if (in_array($s['status'], ['present', 'completed'])) $todayPresent++;
                if ($s['status'] !== 'off') $todayScheduled++;
            }
            if ($s['status'] === 'off') $offCount++;
        }

        echo json_encode([
            'status' => 'success',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'schedules' => $schedules,
            'stats' => [
                'total_scheduled' => $totalScheduled,
                'today_present' => $todayPresent,
                'today_scheduled' => $todayScheduled,
                'off_count' => $offCount
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. SAVE / ASSIGN SCHEDULE (SINGLE OR MULTI-DATE)
if ($action === 'save_schedule') {
    header('Content-Type: application/json; charset=utf-8');
    $user_id = intval($_POST['user_id'] ?? 0);
    $shift_id = intval($_POST['shift_id'] ?? 0);
    $warehouse_id = intval($_POST['warehouse_id'] ?? 1);
    $status = $_POST['status'] ?? 'scheduled';
    $notes = trim($_POST['notes'] ?? '');
    
    // Dates can be a single date string or JSON array
    $dates = [];
    if (!empty($_POST['dates'])) {
        $decoded = json_decode($_POST['dates'], true);
        if (is_array($decoded)) {
            $dates = $decoded;
        } else {
            $dates = [$_POST['dates']];
        }
    } elseif (!empty($_POST['schedule_date'])) {
        $dates = [$_POST['schedule_date']];
    }

    if ($user_id <= 0 || $shift_id <= 0 || empty($dates)) {
        echo json_encode(['status' => 'error', 'message' => 'Karyawan, Shift, dan Tanggal Penugasan wajib dipilih!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO employee_shift_schedules_pos 
            (user_id, warehouse_id, shift_id, schedule_date, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                warehouse_id = VALUES(warehouse_id),
                shift_id = VALUES(shift_id),
                status = VALUES(status),
                notes = VALUES(notes),
                created_by = VALUES(created_by)
        ");

        $insertedCount = 0;
        foreach ($dates as $dateStr) {
            $dateClean = trim($dateStr);
            if (!empty($dateClean)) {
                $stmt->execute([$user_id, $warehouse_id, $shift_id, $dateClean, $status, $notes, $admin_user_id]);
                $insertedCount++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil menjadwalkan shift untuk {$insertedCount} tanggal penugasan!"
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

// 4. QUICK UPDATE STATUS
if ($action === 'update_status') {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'scheduled');
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID jadwal tidak valid!']);
        exit;
    }

    try {
        if ($notes !== null) {
            $stmt = $pdo->prepare("UPDATE employee_shift_schedules_pos SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE employee_shift_schedules_pos SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Status jadwal shift berhasil diubah!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 5. DELETE SCHEDULE
if ($action === 'delete_schedule') {
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID jadwal tidak valid!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM employee_shift_schedules_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Jadwal shift berhasil dihapus!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 6. COPY SCHEDULE FROM PREVIOUS WEEK TO TARGET WEEK
if ($action === 'copy_week') {
    header('Content-Type: application/json; charset=utf-8');
    $source_week_start = $_POST['source_week_start'] ?? '';
    $target_week_start = $_POST['target_week_start'] ?? '';

    if (empty($source_week_start) || empty($target_week_start)) {
        echo json_encode(['status' => 'error', 'message' => 'Periode minggu asal dan target harus ditentukan!']);
        exit;
    }

    try {
        $sourceEnd = date('Y-m-d', strtotime($source_week_start . ' +6 days'));
        $dayDiff = (strtotime($target_week_start) - strtotime($source_week_start)) / 86400;

        $stmtFetch = $pdo->prepare("
            SELECT user_id, warehouse_id, shift_id, schedule_date, status, notes
            FROM employee_shift_schedules_pos
            WHERE schedule_date BETWEEN ? AND ?
        ");
        $stmtFetch->execute([$source_week_start, $sourceEnd]);
        $rows = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data jadwal pada minggu asal yang dipilih.']);
            exit;
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO employee_shift_schedules_pos 
            (user_id, warehouse_id, shift_id, schedule_date, status, notes, created_by)
            VALUES (?, ?, ?, ?, 'scheduled', ?, ?)
            ON DUPLICATE KEY UPDATE 
                warehouse_id = VALUES(warehouse_id),
                shift_id = VALUES(shift_id),
                status = 'scheduled',
                notes = VALUES(notes),
                created_by = VALUES(created_by)
        ");

        $copied = 0;
        foreach ($rows as $r) {
            $newDate = date('Y-m-d', strtotime($r['schedule_date'] . " +{$dayDiff} days"));
            $stmtInsert->execute([$r['user_id'], $r['warehouse_id'], $r['shift_id'], $newDate, $r['notes'], $admin_user_id]);
            $copied++;
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil menyalin {$copied} penugasan shift ke minggu target!"
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 7. EXPORT EXCEL
if ($action === 'export_excel') {
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
    $end_date   = $_GET['end_date']   ?? date('Y-m-d', strtotime('sunday this week'));
    $filter_wh  = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : $wh_id;

    $conds = ["es.schedule_date BETWEEN ? AND ?"];
    $params = [$start_date, $end_date];
    if ($filter_wh > 0) {
        $conds[] = "(es.warehouse_id = ? OR (es.warehouse_id IS NULL AND ? = 1))";
        $params[] = $filter_wh;
        $params[] = $filter_wh;
    }
    $whereClause = implode(" AND ", $conds);

    $stmt = $pdo->prepare("
        SELECT 
            es.schedule_date, u.name as employee_name, r.role_name,
            COALESCE(w.name, 'Store 01') as store_name,
            ms.shift_name, ms.start_time, ms.end_time, es.status, es.notes
        FROM employee_shift_schedules_pos es
        JOIN users_pos u ON es.user_id = u.id
        LEFT JOIN roles_pos r ON u.role_id = r.id
        LEFT JOIN warehouses w ON es.warehouse_id = w.id
        LEFT JOIN master_shifts_pos ms ON es.shift_id = ms.id
        WHERE $whereClause
        ORDER BY es.schedule_date ASC, ms.start_time ASC, u.name ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Jadwal_Shift_Karyawan_{$start_date}_to_{$end_date}.xls");

    echo "<table border='1'>";
    echo "<tr><th colspan='8' style='font-size:16px; font-weight:bold; background-color:#2563eb; color:white; padding:10px;'>JADWAL SHIFT KARYAWAN (" . date('d M Y', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date)) . ")</th></tr>";
    echo "<tr style='background-color:#f1f5f9; font-weight:bold;'>
            <th>Tanggal</th><th>Hari</th><th>Karyawan</th><th>Role</th><th>Outlet</th><th>Shift</th><th>Jam Kerja</th><th>Status</th>
          </tr>";

    $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];

    foreach ($rows as $r) {
        $dayName = $days[date('l', strtotime($r['schedule_date']))] ?? date('l', strtotime($r['schedule_date']));
        $hours = substr($r['start_time'] ?? '', 0, 5) . ' - ' . substr($r['end_time'] ?? '', 0, 5);
        echo "<tr>";
        echo "<td>{$r['schedule_date']}</td>";
        echo "<td>{$dayName}</td>";
        echo "<td>{$r['employee_name']}</td>";
        echo "<td>{$r['role_name']}</td>";
        echo "<td>{$r['store_name']}</td>";
        echo "<td>{$r['shift_name']}</td>";
        echo "<td>{$hours}</td>";
        echo "<td>" . strtoupper($r['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
