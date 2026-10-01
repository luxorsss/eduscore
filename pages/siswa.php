<?php
session_start();
require_once '../config/koneksi.php';

// Penjaga Pintu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Data Siswa (LEFT JOIN agar yang tidak punya kelas tetap muncul)
$sql = "SELECT s.*, c.nama_kelas 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        ORDER BY c.nama_kelas ASC, s.nama ASC";
$stmt = $pdo->query($sql);
$daftar_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil Data Kelas untuk Dropdown
$stmt_kelas = $pdo->query("SELECT * FROM classes ORDER BY nama_kelas ASC");
$list_kelas = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Induk Siswa - EduScore";
$page_heading = "Data Induk Siswa";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6 relative">
    
    <!-- Header Bagian & Filter Pencarian -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-surface-card p-5 rounded-xl border border-border-main shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-text-main">Daftar Siswa & Penempatan Kelas</h2>
            <p class="text-xs text-text-muted mt-0.5">Kelola data murid, penempatan rombongan belajar, dan tindakan massal.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-base">search</span>
                <input type="text" id="searchInput" class="w-full bg-white border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-lg pl-9 pr-3.5 py-2 text-xs font-medium placeholder:text-slate-400 min-h-[40px]" placeholder="Cari nama siswa...">
            </div>
            
            <select id="filterKelas" class="w-full sm:w-auto bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2 font-medium cursor-pointer min-h-[40px]">
                <option value="">Semua Rombel / Kelas</option>
                <option value="Tanpa Kelas">Belum Ada Kelas</option>
                <?php foreach($list_kelas as $lk): ?>
                    <option value="<?= htmlspecialchars($lk['nama_kelas']) ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <form action="proses_bulk_siswa.php" method="POST">
        <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden relative">
            <div class="overflow-x-auto max-h-[60vh] custom-scroll">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[11px] uppercase font-semibold text-text-muted border-b border-border-main sticky top-0 z-10 shadow-xs">
                        <tr>
                            <th class="px-3 md:px-4 py-3 w-12 text-center">
                                <input type="checkbox" id="checkAll" aria-label="Pilih semua siswa" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-3 md:px-4 py-3">Nama Lengkap Siswa</th>
                            <th class="px-3 md:px-4 py-3 w-36 md:w-48">Rombel / Kelas</th>
                            <th class="px-3 md:px-4 py-3 w-28 text-center">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody id="tabelDataSiswa" class="text-xs divide-y divide-border-main">
                        <?php if (empty($daftar_siswa)): ?>
                            <tr id="emptyStateRow">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-4xl text-slate-300">group_off</span>
                                        <p class="text-sm font-semibold text-text-main">Belum Ada Data Siswa</p>
                                        <p class="text-xs text-text-muted max-w-sm">Tambahkan siswa baru menggunakan tombol di bawah atau gunakan fitur salin-tempel massal.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_siswa as $s): ?>
                            <?php $nama_kelas_label = !empty($s['nama_kelas']) ? htmlspecialchars($s['nama_kelas']) : "Tanpa Kelas"; ?>
                            
                            <tr class="student-row hover:bg-slate-50/80 transition-colors" data-nama="<?= strtolower(htmlspecialchars($s['nama'])) ?>" data-kelas="<?= strtolower($nama_kelas_label) ?>">
                                <td class="px-3 md:px-4 py-3 text-center">
                                    <input type="checkbox" name="id_hapus[]" value="<?= $s['id'] ?>" aria-label="Pilih siswa <?= htmlspecialchars($s['nama']) ?>" class="cb-siswa rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                </td>
                                <td class="px-3 md:px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 text-primary flex items-center justify-center font-bold text-xs shrink-0 border border-slate-200">
                                            <?= strtoupper(substr($s['nama'], 0, 2)); ?>
                                        </div>
                                        <span class="font-semibold text-xs md:text-sm text-text-main"><?= htmlspecialchars($s['nama']) ?></span>
                                    </div>
                                </td>
                                <td class="px-3 md:px-4 py-3">
                                    <?php if (!empty($s['nama_kelas'])): ?>
                                        <span class="badge-grade-neutral px-2.5 py-1 rounded-md text-[11px] font-semibold inline-block">
                                            <?= htmlspecialchars($s['nama_kelas']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-grade-warning px-2.5 py-1 rounded-md text-[11px] font-semibold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">warning</span> Belum Diplot
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 md:px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" onclick="bukaModalEdit(<?= $s['id'] ?>, '<?= addslashes($s['nama']) ?>', '<?= $s['class_id'] ?>')" class="w-8 h-8 rounded-lg flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-100 transition-colors" title="Ubah Data">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <a href="proses_bulk_siswa.php?hapus_single=<?= $s['id'] ?>" onclick="konfirmasiLink(event, this.href, 'Data siswa <?= addslashes($s['nama']) ?> beserta seluruh riwayat nilainya akan dihapus permanen.')" class="w-8 h-8 rounded-lg flex items-center justify-center text-text-muted hover:text-danger hover:bg-danger-subtle transition-colors" title="Hapus Siswa">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Baris Pesan Pencarian Nihil -->
                        <tr id="noSearchResultRow" class="hidden">
                            <td colspan="4" class="px-6 py-8 text-center text-text-muted text-xs">
                                Tidak ada siswa yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    </tbody>

                    <tbody id="containerInputSiswa" class="divide-y divide-border-main bg-slate-50/50">
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 flex flex-wrap justify-between items-center gap-3 border-t border-border-main">
                <button type="button" onclick="tambahBarisInput()" class="flex items-center gap-2 text-primary font-semibold text-xs hover:underline min-h-[44px] px-2">
                    <span class="material-symbols-outlined text-lg">add_circle</span> Tambah Baris Siswa Manual
                </button>
                <button type="submit" name="aksi" value="simpan_massal" class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg text-xs font-semibold transition-colors shadow-xs min-h-[44px]">
                    Simpan Siswa Baru
                </button>
            </div>
        </div>

        <!-- Floating Bulk Action Bar -->
        <div id="bulkActionBar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-surface-card border border-border-main shadow-lg rounded-xl px-5 py-3 flex flex-wrap items-center justify-center gap-4 transition-all duration-200 translate-y-32 opacity-0 z-40 w-[95%] md:w-auto">
            
            <span class="text-xs font-semibold text-text-main whitespace-nowrap">
                <span id="selectedCount" class="text-primary font-bold">0</span> Siswa Terpilih
            </span>
            
            <div class="hidden sm:block w-px h-5 bg-border-main"></div>

            <div class="flex items-center gap-2">
                <select name="target_class_id" class="text-xs font-medium rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 bg-white text-text-main cursor-pointer min-h-[36px]">
                    <option value="" disabled selected>Pindahkan ke Kelas...</option>
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="aksi" value="pindah_massal" onclick="konfirmasiForm(event, 'Pindahkan seluruh siswa yang dipilih ke kelas tujuan?')" class="px-3 py-1.5 text-xs font-semibold bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors min-h-[36px]">
                    Pindahkan
                </button>
            </div>

            <div class="w-px h-5 bg-border-main"></div>

            <button type="submit" name="aksi" value="hapus_massal" onclick="konfirmasiForm(event, 'Hapus seluruh siswa terpilih beserta seluruh riwayat nilainya?')" class="px-3 py-1.5 text-xs font-semibold text-danger hover:bg-danger-subtle rounded-lg transition-colors min-h-[36px]">
                Hapus Terpilih
            </button>
        </div>
    </form>

    <!-- Opsi Input Massal via Salin Tempel -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden mb-12">
        <div class="p-5 border-b border-border-main bg-slate-50">
            <h3 class="font-bold text-sm text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-primary">content_paste</span>
                Penambahan Massal (Salin - Tempel Teks)
            </h3>
            <p class="text-xs text-text-muted mt-1">
                Tempel data siswa dalam format: <b>Nama Siswa, Nama Kelas</b> (pisahkan setiap siswa dengan baris baru).
            </p>
        </div>
        <form action="proses_bulk_siswa.php" method="POST" class="p-5 flex flex-col gap-4">
            <textarea name="data_copas" rows="4" class="w-full bg-white border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-lg p-3 text-xs font-mono text-text-main leading-relaxed" placeholder="Contoh:&#10;Ahmad Dahlan, 10 IPA 1&#10;Siti Fatimah, 10 IPA 1" required></textarea>
            
            <div>
                <button type="submit" name="aksi" value="copas_massal" class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg font-semibold text-xs transition-colors shadow-xs min-h-[44px]">
                    Proses & Simpan Data
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Edit Siswa -->
    <div id="modalEditSiswa" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 opacity-0 transition-opacity duration-200">
        <div class="bg-surface-card w-full max-w-sm rounded-xl border border-border-main shadow-lg overflow-hidden transform scale-95 transition-transform duration-200" id="modalEditContent">
            <div class="p-4 border-b border-border-main flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-sm text-text-main">Perbarui Data Siswa</h3>
                <button type="button" onclick="tutupModalEdit()" aria-label="Tutup jendela edit" class="text-text-muted hover:text-text-main transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form action="proses_bulk_siswa.php" method="POST" class="p-5 flex flex-col gap-4">
                <input type="hidden" name="aksi" value="edit_single">
                <input type="hidden" id="edit_id" name="id_siswa" value="">
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="edit_nama">Nama Lengkap</label>
                    <input type="text" id="edit_nama" name="nama_siswa" class="w-full bg-white rounded-lg px-3 py-2 text-xs border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="edit_kelas">Penempatan Rombel</label>
                    <select id="edit_kelas" name="class_id" class="w-full bg-white rounded-lg px-3 py-2 text-xs border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer">
                        <option value="">-- Tanpa Rombel --</option>
                        <?php foreach($list_kelas as $lk): ?>
                            <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex justify-end gap-2 pt-3 border-t border-border-main">
                    <button type="button" onclick="tutupModalEdit()" class="px-4 py-2 rounded-lg text-xs font-semibold text-text-muted hover:bg-slate-100 transition-colors">Batal</button>
                    <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-xs font-semibold transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // 1. LIVE SEARCH & FILTER KELAS
    const searchInput = document.getElementById('searchInput');
    const filterKelas = document.getElementById('filterKelas');
    const studentRows = document.querySelectorAll('.student-row');
    const noSearchResultRow = document.getElementById('noSearchResultRow');

    function filterData() {
        const querySearch = searchInput.value.toLowerCase().trim();
        const queryKelas = filterKelas.value.toLowerCase().trim();
        let visibleCount = 0;

        studentRows.forEach(row => {
            const namaSiswa = row.getAttribute('data-nama') || '';
            const kelasSiswa = row.getAttribute('data-kelas') || '';

            const matchesSearch = querySearch === '' || namaSiswa.includes(querySearch);
            const matchesKelas = queryKelas === '' || kelasSiswa === queryKelas;

            if (matchesSearch && matchesKelas) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noSearchResultRow) {
            if (studentRows.length > 0 && visibleCount === 0) {
                noSearchResultRow.classList.remove('hidden');
            } else {
                noSearchResultRow.classList.add('hidden');
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterData);
    if (filterKelas) filterKelas.addEventListener('change', filterData);

    // 2. TAMBAH BARIS MANUAL
    function tambahBarisInput() {
        const container = document.getElementById('containerInputSiswa');
        const row = document.createElement('tr');
        row.className = "bg-primary-subtle/30 border-t border-border-main";
        row.innerHTML = `
            <td class="px-3 md:px-4 py-2.5 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-danger hover:opacity-80 p-1" title="Batalkan baris">
                    <span class="material-symbols-outlined text-[18px]">remove_circle</span>
                </button>
            </td>
            <td class="px-3 md:px-4 py-2.5">
                <input type="text" name="nama_siswa[]" required class="w-full bg-white border border-slate-300 rounded-md px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Ketik nama siswa baru...">
                <input type="hidden" name="nis_siswa[]" value="AUTO"> 
            </td>
            <td class="px-3 md:px-4 py-2.5">
                <select name="class_id_siswa[]" class="w-full bg-white border border-slate-300 rounded-md px-2.5 py-1.5 text-xs font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                    <option value="" disabled selected>Pilih Kelas</option>
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
        `;
        container.appendChild(row);
        
        const tableContainer = document.querySelector('.overflow-x-auto');
        if (tableContainer) tableContainer.scrollTop = tableContainer.scrollHeight;
    }

    // 3. LOGIKA FLOATING ACTION BAR 
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.cb-siswa');
    const actionBar = document.getElementById('bulkActionBar');
    const selectedCountLabel = document.getElementById('selectedCount');

    function updateActionBar() {
        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.checked && cb.closest('tr').style.display !== 'none') {
                count++;
            }
        });

        if (selectedCountLabel) selectedCountLabel.innerText = count;

        if (actionBar) {
            if (count > 0) {
                actionBar.classList.remove('translate-y-32', 'opacity-0');
            } else {
                actionBar.classList.add('translate-y-32', 'opacity-0');
            }
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = this.checked;
                }
            });
            updateActionBar();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateActionBar));

    // 4. MODAL EDIT & ESCAPE KEY (R-32)
    const modalEdit = document.getElementById('modalEditSiswa');
    const modalEditContent = document.getElementById('modalEditContent');
    const inputEditId = document.getElementById('edit_id');
    const inputEditNama = document.getElementById('edit_nama');
    const inputEditKelas = document.getElementById('edit_kelas');

    function bukaModalEdit(id, nama, idKelas) {
        inputEditId.value = id;
        inputEditNama.value = nama;
        inputEditKelas.value = idKelas || ""; 
        
        modalEdit.classList.remove('hidden');
        modalEdit.classList.add('flex');
        setTimeout(() => {
            modalEdit.classList.remove('opacity-0');
            modalEditContent.classList.remove('scale-95');
        }, 10);
    }

    function tutupModalEdit() {
        modalEdit.classList.add('opacity-0');
        modalEditContent.classList.add('scale-95');
        setTimeout(() => {
            modalEdit.classList.add('hidden');
            modalEdit.classList.remove('flex');
        }, 200);
    }

    // Keyboard Accessibility: Escape key menutup modal (R-32)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modalEdit && !modalEdit.classList.contains('hidden')) {
            tutupModalEdit();
        }
    });
</script>

<?php require_once '../components/footer.php'; ?>