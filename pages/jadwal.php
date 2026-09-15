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

// 3. Filter Kelas (via GET)
$filter_kelas = isset($_GET['filter_kelas']) && $_GET['filter_kelas'] !== '' ? (int)$_GET['filter_kelas'] : null;

// 4. Ambil Jadwal Aktif Milik Guru Ini (dengan filter kelas jika dipilih)
$sql_jadwal = "
    SELECT ts.id as jadwal_id, ts.class_id, c.nama_kelas, c.jenjang, s.nama_mapel 
    FROM teaching_schedules ts
    JOIN classes c ON ts.class_id = c.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.user_id = ?
";
$params = [$user_id];

if ($filter_kelas) {
    $sql_jadwal .= " AND ts.class_id = ?";
    $params[] = $filter_kelas;
}

$sql_jadwal .= " ORDER BY c.jenjang, c.nama_kelas, s.nama_mapel ASC";

$stmt_jadwal = $pdo->prepare($sql_jadwal);
$stmt_jadwal->execute($params);
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
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">
                <?= isset($_SESSION['nama_lengkap']) ? strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)) : 'BS' ?>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Pengaturan Mata Pelajaran</h1>
        <p class="text-on-surface-variant text-sm">Tentukan di kelas mana saja Anda mengajar dan mata pelajaran apa yang Anda ampu.</p>
    </div>

    <!-- Alert Notifikasi Feedback -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'sukses_hapus'): ?>
            <div class="mb-6 flex items-center gap-3 p-4 text-sm rounded-xl border border-emerald-500/20 bg-emerald-50 text-emerald-900">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <span>Jadwal yang dipilih berhasil dihapus.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sebagian_gagal'): ?>
            <div class="mb-6 flex items-center gap-3 p-4 text-sm rounded-xl border border-amber-500/20 bg-amber-50 text-amber-900">
                <span class="material-symbols-outlined text-amber-600">warning</span>
                <span>Beberapa jadwal tidak dapat dihapus karena sudah memiliki data nilai siswa terkait.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'gagal_nilai'): ?>
            <div class="mb-6 flex items-center gap-3 p-4 text-sm rounded-xl border border-amber-500/20 bg-amber-50 text-amber-900">
                <span class="material-symbols-outlined text-amber-600">warning</span>
                <span>Jadwal tidak dapat dihapus karena sudah memiliki data nilai siswa terkait.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'kosong'): ?>
            <div class="mb-6 flex items-center gap-3 p-4 text-sm rounded-xl border border-slate-500/20 bg-slate-50 text-slate-900">
                <span class="material-symbols-outlined text-slate-600">info</span>
                <span>Tidak ada jadwal yang dipilih untuk dihapus.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Tambah Jadwal -->
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

        <!-- Daftar Jadwal + Filter + Bulk Delete -->
        <div class="lg:col-span-2">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden flex flex-col h-full">
                
                <!-- Filter Toolbar -->
                <div class="p-5 border-b border-outline-variant/20 bg-surface-container-low flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-on-surface">Jadwal Mengajar Saya</h2>
                        <span class="text-xs text-on-surface-variant"><?= count($jadwal_aktif) ?> jadwal ditampilkan</span>
                    </div>

                    <form method="GET" action="jadwal.php" class="flex items-center gap-2">
                        <select name="filter_kelas" onchange="this.form.submit()" class="text-xs font-medium rounded-lg border border-outline-variant/40 bg-surface-container-lowest text-on-surface px-3 py-2 focus:ring-1 focus:ring-primary">
                            <option value="">Semua Kelas</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($filter_kelas == $k['id']) ? 'selected' : '' ?>>
                                    <?= $k['jenjang'] ?> - <?= $k['nama_kelas'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filter_kelas): ?>
                            <a href="jadwal.php" class="text-xs text-primary hover:underline font-medium">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Form Bulk Delete -->
                <form action="proses_jadwal.php" method="POST" id="formBulkDelete">
                    <input type="hidden" name="aksi" value="bulk_delete">
                    <input type="hidden" name="redirect_filter" value="<?= htmlspecialchars((string)$filter_kelas) ?>">

                    <?php if(!empty($jadwal_aktif)): ?>
                        <div class="px-5 py-3 bg-surface-container-low/50 border-b border-outline-variant/10 flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-on-surface-variant cursor-pointer select-none">
                                <input type="checkbox" id="checkAll" class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                <span>Pilih Semua</span>
                            </label>
                            <button type="submit" id="btnBulkDelete" onclick="return confirm('Hapus semua jadwal yang dicentang?')" class="hidden items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                <span id="countSelected">Hapus Terpilih</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="p-5 flex flex-col gap-3">
                        <?php if(empty($jadwal_aktif)): ?>
                            <div class="flex flex-col items-center justify-center py-10 text-center opacity-60">
                                <span class="material-symbols-outlined text-5xl mb-3">calendar_add_on</span>
                                <p class="font-medium">Belum ada jadwal mengajar<?= $filter_kelas ? ' untuk kelas ini' : '' ?>.<br>Silakan tambah kelas dan mapel melalui form di samping.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($jadwal_aktif as $jadwal): ?>
                                <div class="flex items-center justify-between p-4 rounded-xl border border-outline-variant/30 hover:border-primary/50 hover:shadow-sm transition-all bg-surface">
                                    <div class="flex items-center gap-4">
                                        <input type="checkbox" name="jadwal_ids[]" value="<?= $jadwal['jadwal_id'] ?>" class="item-checkbox rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                        
                                        <div class="w-11 h-11 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined">book</span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-on-surface text-base md:text-lg"><?= htmlspecialchars($jadwal['nama_mapel']) ?></h3>
                                            <p class="text-sm font-medium text-on-surface-variant flex items-center gap-1 mt-0.5">
                                                <span class="material-symbols-outlined text-[16px]">meeting_room</span> 
                                                Kelas <?= htmlspecialchars($jadwal['nama_kelas']) ?> (<?= $jadwal['jenjang'] ?>)
                                            </p>
                                        </div>
                                    </div>
                                    <a href="proses_jadwal.php?hapus=<?= $jadwal['jadwal_id'] ?>&redirect_filter=<?= urlencode((string)$filter_kelas) ?>" 
                                       onclick="return confirm('Hapus jadwal mata pelajaran <?= htmlspecialchars($jadwal['nama_mapel']) ?> di kelas <?= htmlspecialchars($jadwal['nama_kelas']) ?>?')" 
                                       class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" 
                                       title="Hapus Jadwal">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </form>

            </div>
        </div>
        
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const countSelected = document.getElementById('countSelected');

    function updateBulkButton() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        if (checkedCount > 0) {
            btnBulkDelete.classList.remove('hidden');
            btnBulkDelete.classList.add('inline-flex');
            countSelected.textContent = `Hapus (${checkedCount}) Terpilih`;
        } else {
            btnBulkDelete.classList.add('hidden');
            btnBulkDelete.classList.remove('inline-flex');
        }

        if (checkAll && itemCheckboxes.length > 0) {
            checkAll.checked = checkedCount === itemCheckboxes.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = checkAll.checked);
            updateBulkButton();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkButton);
    });
});
</script>

<?php require_once '../components/footer.php'; ?>