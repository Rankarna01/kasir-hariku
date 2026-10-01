<?php
$current_uri = $_SERVER['REQUEST_URI'] ?? '';

function getNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'bg-[#FF3870] hover:bg-[#5C2D16] text-white font-medium shadow-md shadow-pink-500/20 ring-1 ring-pink-400/50 active-nav-link transition-all duration-150';
    }
    return 'text-[#5C2D16] hover:bg-[#FFF0F5] hover:text-[#FF3870] font-medium transition-all duration-150';
}

function getSubNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'text-[#FF3870] hover:text-[#5C2D16] font-semibold bg-[#FFF0F5] border-l-2 border-[#FF3870] pl-3 transition-all duration-150';
    }
    return 'text-[#5C2D16]/80 hover:text-[#FF3870] hover:bg-[#FFF0F5]/70 font-medium transition-all duration-150';
}

function isDropdownActive($paths, $current_uri) {
    foreach ($paths as $path) {
        if (strpos($current_uri, $path) !== false) return true;
    }
    return false;
}
?>

<style>
    #main-sidebar { 
        font-family: 'Avenir', 'Avenir Next', 'Plus Jakarta Sans', sans-serif !important; 
        font-weight: 500;
        transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E7D5C4; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #FF97B6; }

    /* Pastikan popup SweetAlert2 selalu berada di lapisan paling depan */
    div:where(.swal2-container),
    .swal2-container {
        z-index: 100000 !important;
    }

    /* Collapsed Sidebar Styling */
    #main-sidebar.sidebar-collapsed {
        width: 72px !important;
        min-width: 72px !important;
        max-width: 72px !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-text,
    #main-sidebar.sidebar-collapsed .sidebar-chevron,
    #main-sidebar.sidebar-collapsed .sidebar-section-title,
    #main-sidebar.sidebar-collapsed .outlet-filter-container,
    #main-sidebar.sidebar-collapsed .sidebar-user-info {
        display: none !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-collapsed-outlet {
        display: flex !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-collapsed-divider {
        display: block !important;
    }
    #main-sidebar.sidebar-collapsed .nav-item {
        justify-content: center !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        width: 44px !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    #main-sidebar.sidebar-collapsed .nav-item i.nav-icon {
        margin: 0 !important;
        font-size: 1.15rem !important;
    }
    #main-sidebar.sidebar-collapsed .submenu-container {
        display: none !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-header-box {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        justify-content: center !important;
    }
</style>

<aside id="main-sidebar" class="w-[260px] bg-white border-r border-[#FFE4EC] flex-col shadow-sm fixed inset-y-0 left-0 z-30 transform -translate-x-full md:relative md:translate-x-0 flex h-screen max-h-screen">

    <!-- HEADER SIDEBAR (LOGO HARIKU) -->
    <div class="h-16 flex items-center justify-between px-3.5 border-b border-[#FFE4EC] shrink-0 bg-white sidebar-header-box">
        <a href="<?= BASE_URL ?>pos/dashboard/" onclick="handleBrandClick(event)" class="flex items-center gap-2.5 overflow-hidden group" title="Ayam Goreng Hariku">
            <div class="w-10 h-10 rounded-xl p-0.5 bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] shadow-xs shrink-0 flex items-center justify-center">
                <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[10px] bg-white">
            </div>
            <div class="sidebar-text flex flex-col leading-none">
                <span class="text-[9px] font-black tracking-widest text-[#5C2D16] uppercase">Ayam Goreng</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-base font-black tracking-tight text-[#FF3870]">HARIKU</span>
                    <span class="bg-[#FF3870] text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full uppercase">ADMIN</span>
                </div>
            </div>
        </a>

        <!-- Tombol Tutup Khusus Mobile (Tidak ada duplikat hamburger) -->
        <button type="button" onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-[#FF3870] p-1.5 rounded-xl hover:bg-[#FFF0F5] transition-colors" title="Tutup Menu">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- FILTER OUTLET / STORE SWITCHER (CLEAN SELECT DROPDOWN, TANPA CARD/GRID) -->
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
    <div class="px-3 pt-2.5 pb-2 shrink-0 bg-white border-b border-[#FFE4EC]/50 outlet-filter-container">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-[#FF3870]">
                <i class="fa-solid fa-store text-xs"></i>
            </div>
            <select id="globalStoreSwitcher" onchange="switchGlobalStore(this.value)" class="w-full bg-[#FAF5F1] hover:bg-[#FFF0F5] border border-[#FFC5D8] rounded-xl pl-8 pr-7 py-2 text-xs font-bold text-[#5C2D16] outline-none focus:ring-2 focus:ring-[#FF3870] cursor-pointer transition-all appearance-none">
                <option value="0" <?= $active_wh_id === 0 ? 'selected' : '' ?>>Semua Outlet (Global)</option>
                <?php foreach ($warehouses_list as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= $active_wh_id === intval($wh['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['name']) ?> (<?= htmlspecialchars($wh['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-[#8C5638]">
                <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </div>
        </div>
    </div>

    <!-- Compact Outlet Icon when Collapsed -->
    <div class="sidebar-collapsed-outlet hidden justify-center py-2 border-b border-[#FFE4EC]/50 bg-white">
        <button type="button" onclick="toggleSidebarCollapse()" class="w-10 h-10 rounded-xl bg-[#FAF5F1] text-[#FF3870] hover:bg-[#FF3870] hover:text-white flex items-center justify-center transition-all shadow-2xs" title="Filter Outlet: <?= $active_wh_id === 0 ? 'Semua Outlet (Global)' : 'Outlet Terpilih' ?> (Klik untuk ubah)">
            <i class="fa-solid fa-store text-sm"></i>
        </button>
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
    <nav class="flex-1 min-h-0 px-2.5 py-3 space-y-1 overflow-y-auto custom-scrollbar bg-white" style="-webkit-overflow-scrolling: touch;">

        <!-- Section: Menu Utama -->
        <div class="sidebar-section-title px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#FF3870]"></span> Menu Utama
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/dashboard/" title="Dashboard" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/dashboard/', $current_uri) ?>">
            <i class="fa-solid fa-chart-pie nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Dashboard</span>
        </a>

        <!-- Section: Data Master -->
        <div class="sidebar-section-title px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-4 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span> Data Master
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/master_gudang/" title="Store & Outlet" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_gudang/', $current_uri) ?>">
            <i class="fa-solid fa-store nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Store & Outlet</span>
        </a>

        <a href="<?= BASE_URL ?>pos/master_produk/" title="Data Produk" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_produk/', $current_uri) ?>">
            <i class="fa-solid fa-drumstick-bite nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Data Produk</span>
        </a>

        <a href="<?= BASE_URL ?>pos/master_kategori/" title="Kategori Produk" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/master_kategori/', $current_uri) ?>">
            <i class="fa-solid fa-tags nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Kategori Produk</span>
        </a>

        <!-- Section: Operasional -->
        <div class="sidebar-section-title px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-4 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span> Operasional
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/opname/" title="Stok Opname" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/opname/', $current_uri) ?>">
            <i class="fa-solid fa-clipboard-check nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Stok Opname</span>
        </a>

        <a href="<?= BASE_URL ?>pos/produk/custom_items/" title="Item & Harga Dinamis" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/produk/custom_items/', $current_uri) ?>">
            <i class="fa-solid fa-sliders nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Item & Harga Dinamis</span>
        </a>

        <!-- Submenu: Mitra & Kontak -->
        <?php 
            $paths_mitra = ['/pos/mitra/supplier/', '/pos/mitra/pelanggan/']; 
            $isActiveMitra = isDropdownActive($paths_mitra, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-mitra', 'icon-mitra')" title="Mitra & Kontak" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActiveMitra ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-handshake nav-icon w-5 text-center text-base shrink-0"></i> 
                    <span class="sidebar-text text-xs whitespace-nowrap">Mitra & Kontak</span>
                </div>
                <i id="icon-mitra" class="sidebar-chevron fa-solid fa-chevron-<?= $isActiveMitra ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-mitra" class="submenu-container <?= $isActiveMitra ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/mitra/supplier/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/supplier/', $current_uri) ?>">
                    <i class="fa-solid fa-truck-ramp-box text-[11px] w-4 text-center shrink-0"></i>
                    <span>Data Supplier</span>
                </a>
                <a href="<?= BASE_URL ?>pos/mitra/pelanggan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/mitra/pelanggan/', $current_uri) ?>">
                    <i class="fa-solid fa-user-group text-[11px] w-4 text-center shrink-0"></i>
                    <span>Data Pelanggan</span>
                </a>
            </div>
        </div>

        <!-- Submenu: Shift & Karyawan -->
        <?php 
            $paths_shift_karyawan = ['/pos/pengaturan/shift/', '/pos/karyawan/shift/']; 
            $isActiveShiftKaryawan = isDropdownActive($paths_shift_karyawan, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-shift-karyawan', 'icon-shift-karyawan')" title="Shift & Karyawan" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActiveShiftKaryawan ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-user-clock nav-icon w-5 text-center text-base shrink-0"></i> 
                    <span class="sidebar-text text-xs whitespace-nowrap">Shift & Karyawan</span>
                </div>
                <i id="icon-shift-karyawan" class="sidebar-chevron fa-solid fa-chevron-<?= $isActiveShiftKaryawan ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-shift-karyawan" class="submenu-container <?= $isActiveShiftKaryawan ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/pengaturan/shift/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pengaturan/shift/', $current_uri) ?>">
                    <i class="fa-solid fa-clock text-[11px] w-4 text-center shrink-0"></i>
                    <span>Manajemen Shift Panel</span>
                </a>
                <a href="<?= BASE_URL ?>pos/karyawan/shift/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/karyawan/shift/', $current_uri) ?>">
                    <i class="fa-solid fa-calendar-check text-[11px] w-4 text-center shrink-0"></i>
                    <span>Jadwal Shift Karyawan</span>
                </a>
            </div>
        </div>

        <!-- Section: Keuangan & Sales -->
        <div class="sidebar-section-title px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-4 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#EC4899]"></span> Keuangan & Sales
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/transaksi/arus_kas/" title="Kas Keluar (Petty Cash)" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/transaksi/arus_kas/', $current_uri) ?>">
            <i class="fa-solid fa-money-bill-transfer nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Kas Keluar (Petty Cash)</span>
        </a>

        <!-- Submenu: Penjualan & Shift -->
        <?php 
            $paths_shift = ['/pos/laporan/penjualan_shift/ringkasan_omset/', '/pos/laporan/penjualan_shift/analisis_produk/', '/pos/laporan/penjualan_shift/riwayat_transaksi/', '/pos/laporan/penjualan_shift/evaluasi_kasir/', '/pos/laporan/karyawan/'];
            $isActiveShift = isDropdownActive($paths_shift, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-penjualan-shift', 'icon-penjualan-shift')" title="Penjualan & Shift" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActiveShift ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-receipt nav-icon w-5 text-center text-base shrink-0"></i>
                    <span class="sidebar-text text-xs whitespace-nowrap">Penjualan & Shift</span>
                </div>
                <i id="icon-penjualan-shift" class="sidebar-chevron fa-solid fa-chevron-<?= $isActiveShift ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-penjualan-shift" class="submenu-container <?= $isActiveShift ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/ringkasan_omset/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/ringkasan_omset/', $current_uri) ?>">
                    <i class="fa-solid fa-chart-line text-[11px] w-4 text-center shrink-0"></i>
                    <span>Ringkasan Omset</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/analisis_produk/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/analisis_produk/', $current_uri) ?>">
                    <i class="fa-solid fa-chart-pie text-[11px] w-4 text-center shrink-0"></i>
                    <span>Analisis Produk</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/riwayat_transaksi/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/riwayat_transaksi/', $current_uri) ?>">
                    <i class="fa-solid fa-clock-rotate-left text-[11px] w-4 text-center shrink-0"></i>
                    <span>Riwayat Transaksi</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan_shift/evaluasi_kasir/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan_shift/evaluasi_kasir/', $current_uri) ?>">
                    <i class="fa-solid fa-user-check text-[11px] w-4 text-center shrink-0"></i>
                    <span>Evaluasi Kasir</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/karyawan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/karyawan/', $current_uri) ?>">
                    <i class="fa-solid fa-id-badge text-[11px] w-4 text-center shrink-0"></i>
                    <span>Laporan Karyawan</span>
                </a>
            </div>
        </div>

        <!-- Submenu: Laporan Toko -->
        <?php 
            $paths_laporan = ['/pos/laporan/ringkasan/', '/pos/laporan/produk_kategori/', '/pos/laporan/pelanggan/', '/pos/laporan/pencairan/', '/pos/laporan/akuntansi/', '/pos/laporan/pihak_ketiga/', '/pos/laporan/penjualan/', '/pos/laporan/opname/', '/pos/transaksi/pembayaran_digital/', '/pos/laporan/karyawan/']; 
            $isActiveLaporan = isDropdownActive($paths_laporan, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-laporan', 'icon-laporan')" title="Laporan Toko" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActiveLaporan ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-chart-column nav-icon w-5 text-center text-base shrink-0"></i>
                    <span class="sidebar-text text-xs whitespace-nowrap">Laporan Toko</span>
                </div>
                <i id="icon-laporan" class="sidebar-chevron fa-solid fa-chevron-<?= $isActiveLaporan ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-laporan" class="submenu-container <?= $isActiveLaporan ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/laporan/ringkasan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/ringkasan/', $current_uri) ?>">
                    <i class="fa-solid fa-stopwatch text-[11px] w-4 text-center shrink-0"></i>
                    <span>Ringkasan & Jam Ramai</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/produk_kategori/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/produk_kategori/', $current_uri) ?>">
                    <i class="fa-solid fa-ranking-star text-[11px] w-4 text-center shrink-0"></i>
                    <span>Analisa Produk Laku</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/pelanggan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pelanggan/', $current_uri) ?>">
                    <i class="fa-solid fa-address-book text-[11px] w-4 text-center shrink-0"></i>
                    <span>Riwayat Pelanggan</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/penjualan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/penjualan/', $current_uri) ?>">
                    <i class="fa-solid fa-receipt text-[11px] w-4 text-center shrink-0"></i>
                    <span>Penjualan</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/opname/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/opname/', $current_uri) ?>">
                    <i class="fa-solid fa-boxes-stacked text-[11px] w-4 text-center shrink-0"></i>
                    <span>Riwayat Stok Opname</span>
                </a>
                <a href="<?= BASE_URL ?>pos/transaksi/pembayaran_digital/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/pembayaran_digital/', $current_uri) ?>">
                    <i class="fa-solid fa-qrcode text-[11px] w-4 text-center shrink-0"></i>
                    <span>Rekap QRIS & E-Wallet</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/pencairan/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pencairan/', $current_uri) ?>">
                    <i class="fa-solid fa-money-bill-wave text-[11px] w-4 text-center shrink-0"></i>
                    <span>Pencairan Dana</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/akuntansi/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/akuntansi/', $current_uri) ?>">
                    <i class="fa-solid fa-scale-balanced text-[11px] w-4 text-center shrink-0"></i>
                    <span>Akuntansi Internal</span>
                </a>
                <a href="<?= BASE_URL ?>pos/laporan/pihak_ketiga/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/laporan/pihak_ketiga/', $current_uri) ?>">
                    <i class="fa-solid fa-handshake text-[11px] w-4 text-center shrink-0"></i>
                    <span>Laporan Pihak Ketiga</span>
                </a>
            </div>
        </div>

        <!-- Submenu: Pemasaran & Promo -->
        <?php 
            $paths_promo = ['/pos/pemasaran/crm/', '/pos/pemasaran/voucher/', '/pos/pemasaran/poin/', '/pos/pemasaran/promo-items/', '/pos/pemasaran/diskon-otomatis/']; 
            $isActivePromo = isDropdownActive($paths_promo, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-pemasaran', 'icon-pemasaran')" title="Pemasaran & Promo" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActivePromo ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn nav-icon w-5 text-center text-base shrink-0"></i>
                    <span class="sidebar-text text-xs whitespace-nowrap">Pemasaran & Promo</span>
                </div>
                <i id="icon-pemasaran" class="sidebar-chevron fa-solid fa-chevron-<?= $isActivePromo ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-pemasaran" class="submenu-container <?= $isActivePromo ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/pemasaran/voucher/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/voucher/', $current_uri) ?>">
                    <i class="fa-solid fa-ticket text-[11px] w-4 text-center shrink-0"></i>
                    <span>Kelola Voucher</span>
                </a>
                <a href="<?= BASE_URL ?>pos/pemasaran/promo-items/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/promo-items/', $current_uri) ?>">
                    <i class="fa-solid fa-gift text-[11px] w-4 text-center shrink-0"></i>
                    <span>Promo Beli X Gratis Y</span>
                </a>
                <a href="<?= BASE_URL ?>pos/pemasaran/diskon-otomatis/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/diskon-otomatis/', $current_uri) ?>">
                    <i class="fa-solid fa-percent text-[11px] w-4 text-center shrink-0"></i>
                    <span>Diskon Otomatis</span>
                </a>
                <a href="<?= BASE_URL ?>pos/pemasaran/poin-loyalitas/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/pemasaran/poin-loyalitas/', $current_uri) ?>">
                    <i class="fa-solid fa-award text-[11px] w-4 text-center shrink-0"></i>
                    <span>Poin Loyalitas</span>
                </a>
            </div>
        </div>

        <!-- Submenu: Channel Online -->
        <?php 
            $paths_online = ['/pos/online/food_delivery/', '/pos/online/pembayaran/']; 
            $isActiveOnline = isDropdownActive($paths_online, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="handleMenuClick('sub-online', 'icon-online')" title="Channel Online" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all <?= $isActiveOnline ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-motorcycle nav-icon w-5 text-center text-base shrink-0"></i>
                    <span class="sidebar-text text-xs whitespace-nowrap">Channel Online</span>
                </div>
                <i id="icon-online" class="sidebar-chevron fa-solid fa-chevron-<?= $isActiveOnline ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-online" class="submenu-container <?= $isActiveOnline ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-6 pr-1 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/online/food_delivery/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/food_delivery/', $current_uri) ?>">
                    <i class="fa-solid fa-motorcycle text-[11px] w-4 text-center shrink-0"></i>
                    <span>Grab / GoFood / Shopee</span>
                </a>
                <a href="<?= BASE_URL ?>pos/online/pembayaran/" class="flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/online/pembayaran/', $current_uri) ?>">
                    <i class="fa-solid fa-credit-card text-[11px] w-4 text-center shrink-0"></i>
                    <span>Integrasi Pembayaran</span>
                </a>
            </div>
        </div>

        <!-- Section: Sistem & Toko -->
        <div class="sidebar-section-title px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-4 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#64748B]"></span> Sistem & Toko
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/pengaturan/global/" title="Setelan Global" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/global/', $current_uri) ?>">
             <i class="fa-solid fa-sliders nav-icon w-5 text-center text-base shrink-0"></i> 
             <span class="sidebar-text text-xs font-bold whitespace-nowrap">Setelan Global</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/printer/" title="Manajemen Printer" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/printer/', $current_uri) ?>">
             <i class="fa-solid fa-print nav-icon w-5 text-center text-base shrink-0"></i> 
             <span class="sidebar-text text-xs font-bold whitespace-nowrap">Manajemen Printer</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/perangkat/" title="Perangkat Kasir" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/perangkat/', $current_uri) ?>">
             <i class="fa-solid fa-mobile-screen-button nav-icon w-5 text-center text-base shrink-0"></i> 
             <span class="sidebar-text text-xs font-bold whitespace-nowrap">Perangkat Kasir</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/toko/" title="Identitas Toko" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/toko/', $current_uri) ?>">
            <i class="fa-solid fa-shop nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Identitas Toko</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/pembayaran/" title="Metode Pembayaran" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/pembayaran/', $current_uri) ?>">
            <i class="fa-solid fa-credit-card nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Metode Pembayaran</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/barcode/" title="Cetak Barcode" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/pengaturan/barcode/', $current_uri) ?>">
            <i class="fa-solid fa-barcode nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Cetak Barcode</span>
        </a>

        <a href="<?= BASE_URL ?>pos/pengaturan/shift/" title="Pengaturan Shift" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all mb-2 <?= getNavClass('/pos/pengaturan/shift/', $current_uri) ?>">
            <i class="fa-solid fa-business-time nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs font-bold whitespace-nowrap">Pengaturan Shift</span>
        </a>

        <?php if (!empty($_SESSION['secret_reset_authorized']) || (isset($_GET['secret']) && $_GET['secret'] === 'hariku_reset_99x')): ?>
        <div class="sidebar-section-title px-3 text-[10px] font-black text-rose-500 uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Zona Berbahaya
        </div>
        <div class="sidebar-collapsed-divider hidden border-t border-[#FFE4EC] my-1.5"></div>

        <a href="<?= BASE_URL ?>pos/pengaturan/reset_data/?secret=hariku_reset_99x" title="Reset Data Transaksi" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-4 text-rose-500 hover:bg-rose-50 hover:text-rose-600 font-bold <?= strpos($current_uri, '/pos/pengaturan/reset_data/') !== false ? 'bg-rose-50 text-rose-600 shadow-sm ring-1 ring-rose-200' : '' ?>">
            <i class="fa-solid fa-triangle-exclamation nav-icon w-5 text-center text-base shrink-0"></i> 
            <span class="sidebar-text text-xs whitespace-nowrap">Reset Data Transaksi</span>
        </a>
        <?php endif; ?>

        <!-- USER PROFILE & LOGOUT -->
        <div class="mt-3 px-1 pb-4">
            <div class="border-t border-[#FFE4EC] pt-3">
                <div class="sidebar-user-info bg-gradient-to-br from-[#FFF0F5] to-[#FAF5F1] border border-[#FFE4EC] rounded-2xl p-2.5 mb-2 flex items-center gap-2.5 shadow-2xs">
                    <div class="w-8 h-8 rounded-xl bg-[#FF3870] text-white flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="overflow-hidden flex-1 leading-tight">
                        <p class="text-xs font-bold text-[#4A2311] truncate"><?= htmlspecialchars($_SESSION['pos_name'] ?? $_SESSION['pos_username'] ?? 'Administrator') ?></p>
                        <span class="text-[9px] font-bold text-[#FF3870] uppercase">
                            <?= htmlspecialchars($_SESSION['pos_role'] ?? 'Admin') ?>
                        </span>
                    </div>
                </div>
                <button type="button" onclick="doLogoutAdmin()" title="Keluar Sistem" class="nav-item w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold transition-all border border-rose-200 hover:border-rose-300 shadow-2xs text-xs cursor-pointer group">
                    <i class="fa-solid fa-arrow-right-from-bracket group-hover:translate-x-0.5 transition-transform text-sm"></i>
                    <span class="sidebar-text">Keluar</span>
                </button>
            </div>
        </div>

    </nav>
</aside>

<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-[60] hidden md:hidden backdrop-blur-sm transition-opacity opacity-0 duration-300"></div>

<script>
    // ===== INIT SIDEBAR STATE FROM LOCALSTORAGE =====
    (function initSidebar() {
        try {
            var isCollapsed = localStorage.getItem('hariku_sidebar_collapsed') === 'true';
            var sidebar = document.getElementById('main-sidebar');
            if (isCollapsed && window.innerWidth >= 768 && sidebar) {
                sidebar.classList.add('sidebar-collapsed');
            }
        } catch(e) {}
    })();

    // ===== TOGGLE COLLAPSED (TEXT + ICON vs ICON ONLY) =====
    function toggleSidebarCollapse() {
        var sidebar = document.getElementById('main-sidebar');
        if (!sidebar) return;
        
        // If on mobile screen (<768px), hamburger closes or opens drawer
        if (window.innerWidth < 768) {
            toggleSidebar();
            return;
        }

        var isNowCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('hariku_sidebar_collapsed', isNowCollapsed ? 'true' : 'false');
        } catch(e) {}
    }

    // ===== TOGGLE MOBILE SIDEBAR OVERLAY =====
    function toggleSidebar() {
        var sidebar = document.getElementById('main-sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (!sidebar) return;

        // If on desktop screen, toggle collapse (Icon Only vs Text+Icon)
        if (window.innerWidth >= 768) {
            toggleSidebarCollapse();
            return;
        }

        // Mobile drawer behavior
        sidebar.classList.toggle('-translate-x-full');
        if (sidebar.classList.contains('-translate-x-full')) {
            if (overlay) {
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0');
                setTimeout(function() { overlay.classList.add('hidden'); }, 300);
            }
        } else {
            if (overlay) {
                overlay.classList.remove('hidden');
                setTimeout(function() {
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                }, 10);
            }
        }
    }

    // ===== SUBMENU HANDLER (EXPANDS SIDEBAR IF CLICKED IN COLLAPSED MODE) =====
    function handleMenuClick(menuId, iconId) {
        var sidebar = document.getElementById('main-sidebar');
        if (sidebar && sidebar.classList.contains('sidebar-collapsed')) {
            // Un-collapse when clicking a submenu parent in collapsed mode
            toggleSidebarCollapse();
            setTimeout(function() {
                toggleSubmenu(menuId, iconId);
            }, 100);
            return;
        }
        toggleSubmenu(menuId, iconId);
    }

    function toggleSubmenu(menuId, iconId) {
        var menu = document.getElementById(menuId);
        var icon = document.getElementById(iconId);
        if (!menu) return;

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            menu.classList.add('flex');
            if (icon) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            }
        } else {
            menu.classList.add('hidden');
            menu.classList.remove('flex');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }
    }

    // ===== BRAND CLICK (EXPANDS IF CURRENTLY COLLAPSED) =====
    function handleBrandClick(e) {
        var sidebar = document.getElementById('main-sidebar');
        if (sidebar && sidebar.classList.contains('sidebar-collapsed')) {
            e.preventDefault();
            toggleSidebarCollapse();
        }
    }

    // ===== LOGOUT ADMIN =====
    function doLogoutAdmin() {
        var sidebar = document.getElementById('main-sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
            if (typeof toggleSidebar === 'function') toggleSidebar();
        }

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