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
    <title>Login — Love Cakes POS</title>
    <meta name="description" content="Masuk ke sistem kasir Love Cakes untuk memulai sesi Anda.">

    <!-- Lottie Player -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- ajax.js dimuat di sini agar loginApp() terdefinisi sebelum Alpine parse x-data -->
    <script src="ajax.js?v=<?= time() ?>"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }

        /* ---- Animated gradient background for left panel ---- */
        .hero-panel {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 40%, #7c3aed 100%);
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            filter: blur(60px);
            animation: floatOrb 6s ease-in-out infinite;
        }
        .orb-1 { width: 300px; height: 300px; background: #60a5fa; top: -80px; left: -80px; animation-delay: 0s; }
        .orb-2 { width: 200px; height: 200px; background: #a78bfa; bottom: 60px; right: -60px; animation-delay: 2s; }
        .orb-3 { width: 150px; height: 150px; background: #34d399; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 4s; }

        @keyframes floatOrb {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-20px) scale(1.05); }
        }

        /* ---- Input focus ring ---- */
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
        }

        /* ---- Login button pulse ---- */
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.5);
        }
        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        /* ---- Fade-in animation ---- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.6s ease forwards; }
        .delay-1    { animation-delay: 0.1s; opacity: 0; }
        .delay-2    { animation-delay: 0.2s; opacity: 0; }
        .delay-3    { animation-delay: 0.3s; opacity: 0; }
        .delay-4    { animation-delay: 0.4s; opacity: 0; }

        /* ---- Glassmorphism card on hero ---- */
        .glass-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        /* ---- Lottie sizing ---- */
        lottie-player {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body class="min-h-screen flex" x-data="{ ...loginApp(), showPass: false }">

    <!-- ========== LEFT HERO PANEL ========== -->
    <div class="hidden lg:flex hero-panel w-1/2 xl:w-3/5 flex-col items-center justify-center p-12 relative">

        <!-- Floating orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Content -->
        <div class="relative z-10 text-center max-w-lg">

            <!-- Logo badge -->
            <div class="inline-flex items-center gap-3 glass-card rounded-2xl px-5 py-3 mb-8">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-cake-candles text-white text-base"></i>
                </div>
                <span class="text-white font-black text-lg tracking-wide">Love Cakes</span>
            </div>

            <!-- Lottie Animation -->
            <div class="flex justify-center mb-8">
                <lottie-player
                    src="../assets/img/Business Analysis.json"
                    background="transparent"
                    speed="1"
                    loop
                    autoplay>
                </lottie-player>
            </div>

            <!-- Tagline -->
            <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight mb-4">
                Sistem POS<br>
                <span class="text-blue-200">Terpadu & Modern</span>
            </h1>
            <p class="text-blue-100 text-base font-medium leading-relaxed">
                Kelola transaksi, laporan, dan inventori dengan mudah.<br>
                Semua dalam satu platform yang andal.
            </p>

            <!-- Feature badges -->
            <div class="flex flex-wrap justify-center gap-3 mt-8">
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-yellow-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Transaksi Cepat</span>
                </div>
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-green-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Laporan Real-time</span>
                </div>
                <div class="glass-card rounded-xl px-4 py-2 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-purple-300 text-sm"></i>
                    <span class="text-white text-sm font-semibold">Multi Role</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== RIGHT LOGIN PANEL ========== -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-slate-50 min-h-screen">
        <div class="w-full max-w-md">

            <!-- Mobile logo (hidden on desktop) -->
            <div class="flex lg:hidden items-center justify-center gap-3 mb-8">
                <div class="w-11 h-11 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-cake-candles text-white text-xl"></i>
                </div>
                <span class="text-2xl font-black text-slate-800">Love Cakes</span>
            </div>

            <!-- Header -->
            <div class="mb-6 fade-in-up">
                <p class="text-blue-600 text-xs font-black uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    Portal Autentikasi Love Cakes
                </p>
                <h2 class="text-3xl font-black text-slate-800 leading-tight">Masuk ke Sistem</h2>
                <p class="text-slate-500 text-sm mt-1 font-medium">Pilih peran akses Anda dan masukkan kredensial akun.</p>
            </div>

            <!-- ===== ROLE SELECTOR TABS (ADMIN vs PEGAWAI) ===== -->
            <div class="mb-6 p-1.5 bg-slate-200/80 rounded-2xl flex items-center gap-1.5 border border-slate-200 shadow-inner fade-in-up">
                <button type="button" @click="setLoginRole('admin')" 
                    :class="loginRole === 'admin' ? 'bg-white text-blue-600 shadow-md font-black ring-1 ring-slate-200/50' : 'text-slate-500 hover:text-slate-800 font-bold'"
                    class="flex-1 py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all duration-200">
                    <i class="fa-solid fa-shield-halved text-sm" :class="loginRole === 'admin' ? 'text-blue-600' : 'text-slate-400'"></i>
                    <span>Administrator</span>
                </button>
                <button type="button" @click="setLoginRole('pegawai')" 
                    :class="loginRole === 'pegawai' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
                    class="flex-1 py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all duration-200">
                    <i class="fa-solid fa-cash-register text-sm" :class="loginRole === 'pegawai' ? 'text-white' : 'text-slate-400'"></i>
                    <span>Pegawai (Kasir)</span>
                </button>
            </div>

            <!-- Role Badge / Hint -->
            <div class="mb-5 p-3 rounded-2xl flex items-center gap-3 transition-colors fade-in-up"
                 :class="loginRole === 'admin' ? 'bg-blue-50/70 border border-blue-100 text-blue-900' : 'bg-amber-50/70 border border-amber-100 text-amber-900'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0"
                     :class="loginRole === 'admin' ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/30' : 'bg-amber-500 text-white shadow-sm shadow-amber-500/30'">
                    <i :class="loginRole === 'admin' ? 'fa-solid fa-user-gear' : 'fa-solid fa-user-tag'"></i>
                </div>
                <div class="text-xs">
                    <span class="font-black block" x-text="loginRole === 'admin' ? 'Login Administrator' : 'Login Pegawai Toko'"></span>
                    <span class="text-[11px] opacity-80" x-text="loginRole === 'admin' ? 'Akses dasbor backoffice, laporan global, dan pengaturan toko.' : 'Buka sesi kasir dan pilih shift operasional kerja hari ini.'"></span>
                </div>
            </div>

            <!-- ===== FORM LOGIN ===== -->
            <form @submit.prevent="doLogin" class="space-y-4">

                <!-- Username -->
                <div class="fade-in-up delay-1">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        <i class="fa-solid fa-user mr-1 text-blue-500"></i>
                        <span x-text="loginRole === 'admin' ? 'Username Admin' : 'Username Pegawai / Kasir'"></span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            x-model="username"
                            required
                            autocomplete="username"
                            :placeholder="loginRole === 'admin' ? 'Masukkan username admin...' : 'Masukkan username pegawai (contoh: pegawai / kasir1)...'"
                            class="input-field w-full pl-4 pr-4 py-3.5 border-2 border-slate-200 rounded-2xl outline-none bg-white font-semibold text-slate-700 placeholder-slate-300 text-sm"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="fade-in-up delay-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        <i class="fa-solid fa-lock mr-1 text-blue-500"></i> Password
                    </label>
                    <div class="relative">
                        <input
                            :type="showPass ? 'text' : 'password'"
                            x-model="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password akun Anda..."
                            class="input-field w-full pl-4 pr-12 py-3.5 border-2 border-slate-200 rounded-2xl outline-none bg-white font-semibold text-slate-700 placeholder-slate-300 text-sm"
                        >
                        <button type="button" @click="showPass = !showPass"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="fade-in-up delay-3 pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="btn-login w-full text-white font-black py-4 rounded-2xl flex items-center justify-center gap-3 text-base disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none"
                    >
                        <template x-if="!isLoading">
                            <span class="flex items-center gap-2">
                                <i :class="loginRole === 'admin' ? 'fa-solid fa-right-to-bracket' : 'fa-solid fa-cash-register'"></i>
                                <span x-text="loginRole === 'admin' ? 'Masuk ke Administrator' : 'Lanjut Buka Sesi Kasir'"></span>
                            </span>
                        </template>
                        <template x-if="isLoading">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                Memverifikasi Akun...
                            </span>
                        </template>
                    </button>
                </div>

            </form>

            <!-- ===== POP-UP MODAL PEMILIHAN SHIFT DINAMIS (PEGAWAI) ===== -->
            <div x-show="showShiftModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-slate-100 overflow-hidden relative" @click.away="showShiftModal = false">
                    <!-- Modal Header -->
                    <div class="p-6 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white relative">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-xl shadow-inner">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-200">Sesi Kasir Baru</div>
                                    <h3 class="text-xl font-black">Pilih Shift Kerja</h3>
                                </div>
                            </div>
                            <button type="button" @click="showShiftModal = false" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <p class="text-xs text-blue-100 mt-2 font-medium">
                            Halo <span class="font-bold text-white" x-text="loggedInUser?.name || 'Pegawai'"></span>! Silakan tentukan shift bertugas hari ini dari master data toko.
                        </p>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">
                        <!-- Shift Cards -->
                        <div>
                            <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2.5">
                                <i class="fa-solid fa-calendar-check mr-1 text-blue-600"></i> Daftar Shift Aktif
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="s in masterShifts" :key="s.id">
                                    <div @click="selectedShiftId = s.id"
                                        :class="selectedShiftId == s.id ? 'border-blue-600 bg-blue-50/70 ring-2 ring-blue-500/20 shadow-md' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                                        class="cursor-pointer p-4 rounded-2xl border-2 transition-all relative flex flex-col justify-between">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <span class="font-black text-sm text-slate-800 block" x-text="s.shift_name"></span>
                                                <span class="text-xs font-semibold text-slate-500 flex items-center gap-1.5 mt-0.5">
                                                    <i class="fa-regular fa-clock text-blue-500 text-[11px]"></i>
                                                    <span x-text="(s.start_time || '').substring(0,5) + ' - ' + (s.end_time || '').substring(0,5)"></span>
                                                </span>
                                            </div>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                                :class="selectedShiftId == s.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'">
                                                <i x-show="selectedShiftId == s.id" class="fa-solid fa-check text-[10px]"></i>
                                            </div>
                                        </div>
                                        <template x-if="s.is_current">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 w-fit mt-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Shift Jam Sekarang
                                            </span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Modal Awal Kas -->
                        <div>
                            <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">
                                <i class="fa-solid fa-money-bill-wave mr-1 text-emerald-600"></i> Modal Awal di Laci Kas (Cash)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-slate-400 text-sm">Rp</span>
                                <input type="text"
                                    :value="startCashFormatted"
                                    @input="updateCashInput($event.target.value)"
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl outline-none focus:border-blue-600 focus:bg-white font-black text-slate-800 text-base"
                                    placeholder="0">
                            </div>
                            <!-- Quick chips -->
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <button type="button" @click="setCashQuick(0)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors">Rp 0</button>
                                <button type="button" @click="setCashQuick(100000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors">Rp 100.000</button>
                                <button type="button" @click="setCashQuick(200000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors">Rp 200.000</button>
                                <button type="button" @click="setCashQuick(500000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors">Rp 500.000</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-5 bg-slate-50 border-t border-slate-100 flex items-center gap-3">
                        <button type="button" @click="showShiftModal = false" class="flex-1 py-3 px-4 rounded-2xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-sm transition-all">
                            Batal
                        </button>
                        <button type="button" @click="confirmShift()" :disabled="isLoadingShift || !selectedShiftId" class="flex-1 py-3 px-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-sm shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-lock-open" :class="isLoadingShift ? 'fa-spin' : ''"></i>
                            <span>Buka Shift & Masuk</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer info -->
            <div class="fade-in-up delay-4 mt-8 pt-6 border-t border-slate-200">
                <div class="flex items-center justify-center gap-6 text-xs text-slate-400 font-medium">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-green-500"></i>
                        Koneksi Aman
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-clock text-blue-500"></i>
                        Sesi Otomatis
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-wifi text-purple-500"></i>
                        PWA Ready
                    </span>
                </div>
                <p class="text-center text-xs text-slate-300 mt-4">
                    &copy; <?= date('Y') ?> Love Cakes POS System. All rights reserved.
                </p>
            </div>

        </div>
    </div>


</body>
</html>
