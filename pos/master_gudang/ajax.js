let allStores = [];

document.addEventListener("DOMContentLoaded", () => {
    loadData();

    const form = document.getElementById('formGudang');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-store');
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
                    closeModalStore();
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
                alert('Terjadi kesalahan koneksi saat menyimpan!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
});

function openModalStore() {
    resetFormStore();
    document.getElementById('modal-gudang').classList.remove('hidden');
}

function closeModalStore() {
    document.getElementById('modal-gudang').classList.add('hidden');
}

function resetFormStore() {
    document.getElementById('formGudang').reset();
    document.getElementById('warehouse_id').value = '';
    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-store text-primary"></i> Tambah Store / Gudang';
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
            allStores = res.data || [];
            renderTable(allStores);
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-rose-500 font-medium">Gagal memuat: ${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-rose-500 font-medium">Terjadi kesalahan saat memuat data.</td></tr>`;
    } finally {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }
}

function renderTable(stores) {
    const tbody = document.getElementById('table-body');
    if (!stores || stores.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-slate-400 font-medium">Belum ada data store / gudang.</td></tr>`;
        return;
    }

    let html = '';
    stores.forEach((item, index) => {
        const safeItem = JSON.stringify(item).replace(/'/g, "&apos;");
        html += `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="p-4 text-center font-bold text-slate-400">${index + 1}</td>
                <td class="p-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 text-blue-700 border border-blue-100">
                        <i class="fa-solid fa-tag text-[10px]"></i> ${item.code}
                    </span>
                </td>
                <td class="p-4">
                    <div class="font-bold text-slate-800">${item.name}</div>
                    <div class="text-[11px] text-slate-400 font-medium">Outlet Cabang / Gudang Penyimpanan</div>
                </td>
                <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick='editStore(${safeItem})' class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Edit Store">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button onclick="deleteStore(${item.id}, '${item.name.replace(/'/g, "\\'")}')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center text-xs" title="Hapus Store">
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
    const q = (document.getElementById('searchStore').value || '').toLowerCase().trim();
    if (!q) {
        renderTable(allStores);
        return;
    }
    const filtered = allStores.filter(s => 
        (s.name && s.name.toLowerCase().includes(q)) || 
        (s.code && s.code.toLowerCase().includes(q))
    );
    renderTable(filtered);
}

function editStore(item) {
    document.getElementById('warehouse_id').value = item.id;
    document.getElementById('code').value = item.code || '';
    document.getElementById('name').value = item.name || '';
    document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square text-amber-500"></i> Edit Store / Gudang';
    document.getElementById('modal-gudang').classList.remove('hidden');
}

async function deleteStore(id, name) {
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
            alert('Gagal menghapus store!');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Store / Gudang?',
            html: `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br><span class="text-xs text-rose-500">Tindakan ini tidak dapat dibatalkan.</span>`,
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
