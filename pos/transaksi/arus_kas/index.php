<?php
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$folder = $is_localhost ? (defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_PATH) : '/sistem-kasir/kasir-hariku/') : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Pengeluaran Kas (Petty Cash) - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="arusKasApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- TOP APPBAR -->
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 flex justify-between items-center z-20 shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-500 hover:text-slate-800 p-2 rounded-xl hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-100 shrink-0">
                    <i class="fa-solid fa-hand-holding-dollar text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-tight flex items-center gap-2">
                        Pengeluaran Kas (Petty Cash)
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700">Kas Keluar</span>
                    </h2>
                    <p class="text-xs text-slate-500 font-medium hidden sm:block">Pencatatan kas kecil operasional outlet secara langsung & transparan</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button @click="openModal()" class="inline-flex items-center gap-2 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 active:scale-95 text-white px-4 py-2.5 rounded-xl font-black text-xs sm:text-sm shadow-md shadow-rose-500/20 transition-all cursor-pointer">
                    <i class="fa-solid fa-plus-circle text-sm"></i>
                    <span>Catat Pengeluaran</span>
                </button>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-slate-100/60">
            <div class="w-full max-w-7xl mx-auto space-y-5 relative">
                
                <!-- LOADING OVERLAY -->
                <div x-show="isLoading" x-transition.opacity class="absolute inset-0 z-40 bg-white/70 backdrop-blur-xs flex flex-col items-center justify-center rounded-3xl" style="display: none;">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center shadow-lg border border-rose-100 text-rose-500">
                        <i class="fa-solid fa-circle-notch fa-spin text-2xl"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-600 mt-2">Memuat data kas...</span>
                </div>

                <!-- KPI METRIC CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Card 1: Total Pengeluaran Periode -->
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-rose-500/5 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-arrow-trend-up text-rose-500/20 text-5xl"></i>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-black text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Total Periode
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md" x-text="activePreset === 'today' ? 'Hari Ini' : (activePreset === 'yesterday' ? 'Kemarin' : (activePreset === '7days' ? '7 Hari' : (activePreset === 'this_month' ? 'Bulan Ini' : 'Kustom')))"></span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight" x-text="'Rp ' + formatRupiah(summary.total_keluar)">Rp 0</h3>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Total pengeluaran kas kecil di rentang tanggal</p>
                    </div>

                    <!-- Card 2: Pengeluaran Hari Ini -->
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-calendar-day text-amber-500/20 text-5xl"></i>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-black text-amber-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Khusus Hari Ini
                            </span>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Live
                            </span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight" x-text="'Rp ' + formatRupiah(summary.total_today)">Rp 0</h3>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Pengeluaran kas sejak jam 00:00 hari ini</p>
                    </div>

                    <!-- Card 3: Frekuensi / Jumlah Catatan -->
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/5 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-receipt text-indigo-500/20 text-5xl"></i>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-black text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Frekuensi Transaksi
                            </span>
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">Kas Keluar</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                            <span x-text="summary.count_keluar">0</span> <span class="text-sm font-bold text-slate-400">kali</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Total nota/transaksi pengeluaran</p>
                    </div>

                    <!-- Card 4: Rata-rata per Pengeluaran -->
                    <div class="bg-white p-5 rounded-[1.5rem] shadow-xs border border-slate-200/80 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-cyan-500/5 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-scale-balanced text-cyan-500/20 text-5xl"></i>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-black text-cyan-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span> Rata-Rata Pengeluaran
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">Estimasi</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight" x-text="'Rp ' + formatRupiah(summary.avg_keluar)">Rp 0</h3>
                        <p class="text-[11px] text-slate-400 font-semibold mt-1">Nilai rata-rata per kali transaksi</p>
                    </div>

                </div>

                <!-- FILTER BAR & SEARCH -->
                <div class="bg-white p-4 sm:p-5 rounded-[1.5rem] shadow-xs border border-slate-200/80 space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        
                        <!-- PRESET BUTTONS -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button @click="setPreset('today')" :class="activePreset === 'today' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all">
                                Hari Ini
                            </button>
                            <button @click="setPreset('yesterday')" :class="activePreset === 'yesterday' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all">
                                Kemarin
                            </button>
                            <button @click="setPreset('7days')" :class="activePreset === '7days' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all">
                                7 Hari Terakhir
                            </button>
                            <button @click="setPreset('this_month')" :class="activePreset === 'this_month' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all">
                                Bulan Ini
                            </button>
                        </div>

                        <!-- LIVE SEARCH BOX -->
                        <div class="relative w-full lg:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari keterangan, kasir, shift..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all">
                            <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>

                    </div>

                    <!-- DATE RANGE PICKER -->
                    <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-slate-400 font-bold">Rentang:</span>
                            <input type="date" x-model="startDate" @change="onCustomDateChange()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-rose-500/20">
                            <span class="text-slate-400 font-bold">s/d</span>
                            <input type="date" x-model="endDate" @change="onCustomDateChange()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-rose-500/20">
                            <button @click="fetchData()" class="bg-rose-50 text-rose-600 hover:bg-rose-100 px-4 py-1.5 rounded-xl font-black transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-filter text-xs"></i> Terapkan
                            </button>
                        </div>

                        <div class="text-[11px] text-slate-400 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle-info text-blue-500"></i>
                            <span>Menampilkan <strong class="text-slate-700" x-text="filteredHistory.length"></strong> catatan pengeluaran</span>
                        </div>
                    </div>
                </div>

                <!-- RIWAYAT MUTASI PENGELUARAN TABLE -->
                <div class="bg-white rounded-[1.5rem] border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-white flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xs">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-sm text-slate-900 leading-tight">Daftar Pengeluaran Kas Kecil</h3>
                                <p class="text-[11px] text-slate-400 font-medium">Rekapitulasi riwayat mutasi kas keluar operasional</p>
                            </div>
                        </div>

                        <button @click="fetchData()" class="text-xs font-bold text-slate-500 hover:text-slate-800 p-2 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-rotate-right" :class="isLoading ? 'fa-spin' : ''"></i> Segarkan
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] text-slate-400 uppercase tracking-widest font-black">
                                    <th class="p-4 pl-6">ID & Waktu</th>
                                    <th class="p-4">Rincian / Keperluan</th>
                                    <th class="p-4">Petugas & Shift</th>
                                    <th class="p-4 text-right">Nominal Pengeluaran</th>
                                    <th class="p-4 pr-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs divide-y divide-slate-100">
                                <template x-for="item in filteredHistory" :key="item.id">
                                    <tr class="hover:bg-rose-50/20 transition-colors group">
                                        <!-- ID & Waktu -->
                                        <td class="p-4 pl-6 font-bold">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-mono text-[10px] font-black" x-text="'#PC-' + String(item.id).padStart(4, '0')"></span>
                                                <span class="text-slate-700 text-xs font-black" x-text="formatDateTime(item.created_at).date"></span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-semibold mt-0.5 flex items-center gap-1">
                                                <i class="fa-regular fa-clock text-[9px]"></i>
                                                <span x-text="formatDateTime(item.created_at).time"></span>
                                            </div>
                                        </td>

                                        <!-- Keterangan / Keperluan -->
                                        <td class="p-4">
                                            <div class="flex items-start gap-2 max-w-md">
                                                <div class="w-6 h-6 rounded-lg bg-rose-100/60 text-rose-600 flex items-center justify-center shrink-0 text-[10px] mt-0.5">
                                                    <i class="fa-solid fa-arrow-up"></i>
                                                </div>
                                                <div>
                                                    <p class="font-black text-slate-800 text-xs whitespace-normal break-words" x-text="item.keterangan"></p>
                                                    <span class="inline-block mt-0.5 text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.2 rounded">Biaya Operasional</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Petugas & Shift -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-black text-[10px]">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800 text-xs" x-text="item.user_name"></p>
                                                    <p class="text-[10px] text-slate-400 font-semibold" x-text="item.shift_name + ' • ' + (item.warehouse_name || 'Outlet')"></p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Nominal -->
                                        <td class="p-4 text-right">
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-rose-50 border border-rose-100/80">
                                                <i class="fa-solid fa-minus text-[10px] text-rose-500"></i>
                                                <span class="font-black text-sm text-rose-600 tracking-tight" x-text="'Rp ' + formatRupiah(item.nominal)"></span>
                                            </div>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="p-4 pr-6 text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <button @click="printVoucher(item)" title="Cetak Bukti Pengeluaran" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 transition-colors flex items-center justify-center text-xs">
                                                    <i class="fa-solid fa-print"></i>
                                                </button>
                                                <button @click="deleteItem(item)" title="Hapus Catatan" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 transition-colors flex items-center justify-center text-xs">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- EMPTY STATE -->
                                <tr x-show="filteredHistory.length === 0 && !isLoading">
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400 text-2xl mx-auto mb-3">
                                            <i class="fa-solid fa-receipt"></i>
                                        </div>
                                        <h4 class="font-black text-slate-700 text-sm">Tidak Ada Catatan Pengeluaran Kas</h4>
                                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">Belum ada pengeluaran kas kecil yang tercatat pada rentang tanggal atau kata kunci yang dipilih.</p>
                                        <button @click="openModal()" class="mt-4 inline-flex items-center gap-2 bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-2 rounded-xl font-black text-xs transition-all">
                                            <i class="fa-solid fa-plus-circle"></i> Catat Pengeluaran Sekarang
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- MODAL FORM CATAT PENGELUARAN KAS -->
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" x-cloak>
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="closeModal()"></div>
                
                <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl relative z-10 flex flex-col overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                    
                    <!-- Header Modal -->
                    <div class="px-6 py-5 bg-gradient-to-r from-rose-500 to-rose-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-xs flex items-center justify-center text-white border border-white/20">
                                <i class="fa-solid fa-arrow-up-from-bracket text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-white leading-tight">Catat Pengeluaran Kas</h3>
                                <p class="text-xs text-rose-100 font-medium">Petty Cash Out • Biaya Operasional Toko</p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-6 space-y-5 overflow-y-auto max-h-[75vh] custom-scrollbar">
                        
                        <!-- Input Nominal -->
                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                                <span>Nominal Pengeluaran</span>
                                <span class="text-[10px] text-rose-500 font-bold lowercase">wajib diisi</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400 text-base">Rp</span>
                                <input 
                                    id="inputNominalKas"
                                    type="text" 
                                    :value="form.nominalDisplay"
                                    @input="onNominalInput($event)"
                                    placeholder="0"
                                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 font-black text-2xl text-slate-900 outline-none focus:border-rose-500 focus:bg-white transition-all shadow-inner"
                                >
                            </div>

                            <!-- Tombol Cepat Nominal -->
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                <template x-for="amt in quickNominals" :key="amt">
                                    <button type="button" @click="setQuickNominal(amt)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 text-[11px] font-black transition-colors">
                                        +<span x-text="formatRupiah(amt)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Tag Cepat Keperluan -->
                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">
                                Rekomendasi Tag Keperluan
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="tag in quickTags" :key="tag">
                                    <button type="button" @click="addQuickTag(tag)" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-rose-100 hover:text-rose-700 text-slate-600 text-xs font-bold transition-all flex items-center gap-1">
                                        <i class="fa-solid fa-tag text-[9px] opacity-60"></i>
                                        <span x-text="tag"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Input Keterangan Manual -->
                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                                <span>Keterangan Lengkap</span>
                                <span class="text-[10px] text-slate-400 font-semibold">cth: Toko Plastik 2 pak, Es Batu 1 bag</span>
                            </label>
                            <textarea 
                                x-model="form.keterangan" 
                                rows="3" 
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 outline-none focus:border-rose-500 focus:bg-white transition-all placeholder:text-slate-400"
                                placeholder="Tuliskan tujuan / keperluan pengeluaran kas secara spesifik..."
                            ></textarea>
                        </div>

                        <!-- Info Petugas & Shift -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="text-[11px]">
                                <p class="font-black text-slate-700">Tercatat Otomatis</p>
                                <p class="text-slate-400">Pengeluaran akan langsung terkait dengan shift kasir yang sedang aktif dan laporan shift.</p>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Modal -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="closeModal()" class="px-5 py-2.5 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-200 transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="saveData()" :disabled="isSaving" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-black text-xs text-white bg-rose-600 hover:bg-rose-700 active:scale-95 shadow-md shadow-rose-600/20 disabled:opacity-50 transition-all cursor-pointer">
                            <i x-show="isSaving" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                            <i x-show="!isSaving" class="fa-solid fa-check text-xs"></i>
                            <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Pengeluaran'"></span>
                        </button>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>