let allCategories = [];

document.addEventListener("DOMContentLoaded", () => {
    loadData();

    const form = document.getElementById('formKategori');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-kategori');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...';

            const formData = new FormData(this);
            try {
                const response = await fetch('logic.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    closeModalKategori();
                    loadData();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(result.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: result.message
                        });
                    } else {
                        alert(result.message);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan saat menyimpan kategori!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
});

function openModalKategori() {
    resetFormKategori();
    document.getElementById('modal-kategori').classList.remove('hidden');
}

function closeModalKategori() {
    document.getElementById('modal-kategori').classList.add('hidden');
}

function resetFormKategori() {
    document.getElementById('formKategori').reset();
    document.getElementById('kategori_id').value = '';
    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-tags text-primary"></i> Tambah Kategori Baru';
}

async function loadData() {
    const tbody = document.getElementById('table-body');
    const refreshIcon = document.getElementById('refresh-icon');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-slate-400 font-medium"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-primary"></i> Memuat data...</td></tr>`;

    try {
        const response = await fetch('logic.php?action=read');
        const res = await response.json();

        if (res.status === 'success') {
            allCategories = res.data || [];
            renderTable(allCategories);
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-rose-500 font-medium">Gagal memuat: ${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-rose-500 font-medium">Terjadi kesalahan saat memuat kategori.</td></tr>`;
    } finally {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }
}

function renderTable(categories) {
    const tbody = document.getElementById('table-body');
    if (!categories || categories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-slate-400 font-medium">Belum ada kategori produk.</td></tr>`;
        return;
    }

    let html = '';
    categories.forEach((item, index) => {
        const safeItem = JSON.stringify(item).replace(/'/g, "&apos;");
        const formattedDate = item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric'
        }) : '-';

        html += `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="p-4 text-center font-bold text-slate-400">${index + 1}</td>
                <td class="p-4">
                    <span class="inline-flex items-center gap-2 font-bold text-slate-800 text-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary/70"></span>
                        ${item.name}
                    </span>
                </td>
                <td class="p-4 text-center text-xs font-medium text-slate-500">${formattedDate}</td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick='editKategori(${safeItem})' class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Edit Kategori">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button onclick="deleteKategori(${item.id}, '${item.name.replace(/'/g, "\\'")}')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Hapus Kategori">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function filterTable() {
    const q = (document.getElementById('searchKategori').value || '').toLowerCase().trim();
    if (!q) {
        renderTable(allCategories);
        return;
    }
    const filtered = allCategories.filter(c => c.name && c.name.toLowerCase().includes(q));
    renderTable(filtered);
}

function editKategori(item) {
    document.getElementById('kategori_id').value = item.id;
    document.getElementById('name').value = item.name || '';
    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Kategori';
    document.getElementById('modal-kategori').classList.remove('hidden');
}

async function deleteKategori(id, name) {
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
            alert('Gagal menghapus kategori!');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Kategori?',
            html: `Apakah Anda yakin ingin menghapus kategori <b>${name}</b>?<br><span class="text-xs text-rose-500">Produk yang menggunakan kategori ini tetap ada.</span>`,
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
