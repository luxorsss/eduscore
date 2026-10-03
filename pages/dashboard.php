<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();
$user_wk = get_user_wali_kelas($pdo, $user_id);

// Stats tambahan untuk admin
$admin_stats = [];
if ($is_admin) {
    $admin_stats['total_guru'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn();
    $admin_stats['total_kelas'] = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $admin_stats['total_siswa'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $admin_stats['total_mapel'] = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
}

// Jadwal & kelas yang berhak diisi nilainya
if ($is_admin) {
    $stmt_kelas = $pdo->query("
        SELECT DISTINCT c.id, c.nama_kelas, c.jenjang 
        FROM teaching_schedules ts 
        JOIN classes c ON ts.class_id = c.id 
        ORDER BY c.jenjang, c.nama_kelas
    ");
    $kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

    $stmt_jadwal = $pdo->query("
        SELECT ts.id as schedule_id, ts.class_id, ts.user_id, c.nama_kelas, c.jenjang, s.id as subject_id, s.nama_mapel, u.nama_lengkap as nama_guru, u.role as owner_role
        FROM teaching_schedules ts 
        JOIN classes c ON ts.class_id = c.id
        JOIN subjects s ON ts.subject_id = s.id
        LEFT JOIN users u ON ts.user_id = u.id
        ORDER BY c.jenjang, c.nama_kelas, s.nama_mapel
    ");
    $jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Guru: mapel yang diampu sendiri + mapel manual / slot terbuka di kelas binaannya
    $sql_jadwal = "
        SELECT ts.id as schedule_id, ts.class_id, ts.is_manual, ts.user_id, c.nama_kelas, c.jenjang, s.id as subject_id, s.nama_mapel, u.nama_lengkap as nama_guru, u.role as owner_role
        FROM teaching_schedules ts 
        JOIN classes c ON ts.class_id = c.id
        JOIN subjects s ON ts.subject_id = s.id 
        LEFT JOIN users u ON ts.user_id = u.id
        WHERE ts.user_id = ?
    ";
    $params = [$user_id];
    if ($user_wk) {
        $sql_jadwal .= " OR (c.id = ? AND (ts.user_id IS NULL OR ts.is_manual = 1))";
        $params[] = $user_wk['id'];
    }
    $sql_jadwal .= " ORDER BY c.jenjang, c.nama_kelas, s.nama_mapel";
    
    $stmt_jadwal = $pdo->prepare($sql_jadwal);
    $stmt_jadwal->execute($params);
    $jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    // Daftar kelas unik dari jadwal
    $kelas_map = [];
    foreach ($jadwal_list as $row) {
        $kelas_map[$row['class_id']] = [
            'id' => $row['class_id'],
            'nama_kelas' => $row['nama_kelas'],
            'jenjang' => $row['jenjang']
        ];
    }
    $kelas_list = array_values($kelas_map);
}

// Siapkan data JSON untuk dibaca oleh JavaScript filter
$kelas_json = json_encode($kelas_list);

$mapel_per_kelas = [];
foreach ($jadwal_list as $row) {
    $label_mapel = $row['nama_mapel'];
    if ($is_admin) {
        if (!empty($row['nama_guru'])) {
            $label_mapel .= ' (' . $row['nama_guru'] . ')';
        } else {
            $label_mapel .= ' (Belum Ada Guru)';
        }
    } elseif ((empty($row['user_id']) || (int)($row['is_manual'] ?? 0) === 1) && !$is_admin) {
        $label_mapel .= empty($row['user_id']) ? ' (Slot Terbuka / Binaan)' : ' (Manual / Binaan)';
    }
    $mapel_per_kelas[$row['class_id']][] = [
        'id' => $row['subject_id'],
        'nama' => $label_mapel
    ];
}
$mapel_json = json_encode($mapel_per_kelas);

$page_title = "Dashboard Pengajar - EduScore";
$page_heading = "Dashboard Pengajar";
require_once '../components/header.php';
?>

<main class="flex-grow p-4 md:p-8 max-w-6xl mx-auto w-full flex flex-col gap-6">

    <!-- Ringkasan Profil Akademik Pengajar -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-success"></span>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Tahun Akademik Aktif</span>
            </div>
            <h2 class="text-lg md:text-xl font-bold text-text-main mt-1">
                Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengajar'); ?>
            </h2>
            <p class="text-xs md:text-sm text-text-muted mt-0.5">
                Kelola penilaian siswa dan rekap hasil belajar kelas yang Anda ampu.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="bukaModalPanduan()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-text-main text-xs font-semibold shadow-2xs transition-colors cursor-pointer w-full sm:w-auto justify-center">
                <span class="material-symbols-outlined text-base text-primary">menu_book</span>
                <span>Panduan Pengajar</span>
            </button>
            <div class="flex items-center gap-3 flex-1 sm:flex-none">
                <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 sm:flex-none">
                    <span class="text-[11px] font-medium text-text-muted block">Kelas Diampu</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= count($kelas_list) ?></span>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 sm:flex-none">
                    <span class="text-[11px] font-medium text-text-muted block">Jadwal Mapel</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= count($jadwal_list) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Onboarding Card untuk Guru yang Belum Mengambil Jadwal -->
    <?php if (!$is_admin && empty($jadwal_list)): ?>
        <div class="bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white rounded-xl border border-blue-200/80 p-5 md:p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-2xl">school</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-primary bg-primary-subtle px-2 py-0.5 rounded-full">Langkah Awal Guru Baru</span>
                    </div>
                    <h3 class="text-sm md:text-base font-bold text-text-main mt-1">
                        Selamat datang di EduScore! Mulai dengan menentukan jadwal mengajar Anda.
                    </h3>
                    <p class="text-xs text-text-muted mt-1 max-w-2xl leading-relaxed">
                        Akun Anda belum memiliki mata pelajaran yang diampu. Silakan tentukan mata pelajaran dan kelas yang Anda ajar agar formulir pengisian nilai aktif.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 w-full md:w-auto shrink-0">
                <button type="button" onclick="bukaModalPanduan()" class="w-full md:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-text-main transition-colors shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-base text-primary">help_outline</span>
                    <span>Pelajari Alur</span>
                </button>
                <a href="jadwal.php" class="w-full md:w-auto px-4 py-2.5 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors shadow-xs flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-base">calendar_add_on</span>
                    <span>Atur Jadwal</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alert Notifikasi Akses -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'akses_ditolak'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-rose-200 bg-danger-subtle text-danger">
                <span class="material-symbols-outlined text-base">lock</span>
                <span><strong>Akses Ditolak:</strong> Halaman tersebut dikhususkan untuk Administrator sistem.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'bukan_walikelas'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">info</span>
                <span><strong>Akses Dibatasi:</strong> Fitur Rekap & Catatan Wali Kelas hanya tersedia untuk guru yang telah ditugaskan membina rombel kelas.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Banner Khusus Wali Kelas -->
    <?php if ($user_wk): ?>
        <div class="bg-gradient-to-r from-primary-subtle/50 to-white rounded-xl border border-primary/20 p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-xl">assignment_ind</span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-primary block">Tugas Binaan Aktif</span>
                    <h3 class="text-sm md:text-base font-bold text-text-main">
                        Wali Kelas <?= htmlspecialchars($user_wk['jenjang']) ?> - <?= htmlspecialchars($user_wk['nama_kelas']) ?>
                    </h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Anda dapat memantau seluruh nilai mata pelajaran dari guru lain dan mengelola catatan siswa di kelas ini.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="walikelas.php" class="w-full sm:w-auto text-center px-4 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors shadow-xs">
                    Buka Rekap Kelas
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kartu Statistik Khusus Administrator -->
    <?php if ($is_admin): ?>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">badge</span>
                </div>
                <div>
                    <span class="text-[11px] text-text-muted font-medium block">Total Guru</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= $admin_stats['total_guru'] ?></span>
                </div>
            </div>
            <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                    <span class="material-symbols-outlined text-xl">folder_open</span>
                </div>
                <div>
                    <span class="text-[11px] text-text-muted font-medium block">Total Rombel</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= $admin_stats['total_kelas'] ?></span>
                </div>
            </div>
            <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-200">
                    <span class="material-symbols-outlined text-xl">group</span>
                </div>
                <div>
                    <span class="text-[11px] text-text-muted font-medium block">Total Murid</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= $admin_stats['total_siswa'] ?></span>
                </div>
            </div>
            <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                    <span class="material-symbols-outlined text-xl">book</span>
                </div>
                <div>
                    <span class="text-[11px] text-text-muted font-medium block">Total Mapel</span>
                    <span class="text-base font-bold text-text-main tabular-nums"><?= $admin_stats['total_mapel'] ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Area Kerja Utama: Formulir Pemilihan Kelas & Nilai -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Kolom Kiri: Formulir Seleksi Kelas & Kategori Nilai -->
        <div class="lg:col-span-7 flex flex-col gap-6">
            <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs">
                <div class="mb-5 pb-4 border-b border-border-main">
                    <h3 class="text-base font-bold text-text-main">Input Nilai Siswa</h3>
                    <p class="text-xs text-text-muted mt-1">Pilih kelas, mata pelajaran, dan kategori nilai yang ingin Anda isi.</p>
                </div>

                <form action="input_data.php" method="POST" class="flex flex-col gap-4">
                    
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main">Jenjang Pendidikan</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input checked class="peer sr-only" name="jenjang" type="radio" value="smp"/>
                                <div class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-center peer-checked:border-primary peer-checked:bg-primary-subtle peer-checked:text-primary transition-colors font-semibold text-xs min-h-[44px] flex items-center justify-center">
                                    SMP
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input class="peer sr-only" name="jenjang" type="radio" value="sma"/>
                                <div class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-center peer-checked:border-primary peer-checked:bg-primary-subtle peer-checked:text-primary transition-colors font-semibold text-xs min-h-[44px] flex items-center justify-center">
                                    SMA
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main" for="kelas">Kelas</label>
                        <select id="kelas" name="kelas" class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required>
                            <option value="" disabled selected>-- Pilih Kelas --</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main" for="mapel">Mata Pelajaran</label>
                        <select id="mapel" name="mapel" class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required>
                            <option value="" disabled selected>-- Pilih Mata Pelajaran --</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-text-main" for="kategori">Kategori Nilai</label>
                        <select id="kategori" name="kategori" class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <option value="h_uts">Nilai Harian UTS</option>
                            <option value="uts">Ujian Tengah Semester (UTS)</option>
                            <option value="tambahan_uts">Tambahan / Remedial UTS</option>
                            <option value="h_uas">Nilai Harian UAS</option>
                            <option value="uas">Ujian Akhir Semester (UAS)</option>
                            <option value="tambahan_uas">Tambahan / Remedial UAS</option>
                        </select>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white px-5 py-3 rounded-lg text-xs md:text-sm font-semibold flex items-center justify-center transition-colors shadow-xs min-h-[44px]">
                            Lanjutkan ke Pengisian Nilai
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Akses Cepat Jadwal Mengajar & Tindakan Berkala -->
        <div class="lg:col-span-5 flex flex-col gap-6">
            
            <!-- Daftar Jadwal Mengajar Guru -->
            <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-border-main">
                    <h3 class="text-sm font-bold text-text-main"><?= $is_admin ? 'Jadwal & Penugasan Mapel' : 'Jadwal Mengajar Anda' ?></h3>
                    <a href="jadwal.php" class="text-xs text-primary hover:underline font-semibold">Atur Jadwal</a>
                </div>

                <?php if (empty($jadwal_list)): ?>
                    <div class="p-6 text-center rounded-lg border border-dashed border-slate-200 bg-slate-50">
                        <p class="text-xs font-medium text-text-muted">Belum ada jadwal mengajar yang terdaftar.</p>
                        <a href="jadwal.php" class="mt-2 inline-block text-xs font-semibold text-primary hover:underline">
                            + Tambahkan Jadwal
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-border-main max-h-[300px] overflow-y-auto custom-scroll pr-1">
                        <?php foreach ($jadwal_list as $jdw): ?>
                            <div class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0 pr-2">
                                    <span class="text-xs font-semibold text-text-main block truncate">
                                        <?= htmlspecialchars($jdw['nama_kelas']) ?>
                                    </span>
                                    <span class="text-[11px] text-text-muted block truncate">
                                        <?= htmlspecialchars($jdw['nama_mapel']) ?> (<?= htmlspecialchars($jdw['jenjang']) ?>)
                                        <?php if ($is_admin): ?>
                                            &bull; <span class="text-slate-500 font-medium"><?= !empty($jdw['nama_guru']) ? htmlspecialchars($jdw['nama_guru']) : 'Belum Ada Guru' ?></span>
                                        <?php elseif (empty($jdw['user_id']) || (int)($jdw['is_manual'] ?? 0) === 1): ?>
                                            &bull; <span class="text-amber-600 font-medium"><?= empty($jdw['user_id']) ? 'Slot Terbuka (Binaan)' : 'Manual Binaan' ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <form action="input_data.php" method="POST" class="shrink-0">
                                    <input type="hidden" name="kelas" value="<?= $jdw['class_id'] ?>">
                                    <input type="hidden" name="mapel" value="<?= $jdw['subject_id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold text-primary hover:bg-slate-50 transition-colors">
                                        Isi Nilai
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($is_admin): ?>
            <!-- Pemeliharaan Semester -->
            <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-danger text-xl shrink-0 mt-0.5">cleaning_services</span>
                    <div>
                        <h4 class="text-xs font-bold text-text-main">Persiapan Semester Baru</h4>
                        <p class="text-[11px] text-text-muted mt-1 leading-relaxed">
                            Mengosongkan data nilai untuk memulai periode penilaian baru. Data siswa, kelas, dan jadwal mengajar tetap tersimpan aman.
                        </p>
                        <form action="proses_reset.php" method="POST" class="mt-3">
                            <input type="hidden" name="reset_semua_nilai" value="1">
                            <button type="submit" 
                                onclick="konfirmasiForm(event, 'Semua data nilai semester ini akan dikosongkan. Data siswa dan kelas tidak akan terhapus. Lanjutkan?')"
                                class="bg-slate-50 text-danger hover:bg-danger hover:text-white px-3.5 py-2 rounded-lg text-xs font-semibold border border-danger/30 transition-colors">
                                Kosongkan Data Nilai
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>

</main>

<script>
    const dataKelas = <?= $kelas_json ?>;
    const dataMapel = <?= $mapel_json ?>;

    const radioJenjang = document.querySelectorAll('input[name="jenjang"]');
    const selectKelas = document.getElementById('kelas');
    const selectMapel = document.getElementById('mapel');

    function updateDropdownKelas() {
        const checkedRadio = document.querySelector('input[name="jenjang"]:checked');
        if (!checkedRadio) return;
        
        const jenjangTerpilih = checkedRadio.value.toUpperCase();
        
        selectKelas.innerHTML = '<option value="" disabled selected>-- Pilih Kelas --</option>';
        selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mata Pelajaran --</option>';
        
        let adaKelas = false;

        dataKelas.forEach(kelas => {
            if (kelas.jenjang === jenjangTerpilih) {
                const option = document.createElement('option');
                option.value = kelas.id;
                option.textContent = kelas.nama_kelas;
                selectKelas.appendChild(option);
                adaKelas = true;
            }
        });

        if (!adaKelas) {
            selectKelas.innerHTML = '<option value="" disabled selected>Belum ada jadwal kelas ' + jenjangTerpilih + '</option>';
        }
    }

    function updateDropdownMapel() {
        const idKelasTerpilih = selectKelas.value;
        selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mata Pelajaran --</option>';
        
        if (idKelasTerpilih && dataMapel[idKelasTerpilih]) {
            dataMapel[idKelasTerpilih].forEach(mapel => {
                const option = document.createElement('option');
                option.value = mapel.id;
                option.textContent = mapel.nama;
                selectMapel.appendChild(option);
            });
        } else {
            selectMapel.innerHTML = '<option value="" disabled selected>Belum ada jadwal mapel untuk kelas ini.</option>';
        }
    }

    radioJenjang.forEach(radio => {
        radio.addEventListener('change', updateDropdownKelas);
    });
    
    selectKelas.addEventListener('change', updateDropdownMapel);

    updateDropdownKelas();

    // Fungsi Modal Panduan Pengajar
    function bukaModalPanduan(defaultTab = 'guru') {
        const modal = document.getElementById('modalPanduanPengajar');
        if (modal) {
            modal.classList.remove('hidden');
            gantiTabPanduan(defaultTab);
        }
    }

    function tutupModalPanduan() {
        const modal = document.getElementById('modalPanduanPengajar');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function gantiTabPanduan(tab) {
        const tabGuru = document.getElementById('tabContentGuru');
        const tabWali = document.getElementById('tabContentWali');
        const btnGuru = document.getElementById('btnTabGuru');
        const btnWali = document.getElementById('btnTabWali');

        if (tab === 'guru') {
            tabGuru.classList.remove('hidden');
            tabWali.classList.add('hidden');
            btnGuru.className = "flex-1 py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary bg-primary-subtle/30 transition-colors flex items-center justify-center gap-1.5";
            btnWali.className = "flex-1 py-2.5 px-4 text-xs font-semibold border-b-2 border-transparent text-text-muted hover:text-text-main transition-colors flex items-center justify-center gap-1.5";
        } else {
            tabGuru.classList.add('hidden');
            tabWali.classList.remove('hidden');
            btnWali.className = "flex-1 py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary bg-primary-subtle/30 transition-colors flex items-center justify-center gap-1.5";
            btnGuru.className = "flex-1 py-2.5 px-4 text-xs font-semibold border-b-2 border-transparent text-text-muted hover:text-text-main transition-colors flex items-center justify-center gap-1.5";
        }
    }
</script>

<!-- Modal Panduan Alur Kerja Pengajar -->
<div id="modalPanduanPengajar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-2xl max-w-2xl w-full flex flex-col max-h-[90vh] overflow-hidden animate-fadeIn">
        
        <!-- Header Modal -->
        <div class="p-5 border-b border-border-main flex items-center justify-between bg-slate-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-xl">menu_book</span>
                </div>
                <div>
                    <h3 class="text-sm md:text-base font-bold text-text-main">Panduan Alur Pengajar</h3>
                    <p class="text-[11px] text-text-muted">Petunjuk langkah awal untuk Guru Pengajar dan Wali Kelas</p>
                </div>
            </div>
            <button type="button" onclick="tutupModalPanduan()" class="text-text-muted hover:text-danger p-1.5 rounded-lg transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Tab Selector -->
        <div class="flex border-b border-border-main bg-white">
            <button type="button" id="btnTabGuru" onclick="gantiTabPanduan('guru')" class="flex-1 py-2.5 px-4 text-xs font-bold border-b-2 border-primary text-primary bg-primary-subtle/30 transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-base">school</span>
                <span>Alur Guru Mapel</span>
            </button>
            <button type="button" id="btnTabWali" onclick="gantiTabPanduan('wali')" class="flex-1 py-2.5 px-4 text-xs font-semibold border-b-2 border-transparent text-text-muted hover:text-text-main transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-base">assignment_ind</span>
                <span>Khusus Wali Kelas</span>
            </button>
        </div>

        <!-- Body Konten Panduan (Scrollable) -->
        <div class="p-5 sm:p-6 overflow-y-auto custom-scroll flex flex-col gap-4 text-xs">
            
            <!-- KONTEN TAB 1: GURU MAPEL -->
            <div id="tabContentGuru" class="flex flex-col gap-4">
                
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary font-bold flex items-center justify-center shrink-0 text-xs">
                        1
                    </div>
                    <div>
                        <h4 class="font-bold text-text-main text-xs">Tentukan Jadwal Mengajar Anda</h4>
                        <p class="text-text-muted text-[11px] mt-1 leading-relaxed">
                            Buka menu <strong class="text-text-main">Jadwal Mengajar</strong>. Pilih jenjang, kelas, dan mata pelajaran yang Anda ampu pada semester ini, lalu klik tombol Simpan.
                        </p>
                        <div class="mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 flex items-start gap-1.5">
                            <span class="material-symbols-outlined text-xs shrink-0 mt-0.5">info</span>
                            <span>Jika mata pelajaran Anda belum ada pada daftar pilihan, hubungi Administrator sistem untuk menambahkannya ke master mata pelajaran.</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary font-bold flex items-center justify-center shrink-0 text-xs">
                        2
                    </div>
                    <div>
                        <h4 class="font-bold text-text-main text-xs">Input & Simpan Nilai Siswa</h4>
                        <p class="text-text-muted text-[11px] mt-1 leading-relaxed">
                            Pada Dashboard, pilih jenjang, kelas, dan mata pelajaran yang sudah Anda ambil. Tentukan kategori ujian (Harian UTS, Ujian UTS, UAS, dll.) lalu masukkan nilai siswa per kelas.
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary font-bold flex items-center justify-center shrink-0 text-xs">
                        3
                    </div>
                    <div>
                        <h4 class="font-bold text-text-main text-xs">Pantau Analisis Hasil Belajar</h4>
                        <p class="text-text-muted text-[11px] mt-1 leading-relaxed">
                            Buka menu <strong class="text-text-main">Analisis Nilai</strong> untuk melihat ketuntasan belajar, nilai tertinggi/terendah, dan rata-rata kelas secara otomatis.
                        </p>
                    </div>
                </div>

            </div>

            <!-- KONTEN TAB 2: KHUSUS WALI KELAS -->
            <div id="tabContentWali" class="flex flex-col gap-4 hidden">
                
                <div class="p-4 rounded-xl border border-primary/20 bg-primary-subtle/20 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-primary text-white font-bold flex items-center justify-center shrink-0 text-xs">
                        1
                    </div>
                    <div>
                        <h4 class="font-bold text-text-main text-xs">Pantau Rekap Nilai Seluruh Mapel</h4>
                        <p class="text-text-muted text-[11px] mt-1 leading-relaxed">
                            Buka menu <strong class="text-text-main">Rekap Nilai Wali Kelas</strong>. Nilai yang telah diinput oleh guru mata pelajaran lain akan otomatis terkumpul di tabel kelas binaan Anda secara real-time.
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-600 text-white font-bold flex items-center justify-center shrink-0 text-xs">
                        2
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-900 text-xs">Entri Nilai Mapel Offline / Manual</h4>
                        <p class="text-amber-800 text-[11px] mt-1 leading-relaxed">
                            Jika ada guru yang belum memiliki akun aplikasi dan mengirim nilai secara offline (kertas/excel), mapel tersebut sementara berstatus titipan di akun Admin. Anda berhak langsung menginput nilainya lewat tombol edit pada tabel rekap kelas binaan Anda.
                        </p>
                        <p class="text-amber-700 text-[10px] mt-1.5 italic">
                            *Catatan: Saat guru bersangkutan mendaftar akun, Admin akan mengalihkan jadwal ke guru tersebut dan status Anda otomatis menjadi pemantau (read-only).
                        </p>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-start gap-3.5">
                    <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary font-bold flex items-center justify-center shrink-0 text-xs">
                        3
                    </div>
                    <div>
                        <h4 class="font-bold text-text-main text-xs">Jurnal Catatan Sikap & Perilaku Siswa</h4>
                        <p class="text-text-muted text-[11px] mt-1 leading-relaxed">
                            Gunakan menu <strong class="text-text-main">Catatan Siswa</strong> atau <strong class="text-text-main">Bulk Catatan</strong> untuk mencatat kedisiplinan dan kejadian penting. Rekapitulasi per siswa dapat dipantau di <strong class="text-text-main">Summary Catatan</strong>.
                        </p>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer Modal -->
        <div class="p-4 border-t border-border-main bg-slate-50/70 flex items-center justify-between gap-3">
            <span class="text-[11px] text-text-muted">Butuh bantuan lain? Hubungi Admin sekolah.</span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="tutupModalPanduan()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-200 hover:bg-slate-300 text-text-main transition-colors cursor-pointer">
                    Tutup
                </button>
                <a href="jadwal.php" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors cursor-pointer flex items-center gap-1">
                    <span>Atur Jadwal</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>

    </div>
</div>

<?php 
require_once '../components/footer.php'; 
?>