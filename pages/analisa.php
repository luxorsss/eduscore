<?php
session_start();
require_once '../config/koneksi.php';

// Tentukan KKM (bisa diubah sesuai kebijakan sekolah)
$kkm = 60;

function getWarnaNilai($nilai, $kkm) {
    if ($nilai === null || $nilai === '') return ''; // Jika kosong, tidak ada warna
    
    $val = (float)$nilai;
    
    if ($val == 0) {
        return 'bg-red-100 text-red-800 border border-red-200'; // Blok 0
    } elseif ($val < $kkm) {
        return 'bg-orange-100 text-orange-800 border border-orange-200'; // Di bawah KKM
    } elseif ($val < 90) {
        return 'bg-green-100 text-green-800 border border-green-200'; // Di atas KKM - 89
    } else {
        return 'bg-blue-100 text-blue-800 border border-blue-200'; // 90 - 100
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_GET['kelas'] ?? null;
$mapel_id = $_GET['mapel'] ?? null;

// 1. Ambil Data Kelas & Mapel 
$stmt_kelas = $pdo->prepare("SELECT DISTINCT c.id, c.nama_kelas, c.jenjang FROM teaching_schedules ts JOIN classes c ON ts.class_id = c.id WHERE ts.user_id = ?");
$stmt_kelas->execute([$user_id]);
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$stmt_jadwal = $pdo->prepare("SELECT ts.class_id, s.id as subject_id, s.nama_mapel FROM teaching_schedules ts JOIN subjects s ON ts.subject_id = s.id WHERE ts.user_id = ?");
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

// 2. Tarik Data Nilai (Sudah Termasuk tambahan_uts dan tambahan_uas)
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

$page_title = "EduScore - Analisa & Rekap";
require_once '../components/header.php';
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center gap-4">
        <button onclick="toggleSidebar()" class="md:hidden p-2 text-on-surface-variant"><span class="material-symbols-outlined">menu</span></button>
        <span class="font-bold text-primary text-lg">Rekapitulasi Nilai</span>
    </div>
</nav>

<main class="max-w-6xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">

    <div class="bg-surface-container-lowest rounded-xl p-4 md:p-6 shadow-sm border border-outline-variant/20">
        <form action="" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-1/3">
                <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Pilih Kelas</label>
                <select id="kelas" name="kelas" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary cursor-pointer">
                    <option value="" disabled selected>-- Pilih Kelas --</option>
                </select>
            </div>
            <div class="w-full md:w-1/3">
                <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Mata Pelajaran</label>
                <select id="mapel" name="mapel" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary cursor-pointer">
                    <option value="" disabled selected>-- Pilih Mapel --</option>
                </select>
            </div>
            <div class="w-full md:w-1/3">
                <button type="submit" class="w-full bg-primary text-on-primary font-bold py-2.5 rounded-lg text-sm shadow-sm hover:bg-primary-container transition-all">Tampilkan Rekap</button>
            </div>
        </form>
    </div>

    <?php if ($class_id && $mapel_id && $info): ?>
    
    <div class="flex flex-wrap gap-4 mb-4 text-[10px] font-bold uppercase tracking-wider">
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-red-100 border border-red-200 rounded"></span> Nilai 0</div>
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-orange-100 border border-orange-200 rounded"></span> Di Bawah KKM (<?= $kkm ?>)</div>
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-100 border border-green-200 rounded"></span> Tuntas (<?= $kkm ?>-89)</div>
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-blue-100 border border-blue-200 rounded"></span> Sangat Baik (90-100)</div>
    </div>

    <div class="bg-primary/5 rounded-xl border border-primary/20 overflow-hidden">
        <button onclick="document.getElementById('syncArea').classList.toggle('hidden')" class="w-full bg-primary/10 px-4 py-3 flex justify-between items-center text-primary font-bold text-sm hover:bg-primary/20 transition-colors">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">sync_alt</span> Mode Sinkronisasi Urutan Excel Sekolah</div>
            <span class="material-symbols-outlined">expand_more</span>
        </button>
        <div id="syncArea" class="hidden p-4 flex flex-col gap-3">
            <p class="text-[11px] text-on-surface-variant">Paste daftar nama siswa dari Excel sekolah ke kotak ini. Tabel akan otomatis mengurutkan baris sesuai Excel Anda.</p>
            <textarea id="excelNames" rows="4" class="w-full bg-surface-container-lowest text-xs rounded-lg border border-outline-variant/30 p-3 focus:ring-primary font-mono" placeholder="Paste daftar nama dari Excel di sini..."></textarea>
            
            <div class="flex justify-between items-center mt-1">
                <button onclick="hapusMemori()" class="text-error text-[11px] font-bold hover:underline px-2">Hapus Memori Sinkronisasi</button>
                <button onclick="sesuaikanUrutan(false)" class="bg-primary text-on-primary px-5 py-2 rounded-lg text-xs font-bold shadow hover:bg-primary-container">Sesuaikan Urutan</button>
            </div>
            
            <div id="warningBox" class="hidden bg-error-container text-error text-[12px] p-4 rounded-lg border border-error/20 font-medium leading-relaxed"></div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="p-3 border-b border-outline-variant/30 bg-surface-container-lowest flex justify-between items-center">
            <h2 class="font-bold text-xs md:text-sm text-on-surface"><?= $info['nama_kelas'] ?> - <?= $info['nama_mapel'] ?></h2>
            <span class="text-[10px] text-on-surface-variant font-medium bg-surface-container px-2 py-1 rounded-md"><?= count($students) ?> Siswa</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase tracking-wider">
                        <th class="p-3 font-bold border border-outline-variant/30 text-center">No</th>
                        <th class="p-3 font-bold border border-outline-variant/30 min-w-[200px]">Nama Siswa</th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px]">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold">H.UTS</span>
                                <button onclick="copyKolom('col-huts')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px]">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold">UTS</span>
                                <button onclick="copyKolom('col-uts')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px] bg-primary/5">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold text-primary">T.UTS</span>
                                <button onclick="copyKolom('col-tuts')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px]">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold">H.UAS</span>
                                <button onclick="copyKolom('col-huas')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px]">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold">UAS</span>
                                <button onclick="copyKolom('col-uas')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-2 border border-outline-variant/30 text-center w-[80px] bg-primary/5">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-bold text-primary">T.UAS</span>
                                <button onclick="copyKolom('col-tuas')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="tabelNilai" class="text-on-surface">
                    <?php $no = 1; foreach ($students as $s): ?>
                    <tr class="hover:bg-surface-container-highest transition-colors data-row group" data-nama="<?= htmlspecialchars(strtolower(trim($s['nama']))) ?>">
                        <td class="p-3 border border-outline-variant/30 text-center text-on-surface-variant nomor-urut"><?= $no++ ?></td>
                        <td class="p-3 border border-outline-variant/30 font-medium text-xs md:text-sm"><?= htmlspecialchars($s['nama']) ?></td>
                        
                        <td class="p-2 border border-outline-variant/30 text-center col-huts">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['h_uts'], $kkm) ?>">
                                <?= $s['h_uts'] !== null ? str_replace('.', ',', (float)$s['h_uts']) : '' ?>
                            </div>
                        </td>
                        <td class="p-2 border border-outline-variant/30 text-center col-uts">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['uts'], $kkm) ?>">
                                <?= $s['uts'] !== null ? str_replace('.', ',', (float)$s['uts']) : '' ?>
                            </div>
                        </td>
                        <td class="p-2 border border-outline-variant/30 text-center bg-primary/5 col-tuts">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['tambahan_uts'], $kkm) ?>">
                                <?= $s['tambahan_uts'] !== null ? str_replace('.', ',', (float)$s['tambahan_uts']) : '' ?>
                            </div>
                        </td>

                        <td class="p-2 border border-outline-variant/30 text-center col-huas">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['h_uas'], $kkm) ?>">
                                <?= $s['h_uas'] !== null ? str_replace('.', ',', (float)$s['h_uas']) : '' ?>
                            </div>
                        </td>
                        <td class="p-2 border border-outline-variant/30 text-center col-uas">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['uas'], $kkm) ?>">
                                <?= $s['uas'] !== null ? str_replace('.', ',', (float)$s['uas']) : '' ?>
                            </div>
                        </td>
                        <td class="p-2 border border-outline-variant/30 text-center bg-primary/5 col-tuas">
                            <div class="inline-block px-3 py-1 rounded-md font-bold text-xs <?= getWarnaNilai($s['tambahan_uas'], $kkm) ?>">
                                <?= $s['tambahan_uas'] !== null ? str_replace('.', ',', (float)$s['tambahan_uas']) : '' ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
    const dataKelas = <?= $kelas_json ?>;
    const dataMapel = <?= $mapel_json ?>;
    const selectKelas = document.getElementById('kelas');
    const selectMapel = document.getElementById('mapel');

    // Inisialisasi Dropdown
    function initDropdowns() {
        dataKelas.forEach(kelas => {
            const option = document.createElement('option');
            option.value = kelas.id;
            option.textContent = kelas.jenjang.toUpperCase() + ' - ' + kelas.nama_kelas;
            if(kelas.id == "<?= $class_id ?>") option.selected = true;
            selectKelas.appendChild(option);
        });
        updateDropdownMapel();
    }

    function updateDropdownMapel() {
        const idKelas = selectKelas.value;
        selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mapel --</option>';
        if (idKelas && dataMapel[idKelas]) {
            dataMapel[idKelas].forEach(mapel => {
                const option = document.createElement('option');
                option.value = mapel.id;
                option.textContent = mapel.nama;
                if(mapel.id == "<?= $mapel_id ?>") option.selected = true;
                selectMapel.appendChild(option);
            });
        }
    }
    
    selectKelas.addEventListener('change', updateDropdownMapel);
    initDropdowns(); 

    // Fitur Copy Kolom
    function copyKolom(namaClass) {
        const cells = document.querySelectorAll('.' + namaClass);
        const values = Array.from(cells).map(td => td.innerText.trim());
        const stringToCopy = values.join('\n');
        navigator.clipboard.writeText(stringToCopy).then(() => {
            // Animasi tombol copy agar user tahu sudah ter-klik
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] text-green-600">check</span>';
            setTimeout(() => btn.innerHTML = originalHTML, 1500);
        });
    }

    // --- Fitur Chameleon Sort (Ditingkatkan dengan Memori Otomatis) ---
    
    const STORAGE_KEY = 'memori_excel_eduscore';

    // 1. Eksekusi Otomatis saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
        const memoriTersimpan = sessionStorage.getItem(STORAGE_KEY);
        // Jika ada memori dan tabel nilai sedang tampil di layar
        if (memoriTersimpan && document.getElementById('tabelNilai')) {
            document.getElementById('excelNames').value = memoriTersimpan;
            document.getElementById('syncArea').classList.remove('hidden'); // Buka kotaknya biar kelihatan
            sesuaikanUrutan(true); // true = mode otomatis (tanpa alert)
        }
    });

    // 2. Fungsi Hapus Memori
    function hapusMemori() {
        sessionStorage.removeItem(STORAGE_KEY);
        document.getElementById('excelNames').value = '';
        alert("Memori urutan berhasil dihapus! Jika Anda refresh halaman ini, tabel akan kembali ke urutan abjad default.");
    }

    // 3. Fungsi Utama Sortir
    function sesuaikanUrutan(isAuto = false) {
        const teks = document.getElementById('excelNames').value;
        if (!teks.trim()) {
            if(!isAuto) alert("Kotak teks masih kosong!");
            return;
        }

        // SIMPAN KE MEMORI (Agar tidak hilang saat ganti mapel)
        sessionStorage.setItem(STORAGE_KEY, teks);

        const excelArray = teks.split(/\r?\n/).map(n => n.trim().toLowerCase()).filter(n => n);
        const tbody = document.getElementById('tabelNilai');
        if(!tbody) return; // Cegah error jika tabel belum dirender
        
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
            row.classList.add('bg-primary/5'); 
            tbody.appendChild(row);
        });
        
        barisSisa.forEach(row => {
            row.querySelector('.nomor-urut').innerText = no++;
            row.classList.remove('bg-primary/5');
            row.classList.add('opacity-50', 'bg-surface-container-highest');
            tbody.appendChild(row);
        });

        const warningBox = document.getElementById('warningBox');
        if (namaTidakDitemukan.length > 0) {
            warningBox.classList.remove('hidden');
            let listHTML = '<ul class="list-disc ml-5 mt-2 mb-2 text-error font-bold">';
            namaTidakDitemukan.slice(0, 5).forEach(nama => { listHTML += `<li>${nama}</li>`; });
            if (namaTidakDitemukan.length > 5) listHTML += `<li>... dan ${namaTidakDitemukan.length - 5} nama lainnya.</li>`;
            listHTML += '</ul>';

            warningBox.innerHTML = `
                <div class="flex gap-2 items-start">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <div>
                        <b>SINKRONISASI TIDAK SEMPURNA!</b><br>
                        Ada ${namaTidakDitemukan.length} nama dari Excel yang ejaannya tidak ditemukan di database:
                        ${listHTML}
                    </div>
                </div>
            `;
        } else {
            warningBox.classList.add('hidden');
        }
    }
</script>

<?php require_once '../components/footer.php'; ?>