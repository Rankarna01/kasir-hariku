<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../config/database.php';

$is_json = (($_GET['format'] ?? '') === 'json');
$shift_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT sh.*, COALESCE(u.name, u.username, 'Kasir') AS cashier_name
    FROM shifts_history_pos sh
    LEFT JOIN users_pos u ON sh.user_id = u.id
    WHERE sh.id = ?
");
$stmt->execute([$shift_id]);
$shift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shift) {
    if ($is_json) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Shift tidak ditemukan.']);
        exit;
    }
    die("Shift tidak ditemukan.");
}

$store_name = 'AYAM GORENG HARIKU';
try {
    $stmt_store = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
    $store = $stmt_store ? $stmt_store->fetch(PDO::FETCH_ASSOC) : null;
    if ($store && !empty($store['store_name'])) {
        $store_name = $store['store_name'];
    }
} catch (Exception $e) {}

// Jumlah transaksi dalam shift
$stmt_guests = $pdo->prepare("SELECT COUNT(*) as total FROM sales_pos WHERE created_at BETWEEN ? AND IFNULL(?, NOW())");
$stmt_guests->execute([$shift['start_time'], $shift['end_time']]);
$total_tamu = (int)$stmt_guests->fetchColumn();

// Penjualan per metode pembayaran (dari sale_payments_pos)
$stmt_payments = $pdo->prepare("
    SELECT
        sp.payment_method,
        sp.payment_type,
        SUM(sp.amount) as total_amount
    FROM sale_payments_pos sp
    WHERE sp.created_at BETWEEN ? AND IFNULL(?, NOW())
    GROUP BY sp.payment_method, sp.payment_type
");
$stmt_payments->execute([$shift['start_time'], $shift['end_time']]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

$grouped_payments = [];
$total_tunai = 0;

foreach ($payments as $p) {
    $method = strtoupper($p['payment_method']);
    if (!isset($grouped_payments[$method])) {
        $grouped_payments[$method] = ['penjualan' => 0, 'pembatalan' => 0];
    }
    // Penjualan penuh dan pelunasan/DP dihitung sebagai penerimaan
    if (in_array($p['payment_type'], ['full', 'dp', 'pelunasan'])) {
        $grouped_payments[$method]['penjualan'] += $p['total_amount'];
        if ($method === 'CASH') $total_tunai += $p['total_amount'];
    }
}

// Pembatalan per metode pembayaran
$stmt_void_methods = $pdo->prepare("
    SELECT UPPER(s.payment_method) as payment_method, SUM(sc.amount) as void_amount, SUM(IF(sc.is_cash_deducted=1, sc.amount, 0)) as void_cash
    FROM sale_cancellations_pos sc
    JOIN sales_pos s ON sc.sale_id = s.id
    WHERE sc.created_at BETWEEN ? AND IFNULL(?, NOW())
    GROUP BY UPPER(s.payment_method)
");
$stmt_void_methods->execute([$shift['start_time'], $shift['end_time']]);
$void_methods = $stmt_void_methods->fetchAll(PDO::FETCH_ASSOC);

$total_void_cash = 0;
foreach ($void_methods as $vm) {
    $method = $vm['payment_method'];
    if (!isset($grouped_payments[$method])) {
        $grouped_payments[$method] = ['penjualan' => 0, 'pembatalan' => 0];
    }
    $grouped_payments[$method]['pembatalan'] += $vm['void_amount'];
    $total_void_cash += $vm['void_cash'];
}

// Tunai bersih = penjualan tunai - pembatalan tunai
$tunai_bersih = $total_tunai - $total_void_cash;

// Non-tunai bersih per metode
$non_tunai = [];
$total_non_tunai = 0;
foreach ($grouped_payments as $method => $data) {
    if ($method === 'CASH') continue;
    $netto = $data['penjualan'] - $data['pembatalan'];
    $total_non_tunai += $netto;
    $non_tunai[] = ['method' => $method, 'netto' => (float)$netto];
}

$total_pendapatan = $tunai_bersih + $total_non_tunai;
$is_closed = !empty($shift['end_time']);

$report = [
    'store_name' => $store_name,
    'shift_id' => (int)$shift['id'],
    'is_closed' => $is_closed,
    'cashier' => $shift['cashier_name'] ?? 'Kasir',
    'printed' => date('d/m/Y H:i'),
    'start' => date('d/m/Y H:i', strtotime($shift['start_time'])),
    'end' => $is_closed ? date('d/m/Y H:i', strtotime($shift['end_time'])) : '-',
    'transactions' => $total_tamu,
    'modal_awal' => (float)($shift['start_cash'] ?? 0),
    'tunai' => (float)$tunai_bersih,
    'non_tunai' => $non_tunai,
    'total_pendapatan' => (float)$total_pendapatan
];

if ($is_json) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $report]);
    exit;
}

function fRp($val) {
    return number_format((float)$val, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tutup Kasir #<?= $report['shift_id'] ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; background: #f1f5f9; color: #1e293b; display: flex; flex-direction: column; align-items: center; }
        .card { width: 360px; max-width: 100%; background: #fff; padding: 16px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,.08); font-size: 13px; }
        .title { text-align: center; font-weight: 700; margin-bottom: 10px; }
        .row { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; }
        .row.bold { font-weight: 700; }
        .hr { border-top: 1px dashed #64748b; margin: 8px 0; }
        .actions { display: flex; gap: 8px; margin-bottom: 14px; }
        .btn { padding: 10px 14px; border: none; border-radius: 8px; font-weight: 700; color: #fff; cursor: pointer; }
        .btn-rawbt { background: #5C2D16; }
        .btn-manual { background: #1e293b; }
        .btn-close { background: #64748b; }
        @media print { .no-print { display: none !important; } body { background: #fff; padding: 0; } .card { box-shadow: none; width: 100%; } }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn btn-rawbt" onclick="cetakRawBT()">🖨️ Cetak via RawBT</button>
        <button class="btn btn-manual" onclick="window.print()">📄 Cetak Manual (Browser)</button>
        <button class="btn btn-close" onclick="window.close()">Tutup</button>
    </div>

    <div class="card">
        <div class="title"><?= htmlspecialchars($store_name) ?><br>LAPORAN TUTUP KASIR</div>

        <div class="row"><span>Dicetak</span><span><?= $report['printed'] ?></span></div>
        <div class="row"><span>Kasir</span><span><?= htmlspecialchars($report['cashier']) ?></span></div>
        <div class="row"><span>Buka</span><span><?= $report['start'] ?></span></div>
        <div class="row"><span>Tutup</span><span><?= $report['end'] ?></span></div>
        <div class="row"><span>Jumlah transaksi</span><span><?= $report['transactions'] ?></span></div>

        <div class="hr"></div>
        <div class="row"><span>Modal Awal</span><span><?= fRp($report['modal_awal']) ?></span></div>
        <div class="row"><span>Tunai</span><span><?= fRp($report['tunai']) ?></span></div>
        <?php foreach ($report['non_tunai'] as $nt): ?>
        <div class="row"><span><?= htmlspecialchars($nt['method']) ?></span><span><?= fRp($nt['netto']) ?></span></div>
        <?php endforeach; ?>

        <div class="hr"></div>
        <div class="row bold"><span>Total Pendapatan</span><span>Rp <?= fRp($report['total_pendapatan']) ?></span></div>
    </div>

    <script src="../assets/rawbt_printer.js?v=<?= time() ?>"></script>
    <script>
        const shiftReport = <?= json_encode($report) ?>;

        function cetakRawBT() {
            RawBtPrinter.printShift(shiftReport);
        }
    </script>
</body>
</html>
