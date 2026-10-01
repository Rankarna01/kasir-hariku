<?php
require_once '../../../config/auth.php';
$page_title = "Manajemen Shift Panel - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="shiftPanelApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER APPS -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-business-time text-amber-300"></i> Manajemen Shift Panel
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Kontrol jam kerja, monitor kasir on-shift & riwayat sesi kasir</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 shadow-sm shadow-emerald-500/30">
                    <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Tambah Shift Baru</span>
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] space-y-6">
            <div class="w-full max-w-full space-y-6">

                <!-- 1. KPI SUMMARY CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Total Master Shift -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Master Shift</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="stats.total_master || 0">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Konfigurasi jadwal shift</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>

                    <!-- Master Shift Aktif -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Shift Aktif</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1" x-text="stats.total_active || 0">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tersedia untuk kasir</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>

                    <!-- Kasir On-Shift Live -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kasir Sedang On-Shift</p>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            </div>
                            <h3 class="text-2xl font-black text-indigo-600 mt-1" x-text="stats.total_live || 0">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Kasir aktif di mesin POS</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0 relative z-10">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. TAB NAVIGATION -->
                <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-2 overflow-x-auto custom-scrollbar">
                    <button @click="activeTab = 'master'" 
                            :class="activeTab === 'master' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                            class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-list-check"></i> Master Shift Kerja
                    </button>
                    <button @click="activeTab = 'live'" 
                            :class="activeTab === 'live' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                            class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-satellite-dish"></i> Live Monitor Kasir
                        <span x-show="stats.total_live > 0" class="bg-emerald-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black ml-1" x-text="stats.total_live"></span>
                    </button>
                    <button @click="activeTab = 'history'; fetchHistory();" 
                            :class="activeTab === 'history' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 font-bold'"
                            class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Sesi Shift
                    </button>
                </div>

                <!-- LOADING SPINNER -->
                <div x-show="isLoading" class="text-center py-16 flex flex-col items-center justify-center">
                    <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-3"></div>
                    <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">Memuat Data Shift...</p>
                </div>

                <!-- ============================================== -->
                <!-- TAB 1: MASTER SHIFT KERJA -->
                <!-- ============================================== -->
                <div x-show="!isLoading && activeTab === 'master'" class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-50/50">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-calendar-day text-blue-600"></i> Pengaturan Jam Operasional Shift
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Tentukan jam mulai, jam selesai, dan aktifkan shift untuk digunakan di kasir & penjadwalan</p>
                        </div>
                        <button @click="openModal()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Tambah Shift
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] text-slate-400 uppercase tracking-widest">
                                    <th class="p-4 font-black">Nama Shift</th>
                                    <th class="p-4 font-black text-center">Jam Mulai</th>
                                    <th class="p-4 font-black text-center">Jam Selesai</th>
                                    <th class="p-4 font-black text-center">Durasi Kerja</th>
                                    <th class="p-4 font-black text-center">Status</th>
                                    <th class="p-4 font-black text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <template x-for="s in shifts" :key="s.id">
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <i class="fa-solid fa-briefcase"></i>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-xs sm:text-sm" x-text="s.shift_name"></div>
                                                    <div class="text-[10px] text-slate-400 font-medium" x-text="'Dibuat: ' + (s.created_at ? s.created_at.substring(0, 10) : '-')"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center font-bold text-emerald-600">
                                            <span class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-lg text-xs font-black" x-text="formatTime(s.start_time)"></span>
                                        </td>
                                        <td class="p-4 text-center font-bold text-rose-600">
                                            <span class="bg-rose-50 text-rose-700 px-3 py-1 rounded-lg text-xs font-black" x-text="formatTime(s.end_time)"></span>
                                        </td>
                                        <td class="p-4 text-center font-bold text-slate-600">
                                            <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg text-xs font-black" x-text="(s.duration_hours || 0) + ' Jam'"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button @click="toggleStatus(s.id, s.is_active == 1 ? 0 : 1)" 
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black cursor-pointer transition-all"
                                                    :class="s.is_active == 1 ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                                <i class="fa-solid" :class="s.is_active == 1 ? 'fa-check-circle' : 'fa-times-circle'"></i>
                                                <span x-text="s.is_active == 1 ? 'Aktif' : 'Non-Aktif'"></span>
                                            </button>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openModal(s)" title="Edit Shift" class="w-8 h-8 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-xs">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </button>
                                                <button @click="deleteShift(s.id)" title="Hapus Shift" class="w-8 h-8 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-xs">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="shifts.length === 0">
                                    <td colspan="6" class="p-12 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-business-time text-3xl mb-2 text-slate-300 block"></i>
                                        Belum ada master shift kerja yang dibuat. Silakan klik tombol Tambah Shift Baru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- TAB 2: LIVE MONITOR KASIR ON-SHIFT -->
                <!-- ============================================== -->
                <div x-show="!isLoading && activeTab === 'live'" class="space-y-4">
                    <div class="bg-white p-5 rounded-3xl shadow-xs border border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-satellite-dish text-indigo-600"></i> Kasir Sedang Buka Shift (Live Real-Time)
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Pantau kasir yang saat ini aktif mengoperasikan mesin kasir POS di masing-masing outlet</p>
                        </div>
                        <button @click="fetchData()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                            <i class="fa-solid fa-rotate"></i> Refresh Monitor
                        </button>
                    </div>

                    <!-- Live Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="ls in liveShifts" :key="ls.id">
                            <div class="bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500"></div>

                                <div class="flex items-center justify-between mb-3 mt-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sedang Berjalan
                                    </span>
                                    <span class="text-xs font-bold text-slate-400" x-text="ls.store_name"></span>
                                </div>

                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-lg shadow-xs shrink-0">
                                        <i class="fa-solid fa-user-tag"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h4 class="font-black text-slate-800 text-sm truncate" x-text="ls.kasir_name"></h4>
                                        <p class="text-xs text-indigo-600 font-bold truncate" x-text="ls.shift_name"></p>
                                    </div>
                                </div>

                                <div class="space-y-2 bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-xs font-medium mb-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">Jam Masuk:</span>
                                        <span class="font-bold text-slate-700" x-text="formatDateTime(ls.start_time)"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">Durasi On-Shift:</span>
                                        <span class="font-bold text-indigo-600" x-text="ls.elapsed_formatted"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">Modal Awal Laci:</span>
                                        <span class="font-bold text-slate-700" x-text="formatRupiah(ls.start_cash)"></span>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-slate-200/60 pt-1.5">
                                        <span class="text-slate-400">Omset Cash Masuk:</span>
                                        <span class="font-black text-emerald-600" x-text="formatRupiah(ls.current_cash_in)"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">Petty Cash Keluar:</span>
                                        <span class="font-bold text-amber-600" x-text="formatRupiah(ls.current_petty_cash)"></span>
                                    </div>
                                </div>

                                <button @click="openForceCloseModal(ls)" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 py-2.5 rounded-xl font-black text-xs transition-all flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-lock"></i> Paksa Tutup Shift Ini
                                </button>
                            </div>
                        </template>

                        <div x-show="liveShifts.length === 0" class="col-span-full bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-400 font-bold">
                            <i class="fa-solid fa-mug-hot text-4xl mb-3 text-slate-300 block"></i>
                            Tidak ada kasir yang sedang membuka shift saat ini. Semua shift kasir dalam status closed.
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- TAB 3: RIWAYAT SESI SHIFT -->
                <!-- ============================================== -->
                <div x-show="!isLoading && activeTab === 'history'" class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden space-y-4 p-5">
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> Log Histori Sesi Shift & Closing Kasir
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Audit perhitungan uang fisik laci, uang masuk, dan selisih kasir per sesi</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <input type="date" x-model="historyFilter.startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold outline-none text-slate-700">
                            <span class="text-slate-400 text-xs font-bold">s/d</span>
                            <input type="date" x-model="historyFilter.endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold outline-none text-slate-700">
                            <button @click="fetchHistory()" class="bg-primary hover:bg-blue-700 text-white px-4 py-1.5 rounded-xl text-xs font-black transition-all">
                                Filter
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase tracking-widest text-[10px]">
                                    <th class="p-3.5 font-black">Kasir & Shift</th>
                                    <th class="p-3.5 font-black">Waktu Kerja</th>
                                    <th class="p-3.5 font-black text-center">Outlet</th>
                                    <th class="p-3.5 font-black text-right">Modal Awal</th>
                                    <th class="p-3.5 font-black text-right text-emerald-600">+ Cash Masuk</th>
                                    <th class="p-3.5 font-black text-right text-amber-600">- Kas Keluar</th>
                                    <th class="p-3.5 font-black text-right">Sistem Harus</th>
                                    <th class="p-3.5 font-black text-right text-blue-600">Laci Real</th>
                                    <th class="p-3.5 font-black text-center">Selisih</th>
                                    <th class="p-3.5 font-black text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <template x-for="h in historyData" :key="h.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3.5 font-black text-slate-800">
                                            <div x-text="h.kasir_name"></div>
                                            <div class="text-[10px] text-blue-600 font-bold" x-text="h.shift_name"></div>
                                        </td>
                                        <td class="p-3.5 text-slate-600">
                                            <div x-text="h.formatted_start"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'s/d ' + h.formatted_end"></div>
                                        </td>
                                        <td class="p-3.5 text-center font-bold text-slate-700" x-text="h.store_name"></td>
                                        <td class="p-3.5 text-right font-bold text-slate-700" x-text="formatRupiah(h.start_cash)"></td>
                                        <td class="p-3.5 text-right font-bold text-emerald-600" x-text="formatRupiah(h.total_cash_in)"></td>
                                        <td class="p-3.5 text-right font-bold text-amber-600" x-text="formatRupiah(h.total_kas_keluar)"></td>
                                        <td class="p-3.5 text-right font-black text-slate-800" x-text="formatRupiah(h.expected_cash)"></td>
                                        <td class="p-3.5 text-right font-black text-blue-700">
                                            <span x-show="h.status === 'closed'" x-text="formatRupiah(h.end_cash)"></span>
                                            <span x-show="h.status === 'open'" class="italic text-slate-400">Belum Tutup</span>
                                        </td>
                                        <td class="p-3.5 text-center font-black">
                                            <div x-show="h.status === 'closed'">
                                                <span x-show="h.selisih < 0" class="text-rose-500 bg-rose-50 px-2 py-0.5 rounded-lg" x-text="formatRupiah(h.selisih)"></span>
                                                <span x-show="h.selisih === 0" class="text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded-lg">PAS (0)</span>
                                                <span x-show="h.selisih > 0" class="text-blue-500 bg-blue-50 px-2 py-0.5 rounded-lg" x-text="'+' + formatRupiah(h.selisih)"></span>
                                            </div>
                                            <span x-show="h.status === 'open'" class="text-slate-300">-</span>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase"
                                                  :class="h.status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700'">
                                                <span x-text="h.status === 'closed' ? 'Closed' : 'Open'"></span>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="historyData.length === 0">
                                    <td colspan="10" class="p-10 text-center text-slate-400 font-bold">
                                        Tidak ada riwayat shift pada periode ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <!-- ============================================== -->
        <!-- MODAL ADD / EDIT MASTER SHIFT -->
        <!-- ============================================== -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200" @click.outside="showModal = false">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-base text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-clock text-blue-600"></i>
                        <span x-text="formData.id ? 'Edit Master Shift' : 'Tambah Master Shift Baru'"></span>
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveShift()" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Nama Shift</label>
                        <input type="text" x-model="formData.shift_name" required placeholder="Contoh: Shift Pagi, Shift Siang, Shift Malam" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Jam Mulai</label>
                            <input type="time" x-model="formData.start_time" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Jam Selesai</label>
                            <input type="time" x-model="formData.end_time" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Status Shift</label>
                        <select x-model="formData.is_active" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="1">Aktif (Dapat dipilih kasir & dijadwalkan)</option>
                            <option value="0">Non-Aktif (Disembunyikan)</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-black text-xs hover:bg-slate-50 transition-all">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSaving" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 disabled:opacity-50">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODAL FORCE CLOSE SHIFT -->
        <!-- ============================================== -->
        <div x-show="showForceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200" @click.outside="showForceModal = false">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-rose-50 text-rose-800">
                    <h3 class="font-black text-base flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Paksa Tutup Shift Kasir
                    </h3>
                    <button @click="showForceModal = false" class="text-rose-400 hover:text-rose-700 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form @submit.prevent="submitForceClose()" class="p-6 space-y-4">
                    <p class="text-xs text-slate-500 font-medium">
                        Tindakan ini digunakan jika kasir lupa menutup shift atau perangkat kasir ditinggalkan. Sesi shift kasir <strong class="text-slate-800" x-text="selectedLiveShift?.kasir_name"></strong> akan segera diakhiri.
                    </p>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Uang Fisik Kas Akhir (Opsional)</label>
                        <input type="number" step="any" x-model="forceCloseCash" placeholder="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-black text-slate-800 outline-none focus:ring-2 focus:ring-primary/20">
                        <p class="text-[10px] text-slate-400 font-bold mt-1">Masukkan nominal uang laci jika sudah dihitung, atau biarkan 0.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showForceModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-black text-xs hover:bg-slate-50 transition-all">
                            Batal
                        </button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2">
                            <i class="fa-solid fa-lock"></i> Konfirmasi Tutup Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script src="ajax.js"></script>
</body>
</html>