<?php
session_start();
require_once '../config/koneksi.php';

// Tentukan KKM (bisa diubah sesuai kebijakan sekolah)
$kkm = 60;

function getWarnaNilai($nilai, $kkm) {
    if ($nilai === null || $nilai === '') return 'text-slate-400 font-medium';
    
    $val = (float)$nilai;
    
    if ($val == 0) {
        return 'badge-grade-danger px-2.5 py-0.5 rounded-md font-bold';
    } elseif ($val < $kkm) {
        return 'badge-grade-warning px-2.5 py-0.5 rounded-md font-bold';
    } else {
        return 'badge-grade-pass px-2.5 py-0.5 rounded-md font-semibold';
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_GET['kelas'] ?? null;
$mapel_id = $_GET['mapel'] ?? null;

$stmt_kelas = $pdo->prepare("SELECT DISTINCT c.id, c.nama_kelas, c.jenjang FROM teaching_schedules ts JOIN classes c ON ts.class_id = c.id WHERE ts.user_id = ? ORDER BY c.jenjang, c.nama_kelas");
$stmt_kelas->execute([$user_id]);
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$stmt_jadwal = $pdo->prepare("SELECT ts.class_id, s.id as subject_id, s.nama_mapel FROM teaching_schedules ts JOIN subjects s ON ts.subject_id = s.id WHERE ts.user_id = ? ORDER BY s.nama_mapel");
$stmt_jadwal->execute([$user_id]);
$jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

$kelas_json = json_encode($kelas_list);
$mapel_per_kelas = [];
foreach ($jadwal_list as $row) {
    $mapel_per_kelas[$row['class_id']][] = ['id' => $row['subject_id'], 'nama' => $row['nama_mapel']];
}
$mapel_json = json_encode($mapel_per_kelas);

$students = [];
$info = null;

if ($class_id && $mapel_id) {
    $stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
    $stmt_info->execute([$class_id, $mapel_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $stmt_sched = $pdo->prepare("SELECT id FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
    $stmt_sched->execute([$user_id, $class_id, $mapel_id]);
    $schedule = $stmt_sched->fetch(PDO::FETCH_ASSOC);
    $schedule_id = $schedule['id'] ?? 0;

    $stmt_siswa = $pdo->prepare("
        SELECT st.id, st.nama, g.h_uts, g.uts, g.tambahan_uts, g.h_uas, g.uas, g.tambahan_uas 
        FROM students st 
        LEFT JOIN grades g ON g.student_id = st.id AND g.schedule_id = ? 
        WHERE st.class_id = ? 
        ORDER BY st.nama ASC
    ");
    $stmt_siswa->execute([$schedule_id, $class_id]);
    $students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Analisa Nilai - EduScore";
$page_heading = "Analisa & Rekapitulasi Nilai";
require_once '../components/header.php';
?>

<main class="max-w-6xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">

    <!-- Form Seleksi Kelas & Mapel -->
    <div class="bg-surface-card rounded-xl p-5 md:p-6 shadow-xs border border-border-main">
        <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-5">
                <label class="text-xs font-semibold text-text-main mb-1.5 block" for="kelas">Pilih Rombel / Kelas</label>
                <select id="kelas" name="kelas" class="w-full bg-white rounded-lg px-3 py-2.5 text-xs font-medium border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]" required>
                    <option value="" disabled selected>-- Pilih Kelas --</option>
                </select>
            </div>
            <div class="sm:col-span-4">
                <label class="text-xs font-semibold text-text-main mb-1.5 block" for="mapel">Mata Pelajaran</label>
                <select id="mapel" name="mapel" class="w-full bg-white rounded-lg px-3 py-2.5 text-xs font-medium border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]" required>
                    <option value="" disabled selected>-- Pilih Mapel --</option>
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-semibold py-2.5 px-4 rounded-lg text-xs shadow-xs transition-colors min-h-[44px]">
                    Tampilkan Analisa
                </button>
            </div>
        </form>
    </div>

    <?php if ($class_id && $mapel_id && $info): ?>
    
    <!-- Legend Status KKM Berkontras Tinggi -->
    <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2.5 text-xs">
            <span class="text-text-muted font-semibold text-[11px] uppercase tracking-wider">Kriteria:</span>
            <div class="inline-flex items-center gap-1.5 badge-grade-danger px-2.5 py-1 rounded-md text-[11px]">
                <span>Nilai 0 / Kosong</span>
            </div>
            <div class="inline-flex items-center gap-1.5 badge-grade-warning px-2.5 py-1 rounded-md text-[11px]">
                <span>Di Bawah KKM (&lt; <?= $kkm ?>)</span>
            </div>
            <div class="inline-flex items-center gap-1.5 badge-grade-pass px-2.5 py-1 rounded-md text-[11px]">
                <span>Tuntas (≥ <?= $kkm ?>)</span>
            </div>
        </div>

        <a href="input_data.php?kelas=<?= $class_id ?>&mapel=<?= $mapel_id ?>" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1 min-h-[36px]">
            <span class="material-symbols-outlined text-base">edit_square</span> Ubah Nilai di Kelas Ini
        </a>
    </div>

    <!-- Sinkronisasi Urutan Excel -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <button type="button" onclick="document.getElementById('syncArea').classList.toggle('hidden')" class="w-full px-4 py-3 min-h-[44px] flex justify-between items-center text-text-main font-semibold text-xs hover:bg-slate-50 transition-colors focus-ring">
            <div class="flex items-center gap-2 text-text-muted">
                <span class="material-symbols-outlined text-base text-primary">sync_alt</span> 
                <span>Sesuaikan Urutan Siswa Berdasarkan Excel Sekolah</span>
            </div>
            <span class="material-symbols-outlined text-text-muted text-base">expand_more</span>
        </button>
        <div id="syncArea" class="hidden p-4 bg-slate-50/50 border-t border-border-main flex flex-col gap-3">
            <p class="text-xs text-text-muted">Tempel daftar nama siswa dari kolom Excel sekolah untuk menyesuaikan susunan baris secara otomatis.</p>
            <textarea id="excelNames" rows="3" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono" placeholder="Tempel daftar nama dari Excel di sini..."></textarea>
            
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2">
                <button type="button" onclick="hapusMemori()" class="text-danger text-xs font-semibold hover:underline py-2 text-center sm:text-left">Hapus Memori Urutan</button>
                <button type="button" onclick="sesuaikanUrutan(false)" class="bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors min-h-[44px] inline-flex items-center justify-center">Terapkan Urutan</button>
            </div>
            
            <div id="warningBox" class="hidden bg-danger-subtle text-danger text-xs p-3 rounded-lg border border-danger/20 font-medium leading-relaxed"></div>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Komponen Nilai -->
    <div class="bg-surface-card rounded-xl shadow-xs border border-border-main overflow-hidden">
        <div class="p-4 border-b border-border-main bg-slate-50 flex flex-wrap justify-between items-center gap-2">
            <h3 class="font-bold text-xs md:text-sm text-text-main"><?= htmlspecialchars($info['nama_kelas']) ?> — <?= htmlspecialchars($info['nama_mapel']) ?></h3>
            <span class="text-xs text-text-muted bg-white border border-slate-200 px-2.5 py-1 rounded-md tabular-nums"><?= count($students) ?> Siswa Terdata</span>
        </div>
        
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse text-xs tabular-nums">
                <thead>
                    <tr class="bg-slate-50 text-text-muted text-[11px] uppercase tracking-wider font-semibold border-b border-border-main">
                        <th class="p-3 border-r border-border-main text-center w-12">No</th>
                        <th class="p-3 border-r border-border-main min-w-[140px] md:min-w-[180px] sticky left-0 z-20 bg-slate-50 shadow-xs">Nama Siswa</th>
                        <th class="p-2 border-r border-border-main text-center w-[85px]">
                            <div class="flex items-center justify-center gap-1">
                                <span>H.UTS</span>
                                <button type="button" onclick="copyKolom('col-huts')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom H.UTS" aria-label="Salin Kolom H.UTS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border-r border-border-main text-center w-[85px]">
                            <div class="flex items-center justify-center gap-1">
                                <span>UTS</span>
                                <button type="button" onclick="copyKolom('col-uts')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom UTS" aria-label="Salin Kolom UTS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border-r border-border-main text-center w-[85px] bg-primary-subtle/30 text-primary">
                            <div class="flex items-center justify-center gap-1">
                                <span>T.UTS</span>
                                <button type="button" onclick="copyKolom('col-tuts')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom T.UTS" aria-label="Salin Kolom T.UTS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border-r border-border-main text-center w-[85px]">
                            <div class="flex items-center justify-center gap-1">
                                <span>H.UAS</span>
                                <button type="button" onclick="copyKolom('col-huas')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom H.UAS" aria-label="Salin Kolom H.UAS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border-r border-border-main text-center w-[85px]">
                            <div class="flex items-center justify-center gap-1">
                                <span>UAS</span>
                                <button type="button" onclick="copyKolom('col-uas')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom UAS" aria-label="Salin Kolom UAS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 text-center w-[85px] bg-primary-subtle/30 text-primary">
                            <div class="flex items-center justify-center gap-1">
                                <span>T.UAS</span>
                                <button type="button" onclick="copyKolom('col-tuas')" class="w-7 h-7 inline-flex items-center justify-center text-text-muted hover:text-primary hover:bg-slate-200 rounded transition-colors" title="Salin Kolom T.UAS" aria-label="Salin Kolom T.UAS"><span class="material-symbols-outlined text-[15px]">content_copy</span></button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="tabelNilai" class="divide-y divide-border-main">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-text-muted text-xs">
                                Tidak ada siswa terdaftar pada kelas ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($students as $s): ?>
                        <tr class="hover:bg-slate-50 transition-colors data-row" data-nama="<?= htmlspecialchars(strtolower(trim($s['nama']))) ?>">
                            <td class="p-3 border-r border-border-main text-center text-text-muted nomor-urut"><?= $no++ ?></td>
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card shadow-xs"><?= htmlspecialchars($s['nama']) ?></td>
                            
                            <td class="p-2 border-r border-border-main text-center col-huts">
                                <div class="inline-block <?= getWarnaNilai($s['h_uts'], $kkm) ?>">
                                    <?= $s['h_uts'] !== null ? str_replace('.', ',', (float)$s['h_uts']) : '-' ?>
                                </div>
                            </td>
                            <td class="p-2 border-r border-border-main text-center col-uts">
                                <div class="inline-block <?= getWarnaNilai($s['uts'], $kkm) ?>">
                                    <?= $s['uts'] !== null ? str_replace('.', ',', (float)$s['uts']) : '-' ?>
                                </div>
                            </td>
                            <td class="p-2 border-r border-border-main text-center bg-primary-subtle/10 col-tuts">
                                <div class="inline-block <?= getWarnaNilai($s['tambahan_uts'], $kkm) ?>">
                                    <?= $s['tambahan_uts'] !== null ? str_replace('.', ',', (float)$s['tambahan_uts']) : '-' ?>
                                </div>
                            </td>

                            <td class="p-2 border-r border-border-main text-center col-huas">
                                <div class="inline-block <?= getWarnaNilai($s['h_uas'], $kkm) ?>">
                                    <?= $s['h_uas'] !== null ? str_replace('.', ',', (float)$s['h_uas']) : '-' ?>
                                </div>
                            </td>
                            <td class="p-2 border-r border-border-main text-center col-uas">
                                <div class="inline-block <?= getWarnaNilai($s['uas'], $kkm) ?>">
                                    <?= $s['uas'] !== null ? str_replace('.', ',', (float)$s['uas']) : '-' ?>
                                </div>
                            </td>
                            <td class="p-2 text-center bg-primary-subtle/10 col-tuas">
                                <div class="inline-block <?= getWarnaNilai($s['tambahan_uas'], $kkm) ?>">
                                    <?= $s['tambahan_uas'] !== null ? str_replace('.', ',', (float)$s['tambahan_uas']) : '-' ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="bg-surface-card border border-slate-200 rounded-xl p-10 text-center shadow-xs">
            <span class="material-symbols-outlined text-4xl text-slate-300">analytics</span>
            <h4 class="text-sm font-bold text-text-main mt-2">Pilih Kelas dan Mata Pelajaran</h4>
            <p class="text-xs text-text-muted mt-1 max-w-md mx-auto">
                Silakan pilih kelas dan mata pelajaran yang Anda ampu di atas untuk menganalisis perolehan nilai siswa.
            </p>
        </div>
    <?php endif; ?>

</main>

<script>
    const dataKelas = <?= $kelas_json ?>;
    const dataMapel = <?= $mapel_json ?>;
    const selectKelas = document.getElementById('kelas');
    const selectMapel = document.getElementById('mapel');

    function initDropdowns() {
        if (!selectKelas) return;
        dataKelas.forEach(kelas => {
            const option = document.createElement('option');
            option.value = kelas.id;
            option.textContent = kelas.jenjang.toUpperCase() + ' - ' + kelas.nama_kelas;
            if (kelas.id == "<?= $class_id ?>") option.selected = true;
            selectKelas.appendChild(option);
        });
        updateDropdownMapel();
    }

    function updateDropdownMapel() {
        if (!selectKelas || !selectMapel) return;
        const idKelas = selectKelas.value;
        selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mapel --</option>';
        if (idKelas && dataMapel[idKelas]) {
            dataMapel[idKelas].forEach(mapel => {
                const option = document.createElement('option');
                option.value = mapel.id;
                option.textContent = mapel.nama;
                if (mapel.id == "<?= $mapel_id ?>") option.selected = true;
                selectMapel.appendChild(option);
            });
        }
    }
    
    if (selectKelas) selectKelas.addEventListener('change', updateDropdownMapel);
    initDropdowns(); 

    function copyKolom(namaClass) {
        const cells = document.querySelectorAll('.' + namaClass);
        const values = Array.from(cells).map(td => td.innerText.trim());
        const stringToCopy = values.join('\n');
        navigator.clipboard.writeText(stringToCopy).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Kolom Berhasil Disalin',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    const STORAGE_KEY = 'memori_excel_eduscore';

    document.addEventListener('DOMContentLoaded', () => {
        const memoriTersimpan = sessionStorage.getItem(STORAGE_KEY);
        if (memoriTersimpan && document.getElementById('tabelNilai')) {
            const excelEl = document.getElementById('excelNames');
            if (excelEl) excelEl.value = memoriTersimpan;
            const syncEl = document.getElementById('syncArea');
            if (syncEl) syncEl.classList.remove('hidden');
            sesuaikanUrutan(true);
        }
    });

    function hapusMemori() {
        sessionStorage.removeItem(STORAGE_KEY);
        const excelEl = document.getElementById('excelNames');
        if (excelEl) excelEl.value = '';
        alert("Memori urutan berhasil dihapus! Refresh halaman untuk mengembalikan urutan abjad default.");
        location.reload();
    }

    function sesuaikanUrutan(isAuto = false) {
        const excelEl = document.getElementById('excelNames');
        if (!excelEl) return;
        const teks = excelEl.value;
        if (!teks.trim()) {
            if (!isAuto) alert("Kotak teks masih kosong!");
            return;
        }

        sessionStorage.setItem(STORAGE_KEY, teks);

        const excelArray = teks.split(/[\r\n\t]+/).map(n => n.trim().toLowerCase()).filter(n => n);
        const tbody = document.getElementById('tabelNilai');
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr.data-row'));
        
        let barisCocok = [];
        let barisSisa = [...rows];
        let namaTidakDitemukan = [];

        excelArray.forEach(namaExcel => {
            const index = barisSisa.findIndex(r => r.getAttribute('data-nama') === namaExcel);
            if (index > -1) {
                barisCocok.push(barisSisa.splice(index, 1)[0]);
            } else {
                namaTidakDitemukan.push(namaExcel);
            }
        });

        tbody.innerHTML = '';
        let no = 1;
        
        barisCocok.forEach(row => {
            row.querySelector('.nomor-urut').innerText = no++;
            row.classList.remove('opacity-50');
            row.classList.add('bg-primary-subtle/20'); 
            tbody.appendChild(row);
        });
        
        barisSisa.forEach(row => {
            row.querySelector('.nomor-urut').innerText = no++;
            row.classList.remove('bg-primary-subtle/20');
            row.classList.add('opacity-50', 'bg-slate-100');
            tbody.appendChild(row);
        });

        const warningBox = document.getElementById('warningBox');
        if (warningBox) {
            if (namaTidakDitemukan.length > 0) {
                warningBox.classList.remove('hidden');
                let listHTML = '<ul class="list-disc ml-5 mt-2 mb-2 text-danger font-semibold">';
                namaTidakDitemukan.slice(0, 5).forEach(nama => { listHTML += `<li>${nama}</li>`; });
                if (namaTidakDitemukan.length > 5) listHTML += `<li>... dan ${namaTidakDitemukan.length - 5} nama lainnya.</li>`;
                listHTML += '</ul>';

                warningBox.innerHTML = `
                    <div class="flex gap-2 items-start">
                        <span class="material-symbols-outlined text-base">error</span>
                        <div>
                            <b>Perhatian Sinkronisasi:</b><br>
                            Ada ${namaTidakDitemukan.length} nama dari Excel yang tidak ditemukan di sistem:
                            ${listHTML}
                        </div>
                    </div>
                `;
            } else {
                warningBox.classList.add('hidden');
            }
        }
    }
</script>

<?php require_once '../components/footer.php'; ?>