<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

// Ambil daftar kelas (Admin: semua kelas, Wali Kelas: hanya kelas binaannya)
if ($is_admin) {
    $kelas_stmt = $pdo->query("
        SELECT id, jenjang, nama_kelas
        FROM classes
        ORDER BY jenjang, nama_kelas
    ");
    $kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $kelas_list = [$wali_kelas];
}

// Status dari proses_catatan.php
$status = $_GET['status'] ?? '';

$page_title = "Catatan Perilaku Siswa - EduScore";
$page_heading = "Catatan Siswa";
require_once '../components/header.php'; 
?>

<main class="flex-grow p-4 md:p-8 max-w-3xl mx-auto w-full flex flex-col gap-6">

    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">edit_note</span>
        </div>
        <div>
            <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Jurnal Akademik & Perilaku</span>
            <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Catatan Perilaku Siswa</h2>
            <p class="text-xs text-text-muted mt-0.5">
                Dokumentasikan kejadian penting, kedisiplinan, atau catatan kemajuan belajar murid.
            </p>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 sm:p-8 shadow-xs">
        <form action="proses_catatan.php" method="POST" id="catatanForm" class="flex flex-col gap-5">

            <!-- Pilih Kelas -->
            <div class="flex flex-col gap-1.5">
                <label for="kelas_id" class="text-xs font-semibold text-text-main">
                    Kelas / Rombongan Belajar <span class="text-danger">*</span>
                </label>
                <select
                    name="kelas_id"
                    id="kelas_id"
                    required
                    class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]"
                >
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelas_list as $kelas): ?>
                        <option value="<?= (int) $kelas['id'] ?>" <?= count($kelas_list) === 1 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['jenjang'] . ' - ' . $kelas['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pilih Siswa -->
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label for="student_id" class="text-xs font-semibold text-text-main">
                        Nama Siswa <span class="text-danger">*</span>
                    </label>
                    <span id="studentLoading" class="hidden text-xs text-primary font-medium flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Memuat data siswa...
                    </span>
                </div>
                <select
                    name="student_id"
                    id="student_id"
                    required
                    disabled
                    class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px] disabled:bg-slate-50 disabled:text-text-muted disabled:cursor-not-allowed"
                >
                    <option value="">Pilih kelas terlebih dahulu</option>
                </select>
            </div>

            <!-- Tanggal Kejadian -->
            <div class="flex flex-col gap-1.5">
                <label for="tanggal" class="text-xs font-semibold text-text-main">
                    Tanggal Kejadian <span class="text-danger">*</span>
                </label>
                <input
                    type="date"
                    name="tanggal"
                    id="tanggal"
                    value="<?= htmlspecialchars($_GET['tanggal'] ?? date('Y-m-d')) ?>"
                    required
                    class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors font-medium min-h-[44px]"
                >
            </div>

            <!-- Catatan Detail -->
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label for="catatan" class="text-xs font-semibold text-text-main">
                        Isi Catatan <span class="text-danger">*</span>
                    </label>
                    <span id="charCount" class="text-[11px] font-medium text-text-muted tabular-nums">
                        0 / 2000
                    </span>
                </div>
                <textarea
                    name="catatan"
                    id="catatan"
                    rows="5"
                    maxlength="2000"
                    required
                    placeholder="Tuliskan kejadian atau perilaku siswa secara objektif dan jelas..."
                    class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 p-3.5 transition-colors resize-y placeholder:text-slate-400 font-sans leading-relaxed"
                ></textarea>
                <p class="text-[11px] text-text-muted">
                    Catatan ini akan tersimpan dalam riwayat pembinaan murid dan dapat direkap oleh wali kelas.
                </p>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <button
                    type="submit"
                    id="saveButton"
                    class="w-full sm:flex-1 bg-primary hover:bg-primary-hover text-white px-5 py-3 rounded-lg text-xs md:text-sm font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs min-h-[44px]"
                >
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>Simpan Catatan</span>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a
                        href="summary_catatan.php"
                        class="w-full sm:w-auto px-5 py-3 rounded-lg border border-slate-200 bg-white text-text-main hover:bg-slate-50 text-xs md:text-sm font-semibold flex items-center justify-center gap-2 transition-colors min-h-[44px]"
                    >
                        <span class="material-symbols-outlined text-base text-text-muted">summarize</span>
                        <span>Lihat Summary</span>
                    </a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <!-- Alert Edukasi Penggunaan -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-start gap-3">
        <span class="material-symbols-outlined text-primary text-xl shrink-0 mt-0.5">info</span>
        <div class="text-xs text-text-muted leading-relaxed">
            <span class="font-semibold text-text-main">Akses Cepat Pengajar:</span> Formulir ini dapat diisi dengan cepat saat jam mengajar di kelas. Untuk menyalin catatan per siswa atau memfilter berdasarkan rentang tanggal, silakan kunjungi menu 
            <a href="summary_catatan.php" class="text-primary font-semibold hover:underline">Summary Catatan</a>.
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kelasSelect = document.getElementById('kelas_id');
    const studentSelect = document.getElementById('student_id');
    const studentLoading = document.getElementById('studentLoading');
    const catatanInput = document.getElementById('catatan');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('catatanForm');
    const saveButton = document.getElementById('saveButton');

    kelasSelect.addEventListener('change', function () {
        const classId = this.value;
        studentSelect.innerHTML = '';

        if (!classId) {
            studentSelect.disabled = true;
            const option = document.createElement('option');
            option.value = '';
            option.textContent = '-- Pilih kelas terlebih dahulu --';
            studentSelect.appendChild(option);
            return;
        }

        studentSelect.disabled = true;
        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Memuat daftar siswa...';
        studentSelect.appendChild(loadingOption);

        if (studentLoading) studentLoading.classList.remove('hidden');

        fetch('siswa_catatan_api.php?class_id=' + encodeURIComponent(classId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data siswa.');
            }
            return response.json();
        })
        .then(data => {
            studentSelect.innerHTML = '';

            if (!data.success || !Array.isArray(data.students) || data.students.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Tidak ada siswa di kelas ini';
                studentSelect.appendChild(option);
                studentSelect.disabled = true;
                return;
            }

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Pilih Siswa --';
            studentSelect.appendChild(defaultOption);

            data.students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.nama;
                studentSelect.appendChild(option);
            });

            studentSelect.disabled = false;
        })
        .catch(error => {
            console.error('Gagal memuat siswa:', error);
            studentSelect.innerHTML = '';
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Gagal memuat siswa';
            studentSelect.appendChild(option);
            studentSelect.disabled = true;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat Data',
                    text: 'Daftar siswa pada kelas ini tidak dapat dimuat.',
                    confirmButtonColor: '#0f2942'
                });
            }
        })
        .finally(() => {
            if (studentLoading) studentLoading.classList.add('hidden');
        });
    });

    if (kelasSelect && kelasSelect.value) {
        kelasSelect.dispatchEvent(new Event('change'));
    }

    function updateCharCount() {
        const length = catatanInput.value.length;
        charCount.textContent = length + ' / 2000';
    }

    catatanInput.addEventListener('input', updateCharCount);
    updateCharCount();

    form.addEventListener('submit', function () {
        saveButton.disabled = true;
        saveButton.innerHTML = `
            <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
            <span>Menyimpan Catatan...</span>
        `;
    });

    const status = <?= json_encode($status) ?>;

    if (typeof Swal !== 'undefined' && status) {
        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan',
                text: 'Catatan siswa telah berhasil dicatat ke sistem.',
                confirmButtonColor: '#0f2942',
                timer: 2000,
                timerProgressBar: true
            });
        } else if (status === 'invalid') {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Silakan periksa kembali kelas, nama siswa, tanggal, dan isi catatan.',
                confirmButtonColor: '#0f2942'
            });
        } else if (status === 'invalid_student') {
            Swal.fire({
                icon: 'warning',
                title: 'Siswa Tidak Sesuai',
                text: 'Siswa yang dipilih tidak terdaftar pada kelas tersebut.',
                confirmButtonColor: '#0f2942'
            });
        }
    }

    // Bersihkan parameter status dari URL
    if (window.history.replaceState && status) {
        const url = new URL(window.location.href);
        url.searchParams.delete('status');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
});
</script>

<?php require_once '../components/footer.php'; ?>