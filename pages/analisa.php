<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_GET['kelas'] ?? null;
$mapel_id = $_GET['mapel'] ?? null;

// Ambil data untuk Filter (Sama seperti dashboard)
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

// Jika Kelas dan Mapel dipilih, tampilkan SEMUA nilai (Mode Leger)
if ($class_id && $mapel_id) {
    $stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
    $stmt_info->execute([$class_id, $mapel_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $stmt_sched = $pdo->prepare("SELECT id FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
    $stmt_sched->execute([$user_id, $class_id, $mapel_id]);
    $schedule = $stmt_sched->fetch(PDO::FETCH_ASSOC);
    $schedule_id = $schedule['id'] ?? 0;

    // Tarik SEMUA kolom nilai sekaligus
    $stmt_siswa = $pdo->prepare("
        SELECT st.id, st.nama, 
               g.h_uts, g.uts, g.h_uas, g.uas, g.tambahan 
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
                <select id="kelas" name="kelas" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary">
                    <option value="" disabled selected>-- Pilih Kelas --</option>
                </select>
            </div>
            <div class="w-full md:w-1/3">
                <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Mata Pelajaran</label>
                <select id="mapel" name="mapel" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary">
                    <option value="" disabled selected>-- Pilih Mapel --</option>
                </select>
            </div>
            <div class="w-full md:w-1/3">
                <button type="submit" class="w-full bg-primary text-on-primary font-bold py-2.5 rounded-lg text-sm shadow-sm hover:bg-primary-container transition-all">Tampilkan Rekap</button>
            </div>
        </form>
    </div>

    <?php if ($class_id && $mapel_id && $info): ?>
    
    <div class="bg-primary/5 rounded-xl border border-primary/20 overflow-hidden">
        <button onclick="document.getElementById('syncArea').classList.toggle('hidden')" class="w-full bg-primary/10 px-4 py-3 flex justify-between items-center text-primary font-bold text-sm hover:bg-primary/20 transition-colors">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">sync_alt</span> Mode Sinkronisasi Urutan Excel</div>
            <span class="material-symbols-outlined">expand_more</span>
        </button>
        <div id="syncArea" class="hidden p-4 flex flex-col gap-3">
            <p class="text-xs text-on-surface-variant">Copy seluruh nama siswa dari Excel sekolah, lalu paste di kotak ini. Tabel akan otomatis mengurutkan baris sesuai urutan Excel Anda.</p>
            <textarea id="excelNames" rows="4" class="w-full bg-surface-container-lowest text-xs rounded-lg border border-outline-variant/30 p-3 focus:ring-primary font-mono" placeholder="Paste daftar nama dari Excel di sini..."></textarea>
            <button onclick="sesuaikanUrutan()" class="self-end bg-primary text-on-primary px-5 py-2 rounded-lg text-xs font-bold shadow hover:bg-primary-container">Sesuaikan Urutan</button>
            
            <div id="warningBox" class="hidden bg-error-container text-error text-xs p-3 rounded-lg border border-error/20 font-medium"></div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/20 overflow-hidden">
        <div class="p-4 border-b border-outline-variant/20 bg-surface-container-lowest flex justify-between items-center">
            <h2 class="font-bold text-sm text-on-surface"><?= $info['nama_kelas'] ?> - <?= $info['nama_mapel'] ?></h2>
            <span class="text-xs text-on-surface-variant font-medium"><?= count($students) ?> Siswa</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant/30 text-xs uppercase tracking-wider">
                        <th class="p-3 font-bold">No</th>
                        <th class="p-3 font-bold w-full">Nama Siswa</th>
                        <th class="p-3 text-center border-l border-outline-variant/20">
                            <div class="flex flex-col items-center gap-1 group">
                                <span class="font-bold">H.UTS</span>
                                <button onclick="copyKolom('col-huts')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors" title="Copy Kolom H.UTS"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-3 text-center border-l border-outline-variant/20">
                            <div class="flex flex-col items-center gap-1 group">
                                <span class="font-bold">UTS</span>
                                <button onclick="copyKolom('col-uts')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-3 text-center border-l border-outline-variant/20">
                            <div class="flex flex-col items-center gap-1 group">
                                <span class="font-bold">H.UAS</span>
                                <button onclick="copyKolom('col-huas')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-3 text-center border-l border-outline-variant/20">
                            <div class="flex flex-col items-center gap-1 group">
                                <span class="font-bold">UAS</span>
                                <button onclick="copyKolom('col-uas')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                        <th class="p-3 text-center border-l border-outline-variant/20">
                            <div class="flex flex-col items-center gap-1 group">
                                <span class="font-bold">TMBHN</span>
                                <button onclick="copyKolom('col-tmbhn')" class="text-primary hover:bg-primary/10 p-1 rounded transition-colors"><span class="material-symbols-outlined text-[16px]">content_copy</span></button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="tabelNilai" class="divide-y divide-outline-variant/10 text-on-surface">
                    <?php $no = 1; foreach ($students as $s): ?>
                    <tr class="hover:bg-surface-container-highest transition-colors data-row" data-nama="<?= htmlspecialchars(strtolower(trim($s['nama']))) ?>">
                        <td class="p-3 text-on-surface-variant nomor-urut"><?= $no++ ?></td>
                        <td class="p-3 font-medium"><?= htmlspecialchars($s['nama']) ?></td>
                        
                        <td class="p-3 text-center font-bold text-primary border-l border-outline-variant/10 col-huts"><?= $s['h_uts'] ?? '' ?></td>
                        <td class="p-3 text-center font-bold text-primary border-l border-outline-variant/10 col-uts"><?= $s['uts'] ?? '' ?></td>
                        <td class="p-3 text-center font-bold text-primary border-l border-outline-variant/10 col-huas"><?= $s['h_uas'] ?? '' ?></td>
                        <td class="p-3 text-center font-bold text-primary border-l border-outline-variant/10 col-uas"><?= $s['uas'] ?? '' ?></td>
                        <td class="p-3 text-center font-bold text-primary border-l border-outline-variant/10 col-tmbhn"><?= $s['tambahan'] ?? '' ?></td>
                    </tr>
                    <?php endphp; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
    // --- Lojik Dropdown Kelas & Mapel (Sama seperti dashboard) ---
    const dataKelas = <?= $kelas_json ?>;
    const dataMapel = <?= $mapel_json ?>;
    const selectKelas = document.getElementById('kelas');
    const selectMapel = document.getElementById('mapel');

    // Inisialisasi awal dropdown kelas (gabungan SMP & SMA)
    dataKelas.forEach(kelas => {
        const option = document.createElement('option');
        option.value = kelas.id;
        option.textContent = kelas.jenjang.toUpperCase() + ' - ' + kelas.nama_kelas;
        
        // Auto-select jika ada parameter GET
        if(kelas.id == "<?= $class_id ?>") option.selected = true;
        selectKelas.appendChild(option);
    });

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
    // Jalankan sekali saat load jika kelas sudah terpilih
    if("<?= $class_id ?>" !== "") updateDropdownMapel();


    // --- Fitur 1: Copy Per Kolom ---
    function copyKolom(namaClass) {
        const selCells = document.querySelectorAll('.' + namaClass);
        // Map nilai, ubah jadi string kosong jika null/kosong agar urutan Excel tidak rusak
        const values = Array.from(selCells).map(td => td.innerText.trim());
        
        const stringToCopy = values.join('\n');
        navigator.clipboard.writeText(stringToCopy).then(() => {
            alert('Berhasil! Kolom nilai disalin. Silakan Paste di Excel.');
        }).catch(err => {
            alert('Gagal menyalin: ', err);
        });
    }

    // --- Fitur 2: Chameleon Sort (Penyesuai Urutan) ---
    function sesuaikanUrutan() {
        const teks = document.getElementById('excelNames').value;
        if (!teks.trim()) {
            alert("Kotak teks masih kosong!"); return;
        }

        // Bersihkan dan jadikan huruf kecil untuk pencocokan akurat
        const excelArray = teks.split(/\r?\n/).map(n => n.trim().toLowerCase()).filter(n => n);
        
        const tbody = document.getElementById('tabelNilai');
        const rows = Array.from(tbody.querySelectorAll('tr.data-row'));
        
        let barisCocok = [];
        let barisSisa = [...rows];
        let namaTidakDitemukan = [];

        // Proses pencocokan urutan
        excelArray.forEach(namaExcel => {
            // Cari baris di tabel yang atribut data-nama nya sama dengan excel
            const indexPencarian = barisSisa.findIndex(r => r.getAttribute('data-nama') === namaExcel);
            
            if (indexPencarian > -1) {
                // Jika ketemu, cabut dari barisSisa, masukkan ke barisCocok
                barisCocok.push(barisSisa.splice(indexPencarian, 1)[0]);
            } else {
                // Jika dari Excel tidak ada di database kita
                namaTidakDitemukan.push(namaExcel);
            }
        });

        // RE-RENDER TABEL
        tbody.innerHTML = ''; // Kosongkan tabel
        let nomorBerapa = 1;

        // 1. Masukkan baris yang sudah terurut sesuai Excel
        barisCocok.forEach(row => {
            row.querySelector('.nomor-urut').innerText = nomorBerapa++;
            row.classList.add('bg-primary/5'); // Tandai visual bahwa ini hasil sinkronisasi
            tbody.appendChild(row);
        });

        // 2. Masukkan baris sisa (yang ada di database tapi tidak ada di paste-an Excel) di urutan paling bawah
        barisSisa.forEach(row => {
            row.querySelector('.nomor-urut').innerText = nomorBerapa++;
            row.classList.add('opacity-50', 'bg-surface-container-highest'); // Redupkan sisa
            tbody.appendChild(row);
        });

        // 3. Notifikasi Error Handling
        const warningBox = document.getElementById('warningBox');
        if (namaTidakDitemukan.length > 0) {
            warningBox.classList.remove('hidden');
            warningBox.innerHTML = `<b>PERHATIAN:</b> Ada ${namaTidakDitemukan.length} nama dari Excel yang ejaannya tidak ditemukan di database aplikasi (Tidak Ter-sinkron):<br> <ul class="list-disc pl-5 mt-1"><li>${namaTidakDitemukan.slice(0, 5).join('</li><li>')}</li>${namaTidakDitemukan.length > 5 ? '<li>...dan lainnya</li>' : ''}</ul> <br><b>Solusi:</b> Baris data sistem yang tidak tersinkron diletakkan di paling bawah tabel dengan warna redup. Silakan sesuaikan namanya manual.`;
        } else {
            warningBox.classList.add('hidden');
        }
    }
</script>

<?php require_once '../components/footer.php'; ?>