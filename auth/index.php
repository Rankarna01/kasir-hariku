<?php
session_start();
require_once __DIR__ . '/../config/env.php';

// Cegah user yang sudah login melihat form ini lagi
if (isset($_SESSION['pos_user_id'])) {
    if (strtolower($_SESSION['pos_role']) === 'kasir' || strtolower($_SESSION['pos_role']) === 'cashier') {
        header("Location: " . BASE_URL . "pos/kasir/");
    } else {
        header("Location: " . BASE_URL . "pos/dashboard/");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../components/header.php'; ?>
    <title>Login — Ayam Goreng Hariku POS</title>
    <meta name="description" content="Masuk ke sistem POS kasir Ayam Goreng Hariku.">

    <!-- ajax.js dimuat di sini agar loginApp() terdefinisi sebelum Alpine parse x-data -->
    <script src="ajax.js?v=<?= time() ?>"></script>
    <style>
        body { background-color: #FAF7F5; }
        .input-clean:focus {
            border-color: #FF3870 !important;
            box-shadow: 0 0 0 3px rgba(255, 56, 112, 0.12) !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 lg:p-8" x-data="{ ...loginApp(), showPass: false }">

    <!-- Top Spacer for visual vertical centering -->
    <div class="w-full h-2 sm:h-6"></div>

    <!-- MAIN TWO-COLUMN CONTAINER: FORM ON LEFT, LOGO & WORDS ON RIGHT -->
    <div class="w-full max-w-4xl bg-white rounded-3xl border border-[#F0E6DE] shadow-xl shadow-[#5C2D16]/5 overflow-hidden flex flex-col lg:flex-row my-auto">
        
        <!-- ==================== LEFT COLUMN: FORM LOGIN ==================== -->
        <div class="w-full lg:w-1/2 p-6 sm:p-10 flex flex-col justify-center">

            <!-- Mobile Brand Logo (only on small screens) -->
            <div class="flex lg:hidden items-center justify-center gap-3 mb-6">
                <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-12 h-12 rounded-2xl object-cover ring-2 ring-[#FFC5D8]/80 shadow-xs">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black tracking-widest text-[#5C2D16] uppercase">Ayam Goreng</span>
                    <span class="text-lg font-black text-[#FF3870] tracking-tight">HARIKU</span>
                </div>
            </div>

            <!-- Header Text -->
            <div class="mb-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#FFF0F5] border border-[#FFC5D8] mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#FF3870] animate-pulse"></span>
                    <span class="text-[10px] font-bold text-[#FF3870] uppercase tracking-wider">Portal Kasir & Admin</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#5C2D16] tracking-tight">Masuk ke Sistem</h1>
                <p class="text-xs sm:text-sm text-[#8C5638] font-medium mt-1">Pilih peran akses Anda dan masukkan kredensial.</p>
            </div>

            <!-- Role Selector (Segmented Toggle) -->
            <div class="mb-4 p-1 bg-slate-100 rounded-xl flex items-center gap-1 border border-slate-200/80">
                <button type="button" @click="setLoginRole('admin')" 
                    :class="loginRole === 'admin' ? 'bg-[#5C2D16] text-white shadow-xs font-bold' : 'text-slate-500 hover:text-[#5C2D16] font-medium'"
                    class="flex-1 py-2 px-3 rounded-lg text-xs flex items-center justify-center gap-1.5 transition-all">
                    <i class="fa-solid fa-user-shield text-xs"></i>
                    <span>Administrator</span>
                </button>
                <button type="button" @click="setLoginRole('pegawai')" 
                    :class="loginRole === 'pegawai' ? 'bg-[#FF3870] text-white shadow-xs font-bold' : 'text-slate-500 hover:text-[#FF3870] font-medium'"
                    class="flex-1 py-2 px-3 rounded-lg text-xs flex items-center justify-center gap-1.5 transition-all">
                    <i class="fa-solid fa-cash-register text-xs"></i>
                    <span>Pegawai (Kasir)</span>
                </button>
            </div>

            <!-- Role Subtitle Notice -->
            <div class="mb-5 px-3.5 py-2.5 rounded-xl text-xs flex items-center gap-2.5 transition-colors border"
                 :class="loginRole === 'admin' ? 'bg-[#FAF5F1] text-[#5C2D16] border-[#E7D5C4]/70' : 'bg-[#FFF0F5] text-[#5C2D16] border-[#FFC5D8]/70'">
                <i :class="loginRole === 'admin' ? 'fa-solid fa-shield-halved text-[#8C5638]' : 'fa-solid fa-clock text-[#FF3870]'"></i>
                <span class="text-[11px] font-medium" x-text="loginRole === 'admin' ? 'Akses dasbor backoffice, laporan penjualan, dan konfigurasi master.' : 'Masuk untuk membuka mesin kasir dan memilih shift kerja hari ini.'"></span>
            </div>

            <!-- Form Fields -->
            <form @submit.prevent="doLogin" class="space-y-4">
                
                <!-- Username -->
                <div>
                    <label class="block text-xs font-bold text-[#5C2D16] mb-1.5">
                        <span x-text="loginRole === 'admin' ? 'Username Admin' : 'Username Kasir / Pegawai'"></span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input
                            type="text"
                            x-model="username"
                            required
                            autocomplete="username"
                            :placeholder="loginRole === 'admin' ? 'admin' : 'kasir1 atau pegawai'"
                            class="input-clean w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none text-sm font-medium text-[#4A2311] placeholder-slate-400 transition-all"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-[#5C2D16] mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input
                            :type="showPass ? 'text' : 'password'"
                            x-model="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="input-clean w-full pl-9 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl outline-none text-sm font-medium text-[#4A2311] placeholder-slate-400 transition-all"
                        >
                        <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full bg-[#FF3870] hover:bg-[#5C2D16] text-white font-bold py-3 px-4 rounded-xl text-sm transition-all shadow-xs flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <template x-if="!isLoading">
                            <span class="flex items-center gap-2">
                                <span>Masuk ke Sistem</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </span>
                        </template>
                        <template x-if="isLoading">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                <span>Memverifikasi...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>

            <!-- Credentials Helper Info -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Admin: <strong class="text-slate-600 font-semibold">admin</strong></span>
                <span>•</span>
                <span>Kasir: <strong class="text-slate-600 font-semibold">kasir1</strong> / <strong class="text-slate-600 font-semibold">pegawai</strong></span>
            </div>

        </div>

        <!-- ==================== RIGHT COLUMN: LOGO & SIMPLE WORDS ==================== -->
        <div class="w-full lg:w-1/2 bg-gradient-to-br from-[#FFF5F8] via-[#FAF5F1] to-[#F5ECE5] border-t lg:border-t-0 lg:border-l border-[#F0E6DE] p-8 sm:p-12 flex flex-col justify-center items-center text-center relative">
            
            <!-- Logo Box -->
            <div class="relative mb-5">
                <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-3xl p-1 bg-white ring-4 ring-[#FFC5D8]/70 shadow-lg shadow-[#5C2D16]/5 mx-auto">
                    <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[20px] bg-white">
                </div>
            </div>

            <!-- Brand Name & Words -->
            <h2 class="text-2xl sm:text-3xl font-black text-[#5C2D16] tracking-tight mb-2">
                AYAM GORENG HARIKU
            </h2>
            
            <p class="text-sm font-medium text-[#8C5638] max-w-xs leading-relaxed mb-6">
                Renyah & Gurih Setiap Hari.<br>
                Sistem Kasir & Operasional Terpadu.
            </p>

            <!-- Simple Highlights List -->
            <div class="w-full max-w-xs space-y-2 text-left mb-6">
                <div class="flex items-center gap-3 px-3.5 py-2.5 bg-white/80 rounded-xl border border-pink-100 shadow-2xs">
                    <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#FF3870] flex items-center justify-center text-xs shrink-0 font-bold">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span class="text-xs font-semibold text-[#5C2D16]">Transaksi Cepat & Akurat</span>
                </div>
                <div class="flex items-center gap-3 px-3.5 py-2.5 bg-white/80 rounded-xl border border-pink-100 shadow-2xs">
                    <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#FF3870] flex items-center justify-center text-xs shrink-0 font-bold">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <span class="text-xs font-semibold text-[#5C2D16]">Kontrol Shift Kasir Dinamis</span>
                </div>
                <div class="flex items-center gap-3 px-3.5 py-2.5 bg-white/80 rounded-xl border border-pink-100 shadow-2xs">
                    <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#FF3870] flex items-center justify-center text-xs shrink-0 font-bold">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <span class="text-xs font-semibold text-[#5C2D16]">Laporan Omset Real-time</span>
                </div>
            </div>

            <!-- WhatsApp & Store Info Pill -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white text-[#5C2D16] border border-[#FFC5D8] text-xs font-semibold shadow-2xs">
                <i class="fa-brands fa-whatsapp text-emerald-500 text-sm"></i>
                <span>Wa 082345612406</span>
            </div>

        </div>

    </div>

    <!-- ===== POP-UP MODAL PEMILIHAN SHIFT DINAMIS (PEGAWAI) ===== -->
    <div x-show="showShiftModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full border border-slate-200 overflow-hidden" @click.away="showShiftModal = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-[#FAF5F1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#FF3870] text-white flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#5C2D16]">Pilih Shift Kerja</h3>
                        <p class="text-[11px] text-slate-500">Tentukan shift operasional kasir hari ini</p>
                    </div>
                </div>
                <button type="button" @click="showShiftModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                
                <!-- Shift Selection Cards -->
                <div>
                    <label class="block text-xs font-bold text-[#5C2D16] mb-2">Shift Aktif</label>
                    <div class="space-y-2">
                        <template x-for="s in masterShifts" :key="s.id">
                            <div @click="selectedShiftId = s.id"
                                :class="selectedShiftId == s.id ? 'border-[#FF3870] bg-[#FFF0F5] ring-1 ring-[#FF3870]' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                class="cursor-pointer p-3 rounded-xl border transition-all flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                        :class="selectedShiftId == s.id ? 'border-[#FF3870] bg-[#FF3870] text-white' : 'border-slate-300 bg-white'">
                                        <i x-show="selectedShiftId == s.id" class="fa-solid fa-check text-[8px]"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-slate-800 block" x-text="s.shift_name"></span>
                                        <span class="text-[11px] text-slate-500" x-text="(s.start_time || '').substring(0,5) + ' - ' + (s.end_time || '').substring(0,5) + ' WIB'"></span>
                                    </div>
                                </div>
                                <template x-if="s.is_current">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                        Saat ini
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Kas Awal -->
                <div>
                    <label class="block text-xs font-bold text-[#5C2D16] mb-1.5">Modal Awal Kas Tunai (Laci)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-xs">Rp</span>
                        <input type="text"
                            :value="startCashFormatted"
                            @input="updateCashInput($event.target.value)"
                            class="input-clean w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl outline-none font-bold text-slate-800 text-sm"
                            placeholder="0">
                    </div>
                    <!-- Quick chips -->
                    <div class="flex gap-1.5 mt-2">
                        <button type="button" @click="setCashQuick(0)" class="text-[10px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Rp 0</button>
                        <button type="button" @click="setCashQuick(100000)" class="text-[10px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">100k</button>
                        <button type="button" @click="setCashQuick(200000)" class="text-[10px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">200k</button>
                        <button type="button" @click="setCashQuick(500000)" class="text-[10px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">500k</button>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center gap-2.5">
                <button type="button" @click="showShiftModal = false" class="flex-1 py-2.5 px-3 rounded-xl bg-white border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-100">
                    Batal
                </button>
                <button type="button" @click="confirmShift()" :disabled="isLoadingShift || !selectedShiftId" class="flex-1 py-2.5 px-3 rounded-xl bg-[#FF3870] hover:bg-[#5C2D16] text-white font-bold text-xs flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <i class="fa-solid fa-check text-xs" :class="isLoadingShift ? 'fa-spin' : ''"></i>
                    <span>Buka Sesi Kasir</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Clean Footer -->
    <div class="w-full text-center py-4">
        <p class="text-[11px] text-slate-400 font-medium">
            &copy; <?= date('Y') ?> Ayam Goreng Hariku POS. All rights reserved.
        </p>
    </div>

</body>
</html>
