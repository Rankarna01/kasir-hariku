<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../config/auth.php';

// Proteksi hak akses role admin/owner/superadmin
$user_role_page = strtolower(trim($_SESSION['pos_role'] ?? ''));
if (!in_array($user_role_page, ['admin', 'owner', 'superadmin', 'backoffice'])) {
    header("Location: " . BASE_URL . "pos/kasir/");
    exit;
}

$page_title = "Manajemen Karyawan & Akun — Ayam Goreng Hariku POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include __DIR__ . '/../../../components/header.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #FFC5D8; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans">

    <?php include __DIR__ . '/../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER TOP -->
        <header class="bg-white border-b border-[#FFE4EC] px-4 sm:px-6 py-3.5 flex justify-between items-center z-20 shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-primary p-2 rounded-xl hover:bg-[#FFF0F5] transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div>
                    <h2 class="text-lg font-black tracking-tight text-[#4A2311] flex items-center gap-2">
                        <i class="fa-solid fa-users-gear text-primary"></i> Manajemen Karyawan & Akun
                    </h2>
                    <p class="text-xs text-[#8C5638] font-medium hidden sm:block">Kelola kredensial akun kasir, administrator, penugasan outlet, dan reset kata sandi.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2.5">
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="hidden sm:flex bg-[#FFF0F5] text-primary border border-[#FFC5D8] px-3 py-1.5 rounded-xl text-xs font-bold items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-store text-primary"></i> <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>

                <button onclick="loadKaryawan()" class="bg-[#FAF5F1] hover:bg-[#FFF0F5] text-[#5C2D16] hover:text-primary border border-[#FFC5D8]/70 w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-2xs" title="Muat Ulang Data">
                    <i class="fa-solid fa-rotate" id="refresh-icon"></i> 
                </button>

                <div class="border-l border-[#FFE4EC] pl-2.5 ml-1">
                    <button onclick="doLogoutAdmin()" class="bg-rose-50 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-200 w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-2xs" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#fbf9f8] relative">
            <div class="w-full space-y-5 pb-12">
                
                <!-- TOP STATS COUNTER -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <div class="bg-white p-4 rounded-2xl border border-[#FFE4EC] shadow-xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Karyawan</span>
                            <span class="text-xl font-black text-[#4A2311]" id="stat-total">0</span>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-[#FFE4EC] shadow-xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg shrink-0 shadow-xs">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Akun Kasir</span>
                            <span class="text-xl font-black text-emerald-600" id="stat-kasir">0</span>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-[#FFE4EC] shadow-xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-lg shrink-0 shadow-xs">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Administrator</span>
                            <span class="text-xl font-black text-blue-600" id="stat-admin">0</span>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-[#FFE4EC] shadow-xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-lg shrink-0 shadow-xs">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Outlet Terdaftar</span>
                            <span class="text-xl font-black text-purple-600" id="stat-outlets">0</span>
                        </div>
                    </div>
                </div>

                <!-- FILTER BAR & ACTION -->
                <div class="bg-white p-4 rounded-2xl shadow-xs border border-[#FFE4EC] flex flex-col md:flex-row justify-between items-start md:items-center gap-3.5">
                    
                    <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto flex-1">
                        <!-- Search Box -->
                        <div class="relative flex-1 sm:w-64 min-w-[200px]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="searchInput" oninput="filterTable()" placeholder="Cari nama / username..." class="w-full pl-9 pr-3 py-2.5 bg-[#FAF7F5] border border-[#FFC5D8]/70 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-700 transition-all">
                        </div>

                        <!-- Filter Role -->
                        <div class="w-36">
                            <select id="roleFilter" onchange="filterTable()" class="w-full bg-[#FAF7F5] border border-[#FFC5D8]/70 text-xs font-bold text-slate-700 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer">
                                <option value="">Semua Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Kasir">Kasir</option>
                            </select>
                        </div>

                        <!-- Filter Outlet -->
                        <div class="w-44">
                            <select id="outletFilter" onchange="filterTable()" class="w-full bg-[#FAF7F5] border border-[#FFC5D8]/70 text-xs font-bold text-slate-700 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer">
                                <option value="">Semua Outlet</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tombol Tambah Karyawan -->
                    <button onclick="openModalTambah()" class="w-full md:w-auto bg-[#FF3870] hover:bg-[#5C2D16] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md shadow-pink-500/20 flex items-center justify-center gap-2 shrink-0 active:scale-[0.98]">
                        <i class="fa-solid fa-user-plus text-xs"></i> Tambah Karyawan Baru
                    </button>
                </div>

                <!-- TABLE CARD -->
                <div class="bg-white rounded-2xl shadow-xs border border-[#FFE4EC] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#FFF5F8] border-b border-[#FFE4EC] text-[#8C5638] text-[10px] font-black uppercase tracking-wider">
                                    <th class="p-3.5 text-center w-12">No</th>
                                    <th class="p-3.5">Karyawan / Akun</th>
                                    <th class="p-3.5 w-32">Role Akses</th>
                                    <th class="p-3.5 w-48">Penugasan Outlet</th>
                                    <th class="p-3.5 w-44">Aktivitas Shift</th>
                                    <th class="p-3.5 text-center w-48">Aksi & Keamanan</th>
                                </tr>
                            </thead>
                            <tbody id="karyawanTableBody" class="text-xs divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-slate-400 font-medium">
                                        <i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat daftar karyawan...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ==================== MODAL TAMBAH / EDIT KARYAWAN ==================== -->
    <div id="modalKaryawan" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" onclick="closeModalKaryawan()"></div>
        <div class="bg-white rounded-3xl shadow-2xl border border-[#FFE4EC] w-full max-w-lg mx-4 z-10 overflow-hidden transform transition-all">
            
            <div class="px-6 py-4 border-b border-[#FFE4EC] flex justify-between items-center bg-[#FFF8FA]">
                <h3 class="font-black text-sm text-[#4A2311] flex items-center gap-2" id="modalTitle">
                    <i class="fa-solid fa-user-plus text-primary"></i> Tambah Karyawan Baru
                </h3>
                <button type="button" onclick="closeModalKaryawan()" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form id="formKaryawan" onsubmit="submitKaryawan(event)" class="p-6 space-y-4">
                <input type="hidden" id="karyawanId" name="id" value="0">

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Nama Lengkap Karyawan <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-id-card text-xs"></i></span>
                        <input type="text" id="karyawanName" name="name" required placeholder="Contoh: Budi Santoso" class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all">
                    </div>
                </div>

                <!-- Username Login & No WhatsApp -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                            Username Login <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-user text-xs"></i></span>
                            <input type="text" id="karyawanUsername" name="username" required placeholder="kasir1 / budi" class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all lowercase">
                        </div>
                        <span class="text-[9.5px] text-slate-400 mt-1 block">Hanya huruf kecil, angka, dan titik/garis bawah.</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                            No. WhatsApp / HP
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-brands fa-whatsapp text-xs"></i></span>
                            <input type="text" id="karyawanPhone" name="phone" placeholder="08xxxxxxxxxx" class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all">
                        </div>
                    </div>
                </div>

                <!-- Role & Outlet -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                            Role Akses Sistem <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="karyawanRole" name="role_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 cursor-pointer">
                                <option value="2">Kasir (Akses Kasir POS)</option>
                                <option value="1">Admin (Akses Backoffice & Laporan)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                            Penugasan Outlet <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="karyawanWarehouse" name="warehouse_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 cursor-pointer">
                                <option value="">Semua Outlet (Global)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Password (Wajib saat tambah, opsional saat edit) -->
                <div id="passwordWrapper">
                    <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5" id="labelPassword">
                        Kata Sandi Awal <span class="text-rose-500" id="passRequiredStar">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-lock text-xs"></i></span>
                        <input type="password" id="karyawanPassword" name="password" placeholder="Minimal 4 karakter" class="w-full pl-9 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all">
                        <button type="button" onclick="togglePasswordVisibility('karyawanPassword', 'eyeIcon1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye text-xs" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <span class="text-[9.5px] text-slate-400 mt-1 block" id="passNote">Sandi ini digunakan saat karyawan masuk melalui halaman Login Kasir / Admin.</span>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalKaryawan()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitKaryawan" class="px-5 py-2.5 bg-[#FF3870] hover:bg-[#5C2D16] text-white text-xs font-bold rounded-xl shadow-md shadow-pink-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-check"></i> Simpan Data Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL RESET KATA SANDI ==================== -->
    <div id="modalResetPassword" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" onclick="closeModalResetPassword()"></div>
        <div class="bg-white rounded-3xl shadow-2xl border border-[#FFE4EC] w-full max-w-md mx-4 z-10 overflow-hidden transform transition-all">
            
            <div class="px-6 py-4 border-b border-[#FFE4EC] flex justify-between items-center bg-[#FFF8FA]">
                <h3 class="font-black text-sm text-[#4A2311] flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-500"></i> Reset Kata Sandi
                </h3>
                <button type="button" onclick="closeModalResetPassword()" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form id="formResetPassword" onsubmit="submitResetPassword(event)" class="p-6 space-y-4">
                <input type="hidden" id="resetUserId" name="id" value="0">

                <div class="bg-[#FAF5F1] p-3 rounded-2xl border border-[#FFC5D8]/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center font-black text-sm shrink-0 shadow-2xs" id="resetAvatar">
                        K
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-xs font-black text-[#4A2311] truncate" id="resetTargetName">-</h4>
                        <p class="text-[10px] text-slate-500 font-medium">Username: <span class="font-bold text-primary" id="resetTargetUsername">-</span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-lock text-xs"></i></span>
                        <input type="password" id="resetNewPass" name="new_password" required placeholder="Minimal 4 karakter" class="w-full pl-9 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all">
                        <button type="button" onclick="togglePasswordVisibility('resetNewPass', 'eyeIconReset1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye text-xs" id="eyeIconReset1"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-shield-check text-xs"></i></span>
                        <input type="password" id="resetConfirmPass" name="confirm_password" required placeholder="Ketik ulang kata sandi baru" class="w-full pl-9 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs font-bold text-slate-800 transition-all">
                        <button type="button" onclick="togglePasswordVisibility('resetConfirmPass', 'eyeIconReset2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye text-xs" id="eyeIconReset2"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModalResetPassword()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitResetPass" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-key"></i> Simpan Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT LOGIKA HALAMAN KARYAWAN -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        let allKaryawanData = [];
        let allWarehouses = [];
        let allRoles = [];
        let currentUserId = <?= intval($_SESSION['pos_user_id'] ?? 0) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            loadKaryawan();
        });

        // 1. LOAD DATA KARYAWAN
        async function loadKaryawan() {
            const tbody = document.getElementById('karyawanTableBody');
            const icon = document.getElementById('refresh-icon');
            if (icon) icon.classList.add('fa-spin');

            try {
                const res = await fetch('logic.php?action=read&_t=' + Date.now());
                const result = await res.json();

                if (result.status === 'success') {
                    allKaryawanData = result.data || [];
                    allWarehouses = result.warehouses || [];
                    allRoles = result.roles || [];
                    currentUserId = result.current_user_id || currentUserId;

                    populateOutletOptions();
                    updateCounters(allKaryawanData);
                    renderTable(allKaryawanData);
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-rose-500 font-bold">${result.message || 'Gagal memuat data karyawan'}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-rose-500 font-bold">Gagal terhubung ke server logic karyawan.</td></tr>`;
            } finally {
                if (icon) icon.classList.remove('fa-spin');
            }
        }

        // 2. POPULATE DROPDOWNS OUTLET
        function populateOutletOptions() {
            const filterSelect = document.getElementById('outletFilter');
            const formSelect = document.getElementById('karyawanWarehouse');
            
            // Simpan value yang sedang dipilih sebelumnya
            const currentFilterVal = filterSelect.value;

            filterSelect.innerHTML = '<option value="">Semua Outlet</option>';
            formSelect.innerHTML = '<option value="">Semua Outlet (Global)</option>';

            allWarehouses.forEach(wh => {
                const opt1 = document.createElement('option');
                opt1.value = wh.id;
                opt1.textContent = `${wh.name} (${wh.code})`;
                filterSelect.appendChild(opt1);

                const opt2 = document.createElement('option');
                opt2.value = wh.id;
                opt2.textContent = `${wh.name} (${wh.code})`;
                formSelect.appendChild(opt2);
            });

            filterSelect.value = currentFilterVal;
            document.getElementById('stat-outlets').textContent = allWarehouses.length;
        }

        // 3. STATISTIK RINGKAS
        function updateCounters(data) {
            document.getElementById('stat-total').textContent = data.length;
            const kasirCount = data.filter(k => (k.role_name || '').toLowerCase() === 'kasir').length;
            const adminCount = data.filter(k => (k.role_name || '').toLowerCase() === 'admin').length;
            
            document.getElementById('stat-kasir').textContent = kasirCount;
            document.getElementById('stat-admin').textContent = adminCount;
        }

        // 4. RENDER TABEL
        function renderTable(data) {
            const tbody = document.getElementById('karyawanTableBody');
            if (!data || data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-10 text-center text-slate-400 font-bold">Tidak ada data karyawan yang cocok dengan pencarian / filter.</td></tr>`;
                return;
            }

            let html = '';
            data.forEach((row, idx) => {
                const roleLower = (row.role_name || 'kasir').toLowerCase();
                const isAdmin = roleLower === 'admin';
                const isCurrent = (row.id == currentUserId);

                // Badge Role Styling
                const roleBadge = isAdmin
                    ? `<span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider"><i class="fa-solid fa-user-shield text-[9px]"></i> Admin</span>`
                    : `<span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider"><i class="fa-solid fa-cash-register text-[9px]"></i> Kasir</span>`;

                // Badge Shift Terkini
                let shiftBadge = '';
                if (row.active_shift_status === 'open') {
                    shiftBadge = `<div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> <span class="text-emerald-700 font-black text-[11px]">Shift Sedang Aktif</span></div>`;
                } else if (row.total_shifts > 0) {
                    shiftBadge = `<div class="text-[11px] font-bold text-slate-600">${row.total_shifts} Sesi Shift Diselesaikan</div>`;
                } else {
                    shiftBadge = `<span class="text-slate-400 text-[11px] font-medium">Belum ada sesi shift</span>`;
                }

                // Inisial Avatar
                const initial = (row.name || 'U').charAt(0).toUpperCase();

                html += `
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5 text-center text-slate-400 font-bold">${idx + 1}</td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] text-white flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                    ${initial}
                                </div>
                                <div>
                                    <div class="font-black text-slate-800 flex items-center gap-2">
                                        <span>${escapeHtml(row.name)}</span>
                                        ${isCurrent ? '<span class="bg-[#FFF0F5] text-primary border border-[#FFC5D8] text-[9px] font-black px-1.5 py-0.2 rounded-md">Akun Anda</span>' : ''}
                                    </div>
                                    <div class="text-[11px] font-bold text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span class="text-primary font-mono font-bold">@${escapeHtml(row.username)}</span>
                                        ${row.phone ? `<span>• <i class="fa-brands fa-whatsapp text-emerald-500"></i> ${escapeHtml(row.phone)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            ${roleBadge}
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                                <i class="fa-solid fa-store text-primary text-[11px]"></i>
                                <span>${escapeHtml(row.store_name)}</span>
                            </div>
                        </td>
                        <td class="p-3.5">
                            ${shiftBadge}
                            ${row.last_shift_time ? `<span class="text-[10px] text-slate-400 block mt-0.5">Terakhir: ${formatDateTime(row.last_shift_time)}</span>` : ''}
                        </td>
                        <td class="p-3.5 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                
                                <!-- Tombol Reset Sandi -->
                                <button onclick="openModalResetPassword(${row.id}, '${escapeQuote(row.name)}', '${escapeQuote(row.username)}')" 
                                    class="p-2 rounded-xl bg-amber-50 hover:bg-amber-500 text-amber-600 hover:text-white border border-amber-200/80 transition-all shadow-2xs" 
                                    title="Reset Kata Sandi Akun">
                                    <i class="fa-solid fa-key text-xs"></i>
                                </button>

                                <!-- Tombol Edit Profil -->
                                <button onclick="openModalEdit(${row.id})" 
                                    class="p-2 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white border border-blue-200/80 transition-all shadow-2xs" 
                                    title="Edit Data Karyawan">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>

                                <!-- Tombol Hapus -->
                                ${!isCurrent ? `
                                    <button onclick="hapusKaryawan(${row.id}, '${escapeQuote(row.name)}')" 
                                        class="p-2 rounded-xl bg-rose-50 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-200 transition-all shadow-2xs" 
                                        title="Hapus Karyawan">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                ` : `
                                    <button disabled class="p-2 rounded-xl bg-slate-100 text-slate-300 cursor-not-allowed border border-slate-200" title="Tidak dapat menghapus akun Anda sendiri">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                `}

                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // 5. FILTER TABLE REALTIME
        function filterTable() {
            const query = (document.getElementById('searchInput').value || '').toLowerCase();
            const role = (document.getElementById('roleFilter').value || '').toLowerCase();
            const outletId = document.getElementById('outletFilter').value;

            const filtered = allKaryawanData.filter(row => {
                const matchSearch = (row.name || '').toLowerCase().includes(query) || 
                                    (row.username || '').toLowerCase().includes(query) ||
                                    (row.phone || '').toLowerCase().includes(query) ||
                                    (row.store_name || '').toLowerCase().includes(query);

                const matchRole = !role || (row.role_name || '').toLowerCase() === role;
                const matchOutlet = !outletId || (String(row.warehouse_id) === String(outletId));

                return matchSearch && matchRole && matchOutlet;
            });

            renderTable(filtered);
        }

        // 6. MODAL TAMBAH KARYAWAN
        function openModalTambah() {
            document.getElementById('formKaryawan').reset();
            document.getElementById('karyawanId').value = "0";
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-primary"></i> Tambah Karyawan Baru';
            
            // Tampilkan input password saat tambah akun baru
            const passWrapper = document.getElementById('passwordWrapper');
            if (passWrapper) passWrapper.classList.remove('hidden');
            const passInput = document.getElementById('karyawanPassword');
            if (passInput) passInput.required = true;
            
            document.getElementById('passRequiredStar').classList.remove('hidden');
            document.getElementById('passNote').textContent = 'Sandi ini digunakan saat karyawan masuk melalui halaman Login Kasir / Admin.';
            document.getElementById('modalKaryawan').classList.remove('hidden');
        }

        // 7. MODAL EDIT KARYAWAN
        function openModalEdit(id) {
            const item = allKaryawanData.find(k => k.id == id);
            if (!item) return;

            document.getElementById('formKaryawan').reset();
            document.getElementById('karyawanId').value = item.id;
            document.getElementById('karyawanName').value = item.name || '';
            document.getElementById('karyawanUsername').value = item.username || '';
            document.getElementById('karyawanPhone').value = item.phone || '';
            document.getElementById('karyawanRole').value = item.role_id || '2';
            document.getElementById('karyawanWarehouse').value = item.warehouse_id || '';

            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-user-pen text-blue-600"></i> Edit Data Karyawan';
            
            // Kolom password disembunyikan saat edit (cukup gunakan tombol reset password di tabel)
            const passWrapper = document.getElementById('passwordWrapper');
            if (passWrapper) passWrapper.classList.add('hidden');
            const passInput = document.getElementById('karyawanPassword');
            if (passInput) {
                passInput.required = false;
                passInput.value = '';
            }

            document.getElementById('modalKaryawan').classList.remove('hidden');
        }

        function closeModalKaryawan() {
            document.getElementById('modalKaryawan').classList.add('hidden');
        }

        // 8. SUBMIT FORM KARYAWAN (TAMBAH / EDIT)
        async function submitKaryawan(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitKaryawan');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            btn.disabled = true;

            const formData = new FormData(document.getElementById('formKaryawan'));

            try {
                const res = await fetch('logic.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                    closeModalKaryawan();
                    await loadKaryawan();
                } else {
                    Swal.fire('Gagal', result.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal memproses data karyawan ke server.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // 9. MODAL RESET PASSWORD
        function openModalResetPassword(id, name, username) {
            document.getElementById('formResetPassword').reset();
            document.getElementById('resetUserId').value = id;
            document.getElementById('resetTargetName').textContent = name;
            document.getElementById('resetTargetUsername').textContent = '@' + username;
            document.getElementById('resetAvatar').textContent = name.charAt(0).toUpperCase();

            document.getElementById('modalResetPassword').classList.remove('hidden');
        }

        function closeModalResetPassword() {
            document.getElementById('modalResetPassword').classList.add('hidden');
        }

        // 10. SUBMIT RESET PASSWORD
        async function submitResetPassword(e) {
            e.preventDefault();
            const newPass = document.getElementById('resetNewPass').value;
            const confirmPass = document.getElementById('resetConfirmPass').value;

            if (newPass.length < 4) {
                Swal.fire('Perhatian', 'Kata sandi baru minimal 4 karakter!', 'warning');
                return;
            }

            if (newPass !== confirmPass) {
                Swal.fire('Perhatian', 'Konfirmasi kata sandi tidak cocok!', 'warning');
                return;
            }

            const btn = document.getElementById('btnSubmitResetPass');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan Sandi...';
            btn.disabled = true;

            const formData = new FormData(document.getElementById('formResetPassword'));

            try {
                const res = await fetch('logic.php?action=reset_password', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sandi Berhasil Direset!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    closeModalResetPassword();
                } else {
                    Swal.fire('Gagal Reset Sandi', result.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal memproses reset kata sandi.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // 11. HAPUS KARYAWAN
        function hapusKaryawan(id, name) {
            Swal.fire({
                title: 'Hapus Akun Karyawan?',
                html: `Apakah Anda yakin ingin menghapus akun <strong>${escapeHtml(name)}</strong>?<br><span class="text-xs text-slate-500">Karyawan ini tidak akan bisa login kembali ke sistem kasir.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF3870',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Akun',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const fd = new FormData();
                        fd.append('id', id);

                        const res = await fetch('logic.php?action=delete', {
                            method: 'POST',
                            body: fd
                        });
                        const data = await res.json();

                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            await loadKaryawan();
                        } else {
                            Swal.fire('Gagal Menghapus', data.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Gagal memproses penghapusan akun.', 'error');
                    }
                }
            });
        }

        // 12. HELPER FUNCTIONS
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
            });
        }

        function escapeQuote(str) {
            if (!str) return '';
            return String(str).replace(/'/g, "\\'");
        }

        function formatDateTime(dtStr) {
            if (!dtStr) return '-';
            const d = new Date(dtStr.replace(/-/g, "/"));
            if (isNaN(d.getTime())) return dtStr;
            const day = String(d.getDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const mon = monthNames[d.getMonth()];
            const yr = d.getFullYear();
            const hr = String(d.getHours()).padStart(2, '0');
            const min = String(d.getMinutes()).padStart(2, '0');
            return `${day} ${mon} ${yr} ${hr}:${min}`;
        }
    </script>
</body>
</html>
