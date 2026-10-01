<?php
require_once '../../config/auth.php';
$page_title = "Store & Gudang - Love Cakes POS";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../../components/header.php'; ?>
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
                        <i class="fa-solid fa-store text-amber-300"></i> Store & Gudang
                    </h2>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if (!empty($_SESSION['pos_store_name'])): ?>
                <div class="hidden sm:flex bg-black/20 text-amber-300 border border-white/20 px-3 py-1.5 rounded-lg text-xs font-black items-center gap-2 shadow-inner">
                    <i class="fa-solid fa-shop text-amber-400"></i> Outlet: <?= htmlspecialchars($_SESSION['pos_store_name']) ?>
                </div>
                <?php endif; ?>

                <button onclick="loadData()" class="bg-white/20 hover:bg-white/30 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-sm" title="Refresh Data">
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
            <div class="w-full space-y-6">
                
                <!-- SUB-HEADER / ACTIONS -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Daftar Cabang Store & Outlet</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola daftar cabang outlet toko (Store) dan gudang penyimpanan produk.</p>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-64">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="searchStore" oninput="filterTable()" placeholder="Cari store / gudang..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 font-medium text-xs text-slate-700">
                        </div>

                        <button onclick="openModalStore()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black transition-all shadow-sm shadow-primary/30 flex items-center gap-2 shrink-0">
                            <i class="fa-solid fa-plus"></i> Tambah Store
                        </button>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-400 text-[11px] font-black uppercase tracking-wider">
                                    <th class="p-4 text-center w-16">No</th>
                                    <th class="p-4 w-36">Kode Store</th>
                                    <th class="p-4">Nama Store / Outlet</th>
                                    <th class="p-4 text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="table-body" class="text-sm divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-400 font-medium">
                                        <i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL FORM STORE -->
    <div id="modal-gudang" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeModalStore()"></div>
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl z-10 transform transition-all flex flex-col mx-4 overflow-hidden border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                <h3 id="modal-title" class="text-base font-black text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-store text-primary"></i> Tambah Store / Gudang
                </h3>
                <button onclick="closeModalStore()" class="text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form id="formGudang" class="space-y-4">
                    <input type="hidden" id="warehouse_id" name="id">
                    
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Kode Store <span class="text-rose-500">*</span></label>
                        <input type="text" id="code" name="code" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-slate-50 focus:bg-white uppercase font-bold text-sm" placeholder="Contoh: STR-01">
                        <p class="text-[11px] text-slate-400 mt-1">Kode unik untuk mengidentifikasi cabang toko/gudang.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">Nama Store / Gudang <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all bg-slate-50 focus:bg-white font-bold text-sm" placeholder="Contoh: Store Cabang Barat">
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button type="button" onclick="closeModalStore()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                        <button type="submit" id="btn-save-store" class="px-5 py-2 text-xs font-black text-white bg-primary hover:bg-blue-700 rounded-xl transition-all flex items-center gap-2 shadow-sm shadow-primary/30">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="ajax.js?v=<?= time() ?>"></script>
</body>
</html>
