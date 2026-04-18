<?php
session_start();
require_once '../config/koneksi.php';

// 1. Proteksi Sesi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_GET['kelas'] ?? null;
$mapel_id = $_GET['mapel'] ?? null;
$kategori = $_GET['kategori'] ?? null;

// 2. Penanganan Error Elegan (Bukan Redirect Lempar)
if (!$class_id || !$mapel_id || !$kategori) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#333;'>
            <h2>Akses Dihentikan 🛑</h2>
            <p>Data Kelas, Mapel, atau Kategori belum dipilih dengan benar dari Dashboard.</p>
            <a href='dashboard.php' style='color:blue; text-decoration:none; font-weight:bold;'>&larr; Kembali ke Dashboard</a>
         </div>");
}

// 3. KEAMANAN TINGKAT TINGGI (White-listing)
// Ini untuk mencegah WAF/ModSecurity server memberikan error 403 Forbidden
$allowed_categories = [
    'h_uts' => 'Harian UTS', 
    'uts' => 'UTS', 
    'h_uas' => 'Harian UAS', 
    'uas' => 'UAS', 
    'tambahan' => 'Tambahan'
];

if (!array_key_exists($kategori, $allowed_categories)) {
    die("Kategori nilai tidak dikenali oleh sistem keamanan.");
}

$label_kategori = $allowed_categories[$kategori];

// 4. Ambil Info Kelas & Mapel
$stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
$stmt_info->execute([$class_id, $mapel_id]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

// 5. Cari ID Jadwal (schedule_id) yang Tepat
$stmt_sched = $pdo->prepare("SELECT id FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
$stmt_sched->execute([$user_id, $class_id, $mapel_id]);
$schedule = $stmt_sched->fetch(PDO::FETCH_ASSOC);
$schedule_id = $schedule['id'] ?? 0;

// 6. Ambil Daftar Siswa BESERTA Nilainya saat ini (Query ini sekarang 100% aman dari SQL Injection)
$stmt_siswa = $pdo->prepare("
    SELECT st.id, st.nis, st.nama, g.$kategori as nilai_sekarang 
    FROM students st 
    LEFT JOIN grades g ON g.student_id = st.id AND g.schedule_id = ? 
    WHERE st.class_id = ? 
    ORDER BY st.nama ASC
");
$stmt_siswa->execute([$schedule_id, $class_id]);
$students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Input Nilai";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-on-surface-variant"><span class="material-symbols-outlined">menu</span></button>
            <div class="flex flex-col">
                <span class="font-bold text-sm leading-tight"><?= htmlspecialchars($info['nama_kelas'] ?? 'Kelas Tidak Ditemukan') ?> - <?= htmlspecialchars($info['nama_mapel'] ?? '') ?></span>
                <span class="text-[10px] text-primary font-bold uppercase">Input Nilai: <?= htmlspecialchars($label_kategori) ?></span>
            </div>
        </div>
        <button form="formNilai" class="bg-primary text-on-primary px-6 py-2 rounded-lg text-sm font-bold shadow-sm hover:scale-105 transition-transform">Simpan Data</button>
    </div>
</nav>

<div class="bg-surface/90 backdrop-blur-md sticky top-16 z-40 border-b border-outline-variant/20 shadow-sm">
    <div class="max-w-4xl mx-auto p-4 flex gap-2">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" id="cariSiswa" class="w-full bg-surface-container-lowest border-outline-variant/50 focus:ring-primary rounded-xl pl-10 py-3 text-sm font-medium" placeholder="Cari nama siswa...">
        </div>
    </div>
</div>

<main class="max-w-4xl mx-auto w-full p-4 flex flex-col gap-3">
    
    <div class="bg-primary/5 border border-primary/20 rounded-xl p-3 flex flex-col gap-2 mb-2">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-primary uppercase">Fitur Paste Massal</span>
            <span class="text-[10px] text-on-surface-variant">Klik kotak di bawah lalu tekan <b>Ctrl + V</b></span>
        </div>
        <textarea id="pasteBox" rows="1" class="w-full bg-surface-container-lowest border-dashed border-2 border-primary/30 rounded-lg text-xs p-2 focus:ring-primary font-mono text-center" placeholder="Tempel kolom nilai dari Excel di sini..."></textarea>
    </div>

    <form id="formNilai" action="proses_simpan_nilai.php" method="POST">
        <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
        <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
        
        <div class="flex flex-col gap-3" id="daftarSiswa">
            <?php if(empty($students)): ?>
                <div class="text-center py-10 text-on-surface-variant font-medium">Belum ada siswa di kelas ini.</div>
            <?php else: ?>
                <?php foreach ($students as $s): ?>
                <div class="student-card bg-surface-container-lowest rounded-xl p-4 shadow-sm border border-outline-variant/10 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-full bg-primary/5 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                            <?= strtoupper(substr($s['nama'], 0, 2)) ?>
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="student-name font-bold text-on-surface text-sm truncate"><?= htmlspecialchars($s['nama']) ?></h3>
                        </div>
                    </div>
                    <div class="w-[80px] shrink-0">
                        <input type="number" name="nilai[<?= $s['id'] ?>]" value="<?= $s['nilai_sekarang'] !== null ? $s['nilai_sekarang'] : '' ?>" class="nilai-input w-full bg-surface-container-highest border-0 border-b-2 border-transparent focus:border-primary rounded-t-md py-3 text-xl text-center font-black text-primary transition-colors" placeholder="-" min="0" max="100">
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>
</main>

<script>
    // Fitur Search
    const searchInput = document.getElementById('cariSiswa');
    const cards = document.querySelectorAll('.student-card');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            cards.forEach(card => {
                const name = card.querySelector('.student-name').innerText.toLowerCase();
                card.style.display = name.includes(q) ? 'flex' : 'none';
            });
        });
    }

    // Fitur Smart Paste
    const pasteBox = document.getElementById('pasteBox');
    const nilaiInputs = document.querySelectorAll('.nilai-input');

    if(pasteBox) {
        pasteBox.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const rows = text.split(/\r?\n/).filter(row => row.trim() !== "");
            
            let counter = 0;
            rows.forEach((value, index) => {
                if (nilaiInputs[index]) {
                    const cleanedValue = value.replace(/[^0-9]/g, '');
                    if(cleanedValue !== "") {
                        nilaiInputs[index].value = cleanedValue;
                        nilaiInputs[index].classList.add('bg-tertiary-container', 'text-tertiary');
                        setTimeout(() => nilaiInputs[index].classList.remove('bg-tertiary-container', 'text-tertiary'), 1500);
                        counter++;
                    }
                }
            });
            pasteBox.value = '';
            if(counter > 0) alert(counter + " baris nilai berhasil di-paste massal!");
        });
    }
</script>

<?php require_once '../components/footer.php'; ?>