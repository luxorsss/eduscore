<?php
session_start();
require_once '../config/koneksi.php';

// Penjaga Pintu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Data Siswa + Nama Kelasnya (Mendeteksi yang Tanpa Kelas dengan LEFT JOIN)
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

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    
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
            
            <select id="filterKelas" class="w-full sm:w-auto bg-surface-container-lowest text-on-surface text-sm rounded-lg border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary px-3 py-2 font-medium shadow-sm">
                <option value="">Semua Kelas</option>
                <option value="Tanpa Kelas">⚠️ Tanpa Kelas</option>
                <?php foreach($list_kelas as $lk): ?>
                    <option value="<?= htmlspecialchars($lk['nama_kelas']) ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <form action="proses_bulk_siswa.php" method="POST">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden relative">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[500px]">
                    <thead class="bg-surface-container-low text-xs uppercase font-semibold text-on-surface-variant border-b border-outline-variant/20">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">
                                <input type="checkbox" id="checkAll" class="rounded text-primary focus:ring-primary border-outline-variant/50 bg-surface w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-4 py-3">Profil Siswa</th>
                            <th class="px-4 py-3 w-40">Kelas</th>
                            <th class="px-4 py-3 w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    
                    <tbody id="tabelDataSiswa" class="text-sm divide-y divide-outline-variant/10">
                        <?php foreach ($daftar_siswa as $s): ?>
                        <?php 
                            $nama_kelas_label = !empty($s['nama_kelas']) ? htmlspecialchars($s['nama_kelas']) : "Tanpa Kelas"; 
                        ?>
                        <tr class="student-row hover:bg-surface-container-low/50 group transition-colors" data-nama="<?= strtolower(htmlspecialchars($s['nama'])) ?>" data-kelas="<?= strtolower($nama_kelas_label) ?>">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" name="id_hapus[]" value="<?= $s['id'] ?>" class="cb-siswa rounded text-primary border-outline-variant/50 w-4 h-4">
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                        <?= strtoupper(substr($s['nama'], 0, 2)); ?>
                                    </div>
                                    <span class="font-bold text-base"><?= htmlspecialchars($s['nama']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <?php if (!empty($s['nama_kelas'])): ?>
                                    <span class="bg-surface-container-highest px-2.5 py-1 rounded-md text-xs font-bold text-on-surface border border-outline-variant/30">
                                        <?= htmlspecialchars($s['nama_kelas']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-error-container text-error px-2 py-1 rounded text-xs font-bold flex items-center gap-1 w-fit">
                                        <span class="material-symbols-outlined text-[14px]">warning</span> Tanpa Kelas
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <a href="proses_bulk_siswa.php?hapus_single=<?= $s['id'] ?>" onclick="return confirm('Hapus siswa ini?')" class="text-error opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <tbody id="containerInputSiswa" class="text-sm divide-y divide-outline-variant/10">
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

        <div id="bulkActionBar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-surface-container-lowest border border-outline-variant/30 shadow-lg rounded-2xl px-5 py-3 flex items-center gap-5 transition-all duration-300 translate-y-24 opacity-0 z-50">
            <span class="text-sm font-bold text-on-surface"><span id="selectedCount">0</span> Siswa Terpilih</span>
            <button type="submit" name="aksi" value="hapus_massal" onclick="return confirm('Yakin ingin menghapus?')" class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-error hover:bg-error-container rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span> Hapus Massal
            </button>
        </div>
    </form>

    <div class="mt-8 bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden">
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
            <textarea name="data_copas" rows="5" class="w-full bg-surface-container-highest border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl p-4 text-sm font-mono text-on-surface" 
            placeholder="Contoh:&#10;Budi-Santoso, 10 IPA 1&#10;Siti Nurhaliza-Putri, 10 IPA 1&#10;Andi Wijaya, 10 IPA 2" required></textarea>
            
            <button type="submit" name="aksi" value="copas_massal" class="bg-tertiary text-on-primary px-6 py-3 rounded-xl font-bold shadow-md hover:bg-tertiary/90 w-fit flex items-center gap-2 transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-[18px]">fact_check</span>
                Simpan Data Massal
            </button>
        </form>
    </div>
</main>

<script>
    // --- 1. FITUR LIVE SEARCH & FILTER KELAS ---
    const searchInput = document.getElementById('searchInput');
    const filterKelas = document.getElementById('filterKelas');
    const studentRows = document.querySelectorAll('.student-row');

    function filterData() {
        const querySearch = searchInput.value.toLowerCase();
        const queryKelas = filterKelas.value.toLowerCase();

        studentRows.forEach(row => {
            const namaSiswa = row.getAttribute('data-nama');
            const kelasSiswa = row.getAttribute('data-kelas');

            // Cek apakah nama cocok DENGAN pencarian DAN kelas cocok dengan dropdown
            const matchesSearch = namaSiswa.includes(querySearch);
            const matchesKelas = queryKelas === "" || kelasSiswa === queryKelas;

            if (matchesSearch && matchesKelas) {
                row.style.display = ''; // Munculkan baris
            } else {
                row.style.display = 'none'; // Sembunyikan baris
            }
        });
    }

    // Jalankan filter tiap kali ngetik atau milih dropdown
    searchInput.addEventListener('input', filterData);
    filterKelas.addEventListener('change', filterData);


    // --- 2. FITUR TAMBAH BARIS INPUT MASSAL ---
    function tambahBarisInput() {
        const container = document.getElementById('containerInputSiswa');
        const row = document.createElement('tr');
        row.className = "bg-primary/5 border-t-2 border-primary/20";
        // Perhatikan: Kolom NIS sudah dicabut dari HTML baris baru ini
        row.innerHTML = `
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-error-variant hover:text-error" title="Batal Tambah">
                    <span class="material-symbols-outlined text-[18px]">remove_circle</span>
                </button>
            </td>
            <td class="px-4 py-3">
                <input type="text" name="nama_siswa[]" required class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 text-sm font-bold placeholder-primary/50" placeholder="Ketik Nama Siswa Baru...">
                <input type="hidden" name="nis_siswa[]" value="AUTO"> </td>
            <td class="px-4 py-3">
                <select name="class_id_siswa[]" class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 text-xs font-bold" required>
                    <option value="" disabled selected>Pilih Kelas</option>
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= htmlspecialchars($lk['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
        `;
        container.appendChild(row);
    }

    // --- 3. FITUR FLOATING ACTION BAR ---
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.cb-siswa');
    const actionBar = document.getElementById('bulkActionBar');
    const selectedCountLabel = document.getElementById('selectedCount');

    function updateActionBar() {
        const selectedCount = document.querySelectorAll('.cb-siswa:checked').length;
        if (selectedCount > 0) {
            selectedCountLabel.innerText = selectedCount;
            actionBar.classList.remove('translate-y-24', 'opacity-0');
        } else {
            actionBar.classList.add('translate-y-24', 'opacity-0');
        }
    }

    if(checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                // Hanya centang checkbox yang sedang terlihat (tidak di-filter/sembunyi)
                if(cb.closest('tr').style.display !== 'none') {
                    cb.checked = this.checked;
                }
            });
            updateActionBar();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateActionBar));
</script>

<?php require_once '../components/footer.php'; ?>