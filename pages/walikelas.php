<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

if (!$is_admin && $wali_kelas) {
    // Guru wali kelas terkunci otomatis pada kelas binaannya
    $class_id = (int)$wali_kelas['id'];
} else {
    $class_id = isset($_GET['kelas']) && $_GET['kelas'] !== '' ? (int)$_GET['kelas'] : null;
}
$tipe_ujian = $_GET['tipe'] ?? 'UTS';

// Data kelas untuk dropdown
if ($is_admin) {
    $stmt_kelas = $pdo->query("SELECT id, nama_kelas, jenjang FROM classes ORDER BY jenjang, nama_kelas");
    $kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);
} else {
    $kelas_list = [$wali_kelas];
}

$students = [];
$subjects = [];
$matrix = [];
$matrix_akhir = [];
$matrix_ujian = [];
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

    // Ambil Mapel yang diajarkan di kelas ini beserta data pengampu
    $stmt_mapel = $pdo->prepare("
        SELECT DISTINCT s.id, s.nama_mapel, ts.user_id as teacher_id, ts.is_manual, u.nama_lengkap as nama_guru, u.role as guru_role
        FROM teaching_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        LEFT JOIN users u ON ts.user_id = u.id
        WHERE ts.class_id = ?
        ORDER BY s.nama_mapel ASC
    ");
    $stmt_mapel->execute([$class_id]);
    $raw_subs = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);
    $subjects = [];
    foreach ($raw_subs as $rs) {
        $can_edit = $is_admin || ($user_id == $rs['teacher_id']) || empty($rs['teacher_id']) || ((int)$rs['is_manual'] === 1);
        $rs['can_edit'] = $can_edit;
        $subjects[] = $rs;
    }

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

    // Matrix nilai akhir & Matrix nilai ujian murni
    $matrix_akhir = [];
    $matrix_ujian = [];

    // Proses perhitungan: (Harian * 20%) + (Ujian * 80%) + Tambahan -> Maksimal 100
    foreach ($raw_grades as $row) {
        $sid = $row['student_id'];
        $subid = $row['subject_id'];

        if ($tipe_ujian === 'UTS') {
            $h_raw = $row['h_uts'];
            $u_raw = $row['uts'];
            $t_raw = $row['tambahan_uts'];
        } else {
            $h_raw = $row['h_uas'];
            $u_raw = $row['uas'];
            $t_raw = $row['tambahan_uas'];
        }

        // Cek apakah ada komponen nilai yang diisi untuk periode ini
        $has_grade = ($h_raw !== null && $h_raw !== '') || 
                     ($u_raw !== null && $u_raw !== '') || 
                     ($t_raw !== null && $t_raw !== '');

        if ($has_grade) {
            $h = (float)($h_raw ?? 0);
            $u = (float)($u_raw ?? 0);
            $t = (float)($t_raw ?? 0);

            $calc = ($h * 0.20) + ($u * 0.80) + $t;
            $final_score = min(100, $calc); // Limit max 100
            $matrix_akhir[$sid][$subid] = round($final_score, 2);
        } else {
            $matrix_akhir[$sid][$subid] = null;
        }

        $matrix_ujian[$sid][$subid] = ($u_raw !== null && $u_raw !== '') ? round((float)$u_raw, 2) : null;
    }

    $matrix = $matrix_akhir; // Kompatibilitas
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
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <!-- Orientasi Baris -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                <button type="button" onclick="setMode('siswa')" id="btnModeSiswa" class="px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors">
                    Orientasi Baris Siswa
                </button>
                <button type="button" onclick="setMode('mapel')" id="btnModeMapel" class="px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors">
                    Orientasi Baris Mapel
                </button>
            </div>

            <!-- Tipe Nilai (Akhir vs Ujian Murni) -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                <button type="button" onclick="setScoreType('akhir')" id="btnScoreAkhir" class="px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors">
                    Nilai Akhir
                </button>
                <button type="button" onclick="setScoreType('ujian')" id="btnScoreUjian" class="px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors">
                    Nilai Murni Ujian
                </button>
            </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
            <div id="kkmContainer" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-300 shadow-xs">
                <label for="inputKkm" class="text-xs font-semibold text-text-muted">Batas KKM:</label>
                <input type="number" id="inputKkm" value="60" min="0" max="100" oninput="updateKkm()" class="w-12 bg-transparent text-xs font-bold text-primary border-none p-0 focus:ring-0 text-center outline-none tabular-nums">
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
                <div class="flex justify-between items-center mb-1">
                    <label for="urutSiswa" class="text-xs font-semibold text-text-muted">Urutan Siswa (Bebas Kolom / Baris Excel):</label>
                    <span id="badgeTransposeSiswa" class="text-[10px] text-text-muted">Mendukung tempel horizontal</span>
                </div>
                <textarea id="urutSiswa" rows="4" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2 font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Bisa tempel 1 kolom menurun ATAU 1 baris menyamping dari Excel..."></textarea>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-[10px] text-text-muted">Format menyamping (Tab/Excel) otomatis diubah jadi vertikal.</span>
                    <button type="button" onclick="formatTranspose('urutSiswa')" class="text-[10px] font-semibold text-primary hover:underline">Rapikan Baris</button>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="urutMapel" class="text-xs font-semibold text-text-muted">Urutan Mapel (Bebas Kolom / Baris Excel):</label>
                    <span id="badgeTransposeMapel" class="text-[10px] text-text-muted">Mendukung tempel horizontal</span>
                </div>
                <textarea id="urutMapel" rows="4" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2 font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Bisa tempel 1 kolom menurun ATAU 1 baris menyamping dari Excel..."></textarea>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-[10px] text-text-muted">Format menyamping (Tab/Excel) otomatis diubah jadi vertikal.</span>
                    <button type="button" onclick="formatTranspose('urutMapel')" class="text-[10px] font-semibold text-primary hover:underline">Rapikan Baris</button>
                </div>
            </div>
            <div class="md:col-span-2 flex flex-wrap justify-between items-center gap-3 mt-1 pt-2 border-t border-slate-200">
                <span class="text-[11px] text-text-muted">💡 <strong>Tips:</strong> Langsung blok deret nama/mapel horizontal di Excel lalu Ctrl+C dan Ctrl+V di sini, sistem langsung memisahkannya otomatis.</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="resetUrutan()" class="text-danger text-xs font-semibold hover:underline px-3 py-1.5">Reset Urutan Asli</button>
                    <button type="button" onclick="terapkanUrutan()" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-xs transition-colors">Terapkan Urutan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Pintasan Entri Nilai Mapel Kelas Binaan -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <button type="button" onclick="document.getElementById('areaAksiMapel').classList.toggle('hidden')" class="w-full px-4 py-3 flex justify-between items-center text-text-main font-semibold text-xs hover:bg-slate-50 transition-colors focus-ring">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-primary">edit_square</span> 
                <span>Entri Nilai Mapel Kelas Ini (<?= count($subjects) ?> Mapel Terdaftar)</span>
            </div>
            <span class="material-symbols-outlined text-text-muted text-base">expand_more</span>
        </button>
        <div id="areaAksiMapel" class="hidden p-4 bg-slate-50/50 border-t border-border-main grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($subjects as $s): ?>
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <span class="font-bold text-xs text-text-main block truncate"><?= htmlspecialchars($s['nama_mapel']) ?></span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <?php if (empty($s['teacher_id'])): ?>
                                <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                    🟡 Belum Ada Guru (Bisa Diisi Wali Kelas)
                                </span>
                            <?php elseif ((int)$s['is_manual'] === 1): ?>
                                <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                    Manual (Titipan Disetor)
                                </span>
                            <?php else: ?>
                                <span class="text-[10px] text-text-muted truncate">
                                    Pengampu: <?= htmlspecialchars($s['nama_guru']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($s['can_edit']): ?>
                        <a href="input_data.php?kelas=<?= $class_id ?>&mapel=<?= $s['id'] ?>" class="px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-[11px] font-semibold shadow-xs flex items-center gap-1 shrink-0 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">edit</span> Isi Nilai
                        </a>
                    <?php else: ?>
                        <span class="text-[11px] text-text-muted italic flex items-center gap-1 shrink-0 bg-slate-100 px-2 py-1 rounded">
                            <span class="material-symbols-outlined text-[13px]">lock</span> Hanya Guru Mapel
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabel Matriks Rekap Nilai -->
    <div class="bg-surface-card rounded-xl shadow-xs border border-border-main overflow-hidden">
        <div class="p-4 border-b border-border-main bg-slate-50 flex flex-wrap justify-between items-center gap-2">
            <div>
                <h3 id="tableTitle" class="font-bold text-xs md:text-sm text-text-main">
                    Rekap Nilai Akhir <?= htmlspecialchars($tipe_ujian) ?> — Kelas <?= htmlspecialchars($info_kelas) ?>
                </h3>
            </div>
            <span id="tableBadge" class="text-[11px] bg-slate-200 text-text-main px-2.5 py-1 rounded-md font-medium tabular-nums">
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
    const matrixAkhir = <?= json_encode($matrix_akhir) ?>;
    const matrixUjian = <?= json_encode($matrix_ujian) ?>;
    const tipeUjianText = <?= json_encode($tipe_ujian) ?>;
    const infoKelasText = <?= json_encode($info_kelas) ?>;
    const currentClassId = <?= (int)($class_id ?? 0) ?>;
    
    let currentMode = 'siswa'; 
    let currentScoreType = 'akhir'; // 'akhir' atau 'ujian'
    let currentStudents = [...rawStudents];
    let currentSubjects = [...rawSubjects];
    
    let kkmValue = 60;

    function updateKkm() {
        let val = parseInt(document.getElementById('inputKkm').value);
        kkmValue = isNaN(val) ? 0 : val;
        renderTable();
    }

    function getColorClass(score) {
        if (score === null || score === undefined) return 'text-slate-400';
        if (currentScoreType === 'ujian') {
            return 'text-text-main font-semibold';
        }
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
            btnSiswa.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnMapel.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        } else {
            btnMapel.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnSiswa.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
        }
        renderTable();
    }

    function setScoreType(type) {
        currentScoreType = type;
        const btnAkhir = document.getElementById('btnScoreAkhir');
        const btnUjian = document.getElementById('btnScoreUjian');
        const kkmContainer = document.getElementById('kkmContainer');
        const tableTitle = document.getElementById('tableTitle');
        const tableBadge = document.getElementById('tableBadge');

        if (type === 'akhir') {
            btnAkhir.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnUjian.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
            if (kkmContainer) kkmContainer.classList.remove('hidden');
            if (tableTitle) tableTitle.textContent = `Rekap Nilai Akhir ${tipeUjianText} — Kelas ${infoKelasText}`;
            if (tableBadge) tableBadge.textContent = 'Rumus: (Harian × 20%) + (Ujian × 80%) + Tambahan';
        } else {
            btnUjian.className = 'px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-primary shadow-xs transition-colors';
            btnAkhir.className = 'px-3 py-1.5 text-xs font-semibold rounded-md text-text-muted hover:text-text-main transition-colors';
            if (kkmContainer) kkmContainer.classList.add('hidden');
            if (tableTitle) tableTitle.textContent = `Rekap Nilai Murni Ujian ${tipeUjianText} — Kelas ${infoKelasText}`;
            if (tableBadge) tableBadge.textContent = `Nilai Murni Ujian (${tipeUjianText}) Tanpa Campuran`;
        }
        renderTable();
    }

    function renderTable() {
        const container = document.getElementById('tableContainer');
        if (!container) return;

        const activeMatrix = currentScoreType === 'akhir' ? matrixAkhir : matrixUjian;
        let html = '<table class="w-full text-left border-collapse text-xs tabular-nums" id="rekapTable">';
        
        if (currentMode === 'siswa') {
            html += `<thead><tr class="bg-slate-50 text-text-muted text-[11px] uppercase tracking-wider font-semibold border-b border-border-main">
                        <th class="p-3 border-r border-border-main sticky left-0 z-20 bg-slate-50 min-w-[160px] max-w-[220px] shadow-xs">Nama Siswa</th>`;
            
            currentSubjects.forEach(sub => {
                let actionHtml = '';
                if (sub.can_edit) {
                    actionHtml = `<div class="mt-1"><a href="input_data.php?kelas=${currentClassId}&mapel=${sub.id}" class="text-[10px] font-semibold text-primary hover:underline inline-flex items-center gap-0.5 bg-white border border-slate-200 px-1.5 py-0.5 rounded shadow-2xs">Isi</a></div>`;
                }
                html += `<th class="p-2.5 border-r border-border-main text-center min-w-[85px] max-w-[120px] whitespace-normal leading-tight" title="${sub.nama_mapel}">
                    <div class="font-bold truncate">${sub.nama_mapel}</div>
                    ${actionHtml}
                </th>`;
            });
            if (currentScoreType === 'akhir') {
                html += `<th class="p-2.5 font-bold border-border-main text-center bg-primary-subtle text-primary min-w-[90px]">RATA-RATA</th>`;
            }
            html += `</tr></thead><tbody class="divide-y divide-border-main">`;
            
            // Baris KKM (hanya tampil di mode nilai akhir)
            if (currentScoreType === 'akhir') {
                html += `<tr class="bg-slate-50/80 text-text-main kkm-row font-semibold">
                            <td class="p-3 border-r border-border-main sticky left-0 z-10 bg-slate-100 shadow-xs font-bold text-primary">BATAS KKM</td>`;
                currentSubjects.forEach(() => {
                    html += `<td class="p-2.5 border-r border-border-main text-center text-text-muted">${kkmValue}</td>`;
                });
                html += `<td class="p-2.5 text-center font-bold text-primary bg-primary-subtle">${kkmValue}</td>`;
                html += `</tr>`;
            }

            // Baris Data Siswa
            currentStudents.forEach(stu => {
                let totalScore = 0;
                let count = 0;
                
                html += `<tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card whitespace-nowrap truncate max-w-[220px] shadow-xs" title="${stu.nama}">${stu.nama}</td>`;
                
                currentSubjects.forEach(sub => {
                    let score = activeMatrix[stu.id] && activeMatrix[stu.id][sub.id] !== undefined ? activeMatrix[stu.id][sub.id] : null;
                    if (score !== null) {
                        totalScore += parseFloat(score);
                        count++;
                    }
                    html += `<td class="p-2.5 border-r border-border-main text-center whitespace-nowrap data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });

                if (currentScoreType === 'akhir') {
                    let avg = count > 0 ? (totalScore / count) : null;
                    html += `<td class="p-2.5 text-center font-bold whitespace-nowrap ${getColorClass(avg)} bg-slate-50">${fNum(avg)}</td>`;
                }
                html += `</tr>`;
            });
        } else {
            html += `<thead><tr class="bg-slate-50 text-text-muted text-[11px] uppercase tracking-wider font-semibold border-b border-border-main">
                        <th class="p-3 border-r border-border-main sticky left-0 z-20 bg-slate-50 min-w-[150px] max-w-[200px] shadow-xs">Mata Pelajaran</th>`;
            
            if (currentScoreType === 'akhir') {
                html += `<th class="p-2.5 border-r border-border-main bg-primary-subtle text-primary text-center min-w-[65px]">KKM</th>`;
            }
            
            currentStudents.forEach(stu => {
                html += `<th class="p-2.5 border-r border-border-main text-center min-w-[90px] max-w-[120px] whitespace-normal leading-tight" title="${stu.nama}">${stu.nama}</th>`;
            });
            html += `</tr></thead><tbody class="divide-y divide-border-main">`;
            
            let colTotals = {};
            let colCounts = {};

            currentSubjects.forEach(sub => {
                let actionHtml = '';
                if (sub.can_edit) {
                    actionHtml = `<a href="input_data.php?kelas=${currentClassId}&mapel=${sub.id}" class="text-[10px] font-semibold text-primary hover:underline inline-flex items-center gap-0.5 bg-slate-100 hover:bg-white border border-slate-200 px-1.5 py-0.5 rounded shadow-2xs shrink-0">Isi Nilai</a>`;
                }
                html += `<tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card whitespace-normal leading-tight shadow-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <span>${sub.nama_mapel}</span>
                                    ${actionHtml}
                                </div>
                            </td>`;
                
                if (currentScoreType === 'akhir') {
                    html += `<td class="p-2.5 border-r border-border-main text-center font-semibold text-text-muted bg-slate-50">${kkmValue}</td>`;
                }
                
                currentStudents.forEach(stu => {
                    let score = activeMatrix[stu.id] && activeMatrix[stu.id][sub.id] !== undefined ? activeMatrix[stu.id][sub.id] : null;
                    if (score !== null) {
                        colTotals[stu.id] = (colTotals[stu.id] || 0) + parseFloat(score);
                        colCounts[stu.id] = (colCounts[stu.id] || 0) + 1;
                    }
                    html += `<td class="p-2.5 border-r border-border-main text-center whitespace-nowrap data-cell ${getColorClass(score)}">${fNum(score)}</td>`;
                });
                html += `</tr>`;
            });

            // Baris Rata-rata (hanya tampil di mode nilai akhir)
            if (currentScoreType === 'akhir') {
                html += `<tr class="bg-slate-50/80 font-semibold avg-row">
                            <td class="p-3 border-r border-border-main font-bold text-primary sticky left-0 z-10 bg-slate-100 shadow-xs">RATA-RATA SISWA</td>
                            <td class="p-2.5 border-r border-border-main text-center text-text-muted">${kkmValue}</td>`;
                currentStudents.forEach(stu => {
                    let avg = colCounts[stu.id] > 0 ? (colTotals[stu.id] / colCounts[stu.id]) : null;
                    html += `<td class="p-2.5 border-r border-border-main text-center font-bold whitespace-nowrap ${getColorClass(avg)}">${fNum(avg)}</td>`;
                });
                html += `</tr>`;
            }
        }
        
        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    function cleanMapelName(str) {
        if (!str) return '';
        return str
            .toLowerCase()
            // Hapus tanda kurung berisi angka, misal: (1) atau ( 2 )
            .replace(/\(\s*\d+\s*\)/g, '')
            // Hapus pemisah dan angka di akhir nama, misal: " 1", " - 2", ".3"
            .replace(/[\s\-_.]+\d+[\s\-_.]*$/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function parseDelimitedList(textInput) {
        if (!textInput) return [];
        // Support vertical newlines and horizontal Excel tabs
        return textInput
            .split(/[\r\n\t]+/)
            .map(n => n.trim())
            .filter(n => n.length > 0);
    }

    function formatTranspose(textareaId) {
        const el = document.getElementById(textareaId);
        if (!el || !el.value.trim()) return;
        const items = parseDelimitedList(el.value);
        el.value = items.join('\n');
    }

    function customSort(originalArray, textInput, fieldName, isMapel = false) {
        const lines = parseDelimitedList(textInput).map(n => n.toLowerCase());
        if (lines.length === 0) return [...originalArray];
        let matched = [];
        let remaining = [...originalArray];
        lines.forEach(line => {
            let index = remaining.findIndex(item => item[fieldName].toLowerCase() === line);

            // Fallback for subjects with grade level suffixes
            if (index === -1 && isMapel) {
                const cleanedLine = cleanMapelName(line);
                index = remaining.findIndex(item => {
                    const cleanedItem = cleanMapelName(item[fieldName]);
                    return cleanedItem === cleanedLine || cleanedItem === line || item[fieldName].toLowerCase() === cleanedLine;
                });
            }

            if (index > -1) matched.push(remaining.splice(index, 1)[0]);
        });
        return matched.concat(remaining);
    }

    function terapkanUrutan() {
        const elSiswa = document.getElementById('urutSiswa');
        const elMapel = document.getElementById('urutMapel');

        if (elSiswa && elSiswa.value.includes('\t')) formatTranspose('urutSiswa');
        if (elMapel && elMapel.value.includes('\t')) formatTranspose('urutMapel');

        const valSiswa = elSiswa ? elSiswa.value : '';
        const valMapel = elMapel ? elMapel.value : '';
        currentStudents = customSort(rawStudents, valSiswa, 'nama', false);
        currentSubjects = customSort(rawSubjects, valMapel, 'nama_mapel', true);
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

    document.addEventListener('DOMContentLoaded', () => {
        ['urutSiswa', 'urutMapel'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('paste', function(e) {
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                if (pasteData && pasteData.includes('\t')) {
                    e.preventDefault();
                    const items = parseDelimitedList(pasteData);
                    const transposed = items.join('\n');
                    
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    const val = this.value;
                    this.value = val.substring(0, start) + transposed + val.substring(end);
                    this.selectionStart = this.selectionEnd = start + transposed.length;

                    const badgeId = (id === 'urutSiswa') ? 'badgeTransposeSiswa' : 'badgeTransposeMapel';
                    const badge = document.getElementById(badgeId);
                    if (badge) {
                        badge.textContent = `✓ ${items.length} data otomatis ditranspose ke bawah!`;
                        badge.className = 'text-[10px] font-bold text-emerald-600';
                        setTimeout(() => {
                            badge.textContent = 'Mendukung tempel horizontal';
                            badge.className = 'text-[10px] text-text-muted';
                        }, 3000);
                    }
                }
            });
        });
    });

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