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

$page_title = "EduScore - Data Induk Siswa";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6 relative">
    
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Manajemen Siswa</h1>
            <p class="text-on-surface-variant text-sm">Kelola data induk dan penempatan kelas siswa.</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                <input type="text" id="searchInput" class="w-full bg-surface-container-lowest border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary rounded-lg pl-10 pr-4 py-2 text-sm font-medium shadow-sm transition-all" placeholder="Cari nama siswa...">
            </div>
            
            <select id="filterKelas" class="w-full sm:w-auto bg-surface-container-lowest text-on-surface text-sm rounded-lg border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary px-3 py-2 font-medium shadow-sm cursor-pointer">
                <option value="">Semua Kelas</option>
                <option value="Tanpa Kelas">⚠️ Tanpa Kelas</option>
                <?php foreach($list_kelas as $lk): ?>
                    <option value="<?= htmlspecialchars($lk['nama_kelas']) ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <form action="proses_bulk_siswa.php" method="POST">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden relative pb-16 md:pb-0">
            <div class="overflow-auto max-h-[60vh] relative">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low text-[10px] md:text-xs uppercase font-semibold text-on-surface-variant border-b border-outline-variant/30 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-2 md:px-4 py-3 w-10 md:w-12 text-center">
                                <input type="checkbox" id="checkAll" class="rounded text-primary focus:ring-primary border-outline-variant/50 bg-surface w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-2 md:px-4 py-3">Profil Siswa</th>
                            <th class="px-2 md:px-4 py-3 w-24 md:w-40">Kelas</th>
                            <th class="px-2 md:px-4 py-3 w-20 md:w-24 text-center">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody id="tabelDataSiswa" class="text-sm divide-y divide-outline-variant/10">
                        <?php foreach ($daftar_siswa as $s): ?>
                        <?php $nama_kelas_label = !empty($s['nama_kelas']) ? htmlspecialchars($s['nama_kelas']) : "Tanpa Kelas"; ?>
                        
                        <tr class="student-row hover:bg-surface-container-low/50 group transition-colors" data-nama="<?= strtolower(htmlspecialchars($s['nama'])) ?>" data-kelas="<?= strtolower($nama_kelas_label) ?>">
                            <td class="px-2 md:px-4 py-2 md:py-3 text-center">
                                <input type="checkbox" name="id_hapus[]" value="<?= $s['id'] ?>" class="cb-siswa rounded text-primary border-outline-variant/50 w-4 h-4 cursor-pointer">
                            </td>
                            <td class="px-2 md:px-4 py-2 md:py-3">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <div class="w-7 h-7 md:w-10 md:h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[10px] md:text-xs shrink-0">
                                        <?= strtoupper(substr($s['nama'], 0, 2)); ?>
                                    </div>
                                    <span class="font-bold text-xs md:text-sm line-clamp-1 leading-tight"><?= htmlspecialchars($s['nama']) ?></span>
                                </div>
                            </td>
                            <td class="px-2 md:px-4 py-2 md:py-3">
                                <?php if (!empty($s['nama_kelas'])): ?>
                                    <span class="bg-surface-container-highest px-2 py-1 rounded-md text-[10px] md:text-xs font-bold text-on-surface border border-outline-variant/30 whitespace-nowrap">
                                        <?= htmlspecialchars($s['nama_kelas']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-error-container text-error px-1.5 py-1 rounded text-[10px] md:text-xs font-bold flex items-center gap-1 w-fit whitespace-nowrap">
                                        <span class="material-symbols-outlined text-[12px] md:text-[14px]">warning</span> Tanpa Kelas
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 md:py-3 text-center">
                                <div class="flex items-center justify-center gap-1 md:opacity-0 group-hover:opacity-100 transition-opacity flex-nowrap">
                                    <button type="button" onclick="bukaModalEdit(<?= $s['id'] ?>, '<?= addslashes($s['nama']) ?>', '<?= $s['class_id'] ?>')" class="text-primary p-1 md:p-2 hover:bg-primary-container rounded-lg transition-colors shrink-0" title="Edit">
                                        <span class="material-symbols-outlined text-[16px] md:text-[18px]">edit</span>
                                    </button>
                                    <a href="proses_bulk_siswa.php?hapus_single=<?= $s['id'] ?>" onclick="konfirmasiLink(event, this.href, 'Data siswa dan seluruh riwayat nilainya akan hilang.')" class="text-error p-1 md:p-2 hover:bg-error-container rounded-lg transition-colors shrink-0" title="Hapus">
                                        <span class="material-symbols-outlined text-[16px] md:text-[18px]">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tbody id="containerInputSiswa" class="divide-y divide-outline-variant/10">
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-primary/5 flex justify-between items-center border-t border-primary/20">
                <button type="button" onclick="tambahBarisInput()" class="flex items-center gap-2 text-primary font-bold text-sm hover:underline">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span> Tambah Baris Input
                </button>
                <button type="submit" name="aksi" value="simpan_massal" class="bg-primary text-on-primary px-6 py-2 rounded-xl font-bold shadow-md hover:bg-primary-container transition-colors">
                    Simpan Data Baru
                </button>
            </div>
        </div>

        <div id="bulkActionBar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-surface-container-lowest border border-outline-variant/30 shadow-[0_8px_30px_rgb(0,0,0,0.12)] rounded-2xl px-5 py-3 flex flex-wrap items-center justify-center gap-3 md:gap-5 transition-all duration-300 translate-y-32 opacity-0 z-50 w-[95%] md:w-auto">
            
            <span class="text-sm font-bold text-on-surface whitespace-nowrap"><span id="selectedCount" class="text-primary font-black text-lg">0</span> Terpilih</span>
            
            <div class="hidden md:block w-px h-6 bg-outline-variant/30"></div>

            <div class="flex items-center gap-2">
                <select name="target_class_id" class="text-xs font-bold rounded-lg border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary py-2 pl-3 pr-8 bg-surface text-on-surface cursor-pointer">
                    <option value="" disabled selected>-- Pindah ke Kelas --</option>
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="aksi" value="pindah_massal" onclick="return confirm('Yakin pindahkan siswa yang dicentang ke kelas tersebut?')" class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-primary text-on-primary rounded-lg hover:bg-primary-container hover:text-on-primary-container transition-colors shadow-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-[16px]">move_up</span> Pindah
                </button>
            </div>

            <div class="w-px h-6 bg-outline-variant/30"></div>

            <button type="submit" name="aksi" value="hapus_massal" onclick="return confirm('Yakin ingin menghapus siswa beserta riwayat nilainya?')" class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-error hover:bg-error-container hover:text-error rounded-lg transition-colors whitespace-nowrap">
                <span class="material-symbols-outlined text-[16px]">delete</span> Hapus
            </button>
        </div>
    </form>

    <div class="mt-2 bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden mb-10">
        <div class="p-5 border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
            <div>
                <h2 class="font-bold text-lg text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">content_paste</span>
                    Opsi Cepat: Copas Massal (Pemisah Koma)
                </h2>
                <p class="text-sm text-on-surface-variant mt-1">Gunakan koma untuk memisahkan nama dan kelas. Format: <b>Nama Siswa, Nama Kelas</b>.</p>
            </div>
        </div>
        <form action="proses_bulk_siswa.php" method="POST" class="p-5 flex flex-col gap-4">
            <textarea name="data_copas" rows="4" class="w-full bg-surface-container-highest border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl p-4 text-sm font-mono text-on-surface leading-relaxed" placeholder="Contoh:&#10;Budi-Santoso, 10 IPA 1&#10;Siti Nurhaliza, 10 IPA 1" required></textarea>
            
            <button type="submit" name="aksi" value="copas_massal" class="bg-tertiary text-on-primary px-6 py-2.5 rounded-xl font-bold shadow-md hover:bg-tertiary/90 w-fit flex items-center gap-2 transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-[18px]">fact_check</span> Simpan Data Copas
            </button>
        </form>
    </div>

    <div id="modalEditSiswa" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-surface-container-lowest w-full max-w-sm rounded-2xl shadow-xl overflow-hidden transform scale-95 transition-transform duration-300" id="modalEditContent">
            <div class="p-4 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low">
                <h3 class="font-bold text-primary flex items-center gap-2"><span class="material-symbols-outlined">edit_square</span> Edit Siswa</h3>
                <button type="button" onclick="tutupModalEdit()" class="text-on-surface-variant hover:text-error transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="proses_bulk_siswa.php" method="POST" class="p-5 flex flex-col gap-4">
                <input type="hidden" name="aksi" value="edit_single">
                <input type="hidden" id="edit_id" name="id_siswa" value="">
                
                <div>
                    <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Nama Lengkap</label>
                    <input type="text" id="edit_nama" name="nama_siswa" class="w-full bg-surface-container-highest rounded-lg px-4 py-3 text-sm border-0 focus:ring-2 focus:ring-primary" required>
                </div>
                
                <div>
                    <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Penempatan Kelas</label>
                    <select id="edit_kelas" name="class_id" class="w-full bg-surface-container-highest rounded-lg px-4 py-3 text-sm border-0 focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="">-- Kosongkan (Tanpa Kelas) --</option>
                        <?php foreach($list_kelas as $lk): ?>
                            <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mt-2 flex justify-end gap-3 pt-4 border-t border-outline-variant/20">
                    <button type="button" onclick="tutupModalEdit()" class="px-5 py-2 rounded-lg font-bold text-sm text-on-surface-variant hover:bg-surface-container-highest transition-colors">Batal</button>
                    <button type="submit" class="bg-primary text-on-primary px-5 py-2 rounded-lg font-bold text-sm shadow hover:bg-primary-container transition-colors">Simpan</button>
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

    function filterData() {
        const querySearch = searchInput.value.toLowerCase();
        const queryKelas = filterKelas.value.toLowerCase();

        studentRows.forEach(row => {
            const namaSiswa = row.getAttribute('data-nama');
            const kelasSiswa = row.getAttribute('data-kelas');

            const matchesSearch = namaSiswa.includes(querySearch);
            const matchesKelas = queryKelas === "" || kelasSiswa === queryKelas;

            if (matchesSearch && matchesKelas) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterData);
    filterKelas.addEventListener('change', filterData);


    // 2. TAMBAH BARIS MANUAL (Tanpa NIS)
    function tambahBarisInput() {
        const container = document.getElementById('containerInputSiswa');
        const row = document.createElement('tr');
        row.className = "bg-primary/5 border-t-2 border-primary/20";
        row.innerHTML = `
            <td class="px-2 md:px-4 py-2 md:py-3 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-error-variant hover:text-error">
                    <span class="material-symbols-outlined text-[16px] md:text-[18px]">remove_circle</span>
                </button>
            </td>
            <td class="px-2 md:px-4 py-2">
                <input type="text" name="nama_siswa[]" required class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 text-xs md:text-sm font-bold placeholder-primary/50" placeholder="Nama Siswa Baru...">
                <input type="hidden" name="nis_siswa[]" value="AUTO"> 
            </td>
            <td class="px-2 md:px-4 py-2">
                <select name="class_id_siswa[]" class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 text-[10px] md:text-xs font-bold" required>
                    <option value="" disabled selected>Pilih Kelas</option>
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
        `;
        container.appendChild(row);
        
        // Auto-scroll ke bawah saat tambah baris
        const tableContainer = document.querySelector('.overflow-auto');
        tableContainer.scrollTop = tableContainer.scrollHeight;
    }

    // 3. LOGIKA FLOATING ACTION BAR 
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.cb-siswa');
    const actionBar = document.getElementById('bulkActionBar');
    const selectedCountLabel = document.getElementById('selectedCount');

    function updateActionBar() {
        // Hitung checkbox yang dicentang AND yang tidak di-hide oleh filter
        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.checked && cb.closest('tr').style.display !== 'none') {
                count++;
            }
        });

        selectedCountLabel.innerText = count;

        if (count > 0) {
            // Tampilkan Bar: hapus class hide (translate-y-32, opacity-0)
            actionBar.classList.remove('translate-y-32', 'opacity-0');
        } else {
            // Sembunyikan Bar
            actionBar.classList.add('translate-y-32', 'opacity-0');
        }
    }

    if(checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if(cb.closest('tr').style.display !== 'none') {
                    cb.checked = this.checked;
                }
            });
            updateActionBar();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateActionBar));

    // --- Lojik MODAL EDIT ---
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
        }, 300);
    }
</script>

<?php require_once '../components/footer.php'; ?>