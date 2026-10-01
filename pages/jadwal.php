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

$page_title = "Jadwal Mengajar - EduScore";
$page_heading = "Jadwal Mengajar Pengajar";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Pemetaan Penugasan Mengajar</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Jadwal Mengajar Pengajar</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Hubungkan mata pelajaran dengan rombongan belajar kelas yang Anda bimbing.
                </p>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center hidden sm:block">
            <span class="text-[11px] font-medium text-text-muted block">Total Penugasan</span>
            <span class="text-base font-bold text-text-main tabular-nums"><?= count($jadwal_aktif) ?></span>
        </div>
    </div>

    <!-- Alert Notifikasi Feedback -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'sukses_hapus'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Jadwal penugasan yang dipilih berhasil dihapus.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sebagian_gagal' || $_GET['pesan'] == 'gagal_nilai'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">warning</span>
                <span>Beberapa atau seluruh jadwal tidak dapat dihapus karena sudah memiliki data nilai siswa terkait.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_tambah'): ?>
            <?php 
                $added = (int)($_GET['added'] ?? 0);
                $skipped = (int)($_GET['skipped'] ?? 0);
            ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>
                    <strong><?= $added ?></strong> jadwal penugasan berhasil disimpan!
                    <?php if ($skipped > 0): ?>
                        (<?= $skipped ?> jadwal dilewati karena sudah terdaftar sebelumnya).
                    <?php endif; ?>
                </span>
            </div>
        <?php elseif ($_GET['pesan'] == 'kosong'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-slate-200 bg-slate-50 text-text-muted">
                <span class="material-symbols-outlined text-base">info</span>
                <span>Tidak ada jadwal yang dipilih untuk dihapus.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Form Tambah Jadwal (Bulk Support) -->
        <div class="lg:col-span-4 sticky top-20">
            <div class="bg-surface-card rounded-xl border border-border-main shadow-xs p-6">
                <h3 class="font-bold text-sm text-text-main mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base">add_circle</span>
                    Tambah Jadwal Penugasan
                </h3>

                <!-- Tab Segmented Control -->
                <div class="grid grid-cols-2 rounded-lg bg-slate-100 p-1 mb-4 text-xs font-semibold">
                    <button type="button" id="tabBtnKelas" onclick="switchTab('kelas')" class="py-1.5 rounded-md bg-white text-primary shadow-xs transition-colors text-center">
                        Per Kelas
                    </button>
                    <button type="button" id="tabBtnMapel" onclick="switchTab('mapel')" class="py-1.5 rounded-md text-text-muted hover:text-text-main transition-colors text-center">
                        Per Mapel
                    </button>
                </div>
                
                <!-- TAB 1: 1 KELAS -> BANYAK MAPEL -->
                <form id="formPerKelas" action="proses_jadwal.php" method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="hidden" name="mode" value="kelas_to_mapel">
                    
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Kelas</label>
                        <select name="class_id" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[42px]" required>
                            <option value="" disabled selected>-- Pilih Kelas --</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-text-main">Pilih Mata Pelajaran</label>
                            <button type="button" onclick="toggleCheckAllGroup('cb-mapel')" class="text-[11px] text-primary font-semibold hover:underline">Pilih Semua</button>
                        </div>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/50 divide-y divide-slate-100 p-2 custom-scroll">
                            <?php foreach($semua_mapel as $m): ?>
                                <label class="flex items-center gap-2.5 p-1.5 hover:bg-white rounded cursor-pointer text-xs font-medium text-text-main select-none transition-colors">
                                    <input type="checkbox" name="subject_ids[]" value="<?= $m['id'] ?>" class="cb-mapel rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                    <span><?= htmlspecialchars($m['nama_mapel']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors mt-1 min-h-[42px]">
                        Simpan Penugasan Mapel
                    </button>
                </form>

                <!-- TAB 2: 1 MAPEL -> BANYAK KELAS -->
                <form id="formPerMapel" action="proses_jadwal.php" method="POST" class="flex flex-col gap-4 hidden">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="hidden" name="mode" value="mapel_to_kelas">
                    
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Mata Pelajaran</label>
                        <select name="subject_id" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[42px]" required>
                            <option value="" disabled selected>-- Pilih Mapel --</option>
                            <?php foreach($semua_mapel as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-text-main">Pilih Kelas</label>
                            <button type="button" onclick="toggleCheckAllGroup('cb-kelas')" class="text-[11px] text-primary font-semibold hover:underline">Pilih Semua</button>
                        </div>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/50 divide-y divide-slate-100 p-2 custom-scroll">
                            <?php foreach($semua_kelas as $k): ?>
                                <label class="flex items-center gap-2.5 p-1.5 hover:bg-white rounded cursor-pointer text-xs font-medium text-text-main select-none transition-colors">
                                    <input type="checkbox" name="class_ids[]" value="<?= $k['id'] ?>" class="cb-kelas rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                    <span><?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors mt-1 min-h-[42px]">
                        Simpan Penugasan Kelas
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Jadwal + Filter + Bulk Delete -->
        <div class="lg:col-span-8">
            <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
                
                <!-- Filter Toolbar -->
                <div class="p-5 border-b border-border-main bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-xs md:text-sm text-text-main">Daftar Jadwal Mengajar Anda</h3>
                        <span class="text-xs text-text-muted tabular-nums"><?= count($jadwal_aktif) ?> jadwal terdaftar</span>
                    </div>

                    <form method="GET" action="jadwal.php" class="flex items-center gap-2">
                        <select name="filter_kelas" onchange="this.form.submit()" class="text-xs font-medium rounded-lg border border-slate-300 bg-white text-text-main px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[38px]">
                            <option value="">Semua Kelas</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($filter_kelas == $k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filter_kelas): ?>
                            <a href="jadwal.php" class="text-xs text-primary hover:underline font-semibold">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Form Bulk Delete -->
                <form action="proses_jadwal.php" method="POST" id="formBulkDelete">
                    <input type="hidden" name="aksi" value="bulk_delete">
                    <input type="hidden" name="redirect_filter" value="<?= htmlspecialchars((string)$filter_kelas) ?>">

                    <?php if(!empty($jadwal_aktif)): ?>
                        <div class="px-5 py-3 bg-slate-50/60 border-b border-border-main flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-text-muted cursor-pointer select-none">
                                <input type="checkbox" id="checkAll" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                <span>Pilih Semua</span>
                            </label>
                            <button type="submit" id="btnBulkDelete" onclick="konfirmasiForm(event, 'Hapus seluruh jadwal penugasan yang dicentang? Pastikan tidak ada data nilai yang terikat.')" class="hidden items-center gap-1.5 text-xs font-semibold text-danger hover:bg-danger-subtle px-3 py-1.5 rounded-lg border border-danger/20 transition-colors">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                <span id="countSelected">Hapus Terpilih</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="p-5 flex flex-col gap-3">
                        <?php if(empty($jadwal_aktif)): ?>
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">calendar_add_on</span>
                                <p class="text-sm font-semibold text-text-main">Belum Ada Jadwal Mengajar<?= $filter_kelas ? ' untuk Kelas Ini' : '' ?></p>
                                <p class="text-xs text-text-muted mt-1 max-w-sm">Gunakan formulir di samping untuk menambahkan penugasan mata pelajaran ke kelas yang Anda ampu.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($jadwal_aktif as $jadwal): ?>
                                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 transition-colors bg-white">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <input type="checkbox" name="jadwal_ids[]" value="<?= $jadwal['jadwal_id'] ?>" class="item-checkbox rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer shrink-0">
                                        
                                        <div class="w-9 h-9 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">book</span>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-xs md:text-sm text-text-main truncate"><?= htmlspecialchars($jadwal['nama_mapel']) ?></h4>
                                            <p class="text-[11px] text-text-muted flex items-center gap-1 mt-0.5">
                                                <span class="badge-grade-neutral px-2 py-0.5 rounded font-semibold text-[10px]">
                                                    <?= htmlspecialchars($jadwal['nama_kelas']) ?> (<?= htmlspecialchars($jadwal['jenjang']) ?>)
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="input_data.php" method="POST">
                                            <input type="hidden" name="kelas" value="<?= $jadwal['class_id'] ?>">
                                            <input type="hidden" name="mapel" value="<?= $jadwal['subject_id'] ?? '' ?>">
                                            <a href="analisa.php?kelas=<?= $jadwal['class_id'] ?>&mapel=<?= $jadwal['subject_id'] ?? '' ?>" class="p-1.5 rounded-lg text-text-muted hover:text-primary hover:bg-slate-100 transition-colors hidden sm:inline-flex" title="Lihat Analisa">
                                                <span class="material-symbols-outlined text-lg">analytics</span>
                                            </a>
                                        </form>
                                        <a href="proses_jadwal.php?hapus=<?= $jadwal['jadwal_id'] ?>&redirect_filter=<?= urlencode((string)$filter_kelas) ?>" 
                                           onclick="konfirmasiLink(event, this.href, 'Hapus jadwal mata pelajaran <?= htmlspecialchars($jadwal['nama_mapel']) ?> di kelas <?= htmlspecialchars($jadwal['nama_kelas']) ?>?')" 
                                           class="p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors" 
                                           title="Hapus Jadwal">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </a>
                                    </div>
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

// Switch Tab Mode Bulk Add
function switchTab(mode) {
    const formKelas = document.getElementById('formPerKelas');
    const formMapel = document.getElementById('formPerMapel');
    const btnKelas = document.getElementById('tabBtnKelas');
    const btnMapel = document.getElementById('tabBtnMapel');

    if (mode === 'kelas') {
        formKelas.classList.remove('hidden');
        formMapel.classList.add('hidden');
        btnKelas.className = "py-1.5 rounded-md bg-white text-primary shadow-xs transition-colors text-center";
        btnMapel.className = "py-1.5 rounded-md text-text-muted hover:text-text-main transition-colors text-center";
    } else {
        formMapel.classList.remove('hidden');
        formKelas.classList.add('hidden');
        btnMapel.className = "py-1.5 rounded-md bg-white text-primary shadow-xs transition-colors text-center";
        btnKelas.className = "py-1.5 rounded-md text-text-muted hover:text-text-main transition-colors text-center";
    }
}

// Toggle Select All Checkbox Group
function toggleCheckAllGroup(className) {
    const cbs = document.querySelectorAll('.' + className);
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
}
</script>

<?php require_once '../components/footer.php'; ?>