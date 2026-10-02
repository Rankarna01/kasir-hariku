<?php
session_start();
require_once '../../../config/database.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$wh_id      = !empty($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;

$wh_cond = "";
$params = [$start_date, $end_date];
if ($wh_id > 0) {
    $wh_cond = " AND (sh.warehouse_id = ? OR (sh.warehouse_id IS NULL AND ? = 1))";
    $params[] = $wh_id;
    $params[] = $wh_id;
}

$stmtShifts = $pdo->prepare("
    SELECT 
        sh.id, sh.user_id, sh.shift_id, sh.warehouse_id, 
        sh.start_time, sh.end_time, sh.start_cash, sh.end_cash, sh.status,
        COALESCE(u.name, 'Kasir') as kasir_name,
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
$stmtShifts->execute($params);
$rawShifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

// All active staff (Kecualikan Admin/Owner Backoffice)
$stmtUsers = $pdo->query("
    SELECT u.id, u.name, r.role_name, COALESCE(w.name, 'Store 01') as store_name
    FROM users_pos u
    LEFT JOIN roles_pos r ON u.role_id = r.id
    LEFT JOIN warehouses w ON u.warehouse_id = w.id
    WHERE u.role_id != 1 
      AND LOWER(COALESCE(r.role_name, '')) NOT LIKE '%admin%' 
      AND LOWER(COALESCE(r.role_name, '')) NOT LIKE '%owner%'
    ORDER BY u.name ASC
");
$allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$employees = [];
foreach ($allUsers as $u) {
    $employees[$u['id']] = [
        'name' => $u['name'],
        'role_name' => $u['role_name'] ?? 'Kasir',
        'store_name' => str_ireplace('gudang', 'Store', $u['store_name']),
        'total_shifts' => 0,
        'total_hours' => 0,
        'total_transactions' => 0,
        'total_omset' => 0,
        'total_selisih' => 0
    ];
}

foreach ($rawShifts as $s) {
    $roleCheck = strtolower($s['role_name'] ?? '');
    if (strpos($roleCheck, 'admin') !== false || strpos($roleCheck, 'owner') !== false) {
        continue;
    }

    $uId = $s['user_id'];
    if (!isset($employees[$uId])) {
        $employees[$uId] = [
            'name' => $s['kasir_name'],
            'role_name' => $s['role_name'] ?? 'Kasir',
            'store_name' => str_ireplace('gudang', 'Store', $s['store_name']),
            'total_shifts' => 0,
            'total_hours' => 0,
            'total_transactions' => 0,
            'total_omset' => 0,
            'total_selisih' => 0
        ];
    }

    $startTime = strtotime($s['start_time']);
    $endTime = $s['end_time'] ? strtotime($s['end_time']) : time();
    $durationHours = round(($endTime - $startTime) / 3600, 1);

    $expectedCash = floatval($s['start_cash']) + floatval($s['total_cash_in']) - floatval($s['total_kas_keluar']);
    $selisih = $s['status'] === 'closed' ? (floatval($s['end_cash']) - $expectedCash) : 0;

    $employees[$uId]['total_shifts'] += 1;
    $employees[$uId]['total_hours'] += $durationHours;
    $employees[$uId]['total_transactions'] += intval($s['total_transactions']);
    $employees[$uId]['total_omset'] += floatval($s['total_omset']);
    if ($s['status'] === 'closed') {
        $employees[$uId]['total_selisih'] += $selisih;
    }
}

usort($employees, function($a, $b) {
    return $b['total_omset'] <=> $a['total_omset'];
});

function formatRp($val) {
    return number_format(floatval($val), 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Kinerja Karyawan</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 25px; font-size: 11px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; color: #1e293b; text-transform: uppercase; }
        .header p { margin: 0; font-size: 11px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; }
        th { background-color: #f1f5f9; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .footer-sig { display: flex; justify-content: space-between; margin-top: 40px; padding: 0 30px; }
        .sig-block { text-align: center; width: 200px; }
        .sig-space { height: 60px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">🖨️ Cetak Dokumen</button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; margin-left: 8px;">Tutup</button>
    </div>

    <div class="header">
        <h2>Love Cakes & Bakery - Laporan Kinerja Karyawan</h2>
        <p>Periode: <strong><?= date('d M Y', strtotime($start_date)) ?></strong> s/d <strong><?= date('d M Y', strtotime($end_date)) ?></strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th class="text-left">Nama Karyawan</th>
                <th class="text-left">Jabatan / Role</th>
                <th class="text-center">Outlet</th>
                <th class="text-center">Shift Selesai</th>
                <th class="text-center">Total Jam</th>
                <th class="text-center">Nota Transaksi</th>
                <th class="text-right">Total Omset</th>
                <th class="text-center">Selisih Kasir</th>
                <th class="text-center">Status Evaluasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $sumShifts = 0; $sumHours = 0; $sumTrans = 0; $sumOmset = 0; $sumSelisih = 0;
            foreach ($employees as $emp): 
                $sumShifts += $emp['total_shifts'];
                $sumHours += $emp['total_hours'];
                $sumTrans += $emp['total_transactions'];
                $sumOmset += $emp['total_omset'];
                $sumSelisih += $emp['total_selisih'];

                $statusLabel = 'Akurat (Pas)';
                if ($emp['total_shifts'] == 0) $statusLabel = 'Belum Bertugas';
                elseif ($emp['total_selisih'] < 0) $statusLabel = 'Minus (-)';
                elseif ($emp['total_selisih'] > 0) $statusLabel = 'Lebih (+)';
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="font-bold"><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['role_name']) ?></td>
                <td class="text-center"><?= htmlspecialchars($emp['store_name']) ?></td>
                <td class="text-center font-bold"><?= $emp['total_shifts'] ?></td>
                <td class="text-center"><?= $emp['total_hours'] ?> Jam</td>
                <td class="text-center font-bold"><?= $emp['total_transactions'] ?></td>
                <td class="text-right font-bold">Rp <?= formatRp($emp['total_omset']) ?></td>
                <td class="text-center font-bold" style="color: <?= $emp['total_selisih'] < 0 ? '#e11d48' : '#0f172a' ?>;">
                    Rp <?= formatRp($emp['total_selisih']) ?>
                </td>
                <td class="text-center"><?= $statusLabel ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-center"><?= $sumShifts ?></td>
                <td class="text-center"><?= $sumHours ?> Jam</td>
                <td class="text-center"><?= $sumTrans ?></td>
                <td class="text-right">Rp <?= formatRp($sumOmset) ?></td>
                <td class="text-center">Rp <?= formatRp($sumSelisih) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-block">
            <p>Dibuat Oleh,</p>
            <div class="sig-space"></div>
            <p><strong>( Supervisor / Admin POS )</strong></p>
        </div>
        <div class="sig-block">
            <p>Disetujui Oleh,</p>
            <div class="sig-space"></div>
            <p><strong>( Owner / Store Manager )</strong></p>
        </div>
    </div>

</body>
</html>
