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
    <meta name="description" content="Masuk ke sistem kasir Ayam Goreng Hariku untuk memulai sesi operasional Anda.">

    <!-- Google Fonts: Poppins & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- ajax.js dimuat di sini agar loginApp() terdefinisi sebelum Alpine parse x-data -->
    <script src="ajax.js?v=<?= time() ?>"></script>
    <style>
        * { font-family: 'Poppins', sans-serif; }

        /* ---- Animated gradient background for left hero panel ---- */
        .hariku-hero {
            background: linear-gradient(135deg, #3E1C0C 0%, #5C2D16 35%, #8C431F 70%, #FF3870 100%);
            background-size: 250% 250%;
            animation: harikuGradient 10s ease infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes harikuGradient {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            animation: floatOrb 7s ease-in-out infinite;
        }
        .orb-1 { width: 340px; height: 340px; background: rgba(255, 56, 112, 0.35); top: -80px; left: -80px; animation-delay: 0s; }
        .orb-2 { width: 260px; height: 260px; background: rgba(245, 158, 11, 0.3); bottom: 40px; right: -60px; animation-delay: 2.5s; }
        .orb-3 { width: 180px; height: 180px; background: rgba(255, 151, 182, 0.25); top: 45%; left: 45%; transform: translate(-50%,-50%); animation-delay: 5s; }

        @keyframes floatOrb {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-24px) scale(1.08); }
        }

        /* ---- Input focus ring ---- */
        .input-field {
            transition: all 0.25s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(255, 56, 112, 0.15);
            border-color: #FF3870;
        }

        /* ---- Login button pulse ---- */
        .btn-hariku {
            background: linear-gradient(135deg, #FF3870 0%, #E02360 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 24px rgba(255, 56, 112, 0.35);
        }
        .btn-hariku:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255, 56, 112, 0.5);
            background: linear-gradient(135deg, #FF2E6D 0%, #C01344 100%);
        }
        .btn-hariku:active:not(:disabled) {
            transform: translateY(0);
        }

        /* ---- Fade-in animation ---- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.5s ease forwards; }
        .delay-1    { animation-delay: 0.1s; opacity: 0; }
        .delay-2    { animation-delay: 0.2s; opacity: 0; }
        .delay-3    { animation-delay: 0.3s; opacity: 0; }
        .delay-4    { animation-delay: 0.4s; opacity: 0; }

        /* ---- Glassmorphism card on hero ---- */
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
        }

        /* Mascot floating pulse */
        .logo-bounce {
            animation: logoBounce 4s ease-in-out infinite;
        }
        @keyframes logoBounce {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="min-h-screen flex bg-white" x-data="{ ...loginApp(), showPass: false }">

    <!-- ========== LEFT HERO PANEL (BRAND SHOWCASE) ========== -->
    <div class="hidden lg:flex hariku-hero w-1/2 xl:w-7/12 flex-col items-center justify-between p-12 relative text-white">

        <!-- Floating orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Top Brand Pill -->
        <div class="relative z-10 w-full flex justify-between items-center">
            <div class="inline-flex items-center gap-3 glass-card rounded-2xl px-4 py-2.5 shadow-sm">
                <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-8 h-8 rounded-full bg-white object-cover ring-2 ring-white/50">
                <span class="text-white font-black text-sm tracking-wide">AYAM GORENG HARIKU</span>
            </div>
            <div class="glass-card rounded-2xl px-4 py-2 text-xs font-black flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>POS Ready</span>
            </div>
        </div>

        <!-- Center Showcase -->
        <div class="relative z-10 text-center max-w-md my-auto">
            
            <!-- Mascot Big Visual with Cute Frame -->
            <div class="flex justify-center mb-6">
                <div class="relative logo-bounce">
                    <div class="w-52 h-52 sm:w-60 sm:h-60 rounded-3xl p-2 bg-gradient-to-tr from-[#FF3870] via-[#F59E0B] to-white shadow-2xl">
                        <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Mascot Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[20px] bg-white">
                    </div>
                    <!-- Badge Crispy & Juicy -->
                    <div class="absolute -bottom-3 -right-3 bg-gradient-to-r from-amber-400 to-orange-500 text-[#4A2311] font-black text-xs px-3.5 py-1.5 rounded-full shadow-lg border-2 border-white flex items-center gap-1.5">
                        <i class="fa-solid fa-fire text-red-600"></i> Crispy & Juicy
                    </div>
                </div>
            </div>

            <!-- Tagline -->
            <h1 class="text-3xl xl:text-4xl font-black text-white leading-tight mb-2">
                Sistem Kasir & Operasional<br>
                <span class="text-[#FF97B6]">Ayam Goreng Hariku</span>
            </h1>
            <p class="text-white/80 text-sm font-medium leading-relaxed max-w-sm mx-auto">
                Kelola penjualan cepat, kontrol shift karyawan, stok opname, dan laporan keuangan toko secara presisi.
            </p>

            <!-- Channel Order Badges (Grab, GoFood, ShopeeFood, WA) -->
            <div class="flex flex-wrap items-center justify-center gap-2.5 mt-6">
                <div class="glass-card rounded-xl px-3 py-1.5 flex items-center gap-1.5 text-xs font-black">
                    <i class="fa-brands fa-whatsapp text-emerald-300 text-sm"></i>
                    <span>Wa 082345612406</span>
                </div>
                <div class="glass-card rounded-xl px-3 py-1.5 flex items-center gap-1.5 text-xs font-bold text-white/90">
                    <span class="text-emerald-300 font-black">Grab</span>Food
                </div>
                <div class="glass-card rounded-xl px-3 py-1.5 flex items-center gap-1.5 text-xs font-bold text-white/90">
                    <span class="text-rose-300 font-black">Go</span>Food
                </div>
                <div class="glass-card rounded-xl px-3 py-1.5 flex items-center gap-1.5 text-xs font-bold text-white/90">
                    <span class="text-amber-300 font-black">Shopee</span>Food
                </div>
            </div>
        </div>

        <!-- Bottom Feature Highlights -->
        <div class="relative z-10 w-full flex items-center justify-between text-xs text-white/80 pt-4 border-t border-white/15">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-bolt text-amber-300"></i> Transaksi Cepat</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-clock text-pink-300"></i> Shift Terkontrol</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-receipt text-emerald-300"></i> Rekap QRIS & Kas</span>
        </div>
    </div>

    <!-- ========== RIGHT LOGIN PANEL (CLEAN WHITE) ========== -->
    <div class="flex-1 flex items-center justify-center p-6 sm:p-12 bg-white min-h-screen">
        <div class="w-full max-w-md">

            <!-- Mobile Mascot Header (hidden on desktop) -->
            <div class="flex lg:hidden items-center justify-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl p-0.5 bg-gradient-to-tr from-[#FF3870] to-[#F59E0B] shadow-sm shrink-0">
                    <img src="<?= BASE_URL ?>assets/img/logo-hariku.png" alt="Ayam Goreng Hariku" class="w-full h-full object-cover rounded-[14px] bg-white">
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black tracking-widest text-[#5C2D16] uppercase">Ayam Goreng</span>
                    <span class="text-xl font-black text-[#FF3870] tracking-tight">HARIKU</span>
                </div>
            </div>

            <!-- Header Text -->
            <div class="mb-6 fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#FFF0F5] border border-[#FFC5D8] mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#FF3870] animate-pulse"></span>
                    <span class="text-[10px] font-black text-[#FF3870] uppercase tracking-wider">Portal Kasir & Admin</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-[#4A2311] leading-tight">Selamat Datang!</h2>
                <p class="text-[#8C5638] text-xs sm:text-sm mt-1 font-medium">Silakan tentukan peran akses dan masuk ke akun Anda.</p>
            </div>

            <!-- ===== ROLE SELECTOR TABS (ADMIN vs PEGAWAI) ===== -->
            <div class="mb-5 p-1.5 bg-[#FAF5F1] rounded-2xl flex items-center gap-1.5 border border-[#FFE4EC] shadow-xs fade-in-up">
                <button type="button" @click="setLoginRole('admin')" 
                    :class="loginRole === 'admin' ? 'bg-[#5C2D16] text-white shadow-md font-black' : 'text-[#5C2D16]/70 hover:text-[#5C2D16] font-bold'"
                    class="flex-1 py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all duration-200">
                    <i class="fa-solid fa-shield-halved text-sm" :class="loginRole === 'admin' ? 'text-amber-300' : 'text-[#8C5638]'"></i>
                    <span>Administrator</span>
                </button>
                <button type="button" @click="setLoginRole('pegawai')" 
                    :class="loginRole === 'pegawai' ? 'bg-gradient-to-r from-[#FF3870] to-[#E02360] text-white shadow-md shadow-pink-500/25 font-black' : 'text-[#5C2D16]/70 hover:text-[#5C2D16] font-bold'"
                    class="flex-1 py-3 px-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all duration-200">
                    <i class="fa-solid fa-cash-register text-sm" :class="loginRole === 'pegawai' ? 'text-white' : 'text-[#8C5638]'"></i>
                    <span>Pegawai (Kasir)</span>
                </button>
            </div>

            <!-- Role Context Hint Card -->
            <div class="mb-5 p-3 rounded-2xl flex items-center gap-3 transition-colors fade-in-up border"
                 :class="loginRole === 'admin' ? 'bg-[#FAF5F1] border-[#E7D5C4] text-[#5C2D16]' : 'bg-[#FFF0F5] border-[#FFC5D8] text-[#5C2D16]'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0 font-bold"
                     :class="loginRole === 'admin' ? 'bg-[#5C2D16] text-white shadow-xs' : 'bg-[#FF3870] text-white shadow-xs'">
                    <i :class="loginRole === 'admin' ? 'fa-solid fa-user-shield' : 'fa-solid fa-drumstick-bite'"></i>
                </div>
                <div class="text-xs">
                    <span class="font-black block text-[#4A2311]" x-text="loginRole === 'admin' ? 'Login Administrator / Backoffice' : 'Login Pegawai Toko / Kasir'"></span>
                    <span class="text-[11px] text-[#8C5638]" x-text="loginRole === 'admin' ? 'Akses penuh ke dasbor, laporan omset, produk, dan pengaturan.' : 'Akses mesin kasir, buka sesi kasir baru, dan pilih shift kerja.'"></span>
                </div>
            </div>

            <!-- ===== FORM LOGIN ===== -->
            <form @submit.prevent="doLogin" class="space-y-4">

                <!-- Username -->
                <div class="fade-in-up delay-1">
                    <label class="block text-xs font-black text-[#5C2D16] uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-user mr-1 text-[#FF3870]"></i>
                        <span x-text="loginRole === 'admin' ? 'Username Admin' : 'Username Pegawai / Kasir'"></span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            x-model="username"
                            required
                            autocomplete="username"
                            :placeholder="loginRole === 'admin' ? 'Contoh: admin' : 'Contoh: kasir1 atau pegawai'"
                            class="input-field w-full pl-4 pr-4 py-3.5 border-2 border-[#E7D5C4] rounded-2xl outline-none bg-white font-semibold text-[#4A2311] placeholder-[#B6886A]/60 text-sm focus:bg-white"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="fade-in-up delay-2">
                    <label class="block text-xs font-black text-[#5C2D16] uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-lock mr-1 text-[#FF3870]"></i> Kata Sandi
                    </label>
                    <div class="relative">
                        <input
                            :type="showPass ? 'text' : 'password'"
                            x-model="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi akun..."
                            class="input-field w-full pl-4 pr-12 py-3.5 border-2 border-[#E7D5C4] rounded-2xl outline-none bg-white font-semibold text-[#4A2311] placeholder-[#B6886A]/60 text-sm focus:bg-white"
                        >
                        <button type="button" @click="showPass = !showPass"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[#8C5638] hover:text-[#FF3870] transition-colors">
                            <i :class="showPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="fade-in-up delay-3 pt-2">
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="btn-hariku w-full text-white font-black py-4 rounded-2xl flex items-center justify-center gap-3 text-base disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none"
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
            <div x-show="showShiftModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#33170B]/70 backdrop-blur-md"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-pink-100 overflow-hidden relative" @click.away="showShiftModal = false">

                    <!-- Modal Header -->
                    <div class="p-6 bg-gradient-to-r from-[#5C2D16] via-[#78350F] to-[#FF3870] text-white relative">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-xl shadow-inner text-amber-300">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-[#FFC5D8]">Sesi Kasir Baru</div>
                                    <h3 class="text-xl font-black">Pilih Shift Kerja</h3>
                                </div>
                            </div>
                            <button type="button" @click="showShiftModal = false" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <p class="text-xs text-white/90 mt-2 font-medium">
                            Halo <span class="font-black text-amber-300" x-text="loggedInUser?.name || 'Pegawai'"></span>! Tentukan jadwal shift operasional hari ini sebelum mulai melayani pelanggan.
                        </p>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto custom-scrollbar">
                        <!-- Shift Cards -->
                        <div>
                            <label class="block text-xs font-black text-[#5C2D16] uppercase tracking-wider mb-2.5">
                                <i class="fa-solid fa-calendar-check mr-1 text-[#FF3870]"></i> Daftar Master Shift Aktif
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="s in masterShifts" :key="s.id">
                                    <div @click="selectedShiftId = s.id"
                                        :class="selectedShiftId == s.id ? 'border-[#FF3870] bg-[#FFF0F5] ring-2 ring-[#FF3870]/30 shadow-sm' : 'border-[#E7D5C4] bg-white hover:border-[#FFC5D8] hover:bg-[#FAF5F1]'"
                                        class="cursor-pointer p-4 rounded-2xl border-2 transition-all relative flex flex-col justify-between">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <span class="font-black text-sm text-[#4A2311] block" x-text="s.shift_name"></span>
                                                <span class="text-xs font-semibold text-[#8C5638] flex items-center gap-1.5 mt-0.5">
                                                    <i class="fa-regular fa-clock text-[#FF3870] text-[11px]"></i>
                                                    <span x-text="(s.start_time || '').substring(0,5) + ' - ' + (s.end_time || '').substring(0,5)"></span>
                                                </span>
                                            </div>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="selectedShiftId == s.id ? 'border-[#FF3870] bg-[#FF3870] text-white' : 'border-[#E7D5C4] bg-white'">
                                                <i x-show="selectedShiftId == s.id" class="fa-solid fa-check text-[10px]"></i>
                                            </div>
                                        </div>
                                        <template x-if="s.is_current">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 w-fit mt-1">
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
                            <label class="block text-xs font-black text-[#5C2D16] uppercase tracking-wider mb-2">
                                <i class="fa-solid fa-money-bill-wave mr-1 text-emerald-600"></i> Modal Kas Awal di Laci (Uang Tunai)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-[#8C5638] text-sm">Rp</span>
                                <input type="text"
                                    :value="startCashFormatted"
                                    @input="updateCashInput($event.target.value)"
                                    class="w-full pl-12 pr-4 py-3 bg-[#FAF5F1] border-2 border-[#E7D5C4] rounded-2xl outline-none focus:border-[#FF3870] focus:bg-white font-black text-[#4A2311] text-base"
                                    placeholder="0">
                            </div>
                            <!-- Quick chips -->
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <button type="button" @click="setCashQuick(0)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-[#FAF5F1] hover:bg-[#FFE2EC] text-[#5C2D16] border border-[#E7D5C4] transition-colors">Rp 0</button>
                                <button type="button" @click="setCashQuick(100000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-[#FAF5F1] hover:bg-[#FFE2EC] text-[#5C2D16] border border-[#E7D5C4] transition-colors">Rp 100.000</button>
                                <button type="button" @click="setCashQuick(200000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-[#FAF5F1] hover:bg-[#FFE2EC] text-[#5C2D16] border border-[#E7D5C4] transition-colors">Rp 200.000</button>
                                <button type="button" @click="setCashQuick(500000)" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-[#FAF5F1] hover:bg-[#FFE2EC] text-[#5C2D16] border border-[#E7D5C4] transition-colors">Rp 500.000</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-5 bg-[#FAF5F1] border-t border-[#FFE4EC] flex items-center gap-3">
                        <button type="button" @click="showShiftModal = false" class="flex-1 py-3 px-4 rounded-2xl bg-white border border-[#E7D5C4] hover:bg-slate-50 text-[#5C2D16] font-bold text-sm transition-all">
                            Batal
                        </button>
                        <button type="button" @click="confirmShift()" :disabled="isLoadingShift || !selectedShiftId" class="btn-hariku flex-1 py-3 px-4 rounded-2xl text-white font-black text-sm flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-lock-open" :class="isLoadingShift ? 'fa-spin' : ''"></i>
                            <span>Buka Shift & Masuk</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer info -->
            <div class="fade-in-up delay-4 mt-8 pt-6 border-t border-[#FFE4EC]">
                <div class="flex items-center justify-center gap-4 sm:gap-6 text-xs text-[#8C5638] font-medium">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                        Koneksi Aman
                    </span>
                    <span class="w-1 h-1 rounded-full bg-[#E7D5C4]"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-clock text-[#FF3870]"></i>
                        Sesi Terproteksi
                    </span>
                    <span class="w-1 h-1 rounded-full bg-[#E7D5C4]"></span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-wifi text-amber-500"></i>
                        PWA Offline
                    </span>
                </div>
                <p class="text-center text-[11px] text-[#8C5638]/70 mt-3 font-medium">
                    &copy; <?= date('Y') ?> Ayam Goreng Hariku POS. All rights reserved.
                </p>
            </div>

        </div>
    </div>

</body>
</html>
