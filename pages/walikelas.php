<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_GET['kelas'] ?? null;
$tipe_ujian = $_GET['tipe'] ?? 'UTS'; // Default UTS

// 1. Ambil Data Kelas untuk Dropdown
$stmt_kelas = $pdo->query("SELECT id, nama_kelas, jenjang FROM classes ORDER BY jenjang, nama_kelas");
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$students = [];
$subjects = [];
$matrix = [];
$info_kelas = null;

if ($class_id) {
    // Info Kelas
    $stmt_info = $pdo->prepare("SELECT nama_kelas FROM classes WHERE id = ?");
    $stmt_info->execute([$class_id]);
    $info_kelas = $stmt_info->fetchColumn();

    // Ambil Siswa
    $stmt_siswa = $pdo->prepare("SELECT id, nama FROM students WHERE class_id = ? ORDER BY nama ASC");
    $stmt_siswa->execute([$class_id]);
    $students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Mapel yang diajarkan di kelas ini
    $stmt_mapel = $pdo->prepare("
        SELECT DISTINCT s.id, s.nama_mapel 
        FROM teaching_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.class_id = ?
        ORDER BY s.nama_mapel ASC
    ");
    $stmt_mapel->execute([$class_id]);
    $subjects = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Nilai dan Hitung Rumus
    $stmt_nilai = $pdo->prepare("
        SELECT g.*, st.id as student_id, ts.subject_id
        FROM grades g
        JOIN students st ON g.student_id = st.id
        JOIN teaching_schedules ts ON g.schedule_id = ts.id
        WHERE st.class_id = ?
    ");
    $stmt_nilai->execute([$class_id]);
    $raw_grades = $stmt_nilai->fetchAll(PDO::FETCH_ASSOC);

    // Proses perhitungan: (Harian * 20%) + (Ujian * 80%) + Tambahan -> Maksimal 100
    foreach ($raw_grades as $row) {
        $sid = $row['student_id'];
        $subid = $row['subject_id'];
        $score = 0;

        if ($tipe_ujian === 'UTS') {
            $h = (float)($row['h_uts'] ?? 0);
            $u = (float)($row['uts'] ?? 0);
            $t = (float)($row['tambahan_uts'] ?? 0);
        } else {
            $h = (float)($row['h_uas'] ?? 0);
            $u = (float)($row['uas'] ?? 0);
            $t = (float)($row['tambahan_uas'] ?? 0);
        }

        $calc = ($h * 0.20) + ($u * 0.80) + $t;
        $final_score = min(100, $calc); // Limit max 100

        // Format ke 2 desimal atau buang desimal jika .00
        $matrix[$sid][$subid] = round($final_score, 2);
    }
}

$page_title = "EduScore - Rekap Wali Kelas";
require_once '../components/header.php';
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
        <button onclick="toggleSidebar()" class="md:hidden p-2 text-on-surface-variant"><span class="material-symbols-outlined">menu</span></button>
        <span class="font-bold text-primary text-lg">Panel Wali Kelas</span>
    </div>
</nav>

<main class="max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">

    <div class="bg-surface-container-lowest rounded-xl p-4 md:p-6 shadow-sm border border-outline-variant/20">
        <form action="" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-2/5">
                <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Pilih Kelas</label>
                <select name="kelas" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary cursor-pointer" required>
                    <option value="" disabled selected>-- Pilih Kelas --</option>
                    <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($class_id == $k['id']) ? 'selected' : '' ?>>
                            <?= $k['jenjang'] ?> - <?= $k['nama_kelas'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-full md:w-2/5">
                <label class="text-xs font-bold text-on-surface-variant uppercase mb-1 block">Periode Ujian</label>
                <select name="tipe" class="w-full bg-surface-container-highest rounded-lg px-3 py-2.5 text-sm border-0 focus:ring-2 focus:ring-primary cursor-pointer">
                    <option value="UTS" <?= ($tipe_ujian == 'UTS') ? 'selected' : '' ?>>Ujian Tengah Semester (UTS)</option>
                    <option value="UAS" <?= ($tipe_ujian == 'UAS') ? 'selected' : '' ?>>Ujian Akhir Semester (UAS)</option>
                </select>
            </div>
            <div class="w-full md:w-1/5">
                <button type="submit" class="w-full bg-primary text-on-primary font-bold py-2.5 rounded-lg text-sm shadow-sm hover:bg-primary-container transition-all">Generate Rekap</button>
            </div>
        </form>
    </div>

    <?php if ($class_id && !empty($students) && !empty($subjects)): ?>
    
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-surface-container-low p-3 rounded-xl border border-outline-variant/20">
        <div class="flex items-center gap-2 bg-surface-container-highest p-1 rounded-lg">
            <button onclick="setMode('siswa')" id="btnModeSiswa" class="px-4 py-1.5 text-xs font-bold rounded-md bg-surface text-primary shadow-sm transition-all">Siswa di Samping (Baris)</button>
            <button onclick="setMode('mapel')" id="btnModeMapel" class="px-4 py-1.5 text-xs font-bold rounded-md text-on-surface-variant hover:text-on-surface transition-all">Mapel di Samping (Baris)</button>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
            <div class="flex items-center gap-2 bg-surface-container-lowest px-3 py-1.5 rounded-lg border border-outline-variant/30 shadow-sm">
                <label class="text-[10px] font-bold text-on-surface-variant uppercase">KKM:</label>
                <input type="number" id="inputKkm" value="75" oninput="updateKkm()" class="w-12 bg-transparent text-sm font-bold text-primary border-none p-0 focus:ring-0 text-center outline-none">
            </div>

            <button onclick="copyHanyaNilai()" class="bg-tertiary text-on-primary px-4 py-2 rounded-lg text-xs font-bold shadow-sm flex items-center gap-2 hover:bg-tertiary/90 transition-all">
                <span class="material-symbols-outlined text-[16px]">content_copy</span> Copy Angka 
            </button>
        </div>
    </div>

    <div class="bg-primary/5 rounded-xl border border-primary/20 overflow-hidden">
        <button onclick="document.getElementById('syncAreaWali').classList.toggle('hidden')" class="w-full bg-primary/10 px-4 py-3 flex justify-between items-center text-primary font-bold text-sm hover:bg-primary/20 transition-colors">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">tune</span> Custom Urutan Siswa & Mapel</div>
            <span class="material-symbols-outlined">expand_more</span>
        </button>
        <div id="syncAreaWali" class="hidden p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-[10px] font-bold text-primary uppercase mb-1 block">Urutan Siswa (Paste dari Excel)</label>
                <textarea id="urutSiswa" rows="4" class="w-full bg-surface-container-lowest text-xs rounded-lg border border-outline-variant/30 p-2 font-mono"></textarea>
            </div>
            <div>
                <label class="text-[10px] font-bold text-primary uppercase mb-1 block">Urutan Mapel (Paste dari Excel)</label>
                <textarea id="urutMapel" rows="4" class="w-full bg-surface-container-lowest text-xs rounded-lg border border-outline-variant/30 p-2 font-mono"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                <button onclick="resetUrutan()" class="text-error text-[11px] font-bold hover:underline px-4">Reset Default</button>
                <button onclick="terapkanUrutan()" class="bg-primary text-on-primary px-5 py-2 rounded-lg text-xs font-bold shadow hover:bg-primary-container">Terapkan Urutan</button>
            </div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="p-3 border-b border-outline-variant/30 bg-surface-container-lowest flex justify-between items-center">
            <h2 class="font-bold text-xs md:text-sm text-on-surface">Rekap Akhir <?= $tipe_ujian ?> - <?= $info_kelas ?></h2>
            <span class="text-[10px] bg-primary/10 text-primary px-2 py-1 rounded font-bold">Rumus: (Harian x 20%) + (Ujian x 80%) + Tambahan</span>
        </div>
        <div class="overflow-x-auto" id="tableContainer">
            </div>
    </div>
    
    <?php elseif($class_id): ?>
        <div class="bg-error-container text-error p-4 rounded-xl text-center font-bold text-sm">
            Belum ada data siswa atau mata pelajaran di kelas ini.
        </div>
    <?php endif; ?>

</main>

<script>
    // Inisialisasi Data dari PHP
    const rawStudents = <?= json_encode($students) ?>;
    const rawSubjects = <?= json_encode($subjects) ?>;
    const gradeMatrix = <?= json_encode($matrix) ?>;
    
    let currentMode = 'siswa'; 
    let currentStudents = [...rawStudents];
    let currentSubjects = [...rawSubjects];
    
    // Default KKM
    let kkmValue = 75;

    function updateKkm() {
        let val = parseInt(document.getElementById('inputKkm').value);
        kkmValue = isNaN(val) ? 0 : val;
        renderTable();
    }

    function getColorClass(score) {
        if (score === null || score === undefined) return 'text-on-surface-variant/40';
        return score < kkmValue ? 'text-error font-bold' : 'text-success font-bold';
    }

    function fNum(num) {
        if (num === null || num === undefined) return '-';
        return parseFloat(num).toFixed(2).replace('.', ',').replace(',00', '');
    }

    function setMode(mode) {
        currentMode = mode;
        const btnSiswa = document.getElementById('btnModeSiswa');
        const btnMapel = document.getElementById('btnModeMapel');
        
        if (mode === 'siswa') {
            btnSiswa.className = 'px-4 py-1.5 text-xs font-bold rounded-md bg-surface text-primary shadow-sm transition-all';
            btnMapel.className = 'px-4 py-1.5 text-xs font-bold rounded-md text-on-surface-variant hover:text-on-surface transition-all';
        } else {
            btnMapel.className = 'px-4 py-1.5 text-xs font-bold rounded-md bg-surface text-primary shadow-sm transition-all';
            btnSiswa.className = 'px-4 py-1.5 text-xs font-bold rounded-md text-on-surface-variant hover:text-on-surface transition-all';
        }
        renderTable();
    }

    function renderTable() {
        const container = document.getElementById('tableContainer');
        let html = '<table class="w-full text-left border-collapse whitespace-nowrap text-sm" id="rekapTable">';
        
        if (currentMode === 'siswa') {
            // --- MODE SISWA DI SAMPING (BARIS) ---
            html += `<thead><tr class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase tracking-wider">
                        <th class="p-3 font-bold border border-outline-variant/30 sticky left-0 z-20 bg-surface-container-low min-w-[200px]">Nama Siswa</th>`;
            currentSubjects.forEach(sub => {
                html += `<th class="p-2 font-bold border border-outline-variant/30 text-center">${sub.nama_mapel}</th>`;
            });
            html += `<th class="p-2 font-black border border-outline-variant/30 text-center bg-primary/10 text-primary">RATA-RATA</th>`;
            html += `</tr></thead><tbody class="text-on-surface">`;
            
            // Baris KKM
            html += `<tr class="bg-primary/5 text-primary">
                        <td class="p-3 border border-outline-variant/30 font-black text-xs md:text-sm sticky left-0 z-10 bg-primary/10">NILAI KKM</td>`;
            currentSubjects.forEach(sub => {
                html += `<td class="p-2 border border-outline-variant/30 text-center font-bold data-cell">${kkmValue}</td>`;
            });
            // Hapus class data-cell di KKM rata-rata agar tidak ikut dicopy
            html += `<td class="p-2 border border-outline-variant/30 text-center font-black">${kkmValue}</td>`;
            html += `</tr>`;

            // Baris Data Siswa
            currentStudents.forEach(stu => {
                let totalScore = 0;
                let count = 0;
                
                html += `<tr class="hover:bg-surface-container-highest transition-colors">
                            <td class="p-3 border border-outline-variant/30 font-bold text-xs md:text-sm sticky left-0 z-10 bg-surface-container-lowest">${stu.nama}</td>`;
                
                currentSubjects.forEach(sub => {
                    let score = gradeMatrix[stu.id] && gradeMatrix[stu.id][sub.id] !== undefined ? gradeMatrix[stu.id][sub.id] : null;
                    if(score !== null) {
                        totalScore += parseFloat(score);
                        count++;
                    }
                    html += `<td class="p-2 border border-outline-variant/30 text-center data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });

                let avg = count > 0 ? (totalScore / count) : null;
                // Hapus class data-cell di nilai rata-rata agar tidak ikut dicopy
                html += `<td class="p-2 border border-outline-variant/30 text-center font-black ${getColorClass(avg)} bg-primary/5">${fNum(avg)}</td>`;
                html += `</tr>`;
            });
        } else {
            // --- MODE MAPEL DI SAMPING (BARIS) ---
            html += `<thead><tr class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase tracking-wider">
                        <th class="p-3 font-bold border border-outline-variant/30 sticky left-0 z-20 bg-surface-container-low min-w-[150px]">Mata Pelajaran</th>
                        <th class="p-3 font-bold border border-outline-variant/30 bg-primary/10 text-primary text-center">KKM</th>`;
            currentStudents.forEach(stu => {
                html += `<th class="p-2 font-bold border border-outline-variant/30 text-center truncate max-w-[120px]" title="${stu.nama}">${stu.nama}</th>`;
            });
            html += `</tr></thead><tbody class="text-on-surface">`;
            
            let colTotals = {};
            let colCounts = {};

            currentSubjects.forEach(sub => {
                html += `<tr class="hover:bg-surface-container-highest transition-colors">
                            <td class="p-3 border border-outline-variant/30 font-bold text-xs md:text-sm sticky left-0 z-10 bg-surface-container-lowest">${sub.nama_mapel}</td>
                            <td class="p-2 border border-outline-variant/30 text-center font-bold text-primary bg-primary/5 data-cell">${kkmValue}</td>`;
                
                currentStudents.forEach(stu => {
                    let score = gradeMatrix[stu.id] && gradeMatrix[stu.id][sub.id] !== undefined ? gradeMatrix[stu.id][sub.id] : null;
                    if(score !== null) {
                        colTotals[stu.id] = (colTotals[stu.id] || 0) + parseFloat(score);
                        colCounts[stu.id] = (colCounts[stu.id] || 0) + 1;
                    }
                    html += `<td class="p-2 border border-outline-variant/30 text-center data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });
                html += `</tr>`;
            });

            // Tambahkan class avg-row untuk baris rata-rata agar mudah diblokir saat di-copy
            html += `<tr class="bg-primary/5 avg-row">
                        <td class="p-3 border border-outline-variant/30 font-black text-primary sticky left-0 z-10 bg-primary/10">RATA-RATA SISWA</td>
                        <td class="p-2 border border-outline-variant/30 text-center font-bold text-primary">${kkmValue}</td>`;
            currentStudents.forEach(stu => {
                let avg = colCounts[stu.id] > 0 ? (colTotals[stu.id] / colCounts[stu.id]) : null;
                // Hapus data-cell
                html += `<td class="p-2 border border-outline-variant/30 text-center font-black ${getColorClass(avg)}">${fNum(avg)}</td>`;
            });
            html += `</tr>`;
        }
        
        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    // --- LOGIKA CUSTOM URUTAN ---
    function customSort(originalArray, textInput, fieldName) {
        const lines = textInput.split(/\r?\n/).map(n => n.trim().toLowerCase()).filter(n => n);
        if (lines.length === 0) return [...originalArray];
        let matched = [];
        let remaining = [...originalArray];
        lines.forEach(line => {
            const index = remaining.findIndex(item => item[fieldName].toLowerCase() === line);
            if (index > -1) matched.push(remaining.splice(index, 1)[0]);
        });
        return matched.concat(remaining);
    }

    function terapkanUrutan() {
        const valSiswa = document.getElementById('urutSiswa').value;
        const valMapel = document.getElementById('urutMapel').value;
        currentStudents = customSort(rawStudents, valSiswa, 'nama');
        currentSubjects = customSort(rawSubjects, valMapel, 'nama_mapel');
        renderTable();
        alert('Urutan berhasil diterapkan!');
    }

    function resetUrutan() {
        document.getElementById('urutSiswa').value = '';
        document.getElementById('urutMapel').value = '';
        currentStudents = [...rawStudents];
        currentSubjects = [...rawSubjects];
        renderTable();
    }

    // --- LOGIKA COPY HANYA ANGKA (TANPA RATA-RATA) ---
    function copyHanyaNilai() {
        const table = document.getElementById('rekapTable');
        if(!table) return;
        
        let tsv = "";
        // Abaikan baris yang merupakan rata-rata (mode mapel)
        const rows = table.querySelectorAll('tbody tr:not(.avg-row)');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('.data-cell');
            if (cells.length === 0) return; // Mencegah baris kosong

            let rowData = [];
            cells.forEach(cell => {
                let val = cell.innerText.trim();
                if (val === '-') val = ''; 
                rowData.push(val);
            });
            tsv += rowData.join("\t") + "\n";
        });
        
        navigator.clipboard.writeText(tsv).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Tercopy!',
                text: 'Data nilai berhasil disalin (Rata-rata tidak diikutkan).',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    if(document.getElementById('tableContainer')) {
        renderTable();
    }
</script>

<?php require_once '../components/footer.php'; ?>