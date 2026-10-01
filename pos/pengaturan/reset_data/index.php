<?php
session_start();

$SECRET_KEY = 'hariku_reset_99x';

// Jika URL membawa secret token yang tepat, beri izin sesi
if (isset($_GET['secret']) && trim($_GET['secret']) === $SECRET_KEY) {
    $_SESSION['secret_reset_authorized'] = true;
}

// Cek apakah memiliki otorisasi token rahasia
if (empty($_SESSION['secret_reset_authorized'])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 Not Found</title></head><body style="font-family:system-ui,sans-serif;text-align:center;padding:120px 20px;background:#f8fafc;color:#334155;"><h1 style="font-size:48px;margin-bottom:8px;color:#0f172a;font-weight:900;">404</h1><p style="font-size:16px;color:#64748b;margin-bottom:24px;">Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.</p><a href="../../../pos/dashboard/" style="display:inline-block;background:#FF3870;color:#fff;padding:10px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">Kembali ke Dashboard</a></body></html>';
    exit;
}

// Jika belum login, redirect ke halaman login
if (empty($_SESSION['pos_user_id'])) {
    header("Location: ../../../auth/?redirect=" . urlencode($_SERVER['REQUEST_URI']));
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
    // Abaikan jika tabel belum ada
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance & Reset Database - POS Hariku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF3870',
                        'primary-hover': '#e0285f',
                        dark: '#4A2311',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden font-sans">
    
    <?php include __DIR__ . '/../../../components/sidebar_admin.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-primary">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-black text-slate-800">Maintenance & Reset Database</h1>
                        <span class="bg-rose-100 text-rose-700 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border border-rose-200 flex items-center gap-1">
                            <i class="fa-solid fa-user-secret"></i> Secret Mode
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium hidden sm:block">Akses Rahasia Pengosongan Data Sistem untuk Persiapan Go-Live</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col items-end mr-2">
                    <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($_SESSION['pos_username'] ?? 'Admin') ?></span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"><?= htmlspecialchars($_SESSION['pos_role'] ?? 'admin') ?></span>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center text-primary font-bold border border-rose-200 shadow-2xs">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 custom-scrollbar">
            <div class="w-full space-y-6">
                
                <div class="bg-gradient-to-r from-rose-50 to-pink-50 border-l-4 border-rose-500 p-5 rounded-r-2xl shadow-xs">
                    <div class="flex gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="text-rose-900 font-black text-base mb-1">ZONA RAHASIA: PENGOSONGAN DATA SISTEM</h3>
                            <p class="text-rose-700 text-xs leading-relaxed mb-2 font-medium">Halaman ini tersembunyi dari menu navigasi umum untuk mencegah ketidaksengajaan. Gunakan fitur ini ketika Anda ingin membersihkan data uji coba / testing sebelum toko resmi beroperasi (Go-Live).</p>
                            <div class="flex flex-wrap gap-2 text-[11px] font-bold text-rose-800">
                                <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-rose-200"><i class="fa-solid fa-check text-emerald-600 mr-1"></i>Akun Admin Tetap Aman</span>
                                <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-rose-200"><i class="fa-solid fa-check text-emerald-600 mr-1"></i>Identitas Toko Tetap Aman</span>
                                <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-rose-200"><i class="fa-solid fa-check text-emerald-600 mr-1"></i>Metode Bayar Tetap Aman</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-base font-black text-slate-800 mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-primary"></i> Pilih Cakupan Reset Data
                    </h2>
                    
                    <form id="formResetData" action="logic.php" method="POST" class="space-y-5">
                        
                        <!-- Pilihan Scope Reset -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            
                            <!-- Opsi 1: Reset Total Go-Live -->
                            <label class="relative flex flex-col p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary/50 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-50/20 has-[:checked]:shadow-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-bolt text-rose-500"></i> Reset Total (Go-Live Bersih)
                                    </span>
                                    <input type="radio" name="reset_scope" value="total" checked class="w-4 h-4 text-primary focus:ring-primary">
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Hapus semua transaksi, shift kasir, katalog produk & stok, dan member pelanggan. Cocok untuk toko siap buka perdana dari awal yang bersih.</p>
                            </label>

                            <!-- Opsi 2: Hanya Transaksi -->
                            <label class="relative flex flex-col p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary/50 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-50/20 has-[:checked]:shadow-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-cash-register text-blue-500"></i> Hanya Transaksi & Shift
                                    </span>
                                    <input type="radio" name="reset_scope" value="transaksi" class="w-4 h-4 text-primary focus:ring-primary">
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Mengosongkan seluruh penjualan, riwayat shift kasir, mutasi kas, dan log belanja. <strong class="text-slate-700">Produk & Pelanggan tetap utuh.</strong></p>
                            </label>

                            <!-- Opsi 3: Produk & Katalog Saja -->
                            <label class="relative flex flex-col p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary/50 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-50/20 has-[:checked]:shadow-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-boxes-stacked text-amber-500"></i> Master Produk & Stok Saja
                                    </span>
                                    <input type="radio" name="reset_scope" value="produk" class="w-4 h-4 text-primary focus:ring-primary">
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Menghapus seluruh daftar produk, stok gudang, dan kategori untuk input ulang dari nol.</p>
                            </label>

                            <!-- Opsi 4: Pelanggan Saja -->
                            <label class="relative flex flex-col p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-primary/50 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-50/20 has-[:checked]:shadow-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-black text-sm text-slate-800 flex items-center gap-2">
                                        <i class="fa-solid fa-users text-emerald-500"></i> Master Pelanggan Saja
                                    </span>
                                    <input type="radio" name="reset_scope" value="pelanggan" class="w-4 h-4 text-primary focus:ring-primary">
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Menghapus daftar pelanggan terdaftar dan riwayat poin loyalty member.</p>
                            </label>

                        </div>

                        <div class="border-t border-slate-100 pt-4 space-y-4 max-w-lg">
                            <div>
                                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Ketik kata "RESET" untuk konfirmasi:</label>
                                <input type="text" id="confirm_word" required autocomplete="off" placeholder="Ketik tulisan RESET dengan huruf besar" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none text-sm font-bold transition-all">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Password Admin Akun Anda:</label>
                                <input type="password" name="admin_password" id="admin_password" required autocomplete="off" placeholder="Masukkan password akun Anda untuk verifikasi" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none text-sm font-bold transition-all">
                            </div>
                            
                            <div class="pt-2">
                                <button type="submit" id="btnSubmitReset" class="w-full bg-gradient-to-r from-rose-500 to-primary hover:from-rose-600 hover:to-primary-hover text-white font-black py-3.5 px-4 rounded-xl shadow-md shadow-rose-500/20 transition-all flex items-center justify-center gap-2 text-sm active:scale-[0.99]">
                                    <i class="fa-solid fa-trash-can"></i> EKSEKUSI PENGOSONGAN DATA SEKARANG
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <h2 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400"></i> Riwayat Reset & Maintenance Terakhir
                        </h2>
                        <span class="text-[10px] text-slate-400 font-bold">50 Aktivitas Terakhir</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-400 border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3">Waktu</th>
                                    <th class="px-5 py-3">Eksekutor</th>
                                    <th class="px-5 py-3">Keterangan</th>
                                    <th class="px-5 py-3">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">Belum ada riwayat reset database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-5 py-3 whitespace-nowrap font-bold text-slate-700">
                                                <?= date('d M Y, H:i', strtotime($log['reset_date'])) ?>
                                            </td>
                                            <td class="px-5 py-3 font-bold text-primary">
                                                <?= htmlspecialchars($log['username']) ?>
                                            </td>
                                            <td class="px-5 py-3 text-slate-600">
                                                <?= htmlspecialchars($log['description']) ?>
                                            </td>
                                            <td class="px-5 py-3 font-mono text-slate-400 text-[11px]">
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
            
            const confirmWord = document.getElementById('confirm_word').value.trim();
            const adminPass = document.getElementById('admin_password').value;
            const scopeInput = document.querySelector('input[name="reset_scope"]:checked');
            const scopeVal = scopeInput ? scopeInput.value : 'total';
            
            if (confirmWord !== 'RESET') {
                Swal.fire({
                    icon: 'error',
                    title: 'Konfirmasi Tidak Valid',
                    text: 'Ketik tulisan RESET dengan huruf besar tepat untuk konfirmasi keamanan.',
                    confirmButtonColor: '#FF3870'
                });
                return;
            }

            if (!adminPass) {
                Swal.fire('Perhatian', 'Password admin harus diisi untuk verifikasi!', 'warning');
                return;
            }

            let scopeText = "Semua transaksi dan shift kasir";
            if (scopeVal === 'total') scopeText = "SEMUA TRANSAKSI, STOK, PRODUK, DAN PELANGGAN (Reset Total Go-Live)";
            else if (scopeVal === 'produk') scopeText = "Semua master produk, kategori, dan stok gudang";
            else if (scopeVal === 'pelanggan') scopeText = "Semua data pelanggan dan poin member";

            Swal.fire({
                title: 'APAKAH ANDA YAKIN?',
                html: `<div class="text-sm text-left bg-rose-50 p-3 rounded-xl border border-rose-200 text-rose-800">
                    <p class="font-black mb-1">Tindakan ini permanen dan tidak dapat dibatalkan!</p>
                    <p>Cakupan yang akan dikosongkan: <strong>${scopeText}</strong>.</p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF3870',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'YA, KOSONGKAN DATA SEKARANG',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sedang Mengosongkan Database...',
                        text: 'Mohon tunggu beberapa detik.',
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
                                title: 'Database Berhasil Direset!',
                                text: data.message,
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Reset',
                                text: data.message || 'Terjadi kesalahan sistem.',
                                confirmButtonColor: '#FF3870'
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Gagal memproses ke server.', 'error');
                    });
                }
            });
        });
    </script>
</body>
</html>
