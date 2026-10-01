let allProducts = [];
let allCustomPOS = [];
let activeCategory = 'Semua';
let currentTab = 'produk';

document.addEventListener("DOMContentLoaded", () => {
    loadCategoriesDropdown();
    loadData();

    // Form Produk Submit
    const formProduk = document.getElementById('formProduk');
    if (formProduk) {
        formProduk.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-produk');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...';

            const formData = new FormData(this);
            try {
                const response = await fetch('logic.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.status === 'success') {
                    closeModalProduk();
                    loadData();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(res.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: res.message
                        });
                    } else {
                        alert(res.message);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat menyimpan produk!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    // Form Import CSV Submit
    const formImport = document.getElementById('formImport');
    if (formImport) {
        formImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btn-import-submit');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Mengupload...';

            const formData = new FormData(this);
            try {
                const response = await fetch('logic.php?action=import', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.status === 'success') {
                    closeModalImport();
                    loadData();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Berhasil!',
                            text: res.message
                        });
                    } else {
                        alert(res.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal!',
                            text: res.message
                        });
                    } else {
                        alert(res.message);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan saat memproses import CSV!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
});

// Preview Gambar
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('image_preview');
        output.src = reader.result;
    };
    if (event.target.files && event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

// Load Categories into Form Select
async function loadCategoriesDropdown() {
    try {
        const response = await fetch('logic.php?action=get_categories');
        const res = await response.json();
        const select = document.getElementById('category');
        if (res.status === 'success' && res.data) {
            let options = '<option value="">-- Pilih Kategori --</option>';
            res.data.forEach(cat => {
                options += `<option value="${cat}">${cat}</option>`;
            });
            select.innerHTML = options;
        }
    } catch (e) {
        console.error("Gagal load kategori dropdown:", e);
    }
}

function openModalProduk() {
    resetFormProduk();
    loadCategoriesDropdown();
    document.getElementById('modal-produk').classList.remove('hidden');
}

function closeModalProduk() {
    document.getElementById('modal-produk').classList.add('hidden');
}

function resetFormProduk() {
    document.getElementById('formProduk').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('old_image').value = '';
    document.getElementById('image_preview').src = '../../assets/img/no-image.svg';
    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-box text-primary"></i> Tambah Produk Baru';
}

function openModalImport() {
    document.getElementById('formImport').reset();
    document.getElementById('modal-import').classList.remove('hidden');
}

function closeModalImport() {
    document.getElementById('modal-import').classList.add('hidden');
}

function refreshCurrentTab() {
    if (currentTab === 'produk') {
        loadData();
    } else {
        loadCustomPOS();
    }
}

function switchTabProduk(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-content-produk').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn-produk').forEach(btn => {
        btn.classList.remove('bg-primary', 'text-white', 'shadow-sm', 'shadow-primary/30', 'bg-violet-600', 'shadow-violet-200');
        btn.classList.add('text-slate-500', 'hover:bg-slate-100');
    });

    document.getElementById(`tab-${tab}`).classList.remove('hidden');
    const activeBtn = document.getElementById(`tab-btn-${tab}`);
    activeBtn.classList.remove('text-slate-500', 'hover:bg-slate-100');

    const btnGroup = document.getElementById('btn-group-produk');
    if (tab === 'produk') {
        activeBtn.classList.add('bg-primary', 'text-white', 'shadow-sm', 'shadow-primary/30');
        btnGroup.style.display = 'flex';
        loadData();
    } else {
        activeBtn.classList.add('bg-violet-600', 'text-white', 'shadow-sm', 'shadow-violet-200');
        btnGroup.style.display = 'none';
        loadCustomPOS();
    }
}

async function loadData() {
    const tbody = document.getElementById('table-body');
    const refreshIcon = document.getElementById('refresh-icon');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-slate-400 font-medium"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat data produk...</td></tr>`;

    try {
        const response = await fetch('logic.php?action=read');
        const res = await response.json();

        if (res.status === 'success') {
            allProducts = res.data || [];
            buildCategoryFilter(allProducts);
            filterProduk();
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-rose-500 font-medium">Gagal memuat: ${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-rose-500 font-medium">Terjadi kesalahan koneksi data produk.</td></tr>`;
    } finally {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }
}

function buildCategoryFilter(products) {
    const container = document.getElementById('categoryFilterContainer');
    if (!container) return;

    const cats = new Set(['Semua']);
    products.forEach(p => {
        if (p.category && p.category.trim() !== '') {
            cats.add(p.category.trim());
        }
    });

    let html = '';
    cats.forEach(c => {
        const isActive = c === activeCategory;
        const cls = isActive 
            ? 'bg-primary text-white shadow-xs font-black' 
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold';
        html += `<button onclick="setCategoryFilter('${c.replace(/'/g, "\\'")}')" class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all uppercase tracking-wider ${cls}">${c}</button>`;
    });
    container.innerHTML = html;
}

function setCategoryFilter(cat) {
    activeCategory = cat;
    buildCategoryFilter(allProducts);
    filterProduk();
}

function filterProduk() {
    const q = (document.getElementById('searchProduk').value || '').toLowerCase().trim();
    let filtered = allProducts;

    if (activeCategory !== 'Semua') {
        filtered = filtered.filter(p => (p.category || '').toLowerCase() === activeCategory.toLowerCase());
    }

    if (q) {
        filtered = filtered.filter(p => 
            (p.name && p.name.toLowerCase().includes(q)) || 
            (p.code && p.code.toLowerCase().includes(q)) ||
            (p.category && p.category.toLowerCase().includes(q))
        );
    }

    renderTableProduk(filtered);
}

function renderTableProduk(products) {
    const tbody = document.getElementById('table-body');
    if (!products || products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-slate-400 font-medium">Tidak ada produk ditemukan.</td></tr>`;
        return;
    }

    let html = '';
    products.forEach((item, index) => {
        const safeItem = JSON.stringify(item).replace(/'/g, "&apos;");
        const rpModal = new Intl.NumberFormat('id-ID').format(item.modal_price || 0);
        const rpJual = new Intl.NumberFormat('id-ID').format(item.price || 0);
        const rpOnline = new Intl.NumberFormat('id-ID').format(item.online_price || 0);

        const imgSrc = (item.image && item.image !== 'no-image.png' && item.image !== 'no-image.svg') 
            ? `../../assets/img/${item.image}` 
            : `../../assets/img/no-image.svg`;

        html += `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="p-4 text-center font-bold text-slate-400">${index + 1}</td>
                <td class="p-4 text-center">
                    <img src="${imgSrc}" onerror="this.onerror=null; this.src='../../assets/img/no-image.svg';" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shadow-xs mx-auto bg-slate-50" alt="${item.name}">
                </td>
                <td class="p-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-slate-100 text-slate-700 font-mono">
                        ${item.code}
                    </span>
                </td>
                <td class="p-4">
                    <div class="font-bold text-slate-800 text-sm">${item.name}</div>
                </td>
                <td class="p-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 text-blue-700 border border-blue-100">
                        ${item.category || '-'}
                    </span>
                </td>
                <td class="p-4 text-right">
                    <span class="font-bold text-rose-600 text-xs">Rp ${rpModal}</span>
                </td>
                <td class="p-4 text-right">
                    <div class="font-black text-emerald-600 text-xs">Off: Rp ${rpJual}</div>
                    <div class="font-bold text-blue-600 text-[11px]">On: Rp ${rpOnline}</div>
                </td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick='editProduk(${safeItem})' class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Edit Produk">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button onclick="deleteProduk(${item.id}, '${item.name.replace(/'/g, "\\'")}')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Hapus Produk">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function editProduk(item) {
    document.getElementById('product_id').value = item.id;
    document.getElementById('code').value = item.code || '';
    document.getElementById('name').value = item.name || '';
    document.getElementById('modal_price').value = item.modal_price || 0;
    document.getElementById('price').value = item.price || 0;
    document.getElementById('online_price').value = item.online_price || 0;
    document.getElementById('old_image').value = item.image || '';

    // Load category options then select
    loadCategoriesDropdown().then(() => {
        document.getElementById('category').value = item.category || '';
    });

    const imgSrc = (item.image && item.image !== 'no-image.png' && item.image !== 'no-image.svg') 
        ? `../../assets/img/${item.image}` 
        : `../../assets/img/no-image.svg`;
    document.getElementById('image_preview').src = imgSrc;
    document.getElementById('image').value = '';

    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Produk';
    document.getElementById('modal-produk').classList.remove('hidden');
}

async function deleteProduk(id, name) {
    const runDelete = async () => {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('logic.php?action=delete', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                loadData();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(res.message);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message
                    });
                } else {
                    alert(res.message);
                }
            }
        } catch (err) {
            console.error(err);
            alert('Gagal menghapus produk!');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Produk?',
            html: `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br><span class="text-xs text-rose-500">Tindakan ini juga menghapus data produk dari daftar etalase.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i> Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                runDelete();
            }
        });
    } else {
        if (confirm(`Yakin ingin menghapus ${name}?`)) {
            runDelete();
        }
    }
}

// ── CUSTOM ITEM POS LOGIC ──
async function loadCustomPOS() {
    const tbody = document.getElementById('table-custom-pos');
    tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-400 font-medium"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-violet-600"></i> Memuat data item custom POS...</td></tr>`;

    try {
        const response = await fetch('logic.php?action=read_custom_pos');
        const res = await response.json();

        if (res.status === 'success') {
            allCustomPOS = res.data || [];
            renderTableCustomPOS(allCustomPOS);
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-rose-500 font-medium">Gagal memuat: ${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-rose-500 font-medium">Terjadi kesalahan memuat item custom.</td></tr>`;
    }
}

function renderTableCustomPOS(items) {
    const tbody = document.getElementById('table-custom-pos');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-400 font-medium">Belum ada item custom POS tersimpan.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach((item, index) => {
        const rpPrice = new Intl.NumberFormat('id-ID').format(item.price || 0);
        const dateStr = item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        }) : '-';

        html += `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="p-4 text-center font-bold text-slate-400">${index + 1}</td>
                <td class="p-4">
                    <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-violet-500 text-xs"></i>
                        ${item.name}
                    </div>
                </td>
                <td class="p-4 text-right">
                    <span class="font-black text-emerald-600 text-xs">Rp ${rpPrice}</span>
                </td>
                <td class="p-4 text-center text-xs font-medium text-slate-500">${dateStr}</td>
                <td class="p-4 text-center">
                    <button onclick="deleteCustomPOS(${item.id}, '${item.name.replace(/'/g, "\\'")}')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center text-xs mx-auto" title="Hapus Item Custom">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

async function deleteCustomPOS(id, name) {
    const runDelete = async () => {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('logic.php?action=delete_custom_pos', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                loadCustomPOS();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(res.message);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message
                    });
                } else {
                    alert(res.message);
                }
            }
        } catch (err) {
            console.error(err);
            alert('Gagal menghapus item custom POS!');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Item Custom POS?',
            html: `Apakah Anda yakin ingin menghapus <b>${name}</b> dari pilihan kasir?<br><span class="text-xs text-rose-500">Resep kustom terkait juga akan ikut dihapus.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i> Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                runDelete();
            }
        });
    } else {
        if (confirm(`Yakin ingin menghapus ${name}?`)) {
            runDelete();
        }
    }
}
