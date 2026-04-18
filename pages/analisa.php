<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Ambil Kategori yang ingin ditampilkan (Default: uas)
$kategori = $_GET['kategori'] ?? 'uas';
$kategori_list = [
    'h_uts' => 'Harian UTS',
    'uts' => 'UTS',
    'h_uas' => 'Harian UAS',
    'uas' => 'UAS',
    'tambahan' => 'Tambahan'
];

// 2. Pilih Kelas yang ingin dianalisa
$stmt_classes = $pdo->query("SELECT * FROM classes ORDER BY nama_kelas ASC");
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
$active_class_id = $_GET['kelas_id'] ?? ($classes[0]['id'] ?? null);

// 3. Ambil Semua Mapel yang diajarkan di kelas ini (berdasarkan Jadwal)
$subjects_in_class = [];
if ($active_class_id) {
    $stmt_subj = $pdo->prepare("
        SELECT DISTINCT s.nama_mapel, ts.id as schedule_id 
        FROM teaching_schedules ts 
        JOIN subjects s ON ts.subject_id = s.id 
        WHERE ts.class_id = ?
    ");
    $stmt_subj->execute([$active_class_id]);
    $subjects_in_class = $stmt_subj->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Ambil Daftar Siswa dan Nilai mereka
$data_rekap = [];
if ($active_class_id) {
    $stmt_siswa = $pdo->prepare("SELECT id, nama FROM students WHERE class_id = ? ORDER BY nama ASC");
    $stmt_siswa->execute([$active_class_id]);
    $students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $s) {
        $nilai_per_mapel = [];
        foreach ($subjects_in_class as $subj) {
            // Ambil nilai berdasarkan kategori yang dipilih
            $stmt_val = $pdo->prepare("SELECT $kategori FROM grades WHERE student_id = ? AND schedule_id = ?");
            $stmt_val->execute([$s['id'], $subj['schedule_id']]);
            $val = $stmt_val->fetchColumn();
            $nilai_per_mapel[$subj['nama_mapel']] = $val ?: 0;
        }
        $data_rekap[] = [
            'nama' => $s['nama'],
            'nilai' => $nilai_per_mapel
        ];
    }
}

$page_title = "EduScore - Leger Nilai";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden p-2"><span class="material-symbols-outlined">menu</span></button>
            <span class="font-bold text-primary">Rekapitulasi Lintas Mapel</span>
        </div>
        <button onclick="copyTableData()" class="bg-tertiary text-on-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">content_copy</span> Salin Leger
        </button>
    </div>
</nav>

<main class="max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/20">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-bold text-on-surface-variant uppercase">Pilih Kelas</label>
            <select name="kelas_id" onchange="this.form.submit()" class="rounded-lg border-outline-variant/50 text-sm">
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $active_class_id == $c['id'] ? 'selected' : '' ?>><?= $c['nama_kelas'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-bold text-on-surface-variant uppercase">Pilih Kategori Nilai</label>
            <select name="kategori" onchange="this.form.submit()" class="rounded-lg border-outline-variant/50 text-sm">
                <?php foreach($kategori_list as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $kategori == $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="tabelLeger" class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low text-[10px] uppercase font-bold text-on-surface-variant border-b border-outline-variant/20">
                    <tr>
                        <th class="px-4 py-3 border-r border-outline-variant/20">Nama Siswa</th>
                        <?php foreach($subjects_in_class as $subj): ?>
                            <th class="px-4 py-3 text-center border-r border-outline-variant/20"><?= $subj['nama_mapel'] ?></th>
                        <?php endforeach; ?>
                        <th class="px-4 py-3 text-center bg-primary text-on-primary">Rata-rata</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-outline-variant/10">
                    <?php foreach($data_rekap as $row): ?>
                        <tr class="hover:bg-surface-container-low">
                            <td class="px-4 py-3 font-bold border-r border-outline-variant/20"><?= htmlspecialchars($row['nama']) ?></td>
                            <?php 
                            $total = 0;
                            foreach($subjects_in_class as $subj): 
                                $n = $row['nilai'][$subj['nama_mapel']];
                                $total += $n;
                            ?>
                                <td class="px-4 py-3 text-center border-r border-outline-variant/20"><?= $n ?></td>
                            <?php endforeach; ?>
                            <td class="px-4 py-3 text-center font-black text-primary bg-primary/5">
                                <?= count($subjects_in_class) > 0 ? round($total / count($subjects_in_class), 1) : 0 ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function copyTableData() {
    const table = document.getElementById('tabelLeger');
    let tsv = "";
    for (let i = 0; i < table.rows.length; i++) {
        let rowData = [];
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            rowData.push(table.rows[i].cells[j].innerText.trim());
        }
        tsv += rowData.join("\t") + "\n";
    }
    navigator.clipboard.writeText(tsv).then(() => alert("Leger Nilai berhasil disalin!"));
}
</script>

<?php require_once '../components/footer.php'; ?>