<?php
// pos/pengaturan/printer/index.php

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_sub = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/pos/') !== false ? trim(explode('/pos/', $_SERVER['SCRIPT_NAME'])[0], '/') : 'kasir-hariku';
$folder = $is_localhost ? (defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_PATH) : ($base_sub !== '' ? '/' . $base_sub . '/' : '/')) : '/';
if (!defined('BASE_URL')) { define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $folder); }
$page_title = "Pengaturan Printer & RawBT - Hariku POS";

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
$user_role_printer = strtolower(trim($_SESSION['pos_role'] ?? ''));
$is_admin_owner = in_array($user_role_printer, ['admin', 'owner', 'superadmin', 'backoffice']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../../components/header.php'; ?>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #FFC5D8; border-radius: 9999px; }
    </style>
</head>
<body class="bg-[#FFF5F8] text-[#4A2311] antialiased font-sans flex h-screen overflow-hidden" x-data="printerApp()" x-cloak>

    <?php include '../../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-white/80 backdrop-blur-md border-b border-[#FFE4EC] px-6 py-4 flex justify-between items-center z-10 shrink-0 shadow-2xs">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-[#4A2311] hover:text-[#FF3870] p-2 rounded-xl">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#FF3870] to-[#FFA07A] text-white flex items-center justify-center shadow-sm shrink-0">
                    <i class="fa-solid fa-print text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-[#4A2311] tracking-tight">Pengaturan Printer & Layanan RawBT</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Integrasi cetak struk kasir, format layout, dan log riwayat cetak</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>pos/kasir/" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-2xs">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Kasir
                </a>
                <button @click="showRawbtModal = true" class="px-3.5 py-2 bg-[#FFF0F5] hover:bg-[#FF3870] text-[#FF3870] hover:text-white border border-[#FFC5D8] rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-2xs">
                    <i class="fa-solid fa-circle-question"></i> Panduan Setup RawBT
                </button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-5 md:p-8 space-y-6">

            <!-- 1. ALERT BANNER HEALTH CHECK -->
            <template x-if="isTestOldOrMissing === 'missing'">
                <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 font-black">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-amber-900">Perangkat Ini Belum Pernah Melakukan Tes Cetak</h4>
                            <p class="text-[11px] font-semibold text-amber-700">Pastikan printer thermal dan RawBT di tablet Anda sudah terhubung sebelum kasir mulai transaksi.</p>
                        </div>
                    </div>
                    <button @click="runTestPrint()" :disabled="isTesting" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black rounded-xl transition-all flex items-center gap-2 shrink-0 shadow-xs">
                        <i class="fa-solid fa-receipt" :class="isTesting ? 'fa-spin' : ''"></i> Tes Cetak Sekarang
                    </button>
                </div>
            </template>

            <template x-if="isTestOldOrMissing === 'old'">
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-blue-900">Pengingat: Tes Terakhir Sudah Lebih dari 7 Hari</h4>
                            <p class="text-[11px] font-semibold text-blue-700" x-text="formattedLastTest"></p>
                        </div>
                    </div>
                    <button @click="runTestPrint()" :disabled="isTesting" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition-all flex items-center gap-2 shrink-0 shadow-xs">
                        <i class="fa-solid fa-receipt"></i> Tes Ulang
                    </button>
                </div>
            </template>

            <!-- 2. TABS NAVIGASI -->
            <div class="flex items-center gap-2 border-b border-[#FFE4EC] pb-2">
                <button @click="activeTab = 'printer'" 
                        :class="activeTab === 'printer' ? 'bg-[#FF3870] text-white shadow-md shadow-pink-500/20' : 'bg-white text-slate-600 hover:bg-[#FFF0F5] hover:text-[#FF3870] border border-[#FFE4EC]'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                    <i class="fa-solid fa-tablet-screen-button"></i> 1. Pengaturan Perangkat (Tablet)
                </button>
                <button @click="activeTab = 'receipt'" 
                        :class="activeTab === 'receipt' ? 'bg-[#FF3870] text-white shadow-md shadow-pink-500/20' : 'bg-white text-slate-600 hover:bg-[#FFF0F5] hover:text-[#FF3870] border border-[#FFE4EC]'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                    <i class="fa-solid fa-receipt"></i> 2. Pengaturan Format Struk (Database)
                </button>
                <button @click="activeTab = 'logs'" 
                        :class="activeTab === 'logs' ? 'bg-[#FF3870] text-white shadow-md shadow-pink-500/20' : 'bg-white text-slate-600 hover:bg-[#FFF0F5] hover:text-[#FF3870] border border-[#FFE4EC]'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> 3. Log & Riwayat Cetak
                </button>
            </div>

            <!-- ==================== TAB 1: PENGATURAN PERANGKAT (LOKAL) ==================== -->
            <div x-show="activeTab === 'printer'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Form Perangkat (2 Kolom) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl border border-[#FFE4EC] p-6 shadow-xs space-y-5">
                        <div class="border-b border-[#FFE4EC] pb-4 flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-black text-[#4A2311] flex items-center gap-2">
                                    <i class="fa-solid fa-sliders text-[#FF3870]"></i> Konfigurasi Perangkat Cetak
                                </h3>
                                <p class="text-[11px] text-slate-400 font-semibold">Pengaturan ini tersimpan langsung pada browser/tablet kasir ini (localStorage).</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-check"></i> Siap Pakai
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Perangkat -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Nama Perangkat / Tablet <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-tablet-button text-xs"></i></span>
                                    <input type="text" x-model="deviceName" placeholder="Contoh: Tablet Kasir 1" class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800">
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 block">Nama ini akan tercantum di log cetak kasir.</span>
                            </div>

                            <!-- Mode Cetak -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Metode Cetak Utama <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <select x-model="printMode" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800 cursor-pointer">
                                        <option value="rawbt">🚀 RawBT Driver Service (Android / Tablet - Sangat Cepat)</option>
                                        <option value="browser">🖨️ Browser Print (Dialog Cetak Standar / PC)</option>
                                        <option value="manual">✋ Manual (Pilih Sendiri saat Selesai Transaksi)</option>
                                    </select>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 block">Pilih <b>RawBT</b> jika menggunakan tablet/HP Android.</span>
                            </div>

                            <!-- Lebar Kertas -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Ukuran Kertas Thermal <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="paperWidth = '58mm'" 
                                            :class="paperWidth === '58mm' ? 'bg-[#FFF0F5] border-[#FF3870] text-[#FF3870] font-black ring-1 ring-[#FF3870]' : 'bg-slate-50 border-slate-200 text-slate-600 font-bold'"
                                            class="py-2.5 px-3 border rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-scroll text-[11px]"></i> 58mm (32 Karakter)
                                    </button>
                                    <button type="button" @click="paperWidth = '80mm'" 
                                            :class="paperWidth === '80mm' ? 'bg-[#FFF0F5] border-[#FF3870] text-[#FF3870] font-black ring-1 ring-[#FF3870]' : 'bg-slate-50 border-slate-200 text-slate-600 font-bold'"
                                            class="py-2.5 px-3 border rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-scroll text-[11px]"></i> 80mm (48 Karakter)
                                    </button>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 block">Format kolom otomatis disesuaikan agar rapi dan tidak terpotong.</span>
                            </div>

                            <!-- Toggle Cetak Otomatis Saat Bayar -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Cetak Otomatis Saat Bayar
                                </label>
                                <div class="flex items-center gap-3 pt-1">
                                    <button type="button" @click="autoPrintOnPay = !autoPrintOnPay" 
                                            :class="autoPrintOnPay ? 'bg-emerald-500' : 'bg-slate-300'"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span :class="autoPrintOnPay ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                    <span class="text-xs font-black text-slate-700" x-text="autoPrintOnPay ? 'Aktif (Otomatis Cetak)' : 'Nonaktif (Manual)'"></span>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1.5 block">Jika aktif, kasir tidak perlu klik tombol cetak setelah pembayaran lunas.</span>
                            </div>
                        </div>

                        <!-- Status & Tombol Aksi -->
                        <div class="pt-4 border-t border-[#FFE4EC] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full" :class="lastTestStatus === 'success' ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                <span x-text="formattedLastTest"></span>
                            </div>
                            <button type="button" @click="saveDeviceSettings()" class="px-5 py-2.5 bg-[#FF3870] hover:bg-[#5C2D16] text-white text-xs font-bold rounded-xl shadow-md shadow-pink-500/20 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan Tablet
                            </button>
                        </div>
                    </div>

                    <!-- Kartu Tombol Uji Coba & Troubleshooting -->
                    <div class="bg-white rounded-3xl border border-[#FFE4EC] p-6 shadow-xs flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h4 class="text-xs font-black text-[#4A2311]">Uji Coba & Bantuan Cetak</h4>
                            <p class="text-[11px] text-slate-400 font-semibold">Gunakan tombol ini untuk memastikan printer thermal siap beroperasi.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="runTestPrint()" :disabled="isTesting" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center gap-2">
                                <i class="fa-solid fa-receipt" :class="isTesting ? 'fa-spin' : ''"></i> 
                                <span x-text="isTesting ? 'Mencetak...' : 'Tes Cetak Struk'"></span>
                            </button>
                            <button type="button" @click="reprintLast()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center gap-2">
                                <i class="fa-solid fa-rotate-right"></i> Cetak Ulang Struk Terakhir
                            </button>
                            <button type="button" @click="openTroubleshootModal()" class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-exclamation"></i> Struk tidak keluar?
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Checklist Setup RawBT (1 Kolom) -->
                <div class="space-y-6">
                    <div class="bg-gradient-to-br from-[#FFF8FA] to-white rounded-3xl border border-[#FFE4EC] p-6 shadow-xs space-y-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#FFE4EC]">
                            <div class="w-8 h-8 rounded-xl bg-[#FF3870] text-white flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-[#4A2311]">Checklist Setup RawBT (5 Langkah)</h4>
                                <p class="text-[10px] text-slate-400 font-semibold">Pairing dilakukan di Android, bukan di Web!</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#FFF0F5] text-[#FF3870] border border-[#FFC5D8] flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">1</span>
                                <p class="font-bold text-slate-700">Install aplikasi <b>RawBT Print Service</b> dari Google Play Store pada tablet/HP kasir.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#FFF0F5] text-[#FF3870] border border-[#FFC5D8] flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">2</span>
                                <p class="font-bold text-slate-700">Nyalakan printer thermal dan hubungkan Bluetooth melalui menu <b>Pengaturan Bluetooth Android</b>.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#FFF0F5] text-[#FF3870] border border-[#FFC5D8] flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">3</span>
                                <p class="font-bold text-slate-700">Buka aplikasi RawBT > masuk ke menu <b>Printer Settings</b> > pilih printer Anda.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#FFF0F5] text-[#FF3870] border border-[#FFC5D8] flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">4</span>
                                <p class="font-bold text-slate-700">Pastikan status RawBT menampilkan <b>"Ready"</b> atau indikator hijau menyala.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#FFF0F5] text-[#FF3870] border border-[#FFC5D8] flex items-center justify-center font-black text-[10px] shrink-0 mt-0.5">5</span>
                                <p class="font-bold text-slate-700">Pilih mode <b>RawBT</b> di halaman ini, lalu klik tombol <b>Tes Cetak Struk</b>!</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-[#FFE4EC]">
                            <button type="button" @click="showRawbtModal = true" class="w-full py-2.5 bg-[#FFF0F5] hover:bg-[#FF3870] text-[#FF3870] hover:text-white border border-[#FFC5D8] rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-book-open"></i> Baca Solusi Jika Koneksi Terputus
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ==================== TAB 2: PENGATURAN STRUK (DATABASE) ==================== -->
            <div x-show="activeTab === 'receipt'" class="bg-white rounded-3xl border border-[#FFE4EC] p-6 shadow-xs max-w-4xl space-y-6">
                <div class="border-b border-[#FFE4EC] pb-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-black text-[#4A2311] flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-[#FF3870]"></i> Format & Identitas Struk Kasir
                        </h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Pengaturan ini disimpan di database pusat dan berlaku untuk seluruh kasir di outlet ini.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Nama Toko di Struk</label>
                        <input type="text" x-model="storeSettings.store_name" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Nomor Telepon Toko</label>
                        <input type="text" x-model="storeSettings.store_phone" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Alamat Lengkap Toko</label>
                        <textarea x-model="storeSettings.store_address" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Teks Pesan Footer Struk</label>
                        <input type="text" x-model="storeSettings.receipt_footer" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800">
                    </div>
                </div>

                <!-- Opsi Tampil / Sembunyi Elemen Struk -->
                <div class="pt-4 border-t border-[#FFE4EC]">
                    <h4 class="text-xs font-black text-[#4A2311] uppercase tracking-wider mb-3">Opsi Elemen Struk</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-[#FFF0F5]">
                            <input type="checkbox" x-model="storeSettings.show_logo" :true-value="1" :false-value="0" class="rounded text-[#FF3870] focus:ring-[#FF3870]">
                            <span class="text-xs font-bold text-slate-700">Nama Toko Header Besar</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-[#FFF0F5]">
                            <input type="checkbox" x-model="storeSettings.show_cashier" :true-value="1" :false-value="0" class="rounded text-[#FF3870] focus:ring-[#FF3870]">
                            <span class="text-xs font-bold text-slate-700">Tampilkan Nama Kasir</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-[#FFF0F5]">
                            <input type="checkbox" x-model="storeSettings.show_invoice" :true-value="1" :false-value="0" class="rounded text-[#FF3870] focus:ring-[#FF3870]">
                            <span class="text-xs font-bold text-slate-700">Tampilkan No. Invoice</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-[#FFF0F5]">
                            <input type="checkbox" x-model="storeSettings.show_notes" :true-value="1" :false-value="0" class="rounded text-[#FF3870] focus:ring-[#FF3870]">
                            <span class="text-xs font-bold text-slate-700">Tampilkan Catatan Order</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-[#FFF0F5]">
                            <input type="checkbox" x-model="storeSettings.show_tax_ongkir" :true-value="1" :false-value="0" class="rounded text-[#FF3870] focus:ring-[#FF3870]">
                            <span class="text-xs font-bold text-slate-700">Tampilkan Ongkir / Biaya</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#FFE4EC] flex justify-end">
                    <button type="button" @click="saveReceiptSettings()" :disabled="isLoading" class="px-6 py-2.5 bg-[#FF3870] hover:bg-[#5C2D16] disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-md shadow-pink-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Format Struk
                    </button>
                </div>
            </div>

            <!-- ==================== TAB 3: LOG CETAK ADMIN ==================== -->
            <div x-show="activeTab === 'logs'" class="space-y-5">
                
                <!-- Counter Statistik -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-2xl border border-[#FFE4EC] shadow-2xs">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Total Aktivitas Cetak</span>
                        <h4 class="text-xl font-black text-[#4A2311] mt-1" x-text="logStats.total_print">0</h4>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-2xs">
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-wider block">Cetak Sukses Terkirim</span>
                        <h4 class="text-xl font-black text-emerald-600 mt-1" x-text="logStats.total_success">0</h4>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-2xs">
                        <span class="text-[10px] font-black text-rose-600 uppercase tracking-wider block">Laporan "Struk Macet"</span>
                        <h4 class="text-xl font-black text-rose-600 mt-1" x-text="logStats.total_stuck">0</h4>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-2xs">
                        <span class="text-[10px] font-black text-amber-600 uppercase tracking-wider block">Gagal Kirim</span>
                        <h4 class="text-xl font-black text-amber-600 mt-1" x-text="logStats.total_failed">0</h4>
                    </div>
                </div>

                <!-- Tabel Riwayat Log -->
                <div class="bg-white rounded-3xl border border-[#FFE4EC] shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-[#FFE4EC] flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-black text-[#4A2311]">30 Riwayat Aktivitas Cetak Terakhir</h3>
                            <p class="text-[11px] text-slate-400 font-semibold">Mencatat pengiriman cetak per perangkat tablet dan laporan kasir.</p>
                        </div>
                        <button @click="loadPrintLogs()" class="p-2 rounded-xl bg-slate-50 hover:bg-[#FFF0F5] text-slate-600 hover:text-[#FF3870] border border-slate-200 transition-all text-xs">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh Log
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-[#FFF8FA] border-b border-[#FFE4EC] text-[#5C2D16] text-[10px] font-black uppercase tracking-wider">
                                    <th class="p-3.5">Waktu</th>
                                    <th class="p-3.5">No. Invoice</th>
                                    <th class="p-3.5">Perangkat / Tablet</th>
                                    <th class="p-3.5">Metode</th>
                                    <th class="p-3.5">Status Cetak</th>
                                    <th class="p-3.5">Catatan / Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFE4EC]/40">
                                <template x-for="log in printLogs" :key="log.id">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3.5 text-slate-500 font-mono text-[11px]" x-text="log.created_at"></td>
                                        <td class="p-3.5 font-black text-slate-800" x-text="log.invoice_no"></td>
                                        <td class="p-3.5 font-bold text-slate-700" x-text="log.device_name"></td>
                                        <td class="p-3.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                                  :class="log.mode === 'rawbt' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                                  x-text="log.mode">
                                            </span>
                                        </td>
                                        <td class="p-3.5">
                                            <span x-show="log.status === 'success'" class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full text-[10px] font-black">
                                                <i class="fa-solid fa-circle-check text-[9px]"></i> Sukses
                                            </span>
                                            <span x-show="log.status === 'reported_stuck'" class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 rounded-full text-[10px] font-black">
                                                <i class="fa-solid fa-circle-exclamation text-[9px]"></i> Struk Macet
                                            </span>
                                            <span x-show="log.status === 'failed'" class="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 rounded-full text-[10px] font-black">
                                                <i class="fa-solid fa-xmark text-[9px]"></i> Gagal
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-slate-500 font-medium" x-text="log.note || '-'"></td>
                                    </tr>
                                </template>
                                <tr x-show="printLogs.length === 0">
                                    <td colspan="6" class="p-10 text-center text-slate-400 font-bold">Belum ada riwayat cetak tercatat.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- ==================== MODAL PANDUAN LENGKAP RAWBT & KONEKSI ==================== -->
    <div x-show="showRawbtModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" x-transition>
        <div class="bg-white rounded-3xl shadow-2xl border border-[#FFE4EC] w-full max-w-xl overflow-hidden" @click.away="showRawbtModal = false">
            <div class="px-6 py-4 bg-[#FFF8FA] border-b border-[#FFE4EC] flex justify-between items-center">
                <h3 class="font-black text-sm text-[#4A2311] flex items-center gap-2">
                    <i class="fa-brands fa-android text-emerald-500 text-base"></i> Panduan Setup RawBT di Tablet Android
                </h3>
                <button type="button" @click="showRawbtModal = false" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto custom-scrollbar text-xs">
                
                <div class="bg-emerald-50 border border-emerald-200 p-3.5 rounded-2xl">
                    <h4 class="font-black text-emerald-900 text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Prinsip Dasar RawBT:
                    </h4>
                    <p class="text-emerald-800 text-[11px] mt-1 leading-relaxed">
                        Web POS Hariku hanya bertugas <b>mengirimkan data teks struk</b> ke RawBT. Koneksi fisik Bluetooth/USB dikelola sepenuhnya oleh aplikasi RawBT di Android. Web tidak perlu pairing ulang!
                    </p>
                </div>

                <div class="space-y-3">
                    <h4 class="font-black text-slate-800 uppercase tracking-wider text-[11px]">Langkah Instalasi & Setup:</h4>
                    
                    <div class="flex gap-3 items-start bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div class="w-6 h-6 rounded-lg bg-[#FF3870] text-white flex items-center justify-center font-black shrink-0 text-xs">1</div>
                        <div>
                            <p class="font-bold text-slate-800">Pasang RawBT dari Google Play Store</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Buka Play Store di tablet kasir, cari <b>"RawBT print service"</b> dan install secara gratis.</p>
                        </div>
                    </div>

                    <div class="flex gap-3 items-start bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div class="w-6 h-6 rounded-lg bg-[#FF3870] text-white flex items-center justify-center font-black shrink-0 text-xs">2</div>
                        <div>
                            <p class="font-bold text-slate-800">Pairing Bluetooth di Pengaturan Tablet</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Buka Settings Android > Bluetooth > hubungkan printer thermal Anda (PIN default biasanya <b>0000</b> atau <b>1234</b>).</p>
                        </div>
                    </div>

                    <div class="flex gap-3 items-start bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div class="w-6 h-6 rounded-lg bg-[#FF3870] text-white flex items-center justify-center font-black shrink-0 text-xs">3</div>
                        <div>
                            <p class="font-bold text-slate-800">Pilih Printer di Aplikasi RawBT</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Buka aplikasi RawBT > Masuk ke <b>Settings</b> > <b>Printer connection</b> > Pilih Bluetooth > Klik printer thermal Anda.</p>
                        </div>
                    </div>
                </div>

                <!-- Peringatan Jika Putus -->
                <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl space-y-1.5">
                    <h4 class="font-black text-rose-900 text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Apa yang Dilakukan Jika Koneksi Terputus?
                    </h4>
                    <p class="text-rose-800 text-[11px] leading-relaxed">
                        Jika printer sempat mati, baterai habis, atau jarak terlalu jauh:
                    </p>
                    <ul class="list-disc list-inside text-rose-800 text-[11px] space-y-0.5 pl-1 font-semibold">
                        <li><b>JANGAN</b> utak-atik web kasir.</li>
                        <li>Cukup <b>nyalakan printer kembali</b> atau buka aplikasi <b>RawBT di tablet</b> untuk memastikan statusnya kembali hijau/ready.</li>
                        <li>Begitu RawBT terhubung, web POS otomatis langsung bisa mencetak kembali tanpa konfigurasi ulang!</li>
                    </ul>
                </div>

            </div>

            <div class="p-4 bg-slate-50 border-t border-[#FFE4EC] flex justify-end">
                <button type="button" @click="showRawbtModal = false" class="px-5 py-2 bg-[#FF3870] text-white text-xs font-bold rounded-xl transition-all">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL BANTUAN "STRUK TIDAK KELUAR?" ==================== -->
    <div x-show="showTroubleshootModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" x-transition>
        <div class="bg-white rounded-3xl shadow-2xl border border-[#FFE4EC] w-full max-w-md overflow-hidden" @click.away="showTroubleshootModal = false">
            <div class="px-6 py-4 bg-[#FFF8FA] border-b border-[#FFE4EC] flex justify-between items-center">
                <h3 class="font-black text-sm text-[#4A2311] flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-rose-500"></i> Checklist: Struk Tidak Keluar?
                </h3>
                <button type="button" @click="showTroubleshootModal = false" class="text-slate-400 hover:text-rose-500 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <p class="text-slate-600 font-semibold leading-relaxed">
                    Periksa 4 hal cepat berikut sebelum mengulang proses cetak:
                </p>

                <div class="space-y-2.5">
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="checkbox" checked class="mt-0.5 rounded text-[#FF3870]">
                        <span class="text-slate-700 font-bold">1. Lampu Daya (Power) printer menyala warna biru/hijau?</span>
                    </label>
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="checkbox" checked class="mt-0.5 rounded text-[#FF3870]">
                        <span class="text-slate-700 font-bold">2. Kertas thermal tidak habis dan terpasang benar (tidak terbalik)?</span>
                    </label>
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="checkbox" checked class="mt-0.5 rounded text-[#FF3870]">
                        <span class="text-slate-700 font-bold">3. Aplikasi RawBT di tablet dalam keadaan terbuka / Service Running?</span>
                    </label>
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <input type="checkbox" checked class="mt-0.5 rounded text-[#FF3870]">
                        <span class="text-slate-700 font-bold">4. Bluetooth tablet aktif dan berada dekat dengan printer?</span>
                    </label>
                </div>

                <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-[11px] text-amber-800 font-medium">
                    Jika printer baru saja dihidupkan, tunggu 3-5 detik agar Bluetooth tersambung ke RawBT terlebih dahulu.
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-[#FFE4EC] flex items-center justify-between gap-2">
                <button type="button" @click="reportPrinterStuck()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Laporkan Struk Macet
                </button>
                <button type="button" @click="showTroubleshootModal = false; reprintLast()" class="px-4 py-2 bg-[#FF3870] hover:bg-[#5C2D16] text-white text-xs font-bold rounded-xl shadow transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate-right"></i> Cetak Ulang Sekarang
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/rawbt_printer.js?v=<?= time() ?>"></script>
    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>