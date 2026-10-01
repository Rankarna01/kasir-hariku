<?php
require_once '../../config/auth.php';
$page_title = "Data Produk - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans">

    <?php include '../../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER TOP -->
        <header class="bg-primary text-white shadow-md px-4 sm:px-6 py-4 flex justify-between items-center z-20 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-white hover:bg-blue-600 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-box text-amber-300"></i> Data Produk
                    </h2>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="hidden sm:flex bg-black/20 text-amber-300 border border-white/20 px-3 py-1.5 rounded-lg text-xs font-black items-center gap-2 shadow-inner">
                    <i class="fa-solid fa-shop text-amber-400"></i> Outlet: <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>

                <button onclick="refreshCurrentTab()" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Refresh Data">
                    <i class="fa-solid fa-rotate" id="refresh-icon"></i> 
                </button>

                <div class="border-l border-blue-400 pl-4 ml-1">
                    <button onclick="doLogout()" class="bg-rose-500 hover:bg-red-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Keluar">
                        <i class="fa-solid fa-power-off text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 md:p-6 bg-[#f8fafc] relative">
            <div class="w-full max-w-7xl mx-auto space-y-6">
                
                <!-- SUB-HEADER / ACTIONS -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Manajemen Katalog Produk</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola daftar produk jualan, harga modal, harga offline/online, dan item custom kasir.</p>
                    </div>

                    <div id="btn-group-produk" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <button onclick="openModalImport()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-file-csv"></i> Import CSV
                        </button>
                        <button onclick="openModalProduk()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm shadow-primary/30 flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Tambah Produk
                        </button>
                    </div>
                </div>

                <!-- TAB NAVIGATION -->
                <div class="flex items-center gap-1.5 bg-white border border-slate-200 p-1.5 rounded-2xl w-fit shadow-xs">
                    <button id="tab-btn-produk" onclick="switchTabProduk('produk')"
                        class="tab-btn-produk flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all bg-primary text-white shadow-sm shadow-primary/30">
                        <i class="fa-solid fa-cake-candles"></i> Produk Jadi
                    </button>
                    <button id="tab-btn-custom-pos" onclick="switchTabProduk('custom-pos')"
                        class="tab-btn-produk flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all text-slate-500 hover:bg-slate-100">
                        <i class="fa-solid fa-star-half-stroke"></i> Item Custom POS
                    </button>
                </div>

                <!-- TAB CONTENT: PRODUK JADI -->
                <div id="tab-produk" class="tab-content-produk space-y-4">
                    <!-- SEARCH & FILTER BAR -->
                    <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row gap-3 justify-between items-center">
                        <div class="relative w-full md:w-80">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="searchProduk" oninput="filterProduk()" placeholder="Cari nama, kode produk..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-medium text-xs text-slate-700">
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0" id="categoryFilterContainer">
                            <!-- Kategori filter buttons will be injected here dynamically -->
                        </div>
                    </div>

                    <!-- TABLE PRODUK -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-400 text-[11px] font-black uppercase tracking-wider">
                                        <th class="p-4 text-center w-16">No</th>
                                        <th class="p-4 text-center w-20">Gambar</th>
                                        <th class="p-4 w-32">Kode</th>
                                        <th class="p-4">Nama Produk</th>
                                        <th class="p-4 w-36">Kategori</th>
                                        <th class="p-4 text-right w-36">Harga Modal</th>
                                        <th class="p-4 text-right w-44">Harga Jual (Off / On)</th>
                                        <th class="p-4 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body" class="text-sm divide-y divide-slate-100 text-slate-700">
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-slate-400 font-medium">
                                            <i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat data produk...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB CONTENT: ITEM CUSTOM POS -->
                <div id="tab-custom-pos" class="tab-content-produk hidden space-y-4">
                    <div class="p-4 bg-violet-50 border border-violet-200 rounded-2xl flex items-start gap-3">
                        <div class="w-9 h-9 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-circle-info text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-violet-800 uppercase tracking-widest">Item Custom Kasir POS</p>
                            <p class="text-xs text-violet-700 mt-0.5">
                                Item di bawah bersumber dari tabel <code class="bg-violet-100 px-1 rounded font-mono">saved_custom_items_pos</code> yang tersimpan saat kasir membuat pesanan kustom. Item yang dihapus di sini akan hilang dari daftar pilihan kasir.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-400 text-[11px] font-black uppercase tracking-wider">
                                        <th class="p-4 text-center w-16">No</th>
                                        <th class="p-4">Nama Item Custom</th>
                                        <th class="p-4 text-right w-44">Harga POS</th>
                                        <th class="p-4 text-center w-48">Dibuat</th>
                                        <th class="p-4 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="table-custom-pos" class="text-sm divide-y divide-slate-100 text-slate-700">
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                                            <i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat item custom...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL FORM PRODUK -->
    <div id="modal-produk" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeModalProduk()"></div>
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl z-10 transform transition-all flex flex-col mx-4 overflow-hidden border border-slate-100 max-h-[92vh]">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/70 shrink-0">
                <h3 id="modal-title" class="text-base font-black text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-box text-primary"></i> Tambah Produk Baru
                </h3>
                <button onclick="closeModalProduk()" class="text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="formProduk" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" id="product_id" name="id">
                    <input type="hidden" id="old_image" name="old_image">
                    
                    <!-- PREVIEW GAMBAR -->
                    <div class="flex flex-col items-center justify-center mb-2">
                        <img id="image_preview" src="<?= BASE_URL ?>assets/img/no-image.svg" alt="Preview" class="w-28 h-28 object-cover rounded-2xl border-2 border-slate-200 shadow-xs mb-2 bg-slate-50">
                        <div class="w-full text-center">
                            <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1">Foto / Gambar Produk</label>
                            <input type="file" id="image" name="image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" onchange="previewImage(event)">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Kode Produk <span class="text-rose-500">*</span></label>
                            <input type="text" id="code" name="code" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-slate-50 focus:bg-white uppercase font-bold text-sm" placeholder="Cth: RCK-01">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Kategori</label>
                            <select id="category" name="category" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-slate-50 focus:bg-white font-bold text-sm">
                                <option value="">-- Pilih Kategori --</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Nama Produk <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-slate-50 focus:bg-white font-bold text-sm" placeholder="Contoh: Roti Coklat Keju Special">
                    </div>
                    
                    <!-- INPUT HARGA (MODAL, OFFLINE, ONLINE) -->
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Modal (Rp)</label>
                            <input type="number" id="modal_price" name="modal_price" value="0" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all bg-slate-50 focus:bg-white text-rose-600 font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Jual Off (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" id="price" name="price" value="0" min="0" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all bg-slate-50 focus:bg-white text-emerald-600 font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Jual On (Rp)</label>
                            <input type="number" id="online_price" name="online_price" value="0" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all bg-slate-50 focus:bg-white text-blue-600 font-bold text-sm">
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button type="button" onclick="closeModalProduk()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                        <button type="submit" id="btn-save-produk" class="px-5 py-2 text-xs font-black text-white bg-primary hover:bg-blue-700 rounded-xl transition-all flex items-center gap-2 shadow-sm shadow-primary/30">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT CSV -->
    <div id="modal-import" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeModalImport()"></div>
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl z-10 transform transition-all flex flex-col mx-4 overflow-hidden border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-csv text-emerald-600"></i> Import Data via Excel/CSV
                </h3>
                <button onclick="closeModalImport()" class="text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="mb-4 p-4 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-800 space-y-2">
                    <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-info"></i> Petunjuk Import:</p>
                    <ol class="list-decimal pl-5 space-y-1">
                        <li>Unduh template file CSV di bawah.</li>
                        <li>Isi data produk sesuai format kolom.</li>
                        <li>Simpan dalam format <strong>CSV (Comma Delimited)</strong>.</li>
                        <li>Upload file CSV yang sudah diisi ke form berikut.</li>
                    </ol>
                    <div class="pt-1">
                        <a href="logic.php?action=download_template" class="inline-flex items-center gap-1.5 text-xs font-black text-blue-700 hover:text-blue-900 underline">
                            <i class="fa-solid fa-download"></i> Download Template CSV
                        </a>
                    </div>
                </div>

                <form id="formImport" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Pilih File CSV <span class="text-rose-500">*</span></label>
                        <input type="file" id="file_import" name="file_import" accept=".csv" required class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50">
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" onclick="closeModalImport()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                        <button type="submit" id="btn-import-submit" class="px-5 py-2 text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-upload"></i> Proses Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
