<?php
session_start();
require_once '../config/auth.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM subjects ORDER BY nama_mapel ASC");
$daftar_mapel = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mata Pelajaran - EduScore";
$page_heading = "Manajemen Mata Pelajaran";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-5xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">book</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Master Data Kurikulum</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Daftar Mata Pelajaran</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Kelola mata pelajaran dan bidang studi yang dinilai dalam sistem.
                </p>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center hidden sm:block">
            <span class="text-[11px] font-medium text-text-muted block">Total Mapel</span>
            <span class="text-base font-bold text-text-main tabular-nums"><?= count($daftar_mapel) ?></span>
        </div>
    </div>

    <!-- Form Tambah Mapel Baru (Satuan & Massal) -->
    <div class="bg-surface-card rounded-xl border border-border-main p-5 sm:p-6 shadow-xs">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-4 pb-3 border-b border-border-main">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">add_circle</span>
                Tambah Mata Pelajaran
            </h3>
            
            <!-- Tab Switcher: Satuan vs Massal -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                <button type="button" onclick="switchTab('single')" id="tabBtnSingle" class="px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors">
                    Input Satuan
                </button>
                <button type="button" onclick="switchTab('bulk')" id="tabBtnBulk" class="px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors">
                    Input Massal (Excel)
                </button>
            </div>
        </div>

        <!-- Tab 1: Input Satuan -->
        <div id="tabSingleContent">
            <form action="proses_mapel.php" method="POST" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                <input type="hidden" name="action" value="tambah"> 
                
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-text-main mb-1.5" for="nama_mapel_input">Nama Mata Pelajaran</label>
                    <input id="nama_mapel_input" type="text" name="nama_mapel" required placeholder="Contoh: Matematika Wajib, Bahasa Indonesia, Biologi..." class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 font-medium min-h-[42px] placeholder:text-slate-400">
                </div>

                <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-5 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-xs min-h-[42px]">
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Simpan Mapel</span>
                </button>
            </form>
        </div>

        <!-- Tab 2: Input Massal -->
        <div id="tabBulkContent" class="hidden flex flex-col gap-3">
            <div>
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="bulk_mapel_text">
                    Daftar Nama Mata Pelajaran (Satu mapel per baris):
                </label>
                <textarea id="bulk_mapel_text" rows="5" placeholder="Tempel daftar mapel dari Excel di sini, contoh:
Matematika
Bahasa Indonesia
Biologi
Fisika" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 p-3 font-mono placeholder:text-slate-400 leading-relaxed"></textarea>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="clean_numbers_check" checked class="rounded text-primary focus:ring-primary w-4 h-4 border-slate-300">
                    <span class="text-xs text-text-muted font-medium">
                        Otomatis bersihkan angka di belakang nama mapel (misal: <span class="italic text-text-main font-semibold">"Indonesia 1" &rarr; "Indonesia"</span>)
                    </span>
                </label>

                <button type="button" onclick="bukaPratinjauBulk()" class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-5 py-2.5 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-xs min-h-[42px] shrink-0">
                    <span class="material-symbols-outlined text-base">visibility</span>
                    <span>Cek & Pratinjau Mapel</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Mapel -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">
                Daftar Seluruh Mata Pelajaran
            </h3>
            <span class="text-xs text-text-muted bg-white border border-slate-200 px-2.5 py-1 rounded-md tabular-nums">
                <?= count($daftar_mapel) ?> Mapel
            </span>
        </div>

        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[11px] uppercase font-semibold text-text-muted border-b border-border-main">
                        <th class="px-4 py-3 w-16 text-center">No</th>
                        <th class="px-4 py-3">Nama Mata Pelajaran</th>
                        <th class="px-4 py-3 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-main">
                    <?php if (empty($daftar_mapel)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-text-muted">
                                Belum ada mata pelajaran yang didaftarkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftar_mapel as $m): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-4 py-3.5 text-center text-text-muted font-medium tabular-nums"><?= $no++; ?></td>
                            
                            <td class="px-4 py-3.5">
                                <!-- Mode Teks Normal -->
                                <div id="text_<?= $m['id']; ?>" class="font-bold text-xs md:text-sm text-text-main">
                                    <?= htmlspecialchars($m['nama_mapel']); ?>
                                </div>
                                
                                <!-- Mode Form Edit -->
                                <form id="form_<?= $m['id']; ?>" action="proses_mapel.php" method="POST" class="hidden items-center gap-2 w-full max-w-md">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id_mapel" value="<?= $m['id']; ?>">
                                    <input type="text" name="nama_mapel" value="<?= htmlspecialchars($m['nama_mapel']); ?>" required 
                                           class="w-full bg-white border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-lg px-3 py-1.5 text-xs font-semibold text-text-main">
                                    <button type="submit" class="p-1.5 rounded-lg bg-primary-subtle text-primary hover:bg-primary hover:text-white transition-colors" title="Simpan Perubahan">
                                        <span class="material-symbols-outlined text-lg">check</span>
                                    </button>
                                    <button type="button" onclick="toggleEdit(<?= $m['id']; ?>)" class="p-1.5 rounded-lg text-text-muted hover:bg-slate-200 transition-colors" title="Batal">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3.5 text-center space-x-1 whitespace-nowrap">
                                <!-- Tombol Edit -->
                                <button type="button" onclick="toggleEdit(<?= $m['id']; ?>)" class="p-1.5 rounded-lg text-text-muted hover:text-primary hover:bg-slate-100 transition-colors" title="Edit Mata Pelajaran">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <!-- Tombol Hapus -->
                                <a href="proses_mapel.php?hapus=<?= $m['id']; ?>" 
                                   onclick="konfirmasiLink(event, this.href, 'Hapus mata pelajaran <?= htmlspecialchars($m['nama_mapel']); ?>?')" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors inline-block" 
                                   title="Hapus Mapel">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Pratinjau Bulk Mapel -->
    <div id="modalPreviewBulk" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="p-5 border-b border-border-main flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-xl">fact_check</span>
                    <div>
                        <h4 class="text-sm font-bold text-text-main">Pratinjau Bulk Input Mapel</h4>
                        <p class="text-xs text-text-muted mt-0.5">Tinjau mapel baru dan mapel yang dilewati karena dobel.</p>
                    </div>
                </div>
                <button type="button" onclick="tutupPratinjauBulk()" class="text-slate-400 hover:text-text-main p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Form Simpan Bulk (Membungkus Modal Body & Footer) -->
            <form action="proses_mapel.php" method="POST" id="formBulkSubmit" class="flex flex-col flex-1 overflow-hidden">
                <input type="hidden" name="action" value="bulk_tambah">

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto custom-scroll flex flex-col gap-4 flex-1">
                    <!-- Status Badges -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-success-subtle/50 border border-success/20 flex items-center gap-3">
                            <span class="material-symbols-outlined text-success text-2xl">check_circle</span>
                            <div>
                                <span class="text-[11px] font-semibold text-text-muted block">Mapel Baru Siap Masuk</span>
                                <span id="badgeCountBaru" class="text-base font-bold text-success tabular-nums">0</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3">
                            <span class="material-symbols-outlined text-amber-600 text-2xl">warning</span>
                            <div>
                                <span class="text-[11px] font-semibold text-text-muted block">Mapel Dobel (Dilewati)</span>
                                <span id="badgeCountDobel" class="text-base font-bold text-amber-700 tabular-nums">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Mapel Baru -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="text-xs font-bold text-text-main flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-success text-base">playlist_add_check</span>
                                Mapel Baru yang Akan Ditambahkan:
                            </h5>
                            <button type="button" onclick="toggleSelectAllBulk()" id="btnSelectAll" class="text-[11px] font-semibold text-primary hover:underline">
                                Batal Pilih Semua
                            </button>
                        </div>
                        <div id="containerMapelBaru" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto custom-scroll p-1 border border-border-main rounded-xl bg-slate-50/50">
                            <!-- Dihasilkan via JS -->
                        </div>
                    </div>

                    <!-- Bagian Mapel Dobel (Jika ada) -->
                    <div id="wrapperMapelDobel" class="hidden">
                        <h5 class="text-xs font-bold text-text-main mb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-amber-600 text-base">block</span>
                            Mapel Duplikat (Otomatis Dilewati):
                        </h5>
                        <div id="containerMapelDobel" class="flex flex-col gap-1.5 max-h-36 overflow-y-auto custom-scroll p-1 border border-amber-200 rounded-xl bg-amber-50/30">
                            <!-- Dihasilkan via JS -->
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-border-main bg-slate-50 flex items-center justify-between gap-3">
                    <button type="button" onclick="tutupPratinjauBulk()" class="text-xs font-semibold text-text-muted hover:text-text-main px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 transition-colors">
                        Batal / Ubah Teks
                    </button>
                    <button type="submit" id="btnKonfirmasiSimpan" class="bg-primary hover:bg-primary-hover disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-semibold px-5 py-2.5 rounded-lg flex items-center gap-2 transition-colors shadow-xs min-h-[40px]">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span id="labelSimpanBulk">Simpan Mapel Terpilih</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
const existingMapels = <?= json_encode(array_column($daftar_mapel, 'nama_mapel')) ?>;

function switchTab(tab) {
    const btnSingle = document.getElementById('tabBtnSingle');
    const btnBulk = document.getElementById('tabBtnBulk');
    const contentSingle = document.getElementById('tabSingleContent');
    const contentBulk = document.getElementById('tabBulkContent');

    if (tab === 'single') {
        btnSingle.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
        btnBulk.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        contentSingle.classList.remove('hidden');
        contentBulk.classList.add('hidden');
    } else {
        btnBulk.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
        btnSingle.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        contentBulk.classList.remove('hidden');
        contentSingle.classList.add('hidden');
        const textarea = document.getElementById('bulk_mapel_text');
        if (textarea) textarea.focus();
    }
}

function cleanMapelName(str) {
    if (!str) return '';
    return str
        .replace(/\(\s*\d+\s*\)/g, '')
        .replace(/[\s\-_.]+\d+[\s\-_.]*$/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

let isAllSelected = true;

function bukaPratinjauBulk() {
    const textVal = document.getElementById('bulk_mapel_text').value;
    const shouldClean = document.getElementById('clean_numbers_check').checked;

    const rawLines = textVal.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);

    if (rawLines.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Teks Masih Kosong',
            text: 'Silakan tempel atau ketikkan setidaknya satu nama mata pelajaran di textarea.',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    const existingSet = new Set(existingMapels.map(m => m.toLowerCase().trim()));
    const seenInInput = new Set();
    const newMapels = [];
    const duplicateMapels = [];

    rawLines.forEach(rawItem => {
        let item = shouldClean ? cleanMapelName(rawItem) : rawItem.trim();
        if (!item) return;

        const lower = item.toLowerCase();
        if (existingSet.has(lower)) {
            duplicateMapels.push({ name: item, reason: 'Sudah ada di database' });
        } else if (seenInInput.has(lower)) {
            duplicateMapels.push({ name: item, reason: 'Duplikat pada teks input' });
        } else {
            seenInInput.add(lower);
            newMapels.push(item);
        }
    });

    document.getElementById('badgeCountBaru').textContent = newMapels.length;
    document.getElementById('badgeCountDobel').textContent = duplicateMapels.length;

    const containerBaru = document.getElementById('containerMapelBaru');
    if (newMapels.length === 0) {
        containerBaru.innerHTML = `
            <div class="p-4 text-center text-text-muted text-xs bg-white rounded-lg border border-slate-200">
                Tidak ada mapel baru untuk ditambahkan (seluruh nama yang ditempel sudah ada di database).
            </div>
        `;
    } else {
        let htmlBaru = '';
        newMapels.forEach((name, i) => {
            htmlBaru += `
                <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white hover:bg-slate-100/70 border border-slate-200 cursor-pointer transition-colors">
                    <input type="checkbox" name="nama_mapel_bulk[]" value="${escapeHtml(name)}" checked onchange="updateSelectedCount()" class="mapel-checkbox rounded text-primary focus:ring-primary w-4 h-4 border-slate-300">
                    <span class="text-xs font-semibold text-text-main">${escapeHtml(name)}</span>
                </label>
            `;
        });
        containerBaru.innerHTML = htmlBaru;
    }

    const wrapperDobel = document.getElementById('wrapperMapelDobel');
    const containerDobel = document.getElementById('containerMapelDobel');
    if (duplicateMapels.length > 0) {
        wrapperDobel.classList.remove('hidden');
        let htmlDobel = '';
        duplicateMapels.forEach(item => {
            htmlDobel += `
                <div class="flex items-center justify-between px-3 py-1.5 rounded-lg bg-amber-50/80 border border-amber-200 text-xs">
                    <span class="font-medium text-amber-950">${escapeHtml(item.name)}</span>
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded">${item.reason}</span>
                </div>
            `;
        });
        containerDobel.innerHTML = htmlDobel;
    } else {
        wrapperDobel.classList.add('hidden');
        containerDobel.innerHTML = '';
    }

    isAllSelected = true;
    const btnSelectAll = document.getElementById('btnSelectAll');
    if (btnSelectAll) btnSelectAll.textContent = 'Batal Pilih Semua';

    updateSelectedCount();
    document.getElementById('modalPreviewBulk').classList.remove('hidden');
}

function tutupPratinjauBulk() {
    document.getElementById('modalPreviewBulk').classList.add('hidden');
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.mapel-checkbox');
    const checkedCount = document.querySelectorAll('.mapel-checkbox:checked').length;
    const btnSimpan = document.getElementById('btnKonfirmasiSimpan');
    const labelSimpan = document.getElementById('labelSimpanBulk');

    if (checkedCount === 0) {
        btnSimpan.disabled = true;
        labelSimpan.textContent = 'Pilih Minimal 1 Mapel';
    } else {
        btnSimpan.disabled = false;
        labelSimpan.textContent = `Simpan (${checkedCount}) Mapel Terpilih`;
    }
}

function toggleSelectAllBulk() {
    const checkboxes = document.querySelectorAll('.mapel-checkbox');
    const btn = document.getElementById('btnSelectAll');
    isAllSelected = !isAllSelected;

    checkboxes.forEach(cb => {
        cb.checked = isAllSelected;
    });

    btn.textContent = isAllSelected ? 'Batal Pilih Semua' : 'Pilih Semua';
    updateSelectedCount();
}

function toggleEdit(id) {
    const textDiv = document.getElementById('text_' + id);
    const formDiv = document.getElementById('form_' + id);
    
    if (textDiv.classList.contains('hidden')) {
        textDiv.classList.remove('hidden');
        formDiv.classList.add('hidden');
        formDiv.classList.remove('flex');
    } else {
        textDiv.classList.add('hidden');
        formDiv.classList.remove('hidden');
        formDiv.classList.add('flex');
        const input = formDiv.querySelector('input[type="text"]');
        if (input) input.focus();
    }
}
</script>

<?php if (isset($_SESSION['bulk_status'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const status = <?= json_encode($_SESSION['bulk_status']) ?>;
        let skippedHtml = '';
        if (status.skipped && status.skipped.length > 0) {
            skippedHtml = `<div class="mt-3 text-xs text-amber-800 bg-amber-50 p-3 rounded-xl border border-amber-200 text-left">
                <span class="font-bold block mb-1 text-amber-900">${status.skipped.length} mapel dilewati (sudah ada):</span>
                <span class="text-[11px] leading-relaxed block">${status.skipped.join(', ')}</span>
            </div>`;
        }

        Swal.fire({
            icon: 'success',
            title: 'Input Massal Selesai!',
            html: `Berhasil menambahkan <b>${status.inserted}</b> mata pelajaran baru ke dalam sistem.` + skippedHtml,
            confirmButtonColor: '#4f46e5'
        });
    });
</script>
<?php unset($_SESSION['bulk_status']); endif; ?>

<?php require_once '../components/footer.php'; ?>