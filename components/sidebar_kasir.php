<?php
$current_uri = $_SERVER['REQUEST_URI'];

function getNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'bg-[#FF3870] hover:bg-[#5C2D16] text-white font-medium shadow-md shadow-pink-500/20 ring-1 ring-pink-400/50 active-nav-link transition-all duration-150';
    }
    return 'text-[#5C2D16] hover:bg-[#FFF0F5] hover:text-[#FF3870] font-medium transition-all duration-150';
}

function getSubNavClass($path, $current_uri) {
    if (strpos($current_uri, $path) !== false) {
        return 'text-[#FF3870] hover:text-[#5C2D16] font-medium bg-[#FFF0F5] border-l-2 border-[#FF3870] pl-3 transition-all duration-150';
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
    }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E7D5C4; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #FF97B6; }

    /* Sidebar selalu tersembunyi default, muncul sebagai floating overlay */
    #main-sidebar {
        position: fixed;
        inset-y: 0;
        top: 0;
        bottom: 0;
        left: 0;
        height: 100vh;
        height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
        z-index: 9999;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 280px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #main-sidebar.sidebar-open {
        transform: translateX(0);
    }

    /* Overlay gelap di belakang sidebar */
    #sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(45, 20, 10, 0.45);
        z-index: 9998;
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    #sidebar-overlay.overlay-visible {
        display: block;
        opacity: 1;
    }

    /* Tombol Toggle Floating Hariku Pink */
    #sidebar-toggle-btn {
        position: fixed;
        top: 14px;
        left: 14px;
        z-index: 9997;
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #FF3870, #E02360);
        color: white;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        box-shadow: 0 4px 14px rgba(255, 56, 112, 0.35);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 17px;
    }
    #sidebar-toggle-btn:hover {
        background: #5C2D16;
        transform: scale(1.06);
        box-shadow: 0 6px 20px rgba(92, 45, 22, 0.4);
    }
    #sidebar-toggle-btn:active {
        transform: scale(0.95);
    }
</style>

<!-- ===== TOMBOL TOGGLE FLOATING ===== -->
<button id="sidebar-toggle-btn" onclick="toggleSidebar()" title="Menu Navigasi Kasir" aria-label="Toggle Sidebar">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- ===== SIDEBAR KASIR ===== -->
<aside id="main-sidebar" class="bg-white border-r border-[#FFE4EC] flex-col shadow-2xl flex h-screen max-h-screen">

    <!-- HEADER KASIR -->
    <div class="h-20 flex items-center justify-between px-5 border-b border-[#FFE4EC] shrink-0 bg-white">
        <a href="<?= BASE_URL ?>pos/kasir/" class="flex items-center gap-3 group">
            <div class="relative w-11 h-11 rounded-2xl p-0.5 bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] shadow-sm shrink-0">
                <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[14px] bg-white">
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-[10px] font-black tracking-widest text-[#5C2D16] uppercase">Ayam Goreng</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-lg font-black tracking-tight text-[#FF3870]">HARIKU</span>
                    <span class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow-2xs">KASIR</span>
                </div>
            </div>
        </a>
        <button onclick="toggleSidebar()" class="text-slate-400 hover:text-[#FF3870] p-2 rounded-xl hover:bg-[#FFF0F5] transition-colors" title="Tutup Menu">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- INFO OUTLET / LOKASI KASIR -->
    <?php if (!empty($_SESSION['pos_store_name'])): ?>
    <div class="px-4 pt-3.5 pb-1 shrink-0 bg-white">
        <div class="bg-gradient-to-br from-[#FFF0F5] via-white to-[#FAF5F1] border border-[#FFC5D8]/70 rounded-2xl p-3 flex items-center gap-3 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center shrink-0 font-bold text-sm shadow-xs">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-[9px] font-black uppercase tracking-wider text-[#FF3870] leading-tight">Outlet Kasir Aktif</div>
                <div class="text-xs font-black truncate text-[#5C2D16]"><?= htmlspecialchars($_SESSION['pos_store_name']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NAVIGATION LINKS KASIR -->
    <nav class="flex-1 min-h-0 px-3.5 py-4 space-y-1 overflow-y-auto custom-scrollbar bg-white" style="-webkit-overflow-scrolling: touch;">

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#FF3870]"></span> Menu Kasir
        </div>

        <a href="<?= BASE_URL ?>pos/kasir/" title="Mesin Kasir (POS)" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/', $current_uri) ?>">
            <div class="relative w-5 text-center shrink-0">
                <i class="fa-solid fa-cash-register text-base"></i>
            </div>
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Mesin Kasir (POS)</span>
        </a>

        <a href="<?= BASE_URL ?>pos/kasir_online/" title="Kasir Online" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-3 <?= getNavClass('/pos/kasir_online/', $current_uri) ?>">
            <div class="relative w-5 text-center shrink-0">
                <i class="fa-solid fa-motorcycle text-base"></i>
            </div>
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Kasir Online Delivery</span>
        </a>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span> Produk & Stok
        </div>

        <?php 
            $paths = ['/pos/produk/deposit/', '/pos/produk/', '/pos/produk/inventory/', '/pos/produk/mutasi/', '/pos/produk/cetak_barcode/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-produk', 'icon-produk')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Produk & Inventory</span>
                </div>
                <i id="icon-produk" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-produk" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/produk/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/', $current_uri) ?>">
                    <i class="fa-solid fa-drumstick-bite text-[11px] w-4 text-center shrink-0"></i>
                    <span>Katalog Produk</span>
                </a>
                <a href="<?= BASE_URL ?>pos/produk/mutasi/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/mutasi/', $current_uri) ?>">
                    <i class="fa-solid fa-truck-ramp-box text-[11px] w-4 text-center shrink-0"></i>
                    <span>Mutasi Antar Store</span>
                </a>
                <a href="<?= BASE_URL ?>pos/produk/cetak_barcode/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/cetak_barcode/', $current_uri) ?>">
                    <i class="fa-solid fa-barcode text-[11px] w-4 text-center shrink-0"></i>
                    <span>Cetak Barcode SKU</span>
                </a>
                <a href="<?= BASE_URL ?>pos/produk/inventory/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/produk/inventory/', $current_uri) ?>">
                    <i class="fa-solid fa-boxes-stacked text-[11px] w-4 text-center shrink-0"></i>
                    <span>Inventory Gudang</span>
                </a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>pos/kasir/laporan_shift/" title="Laporan Shift Kasir" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/laporan_shift/', $current_uri) ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Laporan Shift Kasir</span>
        </a>

        <a href="<?= BASE_URL ?>pos/kasir/laporan_custom/" title="Laporan Item Custom" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/kasir/laporan_custom/', $current_uri) ?>">
            <i class="fa-solid fa-pen-to-square w-5 text-center text-base shrink-0"></i>
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Laporan Item Custom</span>
        </a>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span> Transaksi & Kas
        </div>

        <?php 
            $paths = ['/pos/transaksi/penjualan/', '/pos/transaksi/arus_kas/', '/pos/transaksi/piutang/']; 
            $isActive = isDropdownActive($paths, $current_uri);
        ?>
        <div class="mb-1">
            <button onclick="toggleSubmenu('sub-transaksi', 'icon-transaksi')" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all <?= $isActive ? 'bg-[#FFF0F5] text-[#FF3870] font-black ring-1 ring-[#FFC5D8]' : 'text-[#5C2D16]/80 hover:bg-[#FFF0F5] hover:text-[#FF3870] font-semibold' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-wallet w-5 text-center text-base shrink-0"></i>
                    <span class="text-xs whitespace-nowrap">Transaksi Kasir</span>
                </div>
                <i id="icon-transaksi" class="fa-solid fa-chevron-<?= $isActive ? 'down' : 'right' ?> text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="sub-transaksi" class="<?= $isActive ? 'flex' : 'hidden' ?> flex-col gap-1 mt-1 pl-10 pr-2 border-l border-pink-100 ml-3">
                <a href="<?= BASE_URL ?>pos/transaksi/penjualan/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/penjualan/', $current_uri) ?>">
                    <i class="fa-solid fa-receipt text-[11px] w-4 text-center shrink-0"></i>
                    <span>Riwayat Penjualan</span>
                </a>
                <a href="<?= BASE_URL ?>pos/transaksi/piutang/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/piutang/', $current_uri) ?>">
                    <i class="fa-solid fa-file-invoice-dollar text-[11px] w-4 text-center shrink-0"></i>
                    <span>Pelunasan DP (Piutang)</span>
                </a>
                <a href="<?= BASE_URL ?>pos/transaksi/arus_kas/" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg transition-all <?= getSubNavClass('/pos/transaksi/arus_kas/', $current_uri) ?>">
                    <i class="fa-solid fa-money-bill-transfer text-[11px] w-4 text-center shrink-0"></i>
                    <span>Kas Keluar (Petty Cash)</span>
                </a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>pos/transaksi/pembayaran_digital/" title="QRIS & E-Wallet" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-1 <?= getNavClass('/pos/transaksi/pembayaran_digital/', $current_uri) ?>">
            <i class="fa-solid fa-qrcode w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Rekap QRIS & Digital</span>
        </a>

        <div class="px-3 text-[10px] font-black text-[#8C5638] uppercase tracking-widest mt-5 mb-1.5 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#64748B]"></span> Pengaturan
        </div>

        <a href="<?= BASE_URL ?>pos/pengaturan/printer/" title="Setelan Printer Kasir" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all mb-4 <?= getNavClass('/pos/pengaturan/printer/', $current_uri) ?>">
            <i class="fa-solid fa-print w-5 text-center text-base shrink-0"></i> 
            <span class="text-xs whitespace-nowrap transition-all duration-300 font-bold">Setelan Printer Kasir</span>
        </a>

        <!-- USER PROFILE & LOGOUT -->
        <div class="mt-4 px-1 pb-4">
            <div class="border-t border-[#FFE4EC] pt-4">
                <div class="bg-gradient-to-br from-[#FFF0F5] to-[#FAF5F1] border border-[#FFE4EC] rounded-2xl p-3 mb-2.5 flex items-center gap-3 shadow-2xs">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center font-black text-sm shrink-0 shadow-xs">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="overflow-hidden flex-1">
                        <p class="text-xs font-black text-[#4A2311] truncate"><?= htmlspecialchars($_SESSION['pos_name'] ?? $_SESSION['pos_username'] ?? 'Kasir') ?></p>
                        <span class="inline-flex items-center gap-1 bg-white text-[#FF3870] border border-[#FFC5D8] text-[9px] font-black px-2 py-0.5 rounded-full mt-0.5 shadow-2xs uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FF3870]"></span>
                            <?= htmlspecialchars($_SESSION['pos_role'] ?? 'Kasir') ?>
                        </span>
                    </div>
                </div>
                <button onclick="doLogoutKasir()" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold transition-all border border-rose-200 hover:border-rose-300 shadow-2xs text-xs group">
                    <i class="fa-solid fa-arrow-right-from-bracket group-hover:translate-x-0.5 transition-transform"></i>
                    <span>Tutup Shift & Keluar</span>
                </button>
            </div>
        </div>

    </nav>
</aside>

<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('main-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const btn = document.getElementById('sidebar-toggle-btn');
        if (!sidebar) return;

        const isOpen = sidebar.classList.contains('sidebar-open');
        if (isOpen) {
            sidebar.classList.remove('sidebar-open');
            if (overlay) overlay.classList.remove('overlay-visible');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-bars"></i>';
        } else {
            sidebar.classList.add('sidebar-open');
            if (overlay) overlay.classList.add('overlay-visible');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        }
    }

    function toggleSubmenu(menuId, iconId) {
        const menu = document.getElementById(menuId);
        const icon = document.getElementById(iconId);
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

    // ===== FUNGSI LOGOUT KASIR =====
    function doLogoutKasir() {
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
                title: 'Akhiri Sesi Kasir?',
                text: 'Pastikan seluruh transaksi shift Anda telah selesai.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF3870',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Keluar!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    jalankanLogout();
                }
            });
        } else {
            if (confirm('Akhiri sesi kasir dan keluar?')) {
                jalankanLogout();
            }
        }
    }
</script>