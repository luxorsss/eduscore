<?php
// Halaman input catatan sengaja TIDAK membutuhkan login.
session_start();
require_once '../config/koneksi.php';

$kelas_stmt = $pdo->query("SELECT id, jenjang, nama_kelas FROM classes ORDER BY jenjang, nama_kelas");
$kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Catatan Siswa";
require_once '../components/header.php';
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-30">
    <div class="max-w-3xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined text-[20px]">edit_note</span>
            </div>
            <div>
                <div class="font-bold text-primary tracking-tight">Catatan Siswa</div>
                <div class="text-xs text-on-surface-variant">Input cepat tanpa login</div>
            </div>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="summary_catatan.php" class="text-primary hover:bg-surface-container-highest p-2 rounded-full" title="Summary">
                <span class="material-symbols-outlined">analytics</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="text-primary hover:bg-surface-container-highest p-2 rounded-full" title="Login untuk melihat summary">
                <span class="material-symbols-outlined">lock</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<main class="flex-grow flex items-start justify-center py-8 px-4 md:py-12">
    <div class="w-full max-w-2xl">
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary">Catat Kejadian Siswa</h1>
            <p class="text-sm text-on-surface-variant mt-1">Pilih kelas, siswa, tanggal, lalu tuliskan apa yang terjadi.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm p-5 md:p-8">
            <form id="catatanForm" action="proses_catatan.php" method="POST" class="space-y-5">
                <div>
                    <label for="kelas" class="block text-sm font-semibold mb-2">Kelas</label>
                    <select id="kelas" name="kelas_id" required
                        class="w-full bg-surface-container-highest rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= (int)$kelas['id'] ?>">
                                <?= htmlspecialchars($kelas['nama_kelas']) ?> (<?= htmlspecialchars($kelas['jenjang']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="siswa" class="block text-sm font-semibold mb-2">Siswa</label>
                    <select id="siswa" name="student_id" required disabled
                        class="w-full bg-surface-container-highest rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 disabled:opacity-60">
                        <option value="">-- Pilih kelas terlebih dahulu --</option>
                    </select>
                </div>

                <div>
                    <label for="tanggal" class="block text-sm font-semibold mb-2">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" value="<?= date('Y-m-d') ?>" required
                        class="w-full bg-surface-container-highest rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5">
                </div>

                <div>
                    <label for="catatan" class="block text-sm font-semibold mb-2">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="5" maxlength="2000" required
                        placeholder="Contoh: Tidur saat pelajaran berlangsung..."
                        class="w-full bg-surface-container-highest rounded-lg border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 resize-y"></textarea>
                    <div class="text-right text-xs text-on-surface-variant mt-1"><span id="counter">0</span>/2000</div>
                </div>

                <button id="submitBtn" type="submit"
                    class="w-full bg-primary text-on-primary px-6 py-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.99] transition-all">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Catatan
                </button>
            </form>
        </div>

        <div class="mt-4 text-center text-xs text-on-surface-variant">
            <span class="material-symbols-outlined align-middle text-[16px]">info</span>
            Summary dan riwayat catatan memerlukan login.
        </div>
    </div>
</main>

<script>
const kelas = document.getElementById('kelas');
const siswa = document.getElementById('siswa');
const catatan = document.getElementById('catatan');
const counter = document.getElementById('counter');
const form = document.getElementById('catatanForm');
const submitBtn = document.getElementById('submitBtn');

kelas.addEventListener('change', async function () {
    siswa.innerHTML = '<option value="">Memuat siswa...</option>';
    siswa.disabled = true;

    if (!this.value) {
        siswa.innerHTML = '<option value="">-- Pilih kelas terlebih dahulu --</option>';
        return;
    }

    try {
        const response = await fetch('api_siswa_catatan.php?class_id=' + encodeURIComponent(this.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        if (!response.ok || !data.success) throw new Error(data.message || 'Gagal mengambil siswa.');

        siswa.innerHTML = '<option value="">-- Pilih Siswa --</option>';
        data.students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.nama + (student.nis ? ' — ' + student.nis : '');
            siswa.appendChild(option);
        });
        siswa.disabled = data.students.length === 0;

        if (data.students.length === 0) {
            siswa.innerHTML = '<option value="">Belum ada siswa di kelas ini</option>';
        }
    } catch (error) {
        siswa.innerHTML = '<option value="">Gagal memuat siswa</option>';
        Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
    }
});

catatan.addEventListener('input', () => counter.textContent = catatan.value.length);

form.addEventListener('submit', function () {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Menyimpan...';
});
</script>

<?php require_once '../components/footer.php'; ?>
