<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/koneksi.php';

$kelas_stmt = $pdo->query("SELECT id, jenjang, nama_kelas FROM classes ORDER BY jenjang, nama_kelas");
$kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);

$class_id = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT) ?: 0;
$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT) ?: 0;
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');

$where = [];
$params = [];

if ($class_id) {
    $where[] = 's.class_id = ?';
    $params[] = $class_id;
}
if ($student_id) {
    $where[] = 'sn.student_id = ?';
    $params[] = $student_id;
}
if ($dari !== '') {
    $where[] = 'sn.tanggal >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = 'sn.tanggal <= ?';
    $params[] = $sampai;
}

$sql = "SELECT
            sn.id,
            sn.student_id,
            sn.tanggal,
            sn.catatan,
            s.nama AS nama_siswa,
            c.id AS class_id,
            c.nama_kelas,
            c.jenjang
        FROM student_notes sn
        JOIN students s ON s.id = sn.student_id
        JOIN classes c ON c.id = s.class_id";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY sn.tanggal DESC, s.nama ASC, sn.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$copy_all_lines = [];
$copy_grouped = [];

foreach ($notes as $note) {
    $date_display = date('d/m/Y', strtotime($note['tanggal']));
    $line = $note['nama_siswa'] . ' - ' . $date_display . ' - ' . preg_replace('/\s+/', ' ', trim($note['catatan']));
    $copy_all_lines[] = $line;

    $student_key = (string)$note['student_id'];
    if (!isset($copy_grouped[$student_key])) {
        $copy_grouped[$student_key] = [
            'nama' => $note['nama_siswa'],
            'notes' => []
        ];
    }
    $copy_grouped[$student_key]['notes'][] = $date_display . ' - ' . preg_replace('/\s+/', ' ', trim($note['catatan']));
}

$copy_per_student_lines = [];
foreach ($copy_grouped as $group) {
    $copy_per_student_lines[] = strtoupper($group['nama']);
    foreach ($group['notes'] as $line) {
        $copy_per_student_lines[] = $line;
    }
    $copy_per_student_lines[] = '';
}

$page_title = "EduScore - Summary Catatan";
require_once '../components/header.php';
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined text-sm">analytics</span>
            </div>
            <div>
                <span class="font-bold text-primary tracking-tight text-lg">Summary Catatan</span>
                <span class="text-on-surface-variant ml-2 text-sm hidden md:inline">| Riwayat siswa</span>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-6xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary">Summary Catatan Siswa</h1>
        <p class="text-sm text-on-surface-variant mt-1">Gunakan filter lalu salin data sesuai kebutuhan.</p>
    </div>

    <form method="GET" class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm p-5 md:p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Kelas</label>
                <select id="filterKelas" name="kelas_id" class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-0 px-3 py-3">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_list as $kelas): ?>
                        <option value="<?= (int)$kelas['id'] ?>" <?= $class_id == $kelas['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['nama_kelas']) ?> (<?= htmlspecialchars($kelas['jenjang']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Siswa</label>
                <select id="filterSiswa" name="student_id" class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-0 px-3 py-3">
                    <option value="">Semua Siswa</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Dari</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>" class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-0 px-3 py-3">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Sampai</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>" class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-0 px-3 py-3">
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 mt-5">
            <button type="submit" class="bg-primary text-on-primary px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">filter_alt</span> Tampilkan
            </button>
            <a href="summary_catatan.php" class="px-6 py-3 rounded-lg bg-surface-container-highest text-on-surface font-semibold text-center">Reset</a>
        </div>
    </form>

    <div class="flex flex-col sm:flex-row gap-3">
        <button type="button" onclick="copyText('all', this)" <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-primary text-on-primary px-5 py-3.5 rounded-xl font-semibold flex items-center justify-center gap-2 disabled:opacity-40">
            <span class="material-symbols-outlined">content_copy</span> Copy Semua Data
        </button>
        <button type="button" onclick="copyText('student', this)" <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-surface-container-lowest text-primary border border-primary/20 px-5 py-3.5 rounded-xl font-semibold flex items-center justify-center gap-2 disabled:opacity-40">
            <span class="material-symbols-outlined">content_copy</span> Copy Per Siswa
        </button>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
        <div class="px-5 md:px-6 py-4 border-b border-outline-variant/20 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-primary">Data Catatan</h2>
                <p class="text-xs text-on-surface-variant mt-1"><?= count($notes) ?> catatan ditemukan</p>
            </div>
        </div>

        <?php if (!$notes): ?>
            <div class="p-10 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl">inbox</span>
                <p class="mt-2 text-sm">Belum ada catatan sesuai filter.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-outline-variant/10">
                <?php foreach ($notes as $note): ?>
                    <div class="p-5 hover:bg-surface-container-low/50">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                            <div>
                                <div class="font-bold text-on-surface"><?= htmlspecialchars($note['nama_siswa']) ?></div>
                                <div class="text-xs text-on-surface-variant mt-1">
                                    <?= htmlspecialchars($note['nama_kelas']) ?> · <?= date('d/m/Y', strtotime($note['tanggal'])) ?>
                                </div>
                            </div>
                            <div class="text-sm md:max-w-2xl md:text-right whitespace-pre-line">
                                <?= htmlspecialchars($note['catatan']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
const filterKelas = document.getElementById('filterKelas');
const filterSiswa = document.getElementById('filterSiswa');
const selectedStudent = <?= json_encode((string)$student_id) ?>;

async function loadFilterStudents() {
    filterSiswa.innerHTML = '<option value="">Semua Siswa</option>';
    if (!filterKelas.value) return;

    try {
        const response = await fetch('siswa_catatan_api.php?class_id=' + encodeURIComponent(filterKelas.value));
        const data = await response.json();
        if (!data.success) return;

        data.students.forEach(student => {
            const option = document.createElement('option');

            option.value = student.id;
            option.textContent = student.nama;

            if (String(student.id) === selectedStudent) {
                option.selected = true;
            }

            filterSiswa.appendChild(option);
        });
    } catch (e) {
        console.error(e);
    }
}

filterKelas.addEventListener('change', () => {
    filterSiswa.value = '';
    loadFilterStudents();
});
loadFilterStudents();

const copyAll = <?= json_encode(implode("\n", $copy_all_lines), JSON_UNESCAPED_UNICODE) ?>;
const copyStudent = <?= json_encode(rtrim(implode("\n", $copy_per_student_lines)), JSON_UNESCAPED_UNICODE) ?>;

async function copyText(type, button) {
    const text = type === 'all' ? copyAll : copyStudent;
    if (!text) return;

    try {
        await navigator.clipboard.writeText(text);
        const original = button.innerHTML;
        button.innerHTML = '<span class="material-symbols-outlined">check</span> Berhasil Dicopy';
        setTimeout(() => button.innerHTML = original, 1600);
    } catch (error) {
        const area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data sudah disalin ke clipboard.', timer: 1200, showConfirmButton: false });
    }
}
</script>

<?php require_once '../components/footer.php'; ?>
