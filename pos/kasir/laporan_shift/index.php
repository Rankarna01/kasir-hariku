<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$base_sub = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/pos/') !== false ? trim(explode('/pos/', $_SERVER['SCRIPT_NAME'])[0], '/') : 'kasir-hariku';
$folder = $is_localhost ? (defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_PATH) : ($base_sub !== '' ? '/' . $base_sub . '/' : '/')) : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $host . $folder); }
$page_title = "Laporan Shift Kasir - Ayam Goreng Hariku";
$today_ymd = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include __DIR__ . '/../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="shiftReportApp()" x-cloak>

    <?php include __DIR__ . '/../../../components/sidebar_kasir.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- TOPBAR HEADER -->
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 flex justify-between items-center z-20 shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="w-10 h-10 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white transition-all flex items-center justify-center cursor-pointer shadow-xs active:scale-95" title="Menu Sidebar">
                    <i class="fa-solid fa-bars text-base"></i>
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary to-rose-500 text-white flex items-center justify-center shadow-xs">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-slate-800 tracking-tight leading-none">Laporan Shift Kasir</h2>
                        <p class="text-[11px] text-slate-400 font-medium hidden sm:block mt-0.5">Monitoring rekapitulasi sesi kasir & kas fisik harian</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="applyFilter()" class="h-9 px-3 rounded-xl bg-slate-100 hover:bg-primary-50 text-slate-600 hover:text-primary transition-all text-xs font-bold flex items-center gap-1.5 border border-slate-200" title="Muat Ulang Data">
                    <i class="fa-solid fa-rotate-right" :class="isLoading ? 'fa-spin' : ''"></i>
                    <span class="hidden sm:inline">Refresh</span>
                </button>
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="bg-rose-50/80 text-primary border border-primary/20 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-store text-primary"></i> 
                    <span>Outlet: <strong class="font-black"><?= htmlspecialchars($_SESSION['pos_store_name']) ?></strong></span>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-3 sm:p-5 bg-slate-100/60">
            <div class="w-full max-w-full space-y-4">
                
                <!-- SUMMARY STATS CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
                    <!-- 1. Total Sesi -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-primary to-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-primary/20">
                            <i class="fa-solid fa-business-time text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Shift</div>
                            <div class="text-base sm:text-xl font-black text-slate-800 leading-tight">
                                <span x-text="summary.total_shifts || 0"></span> <span class="text-xs font-bold text-slate-400">Sesi</span>
                            </div>
                            <div class="text-[10px] text-slate-500 font-bold truncate mt-0.5">
                                <span class="text-emerald-600 font-black" x-text="summary.closed_shifts || 0"></span> Ditutup • <span class="text-amber-600 font-black" x-text="summary.open_shifts || 0"></span> Berjalan
                            </div>
                        </div>
                    </div>

                    <!-- 2. Saldo Sistem -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-cash-register text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Saldo Sistem</div>
                            <div class="text-base sm:text-xl font-black text-slate-800 leading-tight">
                                Rp <span x-text="formatRupiah(summary.total_system || 0)"></span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                Ekspektasi kas laci
                            </div>
                        </div>
                    </div>

                    <!-- 3. Fisik End Cash -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-money-bill-wave text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fisik (End Cash)</div>
                            <div class="text-base sm:text-xl font-black text-emerald-600 leading-tight">
                                Rp <span x-text="formatRupiah(summary.total_actual || 0)"></span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                Kas riil disetor kasir
                            </div>
                        </div>
                    </div>

                    <!-- 4. Total Selisih -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border"
                             :class="(summary.total_difference || 0) < 0 ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200'">
                            <i class="fa-solid text-lg" :class="(summary.total_difference || 0) < 0 ? 'fa-triangle-exclamation' : 'fa-scale-balanced'"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Selisih</div>
                            <div class="text-base sm:text-xl font-black leading-tight"
                                 :class="(summary.total_difference || 0) < 0 ? 'text-rose-600' : ((summary.total_difference || 0) > 0 ? 'text-emerald-600' : 'text-slate-700')">
                                <span x-text="(summary.total_difference || 0) < 0 ? '- Rp ' : 'Rp '"></span><span x-text="formatRupiah(Math.abs(summary.total_difference || 0))"></span>
                            </div>
                            <div class="text-[10px] font-bold truncate mt-0.5"
                                 :class="(summary.total_difference || 0) < 0 ? 'text-rose-500' : 'text-emerald-600'">
                                <span x-text="(summary.total_difference || 0) === 0 ? '✓ Semua Saldo Pas' : ((summary.total_difference || 0) < 0 ? 'Perlu rekonsiliasi kasir' : 'Terdapat kelebihan kas')"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTER BAR CARD -->
                <div class="bg-white p-3 sm:p-4 rounded-2xl shadow-xs border border-slate-200 space-y-3">
                    <!-- Quick Presets -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider mr-1">Filter Cepat:</span>
                            <button type="button" @click="setQuickFilter('today')" 
                                :class="activeQuickFilter === 'today' ? 'bg-primary text-white shadow-xs ring-2 ring-primary/30 font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-regular fa-calendar-check text-[10px]"></i> Hari Ini
                            </button>
                            <button type="button" @click="setQuickFilter('yesterday')" 
                                :class="activeQuickFilter === 'yesterday' ? 'bg-primary text-white shadow-xs ring-2 ring-primary/30 font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                Kemarin
                            </button>
                            <button type="button" @click="setQuickFilter('7days')" 
                                :class="activeQuickFilter === '7days' ? 'bg-primary text-white shadow-xs ring-2 ring-primary/30 font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                7 Hari
                            </button>
                            <button type="button" @click="setQuickFilter('this_month')" 
                                :class="activeQuickFilter === 'this_month' ? 'bg-primary text-white shadow-xs ring-2 ring-primary/30 font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                Bulan Ini
                            </button>
                            <button type="button" @click="setQuickFilter('all')" 
                                :class="activeQuickFilter === 'all' ? 'bg-primary text-white shadow-xs ring-2 ring-primary/30 font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" 
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                Semua Data
                            </button>
                        </div>
                        <div class="text-[11px] font-bold text-slate-400">
                            Menampilkan <strong class="text-slate-700" x-text="shifts.length"></strong> dari <strong class="text-slate-700" x-text="totalData"></strong> shift
                        </div>
                    </div>

                    <!-- Date Picker & Search -->
                    <div class="flex flex-col md:flex-row gap-2.5 items-stretch">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1 sm:w-44">
                                <i class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input type="date" x-model="filters.startDate" @change="activeQuickFilter = 'custom'" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                            <span class="text-slate-400 font-bold text-xs shrink-0">s/d</span>
                            <div class="relative flex-1 sm:w-44">
                                <i class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                <input type="date" x-model="filters.endDate" @change="activeQuickFilter = 'custom'" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                            </div>
                        </div>

                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="filters.search" @input.debounce.400ms="applyFilter()" 
                                placeholder="Cari nama kasir atau shift..." 
                                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 font-bold text-xs text-slate-700 transition-all">
                        </div>

                        <button @click="applyFilter()" 
                            class="bg-gradient-to-r from-primary to-rose-600 hover:from-rose-600 hover:to-primary text-white px-5 py-2 rounded-xl font-bold text-xs transition-all shadow-sm shadow-primary/20 flex items-center justify-center gap-2 cursor-pointer active:scale-95 shrink-0">
                            <i class="fa-solid fa-filter text-xs"></i> Terapkan Filter
                        </button>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden relative">
                    <div x-show="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-xs">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-primary"></i>
                            <span class="text-xs font-bold text-slate-500">Memuat riwayat shift...</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-primary text-white border-b border-[#E62058] text-xs uppercase tracking-wider">
                                    <th class="py-3.5 px-4 font-black">Waktu Buka</th>
                                    <th class="py-3.5 px-4 font-black">Waktu Tutup</th>
                                    <th class="py-3.5 px-4 font-black">Identitas Kasir</th>
                                    <th class="py-3.5 px-4 font-black text-right">Saldo Sistem</th>
                                    <th class="py-3.5 px-4 font-black text-right">Fisik (End Cash)</th>
                                    <th class="py-3.5 px-4 font-black text-center">Status</th>
                                    <th class="py-3.5 px-4 font-black text-center w-24"><i class="fa-solid fa-ellipsis"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-xs divide-y divide-slate-100">
                                <tr x-show="shifts.length === 0">
                                    <td colspan="7" class="py-12 px-4 text-center text-slate-400 font-bold">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl mb-2">
                                            <i class="fa-solid fa-calendar-xmark"></i>
                                        </div>
                                        <div class="text-sm font-bold text-slate-600">Tidak ada riwayat shift untuk rentang tanggal ini.</div>
                                        <div class="text-xs text-slate-400 font-medium mt-1">Coba sesuaikan tanggal filter atau klik "Semua Data".</div>
                                    </td>
                                </tr>
                                <template x-for="shift in shifts" :key="shift.id">
                                    <tr class="transition-colors hover:bg-slate-50/70">
                                        <!-- Waktu Buka -->
                                        <td class="py-3 px-4">
                                            <div class="font-black text-slate-800" x-text="formatDateOnly(shift.start_time)"></div>
                                            <div class="text-[10px] text-slate-500 font-bold flex items-center gap-1 mt-0.5">
                                                <i class="fa-regular fa-clock text-primary text-[10px]"></i>
                                                <span x-text="formatTimeOnly(shift.start_time)"></span>
                                            </div>
                                        </td>

                                        <!-- Waktu Tutup -->
                                        <td class="py-3 px-4">
                                            <template x-if="shift.end_time">
                                                <div>
                                                    <div class="font-black text-slate-800" x-text="formatDateOnly(shift.end_time)"></div>
                                                    <div class="text-[10px] text-slate-500 font-bold flex items-center gap-1 mt-0.5">
                                                        <i class="fa-regular fa-clock text-slate-400 text-[10px]"></i>
                                                        <span x-text="formatTimeOnly(shift.end_time)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="!shift.end_time">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Masih Berjalan
                                                </span>
                                            </template>
                                        </td>

                                        <!-- Identitas Kasir -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-primary-50 text-primary flex items-center justify-center font-black text-xs shrink-0">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-xs" x-text="shift.cashier_name"></div>
                                                    <div class="text-[10px] text-slate-500 font-semibold flex items-center gap-1 mt-0.5">
                                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-bold" x-text="shift.shift_name || 'Shift ' + (shift.shift_id || '-')"></span>
                                                        <span>#<span x-text="shift.id"></span></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Saldo Sistem -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="font-black text-slate-800 text-sm">
                                                Rp <span x-text="formatRupiah(shift.system_balance)"></span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium">Modal + Net Kas</div>
                                        </td>

                                        <!-- Fisik (End Cash) & Selisih -->
                                        <td class="py-3 px-4 text-right">
                                            <template x-if="shift.status === 'open'">
                                                <span class="text-slate-400 font-bold">-</span>
                                            </template>
                                            <template x-if="shift.status === 'closed'">
                                                <div>
                                                    <div class="font-black text-slate-800 text-sm">
                                                        Rp <span x-text="formatRupiah(shift.end_cash)"></span>
                                                    </div>
                                                    <div class="mt-0.5">
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded"
                                                              :class="shift.difference < 0 ? 'bg-rose-50 text-rose-600 border border-rose-200' : (shift.difference > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600')">
                                                            <i class="fa-solid text-[9px]" :class="shift.difference < 0 ? 'fa-arrow-down' : (shift.difference > 0 ? 'fa-arrow-up' : 'fa-check')"></i>
                                                            <span x-text="shift.difference < 0 ? 'Minus ' : (shift.difference > 0 ? 'Lebih ' : 'Pas ')"></span>
                                                            <span x-text="'Rp ' + formatRupiah(Math.abs(shift.difference))"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-4 text-center">
                                            <template x-if="shift.status === 'open'">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                                    Sedang Jalan
                                                </span>
                                            </template>
                                            <template x-if="shift.status === 'closed'">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                                    Ditutup
                                                </span>
                                            </template>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openDetail(shift)" 
                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-primary-50 text-slate-600 hover:text-primary transition-all flex items-center justify-center cursor-pointer" 
                                                    title="Lihat Rincian Sesi">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </button>
                                                <button x-show="shift.status === 'closed'" @click="printShift(shift.id)" 
                                                    class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-primary text-primary hover:text-white transition-all flex items-center justify-center border border-primary/20 cursor-pointer" 
                                                    title="Cetak Ulang Struk Tutup Shift">
                                                    <i class="fa-solid fa-print text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION FOOTER -->
                    <div class="p-3 sm:p-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-center gap-2">
                        <span class="text-xs text-slate-500 font-bold">
                            Halaman <strong class="text-slate-800" x-text="currentPage"></strong> dari <strong class="text-slate-800" x-text="totalPages"></strong> (Total <span x-text="totalData"></span> shift)
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button @click="prevPage()" :disabled="currentPage <= 1" 
                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-primary-50 hover:text-primary disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i> Sebelumnya
                            </button>
                            <button @click="nextPage()" :disabled="currentPage >= totalPages" 
                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-primary-50 hover:text-primary disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1">
                                Berikutnya <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- MODAL DETAIL SESI KASIR -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;" x-cloak>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" @click="showModal = false"></div>
            
            <div class="bg-white w-full max-w-3xl rounded-[2rem] shadow-2xl relative z-10 flex flex-col max-h-[90vh] m-4 transform overflow-hidden border border-slate-200"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-primary text-white flex items-center justify-center text-xs shadow-xs">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-base text-slate-800 leading-tight">Rincian Sesi Kasir</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Shift ID #<span x-text="activeShift?.id"></span> • <span x-text="activeShift?.shift_name || 'Shift Operasional'"></span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button x-show="activeShift?.status === 'closed'" @click="printShift(activeShift.id)" class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-primary text-primary hover:text-white border border-primary/20 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-print"></i> Cetak Struk
                        </button>
                        <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center cursor-pointer text-slate-600">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white space-y-5" x-show="activeShift">
                    <!-- Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Informasi Shift -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-200 pb-2">Informasi Shift</h4>
                            <div class="space-y-2 text-xs font-bold text-slate-600">
                                <div class="flex justify-between"><span>Nama Kasir</span><span class="text-slate-800 font-black" x-text="activeShift?.cashier_name"></span></div>
                                <div class="flex justify-between"><span>Jenis Shift</span><span class="text-primary font-black" x-text="activeShift?.shift_name || 'Shift Kasir'"></span></div>
                                <div class="flex justify-between"><span>Waktu Buka</span><span class="text-slate-800" x-text="formatDate(activeShift?.start_time)"></span></div>
                                <div class="flex justify-between"><span>Waktu Tutup</span><span class="text-slate-800" x-text="activeShift?.end_time ? formatDate(activeShift?.end_time) : 'Masih Berjalan'"></span></div>
                                <div class="flex justify-between"><span>Status Sesi</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black" :class="activeShift?.status === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800'" x-text="activeShift?.status === 'closed' ? 'DITUTUP' : 'SEDANG JALAN'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Rekapitulasi Kas Laci -->
                        <div class="bg-rose-50/50 p-4 rounded-2xl border border-primary/20">
                            <h4 class="text-[10px] font-black text-primary uppercase tracking-widest mb-3 border-b border-primary/20 pb-2">Rekapitulasi Saldo Laci (Tunai)</h4>
                            <div class="space-y-2 text-xs font-bold text-slate-600">
                                <div class="flex justify-between"><span>Modal Awal Kas</span><span class="text-slate-800" x-text="'Rp ' + formatRupiah(activeShift?.start_cash)"></span></div>
                                <div class="flex justify-between text-emerald-600"><span>Penjualan Tunai</span><span x-text="'+ Rp ' + formatRupiah(activeShift?.total_cash_sales)"></span></div>
                                <div class="flex justify-between text-teal-600"><span>Pelunasan Piutang</span><span x-text="'+ Rp ' + formatRupiah(activeShift?.total_cash_pelunasan)"></span></div>
                                <div class="flex justify-between text-rose-600"><span>Kas Keluar (Petty Cash)</span><span x-text="'- Rp ' + formatRupiah(activeShift?.total_kas_keluar)"></span></div>
                                <div class="flex justify-between font-black text-slate-800 border-t border-primary/20 pt-2 mt-2 text-sm">
                                    <span>Saldo Sistem (Diharapkan)</span> 
                                    <span class="text-primary" x-text="'Rp ' + formatRupiah(activeShift?.system_balance)"></span>
                                </div>
                                <div class="flex justify-between font-black text-slate-800 text-sm">
                                    <span>Kas Fisik (End Cash)</span> 
                                    <span class="text-emerald-700" x-text="activeShift?.status === 'closed' ? 'Rp ' + formatRupiah(activeShift?.end_cash) : '-'"></span>
                                </div>
                                <div x-show="activeShift?.status === 'closed'" class="flex justify-between font-black pt-1" :class="activeShift?.difference < 0 ? 'text-rose-600' : (activeShift?.difference > 0 ? 'text-emerald-600' : 'text-slate-600')">
                                    <span>Selisih</span>
                                    <span x-text="(activeShift?.difference < 0 ? 'Minus Rp ' : (activeShift?.difference > 0 ? 'Lebih Rp ' : 'Pas Rp ')) + formatRupiah(Math.abs(activeShift?.difference || 0))"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaksi di Shift Ini -->
                    <div x-show="activeTransactions && activeTransactions.length > 0">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-2.5 flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-primary"></i> Transaksi Penjualan (<span x-text="activeTransactions.length"></span>)
                        </h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px]">
                                    <tr>
                                        <th class="p-2.5 font-bold">Waktu</th>
                                        <th class="p-2.5 font-bold">Invoice</th>
                                        <th class="p-2.5 font-bold">Pelanggan</th>
                                        <th class="p-2.5 font-bold">Metode</th>
                                        <th class="p-2.5 font-bold text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="t in activeTransactions" :key="t.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-2.5 text-slate-500" x-text="new Date(t.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}).replace('.',':')"></td>
                                            <td class="p-2.5 font-bold text-slate-800" x-text="t.invoice_no"></td>
                                            <td class="p-2.5 text-slate-600" x-text="t.customer_name"></td>
                                            <td class="p-2.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-bold uppercase" x-text="t.payment_method"></span></td>
                                            <td class="p-2.5 text-right font-black text-slate-800" x-text="'Rp ' + formatRupiah(t.total_amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Petty Cash di Shift Ini -->
                    <div x-show="activePettyCash && activePettyCash.length > 0">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-2.5 flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-transfer text-amber-500"></i> Mutasi Kas Masuk / Keluar (<span x-text="activePettyCash.length"></span>)
                        </h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-40 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 text-slate-600 uppercase text-[10px]">
                                    <tr>
                                        <th class="p-2.5 font-bold">Waktu</th>
                                        <th class="p-2.5 font-bold">Jenis</th>
                                        <th class="p-2.5 font-bold">Keterangan</th>
                                        <th class="p-2.5 font-bold text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="k in activePettyCash" :key="k.id">
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-2.5 text-slate-500" x-text="new Date(k.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}).replace('.',':')"></td>
                                            <td class="p-2.5">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase" :class="k.jenis === 'keluar' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'" x-text="k.jenis"></span>
                                            </td>
                                            <td class="p-2.5 text-slate-700" x-text="k.keterangan || '-'"></td>
                                            <td class="p-2.5 text-right font-black" :class="k.jenis === 'keluar' ? 'text-rose-600' : 'text-emerald-600'" x-text="(k.jenis === 'keluar' ? '- Rp ' : '+ Rp ') + formatRupiah(k.nominal)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex justify-end">
                    <button @click="showModal = false" class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>

    </div>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>