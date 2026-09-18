<?php
session_start();

require_once '../config/koneksi.php';

// Ambil daftar kelas
$kelas_stmt = $pdo->query("
    SELECT id, jenjang, nama_kelas
    FROM classes
    ORDER BY jenjang, nama_kelas
");

$kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Status dari proses_catatan.php
$status = $_GET['status'] ?? '';
?>

<?php require_once '../components/header.php'; ?>

<div class="min-h-screen bg-surface">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-11 h-11 rounded-xl bg-primary-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">
                        edit_note
                    </span>
                </div>

                <div>
                    <h1 class="text-2xl font-black text-on-surface">
                        Catatan Siswa
                    </h1>

                    <p class="text-sm text-on-surface-variant">
                        Catat kejadian atau perilaku siswa saat kegiatan belajar.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-6 sm:p-8">

            <form action="proses_catatan.php" method="POST" id="catatanForm">

                <!-- Kelas -->
                <div class="mb-5">
                    <label for="kelas_id" class="block text-sm font-bold text-on-surface mb-2">
                        Kelas
                    </label>

                    <select
                        name="kelas_id"
                        id="kelas_id"
                        required
                        class="w-full px-4 py-3 rounded-xl bg-surface-container-highest border border-outline-variant/30 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                    >
                        <option value="">Pilih kelas</option>

                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>">
                                <?= htmlspecialchars($kelas['jenjang'] . ' - ' . $kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Siswa -->
                <div class="mb-5">
                    <label for="student_id" class="block text-sm font-bold text-on-surface mb-2">
                        Siswa
                    </label>

                    <select
                        name="student_id"
                        id="student_id"
                        required
                        disabled
                        class="w-full px-4 py-3 rounded-xl bg-surface-container-highest border border-outline-variant/30 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <option value="">Pilih kelas terlebih dahulu</option>
                    </select>

                    <p id="studentLoading" class="hidden mt-2 text-xs text-on-surface-variant">
                        Memuat daftar siswa...
                    </p>
                </div>

                <!-- Tanggal -->
                <div class="mb-5">
                    <label for="tanggal" class="block text-sm font-bold text-on-surface mb-2">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal"
                        id="tanggal"
                        value="<?= htmlspecialchars($_GET['tanggal'] ?? date('Y-m-d')) ?>"
                        required
                        class="w-full px-4 py-3 rounded-xl bg-surface-container-highest border border-outline-variant/30 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                    >
                </div>

                <!-- Catatan -->
                <div class="mb-6">
                    <label for="catatan" class="block text-sm font-bold text-on-surface mb-2">
                        Catatan
                    </label>

                    <textarea
                        name="catatan"
                        id="catatan"
                        rows="6"
                        maxlength="2000"
                        required
                        placeholder="Contoh: Tidur saat pelajaran berlangsung..."
                        class="w-full px-4 py-3 rounded-xl bg-surface-container-highest border border-outline-variant/30 text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition resize-y"
                    ></textarea>

                    <div class="flex justify-between mt-2">
                        <p class="text-xs text-on-surface-variant">
                            Tulis kejadian secara singkat dan jelas.
                        </p>

                        <span id="charCount" class="text-xs text-on-surface-variant">
                            0 / 2000
                        </span>
                    </div>
                </div>

                <!-- Tombol -->
                <div class="flex flex-col sm:flex-row gap-3">

                    <button
                        type="submit"
                        id="saveButton"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:opacity-90 active:scale-[0.99] transition"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            save
                        </span>

                        <span>Simpan Catatan</span>
                    </button>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a
                            href="summary_catatan.php"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-surface-container-highest text-on-surface font-bold hover:bg-surface-container-high transition"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                summarize
                            </span>

                            Lihat Summary
                        </a>
                    <?php endif; ?>

                </div>

            </form>
        </div>

        <!-- Info -->
        <div class="mt-5 flex items-start gap-3 px-4 py-3 rounded-xl bg-primary-container/40">
            <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">
                info
            </span>

            <p class="text-sm text-on-surface-variant">
                Catatan dapat diisi tanpa login. Untuk melihat dan memfilter seluruh catatan,
                gunakan halaman <strong>Summary Catatan</strong> setelah login.
            </p>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const kelasSelect = document.getElementById('kelas_id');
    const studentSelect = document.getElementById('student_id');
    const studentLoading = document.getElementById('studentLoading');
    const catatanInput = document.getElementById('catatan');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('catatanForm');
    const saveButton = document.getElementById('saveButton');

    // ==========================================
    // Load siswa berdasarkan kelas
    // ==========================================
    kelasSelect.addEventListener('change', function () {

        const classId = this.value;

        studentSelect.innerHTML = '';

        if (!classId) {
            studentSelect.disabled = true;

            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Pilih kelas terlebih dahulu';

            studentSelect.appendChild(option);

            return;
        }

        studentSelect.disabled = true;

        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Memuat siswa...';

        studentSelect.appendChild(loadingOption);

        studentLoading.classList.remove('hidden');

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
            defaultOption.textContent = 'Pilih siswa';

            studentSelect.appendChild(defaultOption);

            data.students.forEach(student => {

                const option = document.createElement('option');

                option.value = student.id;

                // Hanya nama siswa, tanpa NIS
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
                    title: 'Gagal',
                    text: 'Daftar siswa tidak dapat dimuat.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .finally(() => {
            studentLoading.classList.add('hidden');
        });
    });

    // ==========================================
    // Character counter
    // ==========================================
    function updateCharCount() {
        const length = catatanInput.value.length;
        charCount.textContent = length + ' / 2000';
    }

    catatanInput.addEventListener('input', updateCharCount);

    updateCharCount();

    // ==========================================
    // Cegah double submit
    // ==========================================
    form.addEventListener('submit', function () {

        saveButton.disabled = true;

        saveButton.innerHTML = `
            <span class="material-symbols-outlined animate-spin text-[20px]">
                progress_activity
            </span>
            <span>Menyimpan...</span>
        `;
    });

    // ==========================================
    // SweetAlert status
    // ==========================================
    const status = <?= json_encode($status) ?>;

    if (typeof Swal !== 'undefined') {

        if (status === 'success') {

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Catatan siswa berhasil disimpan.',
                confirmButtonText: 'OK',
                timer: 1800,
                timerProgressBar: true
            });

        } else if (status === 'invalid') {

            Swal.fire({
                icon: 'warning',
                title: 'Data belum lengkap',
                text: 'Silakan periksa kembali data yang diisi.',
                confirmButtonText: 'OK'
            });

        } else if (status === 'invalid_student') {

            Swal.fire({
                icon: 'warning',
                title: 'Siswa tidak valid',
                text: 'Siswa yang dipilih tidak sesuai dengan kelas.',
                confirmButtonText: 'OK'
            });

        }
    }

    // ==========================================
    // Bersihkan parameter status dari URL
    // ==========================================
    if (window.history.replaceState && status) {

        const url = new URL(window.location.href);

        url.searchParams.delete('status');

        window.history.replaceState({}, document.title, url.pathname + url.search);
    }

});
</script>