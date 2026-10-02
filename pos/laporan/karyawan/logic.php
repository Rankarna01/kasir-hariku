<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php';
require_once '../../../config/auth.php';

$action = $_REQUEST['action'] ?? '';
$wh_id = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

// 1. GET EMPLOYEE REPORT DATA
if ($action === 'get_report' || $action === 'export_excel') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date   = $_GET['end_date']   ?? date('Y-m-d');
    $filter_wh  = !empty($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : $wh_id;
    $filter_user = !empty($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    try {
        $wh_cond = "";
        $params_shifts = [$start_date, $end_date];
        if ($filter_wh > 0) {
            $wh_cond = " AND (sh.warehouse_id = ? OR (sh.warehouse_id IS NULL AND ? = 1))";
            $params_shifts[] = $filter_wh;
            $params_shifts[] = $filter_wh;
        }

        // Fetch all relevant shifts in the period
        $stmtShifts = $pdo->prepare("
            SELECT 
                sh.id, sh.user_id, sh.shift_id, sh.warehouse_id, 
                sh.start_time, sh.end_time, sh.start_cash, sh.end_cash, sh.status,
                COALESCE(u.name, 'Kasir') as kasir_name,
                COALESCE(u.username, '') as kasir_username,
                COALESCE(r.role_name, 'Kasir') as role_name,
                COALESCE(w.name, 'Store 01') as store_name,
                COALESCE(ms.shift_name, 'Reguler') as shift_name,
                
                (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
                 LEFT JOIN payment_methods pm ON sp.payment_method = pm.name
                 LEFT JOIN sales_pos s ON sp.sale_id = s.id
                 WHERE (pm.type = 'Cash' OR sp.payment_method = 'cash' OR sp.payment_method = 'Cash') 
                 AND sp.created_at >= sh.start_time AND sp.created_at <= COALESCE(sh.end_time, NOW())
                 AND (sh.warehouse_id IS NULL OR s.warehouse_id IS NULL OR s.warehouse_id = sh.warehouse_id)) as total_cash_in,
                 
                (SELECT COALESCE(SUM(nominal), 0) FROM petty_cash_pos 
                 WHERE shift_history_id = sh.id AND jenis = 'keluar') as total_kas_keluar,

                (SELECT COUNT(DISTINCT s.id) FROM sales_pos s
                 WHERE s.created_at >= sh.start_time AND s.created_at <= COALESCE(sh.end_time, NOW())
                 AND (sh.warehouse_id IS NULL OR s.warehouse_id IS NULL OR s.warehouse_id = sh.warehouse_id)
                 AND (s.cancellation_status IS NULL OR s.cancellation_status != 'full')) as total_transactions,

                (SELECT COALESCE(SUM(s.total_amount), 0) FROM sales_pos s
                 WHERE s.created_at >= sh.start_time AND s.created_at <= COALESCE(sh.end_time, NOW())
                 AND (sh.warehouse_id IS NULL OR s.warehouse_id IS NULL OR s.warehouse_id = sh.warehouse_id)
                 AND (s.cancellation_status IS NULL OR s.cancellation_status != 'full')) as total_omset

            FROM shifts_history_pos sh
            JOIN users_pos u ON sh.user_id = u.id
            LEFT JOIN roles_pos r ON u.role_id = r.id
            LEFT JOIN warehouses w ON sh.warehouse_id = w.id
            LEFT JOIN master_shifts_pos ms ON sh.shift_id = ms.id
            WHERE DATE(sh.start_time) BETWEEN ? AND ? $wh_cond
            ORDER BY sh.start_time DESC
        ");
        $stmtShifts->execute($params_shifts);
        $rawShifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

        // Fetch list of all active staff in POS for staff roster (Kecualikan Owner/Admin Backoffice)
        $stmtAllUsers = $pdo->query("
            SELECT u.id, u.name, u.username, r.role_name, u.warehouse_id,
                   COALESCE(w.name, 'Store 01') as store_name
            FROM users_pos u
            LEFT JOIN roles_pos r ON u.role_id = r.id
            LEFT JOIN warehouses w ON u.warehouse_id = w.id
            WHERE u.role_id != 1 
              AND LOWER(COALESCE(r.role_name, '')) NOT LIKE '%admin%' 
              AND LOWER(COALESCE(r.role_name, '')) NOT LIKE '%owner%'
            ORDER BY u.name ASC
        ");
        $allUsers = $stmtAllUsers->fetchAll(PDO::FETCH_ASSOC);

        // Aggregate by user
        $employeesMap = [];
        foreach ($allUsers as $u) {
            $uId = $u['id'];
            if ($filter_user > 0 && $uId != $filter_user) continue;

            $employeesMap[$uId] = [
                'user_id' => $uId,
                'name' => $u['name'],
                'username' => $u['username'],
                'role_name' => $u['role_name'] ?? 'Kasir',
                'store_name' => str_ireplace('gudang', 'Store', $u['store_name']),
                'total_shifts' => 0,
                'total_hours' => 0,
                'total_transactions' => 0,
                'total_omset' => 0,
                'total_start_cash' => 0,
                'total_cash_in' => 0,
                'total_petty_cash' => 0,
                'total_expected_cash' => 0,
                'total_actual_cash' => 0,
                'total_selisih' => 0,
                'shifts_detail' => []
            ];
        }

        // Process shift records
        foreach ($rawShifts as $s) {
            $uId = $s['user_id'];
            if ($filter_user > 0 && $uId != $filter_user) continue;

            // Abaikan shift milik Admin/Owner dari laporan kinerja karyawan
            $roleCheck = strtolower($s['role_name'] ?? '');
            if (strpos($roleCheck, 'admin') !== false || strpos($roleCheck, 'owner') !== false) {
                continue;
            }

            // If user wasn't in allUsers for some reason, create entry
            if (!isset($employeesMap[$uId])) {
                $employeesMap[$uId] = [
                    'user_id' => $uId,
                    'name' => $s['kasir_name'],
                    'username' => $s['kasir_username'],
                    'role_name' => $s['role_name'] ?? 'Kasir',
                    'store_name' => str_ireplace('gudang', 'Store', $s['store_name']),
                    'total_shifts' => 0,
                    'total_hours' => 0,
                    'total_transactions' => 0,
                    'total_omset' => 0,
                    'total_start_cash' => 0,
                    'total_cash_in' => 0,
                    'total_petty_cash' => 0,
                    'total_expected_cash' => 0,
                    'total_actual_cash' => 0,
                    'total_selisih' => 0,
                    'shifts_detail' => []
                ];
            }

            // Calculate shift duration
            $startTime = strtotime($s['start_time']);
            $endTime = $s['end_time'] ? strtotime($s['end_time']) : time();
            $durationHours = round(($endTime - $startTime) / 3600, 1);

            // Calculations
            $startCash = floatval($s['start_cash']);
            $cashIn = floatval($s['total_cash_in']);
            $kasKeluar = floatval($s['total_kas_keluar']);
            $expectedCash = $startCash + $cashIn - $kasKeluar;
            $endCash = floatval($s['end_cash']);
            $selisih = $s['status'] === 'closed' ? ($endCash - $expectedCash) : 0;
            $transCount = intval($s['total_transactions']);
            $omsetVal = floatval($s['total_omset']);

            // Update aggregates
            $employeesMap[$uId]['total_shifts'] += 1;
            $employeesMap[$uId]['total_hours'] += $durationHours;
            $employeesMap[$uId]['total_transactions'] += $transCount;
            $employeesMap[$uId]['total_omset'] += $omsetVal;
            $employeesMap[$uId]['total_start_cash'] += $startCash;
            $employeesMap[$uId]['total_cash_in'] += $cashIn;
            $employeesMap[$uId]['total_petty_cash'] += $kasKeluar;
            $employeesMap[$uId]['total_expected_cash'] += $expectedCash;
            if ($s['status'] === 'closed') {
                $employeesMap[$uId]['total_actual_cash'] += $endCash;
                $employeesMap[$uId]['total_selisih'] += $selisih;
            }

            // Append detailed record
            $s['store_name'] = str_ireplace('gudang', 'Store', $s['store_name']);
            $s['duration_hours'] = $durationHours;
            $s['expected_cash'] = $expectedCash;
            $s['selisih'] = $selisih;
            $s['formatted_start'] = date('d/m/Y H:i', $startTime);
            $s['formatted_end'] = $s['end_time'] ? date('d/m/Y H:i', strtotime($s['end_time'])) : 'Belum Tutup';

            $employeesMap[$uId]['shifts_detail'][] = $s;
        }

        // Finalize averages and status badge for each employee
        $reportList = [];
        $totalTimOmset = 0;
        $totalTimTransactions = 0;
        $totalTimShifts = 0;
        $totalTimHours = 0;
        $totalTimSelisih = 0;

        foreach ($employeesMap as &$emp) {
            $emp['total_hours'] = round($emp['total_hours'], 1);
            $emp['avg_omset_per_shift'] = $emp['total_shifts'] > 0 ? round($emp['total_omset'] / $emp['total_shifts'], 2) : 0;
            $emp['avg_basket_size'] = $emp['total_transactions'] > 0 ? round($emp['total_omset'] / $emp['total_transactions'], 2) : 0;

            // Accuracy evaluation
            if ($emp['total_shifts'] === 0) {
                $emp['evaluation_status'] = 'Belum Bertugas';
                $emp['evaluation_badge'] = 'bg-slate-100 text-slate-500';
            } elseif ($emp['total_selisih'] == 0) {
                $emp['evaluation_status'] = 'Sangat Akurat (Pas)';
                $emp['evaluation_badge'] = 'bg-emerald-100 text-emerald-800';
            } elseif ($emp['total_selisih'] > 0) {
                $emp['evaluation_status'] = 'Lebih Kas (+)';
                $emp['evaluation_badge'] = 'bg-blue-100 text-blue-800';
            } else {
                $emp['evaluation_status'] = 'Selisih Minus (-)';
                $emp['evaluation_badge'] = 'bg-rose-100 text-rose-800';
            }

            $totalTimOmset += $emp['total_omset'];
            $totalTimTransactions += $emp['total_transactions'];
            $totalTimShifts += $emp['total_shifts'];
            $totalTimHours += $emp['total_hours'];
            $totalTimSelisih += $emp['total_selisih'];

            $reportList[] = $emp;
        }
        unset($emp);

        // Sort by total omset descending
        usort($reportList, function($a, $b) {
            return $b['total_omset'] <=> $a['total_omset'];
        });

        // Overall stats
        $kpiStats = [
            'total_employees' => count($reportList),
            'total_shifts' => $totalTimShifts,
            'total_hours' => round($totalTimHours, 1),
            'total_transactions' => $totalTimTransactions,
            'total_omset' => $totalTimOmset,
            'avg_basket_size' => $totalTimTransactions > 0 ? round($totalTimOmset / $totalTimTransactions, 2) : 0,
            'total_selisih' => $totalTimSelisih
        ];

        // JSON Response
        if ($action === 'get_report') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'stats' => $kpiStats,
                'employees' => $reportList
            ]);
            exit;
        }

        // Excel Export
        if ($action === 'export_excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Laporan_Kinerja_Karyawan_{$start_date}_to_{$end_date}.xls");

            echo "<table border='1'>";
            echo "<tr><th colspan='9' style='font-size:16px; font-weight:bold; background-color:#2563eb; color:white; padding:10px;'>LAPORAN KINERJA & EVALUASI KARYAWAN (" . date('d M Y', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date)) . ")</th></tr>";
            echo "<tr style='background-color:#f8fafc; font-weight:bold;'>
                    <th>Nama Karyawan</th><th>Role</th><th>Outlet</th><th>Shift Selesai</th><th>Total Jam Kerja</th><th>Jumlah Transaksi</th><th>Total Omset (Rp)</th><th>Rata-rata/Nota</th><th>Total Selisih Kas</th>
                  </tr>";

            foreach ($reportList as $r) {
                echo "<tr>";
                echo "<td>{$r['name']}</td>";
                echo "<td>{$r['role_name']}</td>";
                echo "<td>{$r['store_name']}</td>";
                echo "<td>{$r['total_shifts']}</td>";
                echo "<td>{$r['total_hours']} Jam</td>";
                echo "<td>{$r['total_transactions']}</td>";
                echo "<td>" . number_format($r['total_omset'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($r['avg_basket_size'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($r['total_selisih'], 0, ',', '.') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        }

    } catch (Exception $e) {
        if ($action === 'get_report') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
