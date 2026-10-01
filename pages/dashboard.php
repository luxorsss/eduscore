<?php
session_start();

// PENJAGA PINTU: Tendang ke login jika belum ada tiket (session)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// 1. Panggil koneksi database
require_once '../config/koneksi.php'; 
$user_id = $_SESSION['user_id'];

// 2. Tarik Data KELAS & JENJANG berdasarkan Jadwal Guru ini
$stmt_kelas = $pdo->prepare("
    SELECT DISTINCT c.id, c.nama_kelas, c.jenjang 
    FROM teaching_schedules ts 
    JOIN classes c ON ts.class_id = c.id 
    WHERE ts.user_id = ?
    ORDER BY c.jenjang, c.nama_kelas
");
$stmt_kelas->execute([$user_id]);
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// 3. Tarik Pemetaan Kelas -> Mapel berdasarkan Jadwal Guru ini
$stmt_jadwal = $pdo->prepare("
    SELECT ts.id as schedule_id, ts.class_id, c.nama_kelas, c.jenjang, s.id as subject_id, s.nama_mapel 
    FROM teaching_schedules ts 
    JOIN classes c ON ts.class_id = c.id
    JOIN subjects s ON ts.subject_id = s.id 
    WHERE ts.user_id = ?
    ORDER BY c.jenjang, c.nama_kelas, s.nama_mapel
");
$stmt_jadwal->execute([$user_id]);
$jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data JSON untuk dibaca oleh JavaScript filter
$kelas_json = json_encode($kelas_list);

$mapel_per_kelas = [];
foreach ($jadwal_list as $row) {
    $mapel_per_kelas[$row['class_id']][] = [
        'id' => $row['subject_id'],
        'nama' => $row['nama_mapel']
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

        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 md:flex-none">
                <span class="text-[11px] font-medium text-text-muted block">Kelas Diampu</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= count($kelas_list) ?></span>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 md:flex-none">
                <span class="text-[11px] font-medium text-text-muted block">Jadwal Mapel</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= count($jadwal_list) ?></span>
            </div>
        </div>
    </div>

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
                    <h3 class="text-sm font-bold text-text-main">Jadwal Mengajar Anda</h3>
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
                                <div>
                                    <span class="text-xs font-semibold text-text-main block">
                                        <?= htmlspecialchars($jdw['nama_kelas']) ?>
                                    </span>
                                    <span class="text-[11px] text-text-muted block">
                                        <?= htmlspecialchars($jdw['nama_mapel']) ?> (<?= htmlspecialchars($jdw['jenjang']) ?>)
                                    </span>
                                </div>
                                <form action="input_data.php" method="POST">
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
</script>

<?php 
require_once '../components/footer.php'; 
?>