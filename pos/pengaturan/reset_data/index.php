<?php
session_start();
// Pastikan hanya role admin/superadmin yang bisa akses
if (!isset($_SESSION['pos_user_id']) || !in_array($_SESSION['pos_role'], ['admin', 'superadmin'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

// Ambil data log reset
$logs = [];
try {
    $stmt = $pdo->query("SELECT * FROM reset_logs_pos ORDER BY reset_date DESC LIMIT 50");
    if ($stmt) {
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Tabel belum dibuat atau error lain, abaikan sementara
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Data Transaksi - Love Cakes POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">
    
    <?php include __DIR__ . '/../../../components/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shrink-0 shadow-sm">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-blue-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="text-lg font-black text-slate-800">Reset Data Transaksi</h1>
                    <p class="text-xs text-slate-500 font-medium hidden sm:block">Pengaturan / Bahaya / Reset Data</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col items-end mr-2">
                    <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($_SESSION['pos_username'] ?? 'Admin') ?></span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"><?= htmlspecialchars($_SESSION['pos_role'] ?? 'admin') ?></span>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold border border-blue-200">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl">
                    <div class="flex gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xl mt-0.5"></i>
                        <div>
                            <h3 class="text-rose-800 font-bold text-lg mb-1">PERINGATAN BAHAYA: HAPUS SEMUA TRANSAKSI</h3>
                            <p class="text-rose-600 text-sm mb-3">Tindakan ini akan mengosongkan seluruh riwayat penjualan, log shift, mutasi stok, kas, dan pembelian. Data transaksi tidak bisa dikembalikan setelah dihapus.</p>
                            <p class="text-rose-600 text-sm font-medium">Data Master seperti produk, kategori, pelanggan, dan user <span class="font-bold">TIDAK AKAN DIHAPUS</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Konfirmasi Reset Data</h2>
                    
                    <form id="formResetData" action="logic.php" method="POST" class="space-y-4 max-w-lg">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Ketik "RESET" untuk melanjutkan:</label>
                            <input type="text" id="confirm_word" required autocomplete="off" placeholder="Ketik tulisan RESET dengan huruf besar" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Password Anda (Verifikasi Keamanan):</label>
                            <input type="password" name="admin_password" id="admin_password" required autocomplete="off" placeholder="Masukkan password akun Anda" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" id="btnSubmitReset" class="w-full bg-rose-500 hover:bg-rose-600 text-white font-bold py-3 px-4 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-trash-can"></i> EKSEKUSI RESET DATA
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Riwayat Reset Terakhir
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-xs uppercase font-black text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3">Waktu</th>
                                    <th class="px-6 py-3">User</th>
                                    <th class="px-6 py-3">Keterangan</th>
                                    <th class="px-6 py-3">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">Belum ada riwayat reset data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-3 whitespace-nowrap font-medium text-slate-700">
                                                <?= date('d M Y, H:i', strtotime($log['reset_date'])) ?>
                                            </td>
                                            <td class="px-6 py-3 font-bold text-blue-600">
                                                <?= htmlspecialchars($log['username']) ?>
                                            </td>
                                            <td class="px-6 py-3 text-xs">
                                                <?= htmlspecialchars($log['description']) ?>
                                            </td>
                                            <td class="px-6 py-3 text-xs font-mono text-slate-500">
                                                <?= htmlspecialchars($log['ip_address']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        document.getElementById('formResetData').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const confirmWord = document.getElementById('confirm_word').value;
            const adminPass = document.getElementById('admin_password').value;
            
            if (confirmWord !== 'RESET') {
                Swal.fire({
                    icon: 'error',
                    title: 'Konfirmasi Gagal',
                    text: 'Anda harus mengetik tulisan RESET dengan huruf besar.',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }

            if (!adminPass) {
                Swal.fire('Error', 'Password harus diisi.', 'error');
                return;
            }

            Swal.fire({
                title: 'ANDA SANGAT YAKIN?',
                text: "Tindakan ini tidak bisa dibatalkan! Semua transaksi akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'YA, HAPUS SEKARANG',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu selagi database dibersihkan.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    
                    const formData = new FormData(this);
                    
                    fetch('logic.php?action=reset', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Data transaksi berhasil direset.',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message || 'Terjadi kesalahan sistem.',
                                confirmButtonColor: '#3b82f6'
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Gagal terhubung ke server', 'error');
                    });
                }
            });
        });
    </script>
</body>
</html>
