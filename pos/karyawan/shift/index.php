<?php
require_once '../../../config/auth.php';
$page_title = "Manajemen Shift Karyawan - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans" x-data="employeeShiftApp()" x-cloak>

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
                        <i class="fa-solid fa-user-clock text-amber-300"></i> Manajemen Shift Karyawan
                    </h2>
                    <p class="text-[11px] text-blue-200 font-bold mt-0.5">Penjadwalan dinas kerja kasir & staf outlet Love Cakes</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button @click="openCopyModal()" class="bg-white/10 hover:bg-white/20 text-white px-3.5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 border border-white/20">
                    <i class="fa-solid fa-copy"></i> <span class="hidden md:inline">Salin Minggu Lalu</span>
                </button>
                <button @click="exportExcel()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3.5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-xs shadow-emerald-500/30">
                    <i class="fa-solid fa-file-excel"></i> <span class="hidden md:inline">Export Excel</span>
                </button>
                <button @click="openAddModal()" class="bg-amber-400 hover:bg-amber-500 text-slate-900 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 shadow-sm shadow-amber-400/30">
                    <i class="fa-solid fa-plus"></i> <span>+ Jadwal Shift</span>
                </button>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] space-y-6">
            <div class="w-full max-w-full space-y-6">

                <!-- 1. KPI SUMMARY & WEEK CONTROLS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Total Shift Terjadwal -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Penugasan</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1" x-text="stats.total_scheduled || 0">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Shift terjadwal di periode ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </div>
                    </div>

                    <!-- Karyawan Masuk Hari Ini -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Masuk Hari Ini</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1" x-text="(stats.today_present || 0) + ' / ' + (stats.today_scheduled || 0)">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Karyawan bertugas hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>

                    <!-- Karyawan Libur / Off -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Libur / Off</p>
                            <h3 class="text-2xl font-black text-amber-600 mt-1" x-text="stats.off_count || 0">0</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Penugasan Day-Off terjadwal</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. FILTER & VIEW SELECTOR BAR -->
                <div class="bg-white p-4 rounded-3xl shadow-xs border border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    
                    <!-- Week Navigator -->
                    <div class="flex items-center gap-2">
                        <button @click="prevWeek()" title="Minggu Sebelumnya" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button @click="thisWeek()" class="bg-blue-50 text-blue-700 hover:bg-blue-100 px-3.5 py-2 rounded-xl text-xs font-black transition-all">
                            Minggu Ini
                        </button>
                        <button @click="nextWeek()" title="Minggu Berikutnya" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                        <span class="text-xs font-black text-slate-700 ml-2" x-text="formatDateRange(startDate, endDate)"></span>
                    </div>

                    <!-- Filters and View Mode Switch -->
                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <!-- Outlet Filter -->
                        <select x-model="selectedWarehouse" @change="fetchSchedules()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 outline-none">
                            <option value="0">🏢 Semua Outlet</option>
                            <template x-for="w in warehouses" :key="w.id">
                                <option :value="w.id" x-text="'🏬 ' + w.name"></option>
                            </template>
                        </select>

                        <!-- View Mode Toggle -->
                        <div class="flex bg-slate-100 p-1 rounded-xl text-xs font-bold">
                            <button @click="viewMode = 'roster'" 
                                    :class="viewMode === 'roster' ? 'bg-white text-primary shadow-xs font-black' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-calendar-week"></i> Roster Mingguan
                            </button>
                            <button @click="viewMode = 'table'" 
                                    :class="viewMode === 'table' ? 'bg-white text-primary shadow-xs font-black' : 'text-slate-500 hover:text-slate-800'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-table-list"></i> Tabel Detail
                            </button>
                        </div>
                    </div>
                </div>

                <!-- LOADING SPINNER -->
                <div x-show="isLoading" class="text-center py-16 flex flex-col items-center justify-center">
                    <div class="w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-3"></div>
                    <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">Memuat Jadwal Shift...</p>
                </div>

                <!-- ============================================== -->
                <!-- VIEW 1: ROSTER MINGGUAN (GRID SENIN - MINGGU)  -->
                <!-- ============================================== -->
                <div x-show="!isLoading && viewMode === 'roster'" class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-blue-600"></i> Roster Jadwal Shift Mingguan
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">Klik pada shift untuk mengubah status atau mengedit</span>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider">
                                    <th class="p-3.5 font-black w-48 border-r border-slate-100">Karyawan</th>
                                    <template x-for="d in weekDays" :key="d.date">
                                        <th class="p-3 font-black text-center min-w-[130px] border-r border-slate-100"
                                            :class="d.isToday ? 'bg-blue-50/80 text-blue-700' : ''">
                                            <div x-text="d.name"></div>
                                            <div class="text-[10px] font-bold text-slate-400" x-text="d.dateFormatted"></div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <template x-for="user in users" :key="user.id">
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <!-- Employee Name & Store -->
                                        <td class="p-3.5 border-r border-slate-100 bg-white">
                                            <div class="font-black text-slate-800" x-text="user.name"></div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold"
                                                      :class="user.role_name.toLowerCase().includes('kasir') ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                                      x-text="user.role_name"></span>
                                                <span class="text-[10px] text-slate-400 truncate" x-text="user.store_name"></span>
                                            </div>
                                        </td>

                                        <!-- Day Cells -->
                                        <template x-for="d in weekDays" :key="d.date">
                                            <td class="p-2 border-r border-slate-100 align-top transition-colors"
                                                :class="d.isToday ? 'bg-blue-50/30' : ''">
                                                
                                                <!-- If user has schedule for this day -->
                                                <template x-if="getSchedule(user.id, d.date)">
                                                    <div @click="openQuickStatusModal(getSchedule(user.id, d.date))"
                                                         class="p-2 rounded-xl border cursor-pointer hover:shadow-md transition-all text-center relative group"
                                                         :class="getStatusCardClass(getSchedule(user.id, d.date).status)">
                                                        
                                                        <div class="font-black text-[11px] truncate" x-text="getSchedule(user.id, d.date).shift_name"></div>
                                                        <div class="text-[9px] font-bold opacity-80 mt-0.5" 
                                                             x-text="getSchedule(user.id, d.date).start_time_short + ' - ' + getSchedule(user.id, d.date).end_time_short"></div>
                                                        <div class="mt-1">
                                                            <span class="inline-block text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md"
                                                                  :class="getStatusBadgeClass(getSchedule(user.id, d.date).status)"
                                                                  x-text="getStatusLabel(getSchedule(user.id, d.date).status)"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- If NO schedule for this day -->
                                                <template x-if="!getSchedule(user.id, d.date)">
                                                    <button @click="quickAssign(user.id, d.date)" 
                                                            class="w-full h-12 rounded-xl border border-dashed border-slate-200 hover:border-blue-400 hover:bg-blue-50/40 text-slate-300 hover:text-blue-600 flex items-center justify-center transition-all text-xs font-bold group">
                                                        <i class="fa-solid fa-plus opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                                    </button>
                                                </template>

                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- VIEW 2: TABEL DETAIL PENJADWALAN               -->
                <!-- ============================================== -->
                <div x-show="!isLoading && viewMode === 'table'" class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden space-y-4 p-5">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="relative w-full sm:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama karyawan / shift..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                        </div>

                        <!-- Status Filter -->
                        <select x-model="statusFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 outline-none">
                            <option value="all">Semua Status</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="present">Hadir</option>
                            <option value="completed">Selesai</option>
                            <option value="off">Libur / Off</option>
                            <option value="absent">Izin / Sakit / Alpha</option>
                            <option value="swapped">Tukar Shift</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase tracking-widest text-[10px]">
                                    <th class="p-3.5 font-black">Tanggal</th>
                                    <th class="p-3.5 font-black">Karyawan</th>
                                    <th class="p-3.5 font-black">Outlet</th>
                                    <th class="p-3.5 font-black">Shift Kerja</th>
                                    <th class="p-3.5 font-black text-center">Jam Kerja</th>
                                    <th class="p-3.5 font-black text-center">Status</th>
                                    <th class="p-3.5 font-black">Catatan</th>
                                    <th class="p-3.5 font-black text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <template x-for="sc in filteredSchedules" :key="sc.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3.5 font-black text-slate-800">
                                            <div x-text="sc.day_name + ', ' + sc.formatted_date"></div>
                                        </td>
                                        <td class="p-3.5">
                                            <div class="font-black text-slate-800" x-text="sc.employee_name"></div>
                                            <div class="text-[10px] text-slate-400" x-text="sc.role_name"></div>
                                        </td>
                                        <td class="p-3.5 font-bold text-slate-700" x-text="sc.store_name"></td>
                                        <td class="p-3.5">
                                            <span class="font-black text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg" x-text="sc.shift_name"></span>
                                        </td>
                                        <td class="p-3.5 text-center font-bold text-slate-600" x-text="sc.start_time_short + ' - ' + sc.end_time_short"></td>
                                        <td class="p-3.5 text-center">
                                            <button @click="openQuickStatusModal(sc)" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase cursor-pointer"
                                                    :class="getStatusBadgeClass(sc.status)">
                                                <span x-text="getStatusLabel(sc.status)"></span>
                                                <i class="fa-solid fa-chevron-down text-[8px] ml-1 opacity-70"></i>
                                            </button>
                                        </td>
                                        <td class="p-3.5 text-slate-400 text-[11px]" x-text="sc.notes || '-'"></td>
                                        <td class="p-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openQuickStatusModal(sc)" title="Ubah Status" class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </button>
                                                <button @click="deleteSchedule(sc.id)" title="Hapus Jadwal" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredSchedules.length === 0">
                                    <td colspan="8" class="p-10 text-center text-slate-400 font-bold">
                                        Tidak ada jadwal shift yang sesuai dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <!-- ============================================== -->
        <!-- MODAL TAMBAH / PENUGASAN SHIFT KARYAWAN        -->
        <!-- ============================================== -->
        <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200" @click.outside="showAddModal = false">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-base text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-plus text-primary"></i> Penugasan Shift Karyawan
                    </h3>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form @submit.prevent="saveSchedule()" class="p-6 space-y-4">
                    <!-- Karyawan -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Pilih Karyawan</label>
                        <select x-model="formData.user_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="">-- Pilih Karyawan / Kasir --</option>
                            <template x-for="u in users" :key="u.id">
                                <option :value="u.id" x-text="u.name + ' (' + u.role_name + ') - ' + u.store_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Outlet & Shift Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Outlet Penugasan</label>
                            <select x-model="formData.warehouse_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                                <template x-for="w in warehouses" :key="w.id">
                                    <option :value="w.id" x-text="'🏬 ' + w.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Shift Kerja</label>
                            <select x-model="formData.shift_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                                <option value="">-- Pilih Shift --</option>
                                <template x-for="s in shifts" :key="s.id">
                                    <option :value="s.id" x-text="s.shift_name + ' (' + s.start_time.substring(0, 5) + ' - ' + s.end_time.substring(0, 5) + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Tanggal Penugasan (Single / Multi-date) -->
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Tanggal Penugasan</label>
                        <input type="date" x-model="formData.schedule_date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                        <p class="text-[10px] text-slate-400 font-medium mt-1">Pilih tanggal spesifik penugasan dinas shift.</p>
                    </div>

                    <!-- Status Awal & Catatan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Status</label>
                            <select x-model="formData.status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                                <option value="scheduled">Dijadwalkan</option>
                                <option value="present">Hadir</option>
                                <option value="off">Libur / Day-Off</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Catatan Tambahan</label>
                            <input type="text" x-model="formData.notes" placeholder="Contoh: Bertugas Kasir Utama" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-black text-xs hover:bg-slate-50 transition-all">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSaving" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2 disabled:opacity-50">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Penugasan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODAL QUICK STATUS & EDIT                      -->
        <!-- ============================================== -->
        <div x-show="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200" @click.outside="showStatusModal = false">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="font-black text-sm text-slate-800" x-text="activeSchedule?.employee_name"></h3>
                        <p class="text-[11px] text-slate-400 font-bold" x-text="activeSchedule?.day_name + ', ' + activeSchedule?.formatted_date"></p>
                    </div>
                    <button @click="showStatusModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Ubah Status Dinas</label>
                        <select x-model="statusUpdateVal" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="scheduled">📅 Dijadwalkan</option>
                            <option value="present">✅ Hadir On-Duty</option>
                            <option value="completed">🏁 Selesai Bertugas</option>
                            <option value="off">☕ Libur / Day-Off</option>
                            <option value="absent">⚠️ Izin / Sakit / Alpha</option>
                            <option value="swapped">🔄 Tukar Shift</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 mb-1.5 uppercase tracking-wider">Catatan</label>
                        <input type="text" x-model="statusUpdateNotes" placeholder="Catatan perubahan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <button type="button" @click="deleteSchedule(activeSchedule.id); showStatusModal = false;" class="text-rose-600 hover:text-rose-700 text-xs font-black">
                            <i class="fa-solid fa-trash mr-1"></i> Hapus
                        </button>
                        <button type="button" @click="saveStatusUpdate()" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition-all">
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODAL COPY JADWAL MINGGU LALU                  -->
        <!-- ============================================== -->
        <div x-show="showCopyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-slate-200" @click.outside="showCopyModal = false">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-blue-50 text-blue-900">
                    <h3 class="font-black text-base flex items-center gap-2">
                        <i class="fa-solid fa-copy text-blue-600"></i> Salin Roster Minggu Lalu
                    </h3>
                    <button @click="showCopyModal = false" class="text-blue-400 hover:text-blue-700 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-600 font-medium leading-relaxed">
                        Fitur ini akan menyalin seluruh penugasan shift karyawan dari minggu lalu ke minggu yang sedang Anda buka saat ini (<span class="font-black text-slate-800" x-text="formatDateRange(startDate, endDate)"></span>).
                    </p>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-[11px] text-slate-500 font-medium">
                        <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i> Jadwal yang sudah ada pada minggu ini akan otomatis diperbarui sesuai pola penugasan minggu lalu.
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showCopyModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-black text-xs hover:bg-slate-50 transition-all">
                            Batal
                        </button>
                        <button type="button" @click="submitCopyWeek()" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition-all flex items-center gap-2">
                            <i class="fa-solid fa-check"></i> Ya, Salin Jadwal
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="ajax.js"></script>
</body>
</html>
