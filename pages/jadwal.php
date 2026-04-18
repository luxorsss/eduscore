<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Ambil Semua Data Kelas untuk Dropdown
$stmt_kelas = $pdo->query("SELECT * FROM classes ORDER BY jenjang, nama_kelas");
$semua_kelas = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil Semua Data Mapel untuk Dropdown
$stmt_mapel = $pdo->query("SELECT * FROM subjects ORDER BY nama_mapel");
$semua_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil Jadwal Aktif Milik Guru Ini
$stmt_jadwal = $pdo->prepare("
    SELECT ts.id as jadwal_id, c.nama_kelas, s.nama_mapel 
    FROM teaching_schedules ts
    JOIN classes c ON ts.class_id = c.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.user_id = ?
");
$stmt_jadwal->execute([$user_id]);
$jadwal_aktif = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Jadwal Mengajar";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary hidden md:flex">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">school</span>
            </div>
            <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
            <span class="text-on-surface-variant ml-2 text-sm font-medium hidden md:block">| Jadwal Mengajar</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">BS</div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Pengaturan Mata Pelajaran</h1>
        <p class="text-on-surface-variant text-sm">Tentukan di kelas mana saja Anda mengajar dan mata pelajaran apa yang Anda ampu.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm p-6 sticky top-24">
                <h2 class="font-bold text-lg text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">add_circle</span>
                    Tambah Kelas & Mapel
                </h2>
                
                <form action="proses_jadwal.php" method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="aksi" value="tambah">
                    
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Pilih Kelas</label>
                        <select name="class_id" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3 transition-colors cursor-pointer" required>
                            <option value="" disabled selected>-- Daftar Kelas --</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['jenjang'] ?> - <?= $k['nama_kelas'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Pilih Mata Pelajaran</label>
                        <select name="subject_id" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3 transition-colors cursor-pointer" required>
                            <option value="" disabled selected>-- Daftar Mata Pelajaran --</option>
                            <?php foreach($semua_mapel as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= $m['nama_mapel'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-primary text-on-primary py-3.5 rounded-lg text-sm font-bold shadow-sm hover:bg-primary-container transition-all mt-2">
                        Simpan Jadwal
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden flex flex-col h-full">
                <div class="p-5 border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
                    <h2 class="font-bold text-on-surface">Jadwal Mengajar Saya Saat Ini</h2>
                    <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded"><?= count($jadwal_aktif) ?> Jadwal Aktif</span>
                </div>

                <div class="p-5 flex flex-col gap-3">
                    
                    <?php if(empty($jadwal_aktif)): ?>
                        <div class="flex flex-col items-center justify-center py-10 text-center opacity-60">
                            <span class="material-symbols-outlined text-5xl mb-3">calendar_add_on</span>
                            <p class="font-medium">Belum ada jadwal mengajar.<br>Silakan tambah kelas dan mapel di samping.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($jadwal_aktif as $jadwal): ?>
                            <div class="flex items-center justify-between p-4 rounded-xl border border-outline-variant/30 hover:border-primary/50 hover:shadow-md transition-all group bg-surface">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                        <span class="material-symbols-outlined">book</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-on-surface text-lg"><?= htmlspecialchars($jadwal['nama_mapel']) ?></h3>
                                        <p class="text-sm font-medium text-on-surface-variant flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-[16px]">meeting_room</span> 
                                            Kelas <?= htmlspecialchars($jadwal['nama_kelas']) ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="proses_jadwal.php?hapus=<?= $jadwal['jadwal_id'] ?>" onclick="return confirm('Hapus jadwal ini?')" class="text-on-surface-variant hover:text-error hover:bg-error-container p-2 rounded-lg transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100" title="Hapus Jadwal">
                                    <span class="material-symbols-outlined">delete</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        
    </div>
</main>

<?php require_once '../components/footer.php'; ?>