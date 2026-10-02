<?php
require_once '../../../config/auth.php';
$page_title = "Laporan Karyawan & Kasir - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="employeeReportApp()" x-cloak>

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
                        <i class="fa-solid fa-id-card-clip text-amber-300"></i> Laporan Kinerja Karyawan
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Analisis produktivitas kerja, jam dinas, omset & akurasi kasir</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button @click="printPdf()" class="bg-rose-500 hover:bg-rose-600 text-white px-3.5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-xs shadow-rose-500/30">
                    <i class="fa-solid fa-file-pdf"></i> <span class="hidden md:inline">Cetak PDF</span>
                </button>
                <button @click="exportExcel()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3.5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-xs shadow-emerald-500/30">
                    <i class="fa-solid fa-file-excel"></i> <span class="hidden md:inline">Export Excel</span>
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] space-y-6">
            <div class="w-full max-w-full space-y-6">

                <!-- 1. DATE PRESET & FILTER BAR -->
                <div class="bg-white p-4 rounded-3xl shadow-xs border border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    
                    <!-- Date Presets -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button @click="setPreset('today')" :class="currentPreset === 'today' ? 'bg-primary text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">Hari Ini</button>
                        <button @click="setPreset('yesterday')" :class="currentPreset === 'yesterday' ? 'bg-primary text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">Kemarin</button>
                        <button @click="setPreset('7days')" :class="currentPreset === '7days' ? 'bg-primary text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">7 Hari</button>
                        <button @click="setPreset('thisMonth')" :class="currentPreset === 'thisMonth' ? 'bg-primary text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">Bulan Ini</button>
                        <button @click="setPreset('lastMonth')" :class="currentPreset === 'lastMonth' ? 'bg-primary text-white font-black' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">Bulan Lalu</button>
                    </div>

                    <!-- Custom Date & Outlet Selectors -->
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <input type="date" x-model="startDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 outline-none">
                        <span class="text-xs font-bold text-slate-400">s/d</span>
                        <input type="date" x-model="endDate" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 outline-none">
                        <button @click="fetchReport()" class="bg-primary hover:bg-blue-700 text-white px-4 py-1.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                        </button>
                    </div>
                </div>

                <!-- 2. KPI SUMMARY CARDS -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <!-- Total Karyawan -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Karyawan</p>
                        <h4 class="text-xl font-black text-slate-800 mt-1" x-text="stats.total_employees || 0">0</h4>
                        <p class="text-[10px] text-slate-400 font-medium">Staf terdaftar</p>
                    </div>

                    <!-- Total Shift -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sesi Shift</p>
                        <h4 class="text-xl font-black text-blue-600 mt-1" x-text="stats.total_shifts || 0">0</h4>
                        <p class="text-[10px] text-slate-400 font-medium">Shift dijalankan</p>
                    </div>

                    <!-- Total Jam Kerja -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jam Kerja</p>
                        <h4 class="text-xl font-black text-indigo-600 mt-1" x-text="(stats.total_hours || 0) + ' Jam'">0 Jam</h4>
                        <p class="text-[10px] text-slate-400 font-medium">Akumulasi dinas</p>
                    </div>

                    <!-- Total Transaksi -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Transaksi</p>
                        <h4 class="text-xl font-black text-emerald-600 mt-1" x-text="stats.total_transactions || 0">0</h4>
                        <p class="text-[10px] text-slate-400 font-medium">Nota penjualan</p>
                    </div>

                    <!-- Total Omset -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Omset</p>
                        <h4 class="text-sm font-black text-slate-800 mt-1 truncate" x-text="formatRupiah(stats.total_omset)">Rp 0</h4>
                        <p class="text-[10px] text-slate-400 font-medium truncate" x-text="'Rata: ' + formatRupiah(stats.avg_basket_size)"></p>
                    </div>

                    <!-- Akumulasi Selisih Kas -->
                    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Selisih Kasir</p>
                        <h4 class="text-sm font-black mt-1 truncate" 
                            :class="stats.total_selisih < 0 ? 'text-rose-600' : (stats.total_selisih > 0 ? 'text-blue-600' : 'text-emerald-600')"
                            x-text="formatRupiah(stats.total_selisih)">Rp 0</h4>
                        <p class="text-[10px] text-slate-400 font-medium" x-text="stats.total_selisih === 0 ? 'Akurat 100%' : 'Ada selisih'"></p>
                    </div>
                </div>

                <!-- LOADING SPINNER -->
                <div x-show="isLoading" class="text-center py-16 flex flex-col items-center justify-center">
                    <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-3"></div>
                    <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">Menghitung Data Kinerja Karyawan...</p>
                </div>

                <!-- 3. TABEL KINERJA KARYAWAN -->
                <div x-show="!isLoading" class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden space-y-4 p-5">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="relative w-full sm:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama karyawan / outlet..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                        </div>

                        <!-- Filter Status Bertugas -->
                        <select x-model="roleFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 outline-none">
                            <option value="all">Semua Kasir Toko</option>
                            <option value="bertugas">Sudah Bertugas (Ada Shift)</option>
                            <option value="belum_bertugas">Belum Bertugas</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase tracking-widest text-[10px]">
                                    <th class="p-3.5 font-black">Karyawan</th>
                                    <th class="p-3.5 font-black text-center">Outlet</th>
                                    <th class="p-3.5 font-black text-center">Shift Selesai</th>
                                    <th class="p-3.5 font-black text-center">Total Jam</th>
                                    <th class="p-3.5 font-black text-center">Nota Transaksi</th>
                                    <th class="p-3.5 font-black text-right">Total Omset</th>
                                    <th class="p-3.5 font-black text-right">Rata-rata / Shift</th>
                                    <th class="p-3.5 font-black text-center">Selisih Kas</th>
                                    <th class="p-3.5 font-black text-center">Status Akurasi</th>
                                    <th class="p-3.5 font-black text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <template x-for="emp in filteredEmployees" :key="emp.user_id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="p-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0"
                                                     :class="emp.role_name.toLowerCase().includes('kasir') ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'"
                                                     x-text="getInitials(emp.name)">
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-800 text-xs" x-text="emp.name"></div>
                                                    <div class="text-[10px] text-slate-400" x-text="emp.role_name"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-center font-bold text-slate-600" x-text="emp.store_name"></td>
                                        <td class="p-3.5 text-center font-bold text-slate-700">
                                            <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg font-black" x-text="emp.total_shifts"></span>
                                        </td>
                                        <td class="p-3.5 text-center font-bold text-indigo-600" x-text="emp.total_hours + ' Jam'"></td>
                                        <td class="p-3.5 text-center font-bold text-slate-800" x-text="emp.total_transactions"></td>
                                        <td class="p-3.5 text-right font-black text-slate-900" x-text="formatRupiah(emp.total_omset)"></td>
                                        <td class="p-3.5 text-right font-bold text-slate-600" x-text="formatRupiah(emp.avg_omset_per_shift)"></td>
                                        <td class="p-3.5 text-center font-black">
                                            <span x-show="emp.total_shifts === 0" class="text-slate-300">-</span>
                                            <div x-show="emp.total_shifts > 0">
                                                <span x-show="emp.total_selisih < 0" class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg text-xs" x-text="formatRupiah(emp.total_selisih)"></span>
                                                <span x-show="emp.total_selisih === 0" class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg text-xs">PAS (0)</span>
                                                <span x-show="emp.total_selisih > 0" class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg text-xs" x-text="'+' + formatRupiah(emp.total_selisih)"></span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                                  :class="emp.evaluation_badge"
                                                  x-text="emp.evaluation_status"></span>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <button @click="openDetailModal(emp)" class="bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white px-3 py-1.5 rounded-xl font-black text-[11px] transition-all flex items-center justify-center gap-1 mx-auto shadow-xs">
                                                <i class="fa-solid fa-list text-[10px]"></i> Rincian
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredEmployees.length === 0">
                                    <td colspan="10" class="p-12 text-center text-slate-400 font-bold">
                                        <i class="fa-solid fa-users-slash text-4xl mb-2 text-slate-300 block"></i>
                                        Tidak ada data kinerja karyawan yang ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <!-- ============================================== -->
        <!-- MODAL RINCIAN SESI SHIFT KARYAWAN              -->
        <!-- ============================================== -->
        <div x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]" @click.outside="showDetailModal = false">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/80 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary text-white flex items-center justify-center font-black text-sm shrink-0"
                             x-text="getInitials(selectedEmployee?.name)">
                        </div>
                        <div>
                            <h3 class="font-black text-base text-slate-800" x-text="selectedEmployee?.name"></h3>
                            <p class="text-xs text-slate-400 font-bold" x-text="selectedEmployee?.role_name + ' • ' + selectedEmployee?.store_name"></p>
                        </div>
                    </div>
                    <button @click="showDetailModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto custom-scrollbar flex-1 space-y-4">
                    
                    <!-- KPI Micro Bar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                        <div>
                            <span class="text-slate-400 font-bold block">Total Shift:</span>
                            <span class="text-sm font-black text-slate-800" x-text="selectedEmployee?.total_shifts + ' Sesi (' + selectedEmployee?.total_hours + ' Jam)'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block">Total Nota:</span>
                            <span class="text-sm font-black text-slate-800" x-text="selectedEmployee?.total_transactions + ' Transaksi'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block">Total Omset:</span>
                            <span class="text-sm font-black text-emerald-600" x-text="formatRupiah(selectedEmployee?.total_omset)"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block">Akumulasi Selisih:</span>
                            <span class="text-sm font-black"
                                  :class="(selectedEmployee?.total_selisih || 0) < 0 ? 'text-rose-600' : 'text-emerald-600'"
                                  x-text="formatRupiah(selectedEmployee?.total_selisih)"></span>
                        </div>
                    </div>

                    <!-- Shifts List Table -->
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="p-3 font-black">Waktu Dinas</th>
                                    <th class="p-3 font-black">Shift</th>
                                    <th class="p-3 font-black text-right">Modal Awal</th>
                                    <th class="p-3 font-black text-right text-emerald-600">+ Cash Masuk</th>
                                    <th class="p-3 font-black text-right text-amber-600">- Kas Keluar</th>
                                    <th class="p-3 font-black text-right">Uang Laci</th>
                                    <th class="p-3 font-black text-center">Selisih</th>
                                    <th class="p-3 font-black text-center">Nota & Omset</th>
                                    <th class="p-3 font-black text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <template x-for="s in selectedEmployee?.shifts_detail || []" :key="s.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3">
                                            <div class="font-black text-slate-800" x-text="s.formatted_start"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'s/d ' + s.formatted_end"></div>
                                        </td>
                                        <td class="p-3">
                                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-black text-[11px]" x-text="s.shift_name"></span>
                                        </td>
                                        <td class="p-3 text-right font-bold text-slate-700" x-text="formatRupiah(s.start_cash)"></td>
                                        <td class="p-3 text-right font-bold text-emerald-600" x-text="formatRupiah(s.total_cash_in)"></td>
                                        <td class="p-3 text-right font-bold text-amber-600" x-text="formatRupiah(s.total_kas_keluar)"></td>
                                        <td class="p-3 text-right font-black text-slate-800">
                                            <span x-show="s.status === 'closed'" x-text="formatRupiah(s.end_cash)"></span>
                                            <span x-show="s.status === 'open'" class="italic text-slate-400">Berjalan</span>
                                        </td>
                                        <td class="p-3 text-center font-black">
                                            <div x-show="s.status === 'closed'">
                                                <span x-show="s.selisih < 0" class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded" x-text="formatRupiah(s.selisih)"></span>
                                                <span x-show="s.selisih === 0" class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">PAS</span>
                                                <span x-show="s.selisih > 0" class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded" x-text="'+' + formatRupiah(s.selisih)"></span>
                                            </div>
                                            <span x-show="s.status === 'open'" class="text-slate-300">-</span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="font-black text-slate-800" x-text="formatRupiah(s.total_omset)"></div>
                                            <div class="text-[10px] text-slate-400 font-bold" x-text="s.total_transactions + ' nota'"></div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase"
                                                  :class="s.status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-800'">
                                                <span x-text="s.status === 'closed' ? 'Tutup' : 'Aktif'"></span>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="(selectedEmployee?.shifts_detail || []).length === 0">
                                    <td colspan="9" class="p-8 text-center text-slate-400 font-bold">
                                        Belum ada catatan riwayat sesi shift untuk karyawan ini dalam periode yang dipilih.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-100 flex justify-end bg-slate-50 shrink-0">
                    <button type="button" @click="showDetailModal = false" class="bg-primary hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-black transition-all">
                        Tutup Rincian
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js"></script>
</body>
</html>
