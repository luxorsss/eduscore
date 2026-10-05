<?php
session_start();
require_once '../config/auth.php';

check_login();

$user_id = (int)$_SESSION['user_id'];
$is_admin = is_admin();

$stmt_kelas = $pdo->query("SELECT * FROM classes ORDER BY jenjang, nama_kelas");
$semua_kelas = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$stmt_mapel = $pdo->query("SELECT * FROM subjects ORDER BY nama_mapel");
$semua_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

$semua_guru = [];
if ($is_admin) {
    $stmt_guru = $pdo->query("SELECT id, nama_lengkap, username, role FROM users ORDER BY nama_lengkap ASC");
    $semua_guru = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);
}

$filter_kelas = isset($_GET['filter_kelas']) && $_GET['filter_kelas'] !== '' ? (int)$_GET['filter_kelas'] : null;
$filter_guru = ($is_admin && isset($_GET['filter_guru']) && $_GET['filter_guru'] !== '') ? $_GET['filter_guru'] : null;

$sql_jadwal = "
    SELECT ts.id as jadwal_id, ts.class_id, ts.subject_id, ts.user_id, ts.is_manual,
           u.nama_lengkap as nama_guru, u.username as username_guru, u.role as guru_role,
           c.nama_kelas, c.jenjang, s.nama_mapel 
    FROM teaching_schedules ts
    JOIN classes c ON ts.class_id = c.id
    JOIN subjects s ON ts.subject_id = s.id
    LEFT JOIN users u ON ts.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!$is_admin) {
    $sql_jadwal .= " AND ts.user_id = ?";
    $params[] = $user_id;
} else {
    if ($filter_guru === 'kosong') {
        $sql_jadwal .= " AND ts.user_id IS NULL";
    } elseif ($filter_guru) {
        $sql_jadwal .= " AND ts.user_id = ?";
        $params[] = (int)$filter_guru;
    }
}

if ($filter_kelas) {
    $sql_jadwal .= " AND ts.class_id = ?";
    $params[] = $filter_kelas;
}

$sql_jadwal .= " ORDER BY c.jenjang, c.nama_kelas, s.nama_mapel ASC";

$stmt_jadwal = $pdo->prepare($sql_jadwal);
$stmt_jadwal->execute($params);
$jadwal_aktif = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

// Pemetaan penugasan yang sudah diambil untuk disable pilihan bentrok
$stmt_taken = $pdo->query("
    SELECT ts.class_id, ts.subject_id, ts.user_id, u.nama_lengkap 
    FROM teaching_schedules ts 
    LEFT JOIN users u ON ts.user_id = u.id
");
$all_taken = $stmt_taken->fetchAll(PDO::FETCH_ASSOC);
$taken_map = [];
foreach ($all_taken as $t) {
    $taken_map[$t['class_id']][$t['subject_id']] = [
        'user_id' => $t['user_id'] !== null ? (int)$t['user_id'] : null,
        'nama' => $t['nama_lengkap'] ?? null
    ];
}

$page_title = "Jadwal Mengajar - EduScore";
$page_heading = $is_admin ? "Manajemen Jadwal Mengajar Sekolah" : "Jadwal Mengajar Pengajar";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-4 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5 sm:gap-4">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">calendar_month</span>
            </div>
            <div>
                <span class="text-[11px] sm:text-xs font-semibold text-text-muted uppercase tracking-wider block">
                    <?= $is_admin ? 'Master Jadwal Kurikulum' : 'Pemetaan Penugasan Mengajar' ?>
                </span>
                <h2 class="text-base sm:text-lg md:text-xl font-bold text-text-main mt-0.5">
                    <?= $is_admin ? 'Pengaturan Penugasan Guru & Jadwal' : 'Jadwal Mengajar Pengajar' ?>
                </h2>
                <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                    <?= $is_admin 
                        ? 'Pantau dan kelola pemetaan mata pelajaran di tiap kelas untuk seluruh dewan guru.' 
                        : 'Tentukan mata pelajaran dan kelas yang Anda ampu (1 mapel di 1 kelas diampu oleh 1 pengajar).' ?>
                </p>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-center self-start sm:self-auto flex items-center sm:flex-col gap-2 sm:gap-0 shrink-0">
            <span class="text-[11px] font-medium text-text-muted">Total Penugasan:</span>
            <span class="text-sm sm:text-base font-bold text-text-main tabular-nums"><?= count($jadwal_aktif) ?></span>
        </div>
    </div>

    <!-- Alert Notifikasi Feedback -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'sukses_alihkan'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Pengampu jadwal mengajar berhasil dialihkan! Seluruh riwayat nilai siswa tetap terjaga utuh.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_dikosongkan'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Pengampu jadwal berhasil dikosongkan! Jadwal kini terbuka dan bisa langsung diambil oleh guru lain.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_bulk_kosong'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Seluruh jadwal terpilih berhasil dikosongkan dan terbuka untuk diambil oleh rekan guru lain.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_semua_kosong'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Seluruh jadwal guru berhasil dikosongkan pengampunya dan siap diambil oleh guru pengganti.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_bulk_alihkan'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Seluruh jadwal terpilih berhasil dialihkan ke pengampu baru! Nilai siswa tetap aman.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_alihkan_semua'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Seluruh jadwal guru berhasil dialihkan / dikosongkan. Akun guru kini aman untuk dinonaktifkan atau dihapus!</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_status'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Sifat penugasan jadwal berhasil diperbarui (Diajar Sendiri / Titipan Manual).</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_hapus'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Jadwal penugasan yang dipilih beserta seluruh data nilai yang terkait berhasil dihapus.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sebagian_gagal' || $_GET['pesan'] == 'gagal_nilai'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">warning</span>
                <span>Beberapa atau seluruh jadwal tidak dapat dihapus karena terjadi kesalahan atau kendala akses.</span>
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
                        (<?= $skipped ?> jadwal dilewati karena sudah diambil oleh guru lain atau terdaftar).
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

    <!-- Tab Switcher Khusus Mobile -->
    <div class="lg:hidden flex rounded-xl bg-slate-100 p-1 border border-border-main shadow-xs">
        <button type="button" id="mobileTabDaftar" onclick="switchMobileView('daftar')" class="flex-1 py-2.5 px-3 rounded-lg bg-white text-primary font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]">
            <span class="material-symbols-outlined text-base">format_list_bulleted</span>
            <span>Daftar Jadwal (<?= count($jadwal_aktif) ?>)</span>
        </button>
        <button type="button" id="mobileTabTambah" onclick="switchMobileView('tambah')" class="flex-1 py-2.5 px-3 rounded-lg text-text-muted hover:text-text-main font-semibold text-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>+ Tambah Jadwal</span>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Form Tambah Jadwal (Bulk Support) -->
        <div id="kolomTambahJadwal" class="hidden lg:block lg:col-span-4 lg:sticky lg:top-20">
            <div class="bg-surface-card rounded-xl border border-border-main shadow-xs p-6">
                <h3 class="font-bold text-sm text-text-main mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base">add_circle</span>
                    <?= $is_admin ? 'Tetapkan Jadwal Guru' : 'Pilih Jadwal Mengajar Anda' ?>
                </h3>

                <!-- Tab Segmented Control -->
                <div class="grid grid-cols-2 rounded-lg bg-slate-100 p-1 mb-4 text-xs font-semibold">
                    <button type="button" id="tabBtnKelas" onclick="switchTab('kelas')" class="min-h-[40px] py-2 rounded-md bg-white text-primary font-bold shadow-xs transition-colors text-center flex items-center justify-center">
                        Per Kelas
                    </button>
                    <button type="button" id="tabBtnMapel" onclick="switchTab('mapel')" class="min-h-[40px] py-2 rounded-md text-text-muted hover:text-text-main font-semibold transition-colors text-center flex items-center justify-center">
                        Per Mapel
                    </button>
                </div>
                
                <!-- TAB 1: 1 KELAS -> BANYAK MAPEL -->
                <form id="formPerKelas" action="proses_jadwal.php" method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="hidden" name="mode" value="kelas_to_mapel">
                    
                    <?php if ($is_admin): ?>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Guru Pengajar</label>
                        <select name="target_user_id" id="target_user_kelas" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required onchange="updateSubjectCheckboxes()">
                            <option value="kosong" <?= ($filter_guru === 'kosong') ? 'selected' : '' ?>>-- Belum Ada Guru / Titipan (Diisi Wali Kelas) --</option>
                            <?php foreach($semua_guru as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($filter_guru == $g['id'] || ($filter_guru === null && $g['id'] == $user_id)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Sifat Penugasan (Jika Dipegang Admin)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="is_manual" value="0" checked class="peer sr-only">
                                <div class="min-h-[44px] p-2.5 rounded-lg border border-slate-300 text-center peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 text-[11px] font-semibold transition-colors flex items-center justify-center">
                                    Diajar Sendiri
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="is_manual" value="1" class="peer sr-only">
                                <div class="min-h-[44px] p-2.5 rounded-lg border border-slate-300 text-center peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 text-[11px] font-semibold transition-colors flex items-center justify-center">
                                    Titipan Manual
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Kelas</label>
                        <select name="class_id" id="select_class_id" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required onchange="updateSubjectCheckboxes()">
                            <option value="" disabled selected>-- Pilih Kelas --</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-text-main">Pilih Mata Pelajaran</label>
                            <button type="button" onclick="toggleCheckAllGroup('cb-mapel')" class="text-[11px] text-primary font-semibold hover:underline py-1">Pilih Semua Tersedia</button>
                        </div>
                        <div class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/50 divide-y divide-slate-100 p-1.5 custom-scroll" id="containerMapel">
                            <?php foreach($semua_mapel as $m): ?>
                                <label id="label_mapel_<?= $m['id'] ?>" class="flex items-center justify-between min-h-[40px] py-2 px-2.5 hover:bg-white rounded-lg cursor-pointer text-xs font-medium text-text-main select-none transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox" name="subject_ids[]" value="<?= $m['id'] ?>" data-mid="<?= $m['id'] ?>" class="cb-mapel rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer shrink-0">
                                        <span class="truncate"><?= htmlspecialchars($m['nama_mapel']) ?></span>
                                    </div>
                                    <span id="badge_mapel_<?= $m['id'] ?>" class="hidden text-[10px] px-1.5 py-0.5 rounded font-medium shrink-0 ml-2"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors mt-1 min-h-[44px] flex items-center justify-center">
                        Simpan Penugasan Mapel
                    </button>
                </form>

                <!-- TAB 2: 1 MAPEL -> BANYAK KELAS -->
                <form id="formPerMapel" action="proses_jadwal.php" method="POST" class="flex flex-col gap-4 hidden">
                    <input type="hidden" name="aksi" value="tambah">
                    <input type="hidden" name="mode" value="mapel_to_kelas">
                    
                    <?php if ($is_admin): ?>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Guru Pengajar</label>
                        <select name="target_user_id" id="target_user_mapel" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required onchange="updateClassCheckboxes()">
                            <option value="kosong" <?= ($filter_guru === 'kosong') ? 'selected' : '' ?>>-- Belum Ada Guru / Titipan (Diisi Wali Kelas) --</option>
                            <?php foreach($semua_guru as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($filter_guru == $g['id'] || ($filter_guru === null && $g['id'] == $user_id)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Sifat Penugasan (Jika Dipegang Admin)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="is_manual" value="0" checked class="peer sr-only">
                                <div class="min-h-[44px] p-2.5 rounded-lg border border-slate-300 text-center peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 text-[11px] font-semibold transition-colors flex items-center justify-center">
                                    Diajar Sendiri
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="is_manual" value="1" class="peer sr-only">
                                <div class="min-h-[44px] p-2.5 rounded-lg border border-slate-300 text-center peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 text-[11px] font-semibold transition-colors flex items-center justify-center">
                                    Titipan Manual
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Pilih Mata Pelajaran</label>
                        <select name="subject_id" id="select_subject_id" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required onchange="updateClassCheckboxes()">
                            <option value="" disabled selected>-- Pilih Mapel --</option>
                            <?php foreach($semua_mapel as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-text-main">Pilih Kelas</label>
                            <button type="button" onclick="toggleCheckAllGroup('cb-kelas')" class="text-[11px] text-primary font-semibold hover:underline py-1">Pilih Semua Tersedia</button>
                        </div>
                        <div class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/50 divide-y divide-slate-100 p-1.5 custom-scroll" id="containerKelas">
                            <?php foreach($semua_kelas as $k): ?>
                                <label id="label_kelas_<?= $k['id'] ?>" class="flex items-center justify-between min-h-[40px] py-2 px-2.5 hover:bg-white rounded-lg cursor-pointer text-xs font-medium text-text-main select-none transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="checkbox" name="class_ids[]" value="<?= $k['id'] ?>" data-cid="<?= $k['id'] ?>" class="cb-kelas rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer shrink-0">
                                        <span class="truncate"><?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?></span>
                                    </div>
                                    <span id="badge_kelas_<?= $k['id'] ?>" class="hidden text-[10px] px-1.5 py-0.5 rounded font-medium shrink-0 ml-2"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors mt-1 min-h-[44px] flex items-center justify-center">
                        Simpan Penugasan Kelas
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Jadwal + Filter + Bulk Delete -->
        <div id="kolomDaftarJadwal" class="block lg:block lg:col-span-8">
            <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
                
                <!-- Filter Toolbar -->
                <div class="p-4 sm:p-5 border-b border-border-main bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                    <div>
                        <h3 class="font-bold text-xs md:text-sm text-text-main">
                            <?= $is_admin ? 'Daftar Jadwal Mengajar Terdaftar' : 'Daftar Jadwal Mengajar Anda' ?>
                        </h3>
                        <span class="text-xs text-text-muted tabular-nums"><?= count($jadwal_aktif) ?> jadwal terdaftar</span>
                    </div>

                    <form method="GET" action="jadwal.php" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                        <?php if ($is_admin): ?>
                            <select name="filter_guru" onchange="this.form.submit()" class="w-full sm:w-auto text-xs font-medium rounded-lg border border-slate-300 bg-white text-text-main px-3 py-2.5 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]">
                                <option value="">Semua Guru</option>
                                <option value="kosong" <?= ($filter_guru === 'kosong') ? 'selected' : '' ?>>🟡 Belum Ada Pengampu (Kosong)</option>
                                <?php foreach($semua_guru as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($filter_guru !== 'kosong' && $filter_guru == $g['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <select name="filter_kelas" onchange="this.form.submit()" class="w-full sm:w-auto text-xs font-medium rounded-lg border border-slate-300 bg-white text-text-main px-3 py-2.5 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]">
                            <option value="">Semua Kelas</option>
                            <?php foreach($semua_kelas as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($filter_kelas == $k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($filter_kelas || $filter_guru): ?>
                            <a href="jadwal.php" class="inline-flex items-center justify-center text-xs text-primary hover:underline font-semibold px-2 py-2 min-h-[36px] self-center sm:self-auto">Reset Filter</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if ($is_admin && $filter_guru && !empty($jadwal_aktif)): ?>
                    <?php 
                        $target_guru_name = '';
                        foreach ($semua_guru as $g) {
                            if ($g['id'] == $filter_guru) { $target_guru_name = $g['nama_lengkap']; break; }
                        }
                    ?>
                    <div class="p-4 bg-amber-50/80 border-b border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5 text-amber-900">
                            <span class="material-symbols-outlined text-amber-700 text-lg shrink-0">info</span>
                            <span>Menampilkan <strong><?= count($jadwal_aktif) ?></strong> jadwal milik <strong><?= htmlspecialchars($target_guru_name) ?></strong>.</span>
                        </div>
                        <button type="button" onclick="bukaModalAlihkanSemuaGuru(<?= (int)$filter_guru ?>, '<?= htmlspecialchars(addslashes($target_guru_name)) ?>', <?= count($jadwal_aktif) ?>)" class="w-full sm:w-auto px-3.5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer min-h-[40px]">
                            <span class="material-symbols-outlined text-sm">swap_horiz</span>
                            <span>Kosongkan / Alihkan Semua Jadwal Guru Ini</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Form Bulk Delete -->
                <form action="proses_jadwal.php" method="POST" id="formBulkDelete">
                    <input type="hidden" name="aksi" value="bulk_delete" id="formBulkAction">
                    <input type="hidden" name="redirect_filter_kelas" value="<?= htmlspecialchars((string)$filter_kelas) ?>">
                    <input type="hidden" name="redirect_filter_guru" value="<?= htmlspecialchars((string)$filter_guru) ?>">

                    <?php if(!empty($jadwal_aktif)): ?>
                        <div class="px-4 sm:px-5 py-3 bg-slate-50/70 border-b border-border-main flex flex-wrap items-center justify-between gap-2.5">
                            <label class="inline-flex items-center gap-2.5 text-xs font-semibold text-text-muted cursor-pointer select-none py-1">
                                <input type="checkbox" id="checkAll" class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                <span>Pilih Semua</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2 ml-auto">
                                <?php if ($is_admin): ?>
                                    <button type="button" id="btnBulkAlihkan" onclick="bukaModalBulkAlihkan()" class="hidden items-center gap-1.5 text-xs font-semibold text-primary hover:bg-primary-subtle px-3 py-2 rounded-lg border border-primary/20 transition-colors cursor-pointer min-h-[38px]">
                                        <span class="material-symbols-outlined text-base">swap_horiz</span>
                                        <span>Alihkan Terpilih</span>
                                    </button>
                                <?php endif; ?>
                                <button type="submit" id="btnBulkDelete" onclick="konfirmasiForm(event, 'Hapus seluruh jadwal penugasan yang dicentang?<br><br><div class=\'text-rose-600 font-medium text-xs bg-rose-50 border border-rose-200 p-3 rounded-lg text-left leading-relaxed\'>⚠️ <strong>Peringatan Penting:</strong><br>Seluruh data nilai siswa yang terkait dengan jadwal terpilih akan <u>ikut terhapus permanen</u>!</div>')" class="hidden items-center gap-1.5 text-xs font-semibold text-danger hover:bg-danger-subtle px-3 py-2 rounded-lg border border-danger/20 transition-colors cursor-pointer min-h-[38px]">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                    <span id="countSelected">Hapus Terpilih</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="p-3 sm:p-5 flex flex-col gap-3">
                        <?php if(empty($jadwal_aktif)): ?>
                            <div class="flex flex-col items-center justify-center py-12 text-center px-4">
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">calendar_add_on</span>
                                <p class="text-sm font-semibold text-text-main">Belum Ada Jadwal Mengajar<?= $filter_kelas ? ' untuk Kriteria Ini' : '' ?></p>
                                <p class="text-xs text-text-muted mt-1 max-w-sm">Gunakan formulir penugasan untuk menambahkan penugasan mata pelajaran ke kelas yang diampu.</p>
                                <button type="button" onclick="switchMobileView('tambah')" class="lg:hidden mt-4 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs transition-colors min-h-[44px]">
                                    <span class="material-symbols-outlined text-sm">add_circle</span>
                                    <span>Buka Form Tambah Jadwal</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach($jadwal_aktif as $jadwal): ?>
                                <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50/50 transition-colors bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-start sm:items-center gap-3 min-w-0">
                                        <div class="pt-0.5 sm:pt-0 shrink-0">
                                            <input type="checkbox" name="jadwal_ids[]" value="<?= $jadwal['jadwal_id'] ?>" class="item-checkbox rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer shrink-0">
                                        </div>
                                        
                                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg sm:text-xl">book</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-xs sm:text-sm text-text-main leading-snug"><?= htmlspecialchars($jadwal['nama_mapel']) ?></h4>
                                                <span class="badge-grade-neutral px-2 py-0.5 rounded font-semibold text-[10px]">
                                                    <?= htmlspecialchars($jadwal['nama_kelas']) ?> (<?= htmlspecialchars($jadwal['jenjang']) ?>)
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-text-muted flex flex-wrap items-center gap-1.5 mt-1.5">
                                                <?php if ($jadwal['user_id'] === null): ?>
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-300">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        <span>Belum Ada Guru (Slot Terbuka)</span>
                                                    </span>
                                                <?php elseif ($is_admin): ?>
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-700 bg-slate-100 sm:bg-transparent px-1.5 py-0.5 sm:p-0 rounded">
                                                        <span class="material-symbols-outlined text-[13px] text-slate-500">person</span>
                                                        <?= htmlspecialchars($jadwal['nama_guru']) ?>
                                                    </span>
                                                    <?php if ($jadwal['guru_role'] === 'admin'): ?>
                                                        <?php if ((int)$jadwal['is_manual'] === 1): ?>
                                                            <a href="proses_jadwal.php?toggle_manual=<?= $jadwal['jadwal_id'] ?>&redirect_filter_kelas=<?= urlencode((string)$filter_kelas) ?>&redirect_filter_guru=<?= urlencode((string)$filter_guru) ?>" 
                                                               class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors"
                                                               title="Klik untuk ubah menjadi: Diajar Sendiri oleh Admin">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                                <span>Titipan Manual (Wali Kelas Boleh Input)</span>
                                                                <span class="material-symbols-outlined text-[11px]">sync</span>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="proses_jadwal.php?toggle_manual=<?= $jadwal['jadwal_id'] ?>&redirect_filter_kelas=<?= urlencode((string)$filter_kelas) ?>&redirect_filter_guru=<?= urlencode((string)$filter_guru) ?>" 
                                                               class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                                               title="Klik untuk ubah menjadi: Titipan Manual (Wali Kelas Boleh Input)">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                                <span>Diajar Sendiri (Wali Kelas Terkunci)</span>
                                                                <span class="material-symbols-outlined text-[11px]">sync</span>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                            <span>Diajar Guru Resmi</span>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center justify-end gap-1.5 pt-2.5 sm:pt-0 border-t border-slate-100 sm:border-t-0 shrink-0">
                                        <a href="input_data.php?kelas=<?= $jadwal['class_id'] ?>&mapel=<?= $jadwal['subject_id'] ?>" 
                                           class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-slate-100 hover:bg-primary hover:text-white sm:bg-transparent sm:text-text-muted sm:hover:bg-slate-100 sm:hover:text-primary transition-colors min-h-[38px] sm:min-h-0 sm:p-2" 
                                           title="Input Nilai">
                                            <span class="material-symbols-outlined text-base sm:text-lg">edit_square</span>
                                            <span class="sm:hidden text-[11px] font-semibold">Nilai</span>
                                        </a>
                                        <a href="analisa.php?kelas=<?= $jadwal['class_id'] ?>&mapel=<?= $jadwal['subject_id'] ?>" 
                                           class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-slate-100 hover:bg-primary hover:text-white sm:bg-transparent sm:text-text-muted sm:hover:bg-slate-100 sm:hover:text-primary transition-colors min-h-[38px] sm:min-h-0 sm:p-2" 
                                           title="Lihat Analisa">
                                            <span class="material-symbols-outlined text-base sm:text-lg">analytics</span>
                                            <span class="sm:hidden text-[11px] font-semibold">Analisa</span>
                                        </a>
                                        <?php if ($is_admin): ?>
                                            <button type="button" 
                                                    onclick="bukaModalAlihkan(<?= htmlspecialchars(json_encode($jadwal)) ?>)"
                                                    class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-slate-100 hover:bg-primary hover:text-white sm:bg-transparent sm:text-slate-500 sm:hover:bg-slate-100 sm:hover:text-primary transition-colors min-h-[38px] sm:min-h-0 sm:p-2 cursor-pointer"
                                                    title="Alihkan Pengampu ke Guru Lain">
                                                <span class="material-symbols-outlined text-base sm:text-lg">swap_horiz</span>
                                                <span class="sm:hidden text-[11px] font-semibold">Alihkan</span>
                                            </button>
                                        <?php endif; ?>
                                        <a href="proses_jadwal.php?hapus=<?= $jadwal['jadwal_id'] ?>&redirect_filter_kelas=<?= urlencode((string)$filter_kelas) ?>&redirect_filter_guru=<?= urlencode((string)$filter_guru) ?>" 
                                           onclick="konfirmasiLink(event, this.href, 'Hapus jadwal mata pelajaran <strong><?= htmlspecialchars(addslashes($jadwal['nama_mapel'])) ?></strong> di kelas <strong><?= htmlspecialchars(addslashes($jadwal['nama_kelas'])) ?></strong>?<br><br><div class=\'text-rose-600 font-medium text-xs bg-rose-50 border border-rose-200 p-3 rounded-lg text-left leading-relaxed\'>⚠️ <strong>Peringatan Penting:</strong><br>Jika sudah ada data nilai siswa pada jadwal ini, seluruh nilai tersebut akan <u>ikut terhapus permanen</u>!</div>')" 
                                           class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 sm:bg-transparent sm:text-slate-400 sm:hover:text-danger sm:hover:bg-danger-subtle transition-colors min-h-[38px] sm:min-h-0 sm:p-2 cursor-pointer" 
                                           title="Hapus Jadwal">
                                            <span class="material-symbols-outlined text-base sm:text-lg">delete</span>
                                            <span class="sm:hidden text-[11px] font-semibold">Hapus</span>
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
function switchMobileView(view) {
    const colTambah = document.getElementById('kolomTambahJadwal');
    const colDaftar = document.getElementById('kolomDaftarJadwal');
    const tabTambah = document.getElementById('mobileTabTambah');
    const tabDaftar = document.getElementById('mobileTabDaftar');

    if (!colTambah || !colDaftar || !tabTambah || !tabDaftar) return;

    if (view === 'tambah') {
        colTambah.classList.remove('hidden');
        colDaftar.classList.add('hidden');
        
        tabTambah.className = "flex-1 py-2.5 px-3 rounded-lg bg-white text-primary font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]";
        tabDaftar.className = "flex-1 py-2.5 px-3 rounded-lg text-text-muted hover:text-text-main font-semibold text-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]";
        window.scrollTo({ top: tabTambah.offsetTop - 80, behavior: 'smooth' });
    } else {
        colDaftar.classList.remove('hidden');
        colTambah.classList.add('hidden');

        tabDaftar.className = "flex-1 py-2.5 px-3 rounded-lg bg-white text-primary font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]";
        tabTambah.className = "flex-1 py-2.5 px-3 rounded-lg text-text-muted hover:text-text-main font-semibold text-xs transition-all flex items-center justify-center gap-1.5 min-h-[44px]";
        window.scrollTo({ top: tabDaftar.offsetTop - 80, behavior: 'smooth' });
    }
}

const takenMap = <?= json_encode($taken_map) ?>;
const currentUserId = <?= (int)$user_id ?>;
const isAdmin = <?= $is_admin ? 'true' : 'false' ?>;

function getSelectedTeacherId(tab) {
    if (isAdmin) {
        const el = tab === 'kelas' ? document.getElementById('target_user_kelas') : document.getElementById('target_user_mapel');
        if (!el || el.value === 'kosong' || el.value === '') return null;
        const parsed = parseInt(el.value);
        return isNaN(parsed) ? null : parsed;
    }
    return currentUserId;
}

function updateSubjectCheckboxes() {
    const classSelect = document.getElementById('select_class_id');
    const classId = classSelect.value;
    const teacherId = getSelectedTeacherId('kelas');

    document.querySelectorAll('.cb-mapel').forEach(cb => {
        const mid = cb.getAttribute('data-mid');
        const badge = document.getElementById('badge_mapel_' + mid);
        const label = document.getElementById('label_mapel_' + mid);

        if (!classId) {
            cb.disabled = false;
            cb.checked = false;
            label.classList.remove('opacity-60', 'cursor-not-allowed');
            badge.classList.add('hidden');
            return;
        }

        const classTaken = takenMap[classId] || {};
        const assignment = classTaken[mid];

        if (assignment) {
            if (assignment.user_id === null) {
                // Jadwal terdaftar tapi masih KOSONG: Bebas diambil oleh guru!
                cb.disabled = false;
                label.classList.remove('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Tersedia (Bisa Diambil)';
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200';
            } else if (assignment.user_id === teacherId) {
                cb.checked = false;
                cb.disabled = true;
                label.classList.add('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Sudah Diambil';
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-medium bg-blue-50 text-blue-700 border border-blue-200';
            } else {
                cb.checked = false;
                cb.disabled = true;
                label.classList.add('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Diampu: ' + (assignment.nama || 'Guru Lain');
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-medium bg-slate-100 text-slate-500 border border-slate-200';
            }
        } else {
            cb.disabled = false;
            label.classList.remove('opacity-60', 'cursor-not-allowed');
            badge.classList.add('hidden');
        }
    });
}

function updateClassCheckboxes() {
    const mapelSelect = document.getElementById('select_subject_id');
    const subjectId = mapelSelect.value;
    const teacherId = getSelectedTeacherId('mapel');

    document.querySelectorAll('.cb-kelas').forEach(cb => {
        const cid = cb.getAttribute('data-cid');
        const badge = document.getElementById('badge_kelas_' + cid);
        const label = document.getElementById('label_kelas_' + cid);

        if (!subjectId) {
            cb.disabled = false;
            cb.checked = false;
            label.classList.remove('opacity-60', 'cursor-not-allowed');
            badge.classList.add('hidden');
            return;
        }

        const classTaken = takenMap[cid] || {};
        const assignment = classTaken[subjectId];

        if (assignment) {
            if (assignment.user_id === null) {
                // Jadwal terdaftar tapi masih KOSONG: Bebas diambil oleh guru!
                cb.disabled = false;
                label.classList.remove('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Tersedia (Bisa Diambil)';
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200';
            } else if (assignment.user_id === teacherId) {
                cb.checked = false;
                cb.disabled = true;
                label.classList.add('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Sudah Diambil';
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-medium bg-blue-50 text-blue-700 border border-blue-200';
            } else {
                cb.checked = false;
                cb.disabled = true;
                label.classList.add('opacity-60', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = 'Diampu: ' + (assignment.nama || 'Guru Lain');
                badge.className = 'text-[10px] px-1.5 py-0.5 rounded font-medium bg-slate-100 text-slate-500 border border-slate-200';
            }
        } else {
            cb.disabled = false;
            label.classList.remove('opacity-60', 'cursor-not-allowed');
            badge.classList.add('hidden');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const countSelected = document.getElementById('countSelected');

    function updateBulkButton() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        const btnBulkAlihkan = document.getElementById('btnBulkAlihkan');

        if (checkedCount > 0) {
            btnBulkDelete.classList.remove('hidden');
            btnBulkDelete.classList.add('inline-flex');
            countSelected.textContent = `Hapus (${checkedCount}) Terpilih`;

            if (btnBulkAlihkan) {
                btnBulkAlihkan.classList.remove('hidden');
                btnBulkAlihkan.classList.add('inline-flex');
            }
        } else {
            btnBulkDelete.classList.add('hidden');
            btnBulkDelete.classList.remove('inline-flex');

            if (btnBulkAlihkan) {
                btnBulkAlihkan.classList.add('hidden');
                btnBulkAlihkan.classList.remove('inline-flex');
            }
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
        btnKelas.className = "min-h-[40px] py-2 rounded-md bg-white text-primary font-bold shadow-xs transition-colors text-center flex items-center justify-center";
        btnMapel.className = "min-h-[40px] py-2 rounded-md text-text-muted hover:text-text-main font-semibold transition-colors text-center flex items-center justify-center";
        updateSubjectCheckboxes();
    } else {
        formMapel.classList.remove('hidden');
        formKelas.classList.add('hidden');
        btnMapel.className = "min-h-[40px] py-2 rounded-md bg-white text-primary font-bold shadow-xs transition-colors text-center flex items-center justify-center";
        btnKelas.className = "min-h-[40px] py-2 rounded-md text-text-muted hover:text-text-main font-semibold transition-colors text-center flex items-center justify-center";
        updateClassCheckboxes();
    }
}

// Toggle Select All Checkbox Group (hanya yang tidak disabled)
function toggleCheckAllGroup(className) {
    const cbs = Array.from(document.querySelectorAll('.' + className)).filter(cb => !cb.disabled);
    if (cbs.length === 0) return;
    const allChecked = cbs.every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
}

function konfirmasiForm(event, message) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Hapus Jadwal',
        html: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#be123c',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Terpilih',
        cancelButtonText: 'Batal',
        background: '#ffffff',
        customClass: { 
            popup: 'rounded-xl shadow-md border border-slate-200 text-sm font-sans',
            confirmButton: 'rounded-lg text-xs font-semibold px-4 py-2.5',
            cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2.5'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formBulkDelete').submit();
        }
    });
}

function konfirmasiLink(event, url, message) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Hapus Jadwal',
        html: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#be123c',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Jadwal',
        cancelButtonText: 'Batal',
        background: '#ffffff',
        customClass: { 
            popup: 'rounded-xl shadow-md border border-slate-200 text-sm font-sans',
            confirmButton: 'rounded-lg text-xs font-semibold px-4 py-2.5',
            cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2.5'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

function bukaModalAlihkan(jadwal) {
    const modal = document.getElementById('modalAlihkanPengampu');
    if (!modal) return;
    document.getElementById('alihkan_jadwal_id').value = jadwal.jadwal_id;
    document.getElementById('alihkan_nama_mapel').textContent = jadwal.nama_mapel;
    document.getElementById('alihkan_nama_kelas').textContent = jadwal.nama_kelas + ' (' + jadwal.jenjang + ')';
    document.getElementById('alihkan_pengampu_saat_ini').textContent = jadwal.nama_guru;
    document.getElementById('new_user_id').value = jadwal.user_id;
    modal.classList.remove('hidden');
}

function tutupModalAlihkan() {
    const modal = document.getElementById('modalAlihkanPengampu');
    if (modal) modal.classList.add('hidden');
}

function bukaModalBulkAlihkan() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    if (checkedBoxes.length === 0) {
        Swal.fire('Perhatian', 'Pilih minimal satu jadwal yang ingin dialihkan.', 'info');
        return;
    }
    document.getElementById('bulk_count_text').textContent = checkedBoxes.length;
    document.getElementById('modalBulkAlihkan').classList.remove('hidden');
}

function tutupModalBulkAlihkan() {
    document.getElementById('modalBulkAlihkan').classList.add('hidden');
}

function submitBulkAlihkan() {
    const targetSelect = document.getElementById('bulk_target_new_user_id');
    const newUserId = targetSelect.value;
    if (!newUserId) {
        Swal.fire('Perhatian', 'Harap pilih pengampu baru.', 'warning');
        return;
    }

    const form = document.getElementById('formBulkDelete');
    document.getElementById('formBulkAction').value = 'bulk_alihkan';

    let inputTarget = document.getElementById('hidden_new_user_id');
    if (!inputTarget) {
        inputTarget = document.createElement('input');
        inputTarget.type = 'hidden';
        inputTarget.name = 'new_user_id';
        inputTarget.id = 'hidden_new_user_id';
        form.appendChild(inputTarget);
    }
    inputTarget.value = newUserId;

    form.submit();
}

function bukaModalAlihkanSemuaGuru(guruId, guruNama, totalJadwal) {
    document.getElementById('semua_from_user_id').value = guruId;
    document.getElementById('semua_nama_guru').textContent = guruNama;
    document.getElementById('semua_total_jadwal').textContent = totalJadwal;
    document.getElementById('modalAlihkanSemuaGuru').classList.remove('hidden');
}

function tutupModalAlihkanSemuaGuru() {
    document.getElementById('modalAlihkanSemuaGuru').classList.add('hidden');
}
</script>

<?php if ($is_admin): ?>
<!-- Modal Alihkan Pengampu Tunggal -->
<div id="modalAlihkanPengampu" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-5 sm:p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">swap_horiz</span>
                Alihkan Pengampu Jadwal
            </h3>
            <button type="button" onclick="tutupModalAlihkan()" class="text-text-muted hover:text-danger w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_jadwal.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="alihkan_pengampu">
            <input type="hidden" name="jadwal_id" id="alihkan_jadwal_id">
            <input type="hidden" name="redirect_filter_kelas" value="<?= htmlspecialchars((string)$filter_kelas) ?>">
            <input type="hidden" name="redirect_filter_guru" value="<?= htmlspecialchars((string)$filter_guru) ?>">

            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p class="text-[11px] text-text-muted font-medium">Mata Pelajaran:</p>
                <p class="text-xs font-bold text-text-main" id="alihkan_nama_mapel">-</p>
                <p class="text-[11px] text-text-muted font-medium mt-1.5">Kelas:</p>
                <p class="text-xs font-bold text-text-main" id="alihkan_nama_kelas">-</p>
                <p class="text-[11px] text-text-muted font-medium mt-1.5">Pengampu Saat Ini:</p>
                <p class="text-xs font-semibold text-primary" id="alihkan_pengampu_saat_ini">-</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="new_user_id">Pilih Pengampu Baru</label>
                <select id="new_user_id" name="new_user_id" class="w-full bg-white text-xs rounded-lg border border-slate-300 px-3 py-2.5 font-medium cursor-pointer min-h-[44px]" required>
                    <option value="" disabled selected>-- Pilih Pengampu Baru --</option>
                    <option value="kosong" class="font-semibold text-amber-700 bg-amber-50">🟡 Kosongkan Pengampu (Slot Terbuka / Tersedia untuk Diambil Guru)</option>
                    <?php foreach ($semua_guru as $g): ?>
                        <option value="<?= $g['id'] ?>">
                            <?= htmlspecialchars($g['nama_lengkap']) ?> (<?= $g['id'] == $user_id ? 'Admin' : 'Guru' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-text-muted mt-1.5 block leading-relaxed">
                    <span class="material-symbols-outlined text-[13px] text-emerald-600 align-middle">check_circle</span>
                    Seluruh riwayat nilai siswa yang telah diisi pada jadwal ini akan otomatis dialihkan dan tidak akan hilang.
                </span>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2.5 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalAlihkan()" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Batal
                </button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Simpan & Alihkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Bulk Alihkan Pengampu Terpilih -->
<div id="modalBulkAlihkan" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-5 sm:p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">swap_horiz</span>
                Alihkan Pengampu Terpilih
            </h3>
            <button type="button" onclick="tutupModalBulkAlihkan()" class="text-text-muted hover:text-danger w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-primary-subtle/30 border border-primary/20 p-3.5 rounded-xl flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">checklist</span>
                <div>
                    <p class="text-xs font-bold text-text-main">
                        <span id="bulk_count_text">0</span> Jadwal Terpilih
                    </p>
                    <p class="text-[11px] text-text-muted mt-0.5">Pilih guru yang akan ditugaskan mengampu jadwal-jadwal tersebut.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="bulk_target_new_user_id">Pilih Pengampu Baru</label>
                <select id="bulk_target_new_user_id" class="w-full bg-white text-xs rounded-lg border border-slate-300 px-3 py-2.5 font-medium cursor-pointer min-h-[44px]" required>
                    <option value="" disabled selected>-- Pilih Pengampu Baru / Tindakan --</option>
                    <option value="kosong" class="font-semibold text-amber-700 bg-amber-50">🟡 Kosongkan Pengampu (Slot Terbuka / Tersedia untuk Diambil Guru)</option>
                    <?php foreach ($semua_guru as $g): ?>
                        <option value="<?= $g['id'] ?>">
                            <?= htmlspecialchars($g['nama_lengkap']) ?> <?= ($g['id'] == $user_id) ? '(Admin - Ditampung Manual)' : '(Guru)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-text-muted mt-1.5 block leading-relaxed">
                    <span class="material-symbols-outlined text-[13px] text-emerald-600 align-middle">info</span>
                    Pilih <strong>Kosongkan Pengampu</strong> agar jadwal bisa langsung dipilih sendiri oleh guru lain. Jika dialihkan ke Admin, jadwal berstatus Titipan Manual sehingga wali kelas bisa mengisi nilai.
                </span>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2.5 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalBulkAlihkan()" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Batal
                </button>
                <button type="button" onclick="submitBulkAlihkan()" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Simpan & Alihkan Semua
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Alihkan Seluruh Jadwal Milik Guru Tertentu -->
<div id="modalAlihkanSemuaGuru" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-5 sm:p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-base">swap_horiz</span>
                Kosongkan / Alihkan Jadwal Guru
            </h3>
            <button type="button" onclick="tutupModalAlihkanSemuaGuru()" class="text-text-muted hover:text-danger w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_jadwal.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="alihkan_semua_guru">
            <input type="hidden" name="from_user_id" id="semua_from_user_id">
            <input type="hidden" name="redirect_filter_kelas" value="<?= htmlspecialchars((string)$filter_kelas) ?>">

            <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl">
                <p class="text-xs font-bold text-amber-900">
                    Guru: <span id="semua_nama_guru">-</span>
                </p>
                <p class="text-[11px] text-amber-800 mt-1 leading-relaxed">
                    Total ada <strong id="semua_total_jadwal">0</strong> jadwal mengajar yang diampu oleh guru ini. Tindakan ini akan mengalihkan seluruh jadwal sekaligus agar guru tidak memiliki jadwal mengampu lagi.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="semua_new_user_id">Alihkan Semua Jadwal Ke:</label>
                <select id="semua_new_user_id" name="new_user_id" class="w-full bg-white text-xs rounded-lg border border-slate-300 px-3 py-2.5 font-medium cursor-pointer min-h-[44px]" required>
                    <option value="" disabled selected>-- Pilih Guru Tujuan / Tindakan --</option>
                    <option value="kosong" class="font-semibold text-amber-700 bg-amber-50">🟡 Kosongkan Semua (Slot Terbuka / Siap Diambil Guru Lain)</option>
                    <?php foreach ($semua_guru as $g): ?>
                        <option value="<?= $g['id'] ?>">
                            <?= htmlspecialchars($g['nama_lengkap']) ?> <?= ($g['id'] == $user_id) ? '(Admin - Ditampung Manual)' : '(Guru)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-text-muted mt-1.5 block leading-relaxed">
                    Pilih <strong>Kosongkan Semua</strong> agar jadwal terbuka untuk dipilih oleh guru baru/pengganti, atau pilih <strong>Admin</strong> jika ditampung sementara.
                </span>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2.5 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalAlihkanSemuaGuru()" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Batal
                </button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors cursor-pointer min-h-[44px] flex items-center justify-center">
                    Proses Alihkan Semua
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../components/footer.php'; ?>