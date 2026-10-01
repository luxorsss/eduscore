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

        $matrix[$sid][$subid] = round($final_score, 2);
    }
}

$page_title = "Rekap Wali Kelas - EduScore";
$page_heading = "Rekapitulasi Nilai Wali Kelas";
require_once '../components/header.php';
?>

<main class="max-w-7xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">

    <!-- Form Pemilihan Kelas & Ujian -->
    <div class="bg-surface-card rounded-xl p-5 md:p-6 shadow-xs border border-border-main">
        <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
            <div class="sm:col-span-5">
                <label class="text-xs font-semibold text-text-main mb-1.5 block" for="selectKelas">Pilih Kelas Binaan</label>
                <select id="selectKelas" name="kelas" class="w-full bg-white rounded-lg px-3 py-2.5 text-xs font-medium border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]" required>
                    <option value="" disabled <?= empty($class_id) ? 'selected' : '' ?>>-- Pilih Kelas --</option>
                    <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($class_id == $k['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['jenjang']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-4">
                <label class="text-xs font-semibold text-text-main mb-1.5 block" for="selectTipe">Periode Ujian</label>
                <select id="selectTipe" name="tipe" class="w-full bg-white rounded-lg px-3 py-2.5 text-xs font-medium border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer min-h-[44px]">
                    <option value="UTS" <?= ($tipe_ujian == 'UTS') ? 'selected' : '' ?>>Ujian Tengah Semester (UTS)</option>
                    <option value="UAS" <?= ($tipe_ujian == 'UAS') ? 'selected' : '' ?>>Ujian Akhir Semester (UAS)</option>
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-semibold py-2.5 px-4 rounded-lg text-xs shadow-xs transition-colors min-h-[44px]">
                    Tampilkan Rekap
                </button>
            </div>
        </form>
    </div>

    <?php if ($class_id && !empty($students) && !empty($subjects)): ?>
    
    <!-- Kontrol Tampilan & KKM -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-surface-card p-4 rounded-xl border border-border-main shadow-xs">
        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg w-full md:w-auto">
            <button type="button" onclick="setMode('siswa')" id="btnModeSiswa" class="flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors">
                Orientasi Baris Siswa
            </button>
            <button type="button" onclick="setMode('mapel')" id="btnModeMapel" class="flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors">
                Orientasi Baris Mapel
            </button>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-300 shadow-xs">
                <label for="inputKkm" class="text-xs font-semibold text-text-muted">Batas KKM:</label>
                <input type="number" id="inputKkm" value="75" min="0" max="100" oninput="updateKkm()" class="w-12 bg-transparent text-xs font-bold text-primary border-none p-0 focus:ring-0 text-center outline-none tabular-nums">
            </div>

            <button type="button" onclick="copyHanyaNilai()" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-xs flex items-center gap-2 transition-colors min-h-[38px]">
                <span class="material-symbols-outlined text-base">content_copy</span> Salin Angka Murni
            </button>
        </div>
    </div>

    <!-- Sinkronisasi Urutan Kustom -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <button type="button" onclick="document.getElementById('syncAreaWali').classList.toggle('hidden')" class="w-full px-4 py-3 flex justify-between items-center text-text-main font-semibold text-xs hover:bg-slate-50 transition-colors focus-ring">
            <div class="flex items-center gap-2 text-text-muted">
                <span class="material-symbols-outlined text-base text-primary">tune</span> 
                <span>Sesuaikan Urutan Urut Siswa & Mapel (Format Excel)</span>
            </div>
            <span class="material-symbols-outlined text-text-muted text-base">expand_more</span>
        </button>
        <div id="syncAreaWali" class="hidden p-4 bg-slate-50/50 border-t border-border-main grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="urutSiswa" class="text-xs font-semibold text-text-muted mb-1 block">Urutan Siswa (Tempel dari Excel):</label>
                <textarea id="urutSiswa" rows="4" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2 font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
            </div>
            <div>
                <label for="urutMapel" class="text-xs font-semibold text-text-muted mb-1 block">Urutan Mapel (Tempel dari Excel):</label>
                <textarea id="urutMapel" rows="4" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2 font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end gap-3 mt-1">
                <button type="button" onclick="resetUrutan()" class="text-danger text-xs font-semibold hover:underline px-3 py-1.5">Reset Urutan Asli</button>
                <button type="button" onclick="terapkanUrutan()" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-xs font-semibold transition-colors">Terapkan Urutan</button>
            </div>
        </div>
    </div>

    <!-- Tabel Matriks Rekap Nilai -->
    <div class="bg-surface-card rounded-xl shadow-xs border border-border-main overflow-hidden">
        <div class="p-4 border-b border-border-main bg-slate-50 flex flex-wrap justify-between items-center gap-2">
            <div>
                <h3 class="font-bold text-xs md:text-sm text-text-main">
                    Rekap Nilai Akhir <?= htmlspecialchars($tipe_ujian) ?> — Kelas <?= htmlspecialchars($info_kelas) ?>
                </h3>
            </div>
            <span class="text-[11px] bg-slate-200 text-text-main px-2.5 py-1 rounded-md font-medium tabular-nums">
                Rumus: (Harian × 20%) + (Ujian × 80%) + Tambahan
            </span>
        </div>
        <div class="overflow-x-auto custom-scroll" id="tableContainer">
        </div>
    </div>
    
    <?php elseif($class_id): ?>
        <div class="bg-surface-card border border-slate-200 rounded-xl p-8 text-center shadow-xs">
            <span class="material-symbols-outlined text-4xl text-slate-300">inventory_2</span>
            <h4 class="text-sm font-bold text-text-main mt-2">Data Belum Lengkap</h4>
            <p class="text-xs text-text-muted mt-1 max-w-md mx-auto">
                Kelas ini belum memiliki daftar siswa terdaftar atau belum ada mata pelajaran yang dihubungkan di jadwal mengajar.
            </p>
        </div>
    <?php else: ?>
        <div class="bg-surface-card border border-slate-200 rounded-xl p-10 text-center shadow-xs">
            <span class="material-symbols-outlined text-4xl text-slate-300">school</span>
            <h4 class="text-sm font-bold text-text-main mt-2">Pilih Kelas untuk Menampilkan Rekap</h4>
            <p class="text-xs text-text-muted mt-1 max-w-md mx-auto">
                Silakan tentukan kelas binaan dan periode ujian di atas, lalu klik Tampilkan Rekap.
            </p>
        </div>
    <?php endif; ?>

</main>

<script>
    const rawStudents = <?= json_encode($students) ?>;
    const rawSubjects = <?= json_encode($subjects) ?>;
    const gradeMatrix = <?= json_encode($matrix) ?>;
    
    let currentMode = 'siswa'; 
    let currentStudents = [...rawStudents];
    let currentSubjects = [...rawSubjects];
    
    let kkmValue = 75;

    function updateKkm() {
        let val = parseInt(document.getElementById('inputKkm').value);
        kkmValue = isNaN(val) ? 0 : val;
        renderTable();
    }

    function getColorClass(score) {
        if (score === null || score === undefined) return 'text-slate-400';
        return score < kkmValue ? 'text-danger font-bold' : 'text-success font-semibold';
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
            btnSiswa.className = 'flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnMapel.className = 'flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        } else {
            btnMapel.className = 'flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnSiswa.className = 'flex-1 md:flex-none px-4 py-2 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        }
        renderTable();
    }

    function renderTable() {
        const container = document.getElementById('tableContainer');
        if (!container) return;

        let html = '<table class="w-full text-left border-collapse text-xs tabular-nums" id="rekapTable">';
        
        if (currentMode === 'siswa') {
            html += `<thead><tr class="bg-slate-50 text-text-muted text-[11px] uppercase tracking-wider font-semibold border-b border-border-main">
                        <th class="p-3 border-r border-border-main sticky left-0 z-20 bg-slate-50 min-w-[160px] max-w-[220px] shadow-xs">Nama Siswa</th>`;
            
            currentSubjects.forEach(sub => {
                html += `<th class="p-2.5 border-r border-border-main text-center min-w-[85px] max-w-[120px] whitespace-normal leading-tight" title="${sub.nama_mapel}">${sub.nama_mapel}</th>`;
            });
            html += `<th class="p-2.5 font-bold border-border-main text-center bg-primary-subtle text-primary min-w-[90px]">RATA-RATA</th>`;
            html += `</tr></thead><tbody class="divide-y divide-border-main">`;
            
            // Baris KKM
            html += `<tr class="bg-slate-50/80 text-text-main kkm-row font-semibold">
                        <td class="p-3 border-r border-border-main sticky left-0 z-10 bg-slate-100 shadow-xs font-bold text-primary">BATAS KKM</td>`;
            currentSubjects.forEach(() => {
                html += `<td class="p-2.5 border-r border-border-main text-center text-text-muted">${kkmValue}</td>`;
            });
            html += `<td class="p-2.5 text-center font-bold text-primary bg-primary-subtle">${kkmValue}</td>`;
            html += `</tr>`;

            // Baris Data Siswa
            currentStudents.forEach(stu => {
                let totalScore = 0;
                let count = 0;
                
                html += `<tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card whitespace-nowrap truncate max-w-[220px] shadow-xs" title="${stu.nama}">${stu.nama}</td>`;
                
                currentSubjects.forEach(sub => {
                    let score = gradeMatrix[stu.id] && gradeMatrix[stu.id][sub.id] !== undefined ? gradeMatrix[stu.id][sub.id] : null;
                    if (score !== null) {
                        totalScore += parseFloat(score);
                        count++;
                    }
                    html += `<td class="p-2.5 border-r border-border-main text-center whitespace-nowrap data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });

                let avg = count > 0 ? (totalScore / count) : null;
                html += `<td class="p-2.5 text-center font-bold whitespace-nowrap ${getColorClass(avg)} bg-slate-50">${fNum(avg)}</td>`;
                html += `</tr>`;
            });
        } else {
            html += `<thead><tr class="bg-slate-50 text-text-muted text-[11px] uppercase tracking-wider font-semibold border-b border-border-main">
                        <th class="p-3 border-r border-border-main sticky left-0 z-20 bg-slate-50 min-w-[150px] max-w-[200px] shadow-xs">Mata Pelajaran</th>
                        <th class="p-2.5 border-r border-border-main bg-primary-subtle text-primary text-center min-w-[65px]">KKM</th>`;
            
            currentStudents.forEach(stu => {
                html += `<th class="p-2.5 border-r border-border-main text-center min-w-[90px] max-w-[120px] whitespace-normal leading-tight" title="${stu.nama}">${stu.nama}</th>`;
            });
            html += `</tr></thead><tbody class="divide-y divide-border-main">`;
            
            let colTotals = {};
            let colCounts = {};

            currentSubjects.forEach(sub => {
                html += `<tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card whitespace-normal leading-tight shadow-xs">${sub.nama_mapel}</td>
                            <td class="p-2.5 border-r border-border-main text-center font-semibold text-text-muted bg-slate-50">${kkmValue}</td>`; 
                
                currentStudents.forEach(stu => {
                    let score = gradeMatrix[stu.id] && gradeMatrix[stu.id][sub.id] !== undefined ? gradeMatrix[stu.id][sub.id] : null;
                    if (score !== null) {
                        colTotals[stu.id] = (colTotals[stu.id] || 0) + parseFloat(score);
                        colCounts[stu.id] = (colCounts[stu.id] || 0) + 1;
                    }
                    html += `<td class="p-2.5 border-r border-border-main text-center whitespace-nowrap data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });
                html += `</tr>`;
            });

            // Baris Rata-rata
            html += `<tr class="bg-slate-50/80 font-semibold avg-row">
                        <td class="p-3 border-r border-border-main font-bold text-primary sticky left-0 z-10 bg-slate-100 shadow-xs">RATA-RATA SISWA</td>
                        <td class="p-2.5 border-r border-border-main text-center text-text-muted">${kkmValue}</td>`;
            currentStudents.forEach(stu => {
                let avg = colCounts[stu.id] > 0 ? (colTotals[stu.id] / colCounts[stu.id]) : null;
                html += `<td class="p-2.5 border-r border-border-main text-center font-bold whitespace-nowrap ${getColorClass(avg)}">${fNum(avg)}</td>`;
            });
            html += `</tr>`;
        }
        
        html += `</tbody></table>`;
        container.innerHTML = html;
    }

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
        Swal.fire({
            icon: 'success',
            title: 'Urutan Disesuaikan',
            text: 'Urutan tabel berhasil disesuaikan dengan daftar Excel.',
            timer: 1500,
            showConfirmButton: false
        });
    }

    function resetUrutan() {
        document.getElementById('urutSiswa').value = '';
        document.getElementById('urutMapel').value = '';
        currentStudents = [...rawStudents];
        currentSubjects = [...rawSubjects];
        renderTable();
    }

    function copyHanyaNilai() {
        const table = document.getElementById('rekapTable');
        if (!table) return;
        
        let tsv = "";
        const rows = table.querySelectorAll('tbody tr:not(.avg-row):not(.kkm-row)');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('.data-cell');
            if (cells.length === 0) return; 

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
                title: 'Nilai Berhasil Disalin',
                text: 'Data angka murni siap ditempel ke aplikasi atau lembar kerja lain.',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    if (document.getElementById('tableContainer')) {
        renderTable();
    }
</script>

<?php require_once '../components/footer.php'; ?>