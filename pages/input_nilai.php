<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Parameter dari Dashboard
$class_id = $_GET['kelas'] ?? null;
$mapel_id = $_GET['mapel'] ?? null;

if (!$class_id || !$mapel_id) {
    header("Location: dashboard.php");
    exit();
}

// Ambil Info Kelas & Mapel
$stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
$stmt_info->execute([$class_id, $mapel_id]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

// Ambil Daftar Siswa di Kelas Ini
$stmt_siswa = $pdo->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY nama ASC");
$stmt_siswa->execute([$class_id]);
$students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Input Nilai UAS";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-on-surface-variant"><span class="material-symbols-outlined">menu</span></button>
            <div class="flex flex-col">
                <span class="font-bold text-sm leading-tight"><?= $info['nama_kelas'] ?> - <?= $info['nama_mapel'] ?></span>
                <span class="text-[10px] text-primary font-bold uppercase">Input Nilai UAS</span>
            </div>
        </div>
        <button form="formNilai" class="bg-primary text-on-primary px-4 py-2 rounded-lg text-sm font-bold shadow-sm">Simpan</button>
    </div>
</nav>

<div class="bg-surface/90 backdrop-blur-md sticky top-16 z-40 border-b border-outline-variant/20 shadow-sm">
    <div class="max-w-4xl mx-auto p-4">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" id="cariSiswa" class="w-full bg-surface-container-lowest border-outline-variant/50 focus:ring-primary rounded-xl pl-10 py-3 text-sm font-medium" placeholder="Cari nama siswa di kertas ujian...">
        </div>
    </div>
</div>

<main class="max-w-4xl mx-auto w-full p-4 flex flex-col gap-3">
    <form id="formNilai" action="proses_simpan_nilai.php" method="POST">
        <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">
        
        <div class="flex flex-col gap-3" id="daftarSiswa">
            <?php foreach ($students as $s): ?>
            <div class="student-card bg-surface-container-lowest rounded-xl p-4 shadow-sm border border-outline-variant/10 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-full bg-primary/5 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                        <?= strtoupper(substr($s['nama'], 0, 2)) ?>
                    </div>
                    <div class="overflow-hidden">
                        <h3 class="student-name font-bold text-on-surface text-sm truncate"><?= htmlspecialchars($s['nama']) ?></h3>
                        <p class="text-[10px] text-on-surface-variant font-medium uppercase">NIS: <?= $s['nis'] ?></p>
                    </div>
                </div>
                <div class="w-[80px] shrink-0">
                    <input type="number" name="nilai_uas[<?= $s['id'] ?>]" class="nilai-input w-full bg-surface-container-highest border-0 border-b-2 border-transparent focus:border-primary rounded-t-md py-3 text-xl text-center font-black text-primary" placeholder="0" min="0" max="100">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </form>
</main>

<script>
    const searchInput = document.getElementById('cariSiswa');
    const cards = document.querySelectorAll('.student-card');

    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        cards.forEach(card => {
            const name = card.querySelector('.student-name').innerText.toLowerCase();
            card.style.display = name.includes(q) ? 'flex' : 'none';
        });
    });

    // Trik Enter to Search (Reset pencarian setelah isi nilai)
    const inputs = document.querySelectorAll('.nilai-input');
    inputs.forEach(input => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    });
</script>

<?php require_once '../components/footer.php'; ?>