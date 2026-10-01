<?php
$current_uri = $_SERVER['REQUEST_URI'];

function getNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'bg-gradient-to-r from-[#FF3870] to-[#E02360] text-white font-bold shadow-md shadow-pink-500/25 ring-1 ring-pink-400/50 active-nav-link';
    }
    return 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold transition-all duration-150';
}

function getSubNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'text-[#FF3870] font-black bg-[#FFF0F5] border-l-2 border-[#FF3870] pl-3';
    }
    return 'text-slate-500 hover:text-[#5C2D16] hover:bg-[#FFF0F5]/60 font-medium transition-all duration-150';
}

function isDropdownActive($paths, $current_uri) {
    foreach ($paths as $path) {
        if (strpos($current_uri, $path) !== false) return true;
    }
    return false;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
    #main-sidebar { font-family: 'Poppins', sans-serif; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E7D5C4; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #FF97B6; }
</style>

<aside id="main-sidebar" class="w-[268px] bg-white border-r border-[#FFE4EC] flex-col shadow-sm fixed inset-y-0 left-0 z-[70] transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex h-screen max-h-screen">

    <!-- HEADER SIDEBAR (LOGO & BRAND HARIKU) -->
    <div class="h-20 flex items-center justify-between px-5 border-b border-[#FFE4EC] shrink-0 bg-white">
        <a href="<?= BASE_URL ?>pos/dashboard/" class="flex items-center gap-3 group">
            <div class="relative w-11 h-11 rounded-2xl p-0.5 bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] shadow-sm shrink-0">
                <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[14px] bg-white">
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-[10px] font-black tracking-widest text-[#5C2D16] uppercase">Ayam Goreng</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-lg font-black tracking-tight text-[#FF3870]">HARIKU</span>
                    <span class="bg-gradient-to-r from-[#FF3870] to-[#E02360] text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow-2xs">ADMIN</span>
                </div>
            </div>
        </a>
        <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-[#FF3870] p-2 rounded-xl hover:bg-[#FFF0F5] transition-colors" title="Tutup Menu">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- FILTER OUTLET / STORE SWITCHER -->
    <?php
    if (!isset($pdo)) {
        try { require_once __DIR__ . '/../config/database.php'; } catch (Exception $e) {}
    }
    $warehouses_list = [];
    try {
        if (isset($pdo)) {
            $stmt_whs = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY id ASC");
            $warehouses_list = $stmt_whs->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
    $active_wh_id = isset($_SESSION['pos_warehouse_id']) ? intval($_SESSION['pos_warehouse_id']) : 0;
    ?>
    <div class="px-4 pt-3.5 pb-1 shrink-0 bg-white">
        <div class="bg-gradient-to-br from-[#FFF0F5] via-white to-[#FAF5F1] border border-[#FFC5D8]/70 rounded-2xl p-3 shadow-xs relative">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-[#5C2D16] flex items-center gap-1.5">
                    <i class="fa-solid fa-store text-[#FF3870]"></i> Filter Outlet
                </span>
                <span class="w-2 h-2 rounded-full bg-[#FF3870] animate-pulse" title="Live Filter Aktif"></span>
            </div>
            <div class="relative">
                <select id="globalStoreSwitcher" onchange="switchGlobalStore(this.value)" class="w-full bg-white/95 border border-[#FFC5D8] rounded-xl px-3 py-2 text-xs font-black text-[#5C2D16] outline-none focus:ring-2 focus:ring-[#FF3870] focus:border-[#FF3870] shadow-xs cursor-pointer transition-all">
                    <option value="0" <?= $active_wh_id === 0 ? 'selected' : '' ?>>🌐 Semua Outlet (Global)</option>
                    <?php foreach ($warehouses_list as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= $active_wh_id === intval($wh['id']) ? 'selected' : '' ?>>
                            🍗 <?= htmlspecialchars($wh['name']) ?> (<?= htmlspecialchars($wh['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-[9px] text-[#8C5638] font-bold mt-1.5 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-[#FF3870]"></i>
                <span>Otomatis sinkron ke seluruh laporan</span>
            </div>
        </div>
    </div>
    <script>
    function switchGlobalStore(whId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Mengganti Filter Outlet...',
                text: 'Harap tunggu, memuat ulang laporan...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
        fetch('<?= BASE_URL ?>config/auth.php?action=switch_store&warehouse_id=' + whId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal mengganti outlet', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                window.location.reload();
            });
    }
    </script>

    <!-- NAVIGATION LINKS -->
    <nav class="flex-1 min-h-0 px-3.5 py-4 space-y-1 overflow-y-auto custom-scrollbar bg-white" style="-webkit-overflow-scrolling: touch;">

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#FF3870]"></span> Menu Utama
        </div>

        <a href="<?= BASE_URL ?>pos/dashboard/" title="Dashboard" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/dashboard/', $current_uri) ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center text-lg shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Dashboard</span>
        </a>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span> Data Master
        </div>

        <a href="<?= BASE_URL ?>pos/master_gudang/" title="Store & Gudang" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_gudang/', $current_uri) ?>">
            <i class="fa-solid fa-store w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Store & Outlet</span>
        </a>

        <a href="<?= BASE_URL ?>pos/master_produk/" title="Data Produk" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_produk/', $current_uri) ?>">
            <i class="fa-solid fa-drumstick-bite w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Data Produk</span>
        </a>

        <a href="<?= BASE_URL ?>pos/master_kategori/" title="Kategori Produk" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_kategori/', $current_uri) ?>">
            <i class="fa-solid fa-tags w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Kategori Produk</span>
        </a>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span> Operasional
        </div>

        <a href="<?= BASE_URL ?>pos/opname/" title="Stok Opname" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/opname/', $current_uri) ?>">
            <i class="fa-solid fa-boxes-stacked w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Stok Opname</span>
        </a>

        <a href="<?= BASE_URL ?>pos/produk/custom_items/" title="Item & Harga Dinamis" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/produk/custom_items/', $current_uri) ?>">
            <i class="fa-solid fa-sliders w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Item & Harga Dinamis</span>
        </a>

        <!-- <a href="<?= BASE_URL ?>pos/transaksi/pembelian/" title="Pembelian & Restock" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/transaksi/pembelian/', $current_uri) ?>">
            <i class="fa-solid fa-boxes-packing w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Pembelian & Restock</span>
        </a> -->

        <?php 
            $paths = ['/pos/mitra/supplier/', '/pos/mitra/pelanggan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-mitra', 'icon-mitra')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-handshake w-5 text-center text-base shrink-0"></i> 
                    <span class="text-xs whitespace-nowrap">Mitra & Kontak</span>
                </div>
                <i id="icon-mitra" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-mitra" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/mitra/supplier/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/supplier/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Data Supplier</a>
                <a href="<?= BASE_URL ?>pos/mitra/pelanggan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/pelanggan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Data Pelanggan</a>
            </div>
        </div>

        <?php 
            $paths_shift_karyawan = ['/pos/pengaturan/shift/', '/pos/karyawan/shift/']; 
            $isActiveShiftKaryawan = isDropdownActive($paths_shift_karyawan, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-shift-karyawan', 'icon-shift-karyawan')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActiveShiftKaryawan ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-user-clock w-5 text-center text-base shrink-0"></i> 
                    <span class="text-xs whitespace-nowrap">Shift & Karyawan</span>
                </div>
                <i id="icon-shift-karyawan" class="fa-solid fa-chevron-<?= $isActiveShiftKaryawan ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-shift-karyawan" class="<?= $isActiveShiftKaryawan ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/pengaturan/shift/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/shift/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Manajemen Shift Panel</a>
                <a href="<?= BASE_URL ?>pos/karyawan/shift/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/karyawan/shift/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Jadwal Shift Karyawan</a>
            </div>
        </div>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#EC4899]"></span> Keuangan & Sales
        </div>

        <!-- <a href="<?= BASE_URL ?>pos/transaksi/arus_kas/" title="Pengeluaran Kas (Petty Cash)" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/transaksi/arus_kas/', $current_uri) ?>">
            <i class="fa-solid fa-hand-holding-dollar w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Kas Keluar (Petty Cash)</span>
        </a> -->

        <?php 
            $paths_shift = ['/pos/laporan/penjualan_shift/ringkasan_omset/', '/pos/laporan/penjualan_shift/analisis_produk/', '/pos/laporan/penjualan_shift/riwayat_transaksi/', '/pos/laporan/penjualan_shift/evaluasi_kasir/', '/pos/laporan/karyawan/'];
            $isActiveShift = isDropdownActive($paths_shift, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-penjualan-shift', 'icon-penjualan-shift')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActiveShift ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-receipt w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Penjualan & Shift</span>
                </div>
                <i id="icon-penjualan-shift" class="fa-solid fa-chevron-<?= $isActiveShift ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-penjualan-shift" class="<?= $isActiveShift ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/ringkasan_omset/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/ringkasan_omset/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Ringkasan Omset</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/analisis_produk/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/analisis_produk/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Analisis Produk</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/riwayat_transaksi/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/riwayat_transaksi/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Riwayat Transaksi</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/evaluasi_kasir/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/evaluasi_kasir/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Evaluasi Kasir</a>
                <a href="<?= BASE_URL ?>pos/laporan/karyawan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/karyawan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Laporan Karyawan</a>
            </div>
        </div>

        <?php 
            $paths = ['/pos/laporan/ringkasan/', '/pos/laporan/produk_kategori/', '/pos/laporan/pelanggan/', '/pos/laporan/pencairan/', '/pos/laporan/akuntansi/', '/pos/laporan/pihak_ketiga/', '/pos/laporan/penjualan/', '/pos/laporan/opname/', '/pos/transaksi/pembayaran_digital/', '/pos/laporan/karyawan/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-laporan', 'icon-laporan')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Laporan Toko</span>
                </div>
                <i id="icon-laporan" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-laporan" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/laporan/ringkasan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/ringkasan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Ringkasan & Jam Ramai</a>
                <a href="<?= BASE_URL ?>pos/laporan/produk_kategori/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/produk_kategori/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Analisa Produk Laku</a>
                <a href="<?= BASE_URL ?>pos/laporan/pelanggan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pelanggan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Riwayat Pelanggan</a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Penjualan</a>
                <a href="<?= BASE_URL ?>pos/laporan/opname/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/opname/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Riwayat Stok Opname</a>
                <div class="my-1 border-t border-[#FFE4EC]"></div> 
                <a href="<?= BASE_URL ?>pos/transaksi/pembayaran_digital/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/pembayaran_digital/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Rekap QRIS & E-Wallet</a>
                <a href="<?= BASE_URL ?>pos/laporan/pencairan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pencairan/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Pencairan Dana</a>
                <a href="<?= BASE_URL ?>pos/laporan/akuntansi/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/akuntansi/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Akuntansi Internal</a>
                <a href="<?= BASE_URL ?>pos/laporan/pihak_ketiga/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pihak_ketiga/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Laporan Pihak Ketiga</a>
            </div>
        </div>

        <?php 
            $paths = ['/pos/pemasaran/crm/', '/pos/pemasaran/voucher/', '/pos/pemasaran/poin/', '/pos/pemasaran/promo-items/', '/pos/pemasaran/diskon-otomatis/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-pemasaran', 'icon-pemasaran')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Pemasaran & Promo</span>
                </div>
                <i id="icon-pemasaran" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pemasaran" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/pemasaran/voucher/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/voucher/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Kelola Voucher</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/promo-items/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/promo-items/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Promo Beli X Gratis Y</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/diskon-otomatis/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/diskon-otomatis/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Diskon Otomatis</a>
                <a href="<?= BASE_URL ?>pos/pemasaran/poin-loyalitas/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/poin-loyalitas/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Poin Loyalitas</a>
            </div>
        </div>

        <?php 
            $paths = ['/pos/online/food_delivery/', '/pos/online/pembayaran/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-online', 'icon-online')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-motorcycle w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Channel Online</span>
                </div>
                <i id="icon-online" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-online" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/online/food_delivery/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/food_delivery/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Grab / GoFood / Shopee</a>
                <a href="<?= BASE_URL ?>pos/online/pembayaran/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/pembayaran/', $current_uri) ?>"><i class="fa-solid fa-circle text-[5px] opacity-50"></i> Integrasi Pembayaran</a>
            </div>
        </div>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#64748B]"></span> Sistem & Toko
        </div>

        <a href="<?= BASE_URL ?>pos/pengaturan/global/" title="Setelan Global" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/global/', $current_uri) ?>">
             <i class="fa-solid fa-sliders w-5 text-center text-base shrink-0"></i> 
             <span class="text-xs font-bold whitespace-nowrap">Setelan Global</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/printer/" title="Manajemen Printer" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/printer/', $current_uri) ?>">
             <i class="fa-solid fa-print w-5 text-center text-base shrink-0"></i> 
             <span class="text-xs font-bold whitespace-nowrap">Manajemen Printer</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/perangkat/" title="Perangkat Kasir" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/perangkat/', $current_uri) ?>">
             <i class="fa-solid fa-mobile-screen-button w-5 text-center text-base shrink-0"></i> 
             <span class="text-xs font-bold whitespace-nowrap">Perangkat Kasir</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/toko/" title="Identitas Toko" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/toko/', $current_uri) ?>">
            <i class="fa-solid fa-shop w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Identitas Toko</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/pembayaran/" title="Metode Pembayaran" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/pembayaran/', $current_uri) ?>">
            <i class="fa-solid fa-credit-card w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Metode Pembayaran</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/barcode/" title="Pengaturan Barcode" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/barcode/', $current_uri) ?>">
            <i class="fa-solid fa-barcode w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Cetak Barcode</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/shift/" title="Pengaturan Shift" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-4 <?= getNavClass('/pos/pengaturan/shift/', $current_uri) ?>">
            <i class="fa-solid fa-business-time w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs font-bold whitespace-nowrap">Pengaturan Shift</span>
        </a>

        <?php if (!empty($_SESSION['secret_reset_authorized']) || (isset($_GET['secret']) && $_GET['secret'] === 'hariku_reset_99x')): ?>
        <div class="px-3 text-[10px] font-black text-rose-500 uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Zona Berbahaya
        </div>

        <a href="<?= BASE_URL ?>pos/pengaturan/reset_data/?secret=hariku_reset_99x" title="Reset Data Transaksi" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-4 text-rose-500 hover:bg-rose-50 hover:text-rose-600 font-bold <?= strpos($current_uri, '/pos/pengaturan/reset_data/') !== false ? 'bg-rose-50 text-rose-600 shadow-sm ring-1 ring-rose-200' : '' ?>">
            <i class="fa-solid fa-triangle-exclamation w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs whitespace-nowrap">Reset Data Transaksi</span>
        </a>
        <?php endif; ?>

        <!-- USER PROFILE & LOGOUT -->
        <div class="mt-4 px-1 pb-4">
            <div class="border-t border-[#FFE4EC] pt-4">
                <div class="bg-gradient-to-br from-[#FFF0F5] to-[#FAF5F1] border border-[#FFE4EC] rounded-2xl p-3 mb-2.5 flex items-center gap-3 shadow-2xs">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center font-black text-sm shrink-0 shadow-xs">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="overflow-hidden flex-1">
                        <p class="text-xs font-black text-[#4A2311] truncate"><?= htmlspecialchars($_SESSION['pos_name'] ?? $_SESSION['pos_username'] ?? 'Administrator') ?></p>
                        <span class="inline-flex items-center gap-1 bg-white text-[#FF3870] border border-[#FFC5D8] text-[9px] font-black px-2 py-0.5 rounded-full mt-0.5 shadow-2xs uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FF3870]"></span>
                            <?= htmlspecialchars($_SESSION['pos_role'] ?? 'Admin') ?>
                        </span>
                    </div>
                </div>
                <button onclick="doLogoutAdmin()" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold transition-all border border-rose-200 hover:border-rose-300 shadow-2xs text-xs group">
                    <i class="fa-solid fa-arrow-right-from-bracket group-hover:translate-x-0.5 transition-transform"></i>
                    <span>Keluar Sistem</span>
                </button>
            </div>
        </div>

    </nav>
</aside>

<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0 duration-300"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        if (sidebar.classList.contains('-translate-x-full')) {
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => { overlay.classList.add('hidden'); }, 300);
        } else {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
            }, 10);
        }
    }

    function toggleSubmenu(menuId, iconId) {
        const menu = document.getElementById(menuId);
        const icon = document.getElementById(iconId);

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            menu.classList.add('flex');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        } else {
            menu.classList.add('hidden');
            menu.classList.remove('flex');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        }
    }

    // ===== FUNGSI LOGOUT ADMIN =====
    function doLogoutAdmin() {
        var jalankanLogout = function() {
            try {
                var dbAuth = localforage.createInstance({ name: 'pos_db', storeName: 'auth_store' });
                dbAuth.removeItem('user_session').finally(function() {
                    window.location.href = '<?= BASE_URL ?>logout_action.php';
                });
            } catch(e) {
                window.location.href = '<?= BASE_URL ?>logout_action.php';
            }
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Yakin mau Keluar?', text: 'Sesi akun admin akan diakhiri.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#FF3870', cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal'
            }).then(function(result) { if (result.isConfirmed) { jalankanLogout(); } });
        } else {
            if (confirm('Yakin mau Keluar?')) { jalankanLogout(); }
        }
    }
</script>