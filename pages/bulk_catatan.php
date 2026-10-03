<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

$page_title = 'Bulk Catatan Siswa - EduScore';
$page_heading = 'Bulk Catatan Siswa';
$status = $_GET['status'] ?? '';
$total = (int) ($_GET['total'] ?? 0);

// Ambil data kelas (Admin: semua kelas, Wali Kelas: hanya kelas binaannya)
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

require_once '../components/header.php'; 
?>

<main class="flex-grow p-4 md:p-8 pb-28 md:pb-8 max-w-5xl mx-auto w-full flex flex-col gap-6">

    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-4 sm:p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">playlist_add</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Entri Catatan Massal</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Bulk Catatan Siswa</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Masukkan catatan perilaku untuk beberapa siswa sekaligus dalam satu kelas.
                </p>
            </div>
        </div>

        <a href="summary_catatan.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-xs font-semibold text-text-main hover:bg-slate-50 transition-colors min-h-[44px]">
            <span class="material-symbols-outlined text-base text-text-muted">summarize</span>
            Lihat Summary
        </a>
    </div>

    <!-- Pilih Rombel / Kelas -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs">
        <div class="flex flex-col gap-1.5">
            <label for="kelas_id" class="text-xs font-semibold text-text-main">
                Pilih Kelas / Rombongan Belajar
            </label>
            <select
                id="kelas_id"
                class="w-full bg-white text-text-main text-xs md:text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]"
            >
                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                <?php foreach ($kelas_list as $kelas): ?>
                    <option value="<?= (int) $kelas['id'] ?>" <?= count($kelas_list) === 1 ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kelas['jenjang']) ?> - <?= htmlspecialchars($kelas['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-[11px] text-text-muted mt-0.5">
                Pilih kelas untuk memunculkan daftar seluruh siswa di kelas tersebut.
            </p>
        </div>
    </div>

    <!-- Container Daftar Siswa -->
    <div id="studentsContainer" class="space-y-3">
        <!-- Initial Empty State -->
        <div id="emptyState" class="bg-surface-card rounded-xl border border-dashed border-slate-300 p-12 text-center shadow-xs">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-2xl">school</span>
            </div>
            <p class="font-bold text-sm text-text-main">Pilih Kelas Terlebih Dahulu</p>
            <p class="text-xs text-text-muted mt-1 max-w-sm mx-auto">
                Daftar siswa akan muncul di sini. Anda dapat menambahkan satu atau lebih catatan untuk setiap siswa.
            </p>
        </div>
    </div>

    <!-- Sticky / Bottom Floating Action Bar -->
    <div id="saveSection" class="hidden sticky bottom-4 z-20">
        <div class="bg-surface-card rounded-xl border border-border-main shadow-lg p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary-subtle text-primary flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">fact_check</span>
                </div>
                <div>
                    <p id="totalInfo" class="text-xs sm:text-sm font-bold text-text-main tabular-nums">
                        0 catatan siap disimpan
                    </p>
                    <p class="text-[11px] text-text-muted">
                        Pastikan seluruh tanggal dan uraian catatan telah terisi dengan benar.
                    </p>
                </div>
            </div>

            <button
                type="button"
                id="saveAllButton"
                onclick="simpanSemuaCatatan()"
                class="w-full sm:w-auto bg-primary hover:bg-primary-hover text-white text-xs md:text-sm font-semibold px-6 py-2.5 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-xs min-h-[44px]"
            >
                <span class="material-symbols-outlined text-base">save</span>
                <span>Simpan Semua Catatan</span>
            </button>
        </div>
    </div>

</main>

<script>
const kelasSelect = document.getElementById('kelas_id');
const studentsContainer = document.getElementById('studentsContainer');
const emptyState = document.getElementById('emptyState');
const saveSection = document.getElementById('saveSection');
const totalInfo = document.getElementById('totalInfo');

let studentsData = [];

kelasSelect.addEventListener('change', function () {
    const classId = this.value;
    studentsContainer.innerHTML = '';
    saveSection.classList.add('hidden');

    if (!classId) {
        studentsContainer.appendChild(emptyState);
        emptyState.classList.remove('hidden');
        return;
    }

    studentsContainer.innerHTML = `
        <div class="bg-surface-card rounded-xl border border-border-main p-10 text-center shadow-xs">
            <span class="material-symbols-outlined animate-spin text-3xl text-primary">progress_activity</span>
            <p class="text-xs font-semibold text-text-main mt-3">Memuat daftar siswa...</p>
        </div>
    `;

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
        if (!data.success) {
            throw new Error(data.message || 'Gagal mengambil data siswa.');
        }
        studentsData = data.students || [];
        renderStudents();
    })
    .catch(error => {
        console.error(error);
        studentsContainer.innerHTML = `
            <div class="bg-danger-subtle text-danger rounded-xl p-6 text-center border border-danger/20">
                <span class="material-symbols-outlined text-3xl">error</span>
                <p class="text-sm font-semibold mt-2">Gagal memuat siswa</p>
                <p class="text-xs mt-1">Silakan periksa koneksi atau coba pilih kelas kembali.</p>
            </div>
        `;
    });
});

if (kelasSelect && kelasSelect.value) {
    kelasSelect.dispatchEvent(new Event('change'));
}

function renderStudents() {
    studentsContainer.innerHTML = '';

    if (studentsData.length === 0) {
        studentsContainer.innerHTML = `
            <div class="bg-surface-card rounded-xl border border-dashed border-slate-300 p-10 text-center shadow-xs">
                <span class="material-symbols-outlined text-4xl text-slate-300">person_off</span>
                <p class="font-bold text-sm text-text-main mt-3">Tidak Ada Siswa</p>
                <p class="text-xs text-text-muted mt-1">Belum ada siswa yang terdaftar di kelas ini.</p>
            </div>
        `;
        return;
    }

    studentsData.forEach(student => {
        const studentCard = document.createElement('div');
        studentCard.className = 'student-card bg-surface-card rounded-xl border border-border-main overflow-hidden shadow-xs transition-colors';
        studentCard.dataset.studentId = student.id;

        const initials = student.nama ? student.nama.substring(0, 2).toUpperCase() : 'S';

        studentCard.innerHTML = `
            <!-- HEADER SISWA -->
            <div
                class="student-header flex items-center justify-between gap-3 px-4 sm:px-5 py-3.5 cursor-pointer hover:bg-slate-50/80 transition-colors"
                onclick="toggleStudent(${student.id})"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-100 text-primary border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0">
                        ${escapeHtml(initials)}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-xs md:text-sm text-text-main truncate">
                            ${escapeHtml(student.nama)}
                        </p>
                        <p class="text-[11px] text-text-muted student-status">
                            Belum ada input
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="add-student-button inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary-subtle text-primary hover:bg-primary hover:text-white transition-colors text-xs font-semibold min-h-[38px]"
                        onclick="event.stopPropagation(); tambahCatatan(${student.id})"
                        title="Tambah baris catatan"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span class="hidden sm:inline">Tambah</span>
                    </button>
                    <span class="material-symbols-outlined text-text-muted text-lg transition-transform expand-icon p-1">
                        expand_more
                    </span>
                </div>
            </div>

            <!-- AREA INPUT -->
            <div class="student-input-area hidden border-t border-border-main px-4 sm:px-5 py-4 bg-slate-50/50">
                <div class="notes-list space-y-3">
                </div>
            </div>
        `;

        studentsContainer.appendChild(studentCard);
    });
}

function toggleStudent(studentId) {
    const card = document.querySelector(`.student-card[data-student-id="${studentId}"]`);
    if (!card) return;

    const inputArea = card.querySelector('.student-input-area');
    const icon = card.querySelector('.expand-icon');
    const isHidden = inputArea.classList.toggle('hidden');

    if (icon) {
        icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

function tambahCatatan(studentId) {
    const card = document.querySelector(`.student-card[data-student-id="${studentId}"]`);
    if (!card) return;

    const inputArea = card.querySelector('.student-input-area');
    const notesList = card.querySelector('.notes-list');
    const icon = card.querySelector('.expand-icon');

    inputArea.classList.remove('hidden');
    if (icon) icon.style.transform = 'rotate(180deg)';

    const row = document.createElement('div');
    row.className = 'note-row bg-white p-3.5 rounded-lg border border-slate-200 flex flex-col md:flex-row gap-3 items-stretch md:items-start shadow-2xs';

    row.innerHTML = `
        <!-- TANGGAL -->
        <div class="w-full md:w-44 shrink-0">
            <label class="block text-[11px] font-semibold text-text-muted mb-1">
                Tanggal
            </label>
            <input
                type="date"
                class="note-date w-full px-3 py-2.5 text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 font-medium min-h-[44px]"
                value="<?= date('Y-m-d') ?>"
            >
        </div>

        <!-- CATATAN -->
        <div class="flex-1">
            <label class="block text-[11px] font-semibold text-text-muted mb-1">
                Uraian Catatan
            </label>
            <textarea
                class="note-text w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 resize-y placeholder:text-slate-400 font-sans"
                rows="2"
                maxlength="2000"
                placeholder="Tuliskan catatan kejadian siswa..."
            ></textarea>
            <div class="text-right text-[10px] text-text-muted mt-0.5 character-count tabular-nums">
                0 / 2000
            </div>
        </div>

        <!-- HAPUS BARIS -->
        <div class="flex items-end self-end md:self-center mt-2 md:mt-4">
            <button
                type="button"
                onclick="hapusBarisCatatan(this)"
                class="w-10 h-10 rounded-lg text-danger hover:bg-danger-subtle transition-colors flex items-center justify-center border border-transparent hover:border-danger/20"
                title="Hapus baris catatan ini"
                aria-label="Hapus baris catatan"
            >
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
        </div>
    `;

    notesList.appendChild(row);

    const textarea = row.querySelector('.note-text');
    const counter = row.querySelector('.character-count');

    textarea.addEventListener('input', function () {
        counter.textContent = this.value.length + ' / 2000';
        updateTotalInfo();
    });

    updateStudentStatus(card);
    updateTotalInfo();
    textarea.focus();
}

function hapusBarisCatatan(button) {
    const row = button.closest('.note-row');
    if (!row) return;

    const card = row.closest('.student-card');
    row.remove();

    updateStudentStatus(card);
    updateTotalInfo();

    const notesList = card.querySelector('.notes-list');
    if (notesList.children.length === 0) {
        card.querySelector('.student-input-area').classList.add('hidden');
        const icon = card.querySelector('.expand-icon');
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

function updateStudentStatus(card) {
    if (!card) return;
    const rows = card.querySelectorAll('.note-row');
    const status = card.querySelector('.student-status');

    if (rows.length === 0) {
        status.textContent = 'Belum ada input';
        status.className = 'text-[11px] text-text-muted student-status';
    } else {
        status.textContent = rows.length + ' catatan ditambahkan';
        status.className = 'text-[11px] font-semibold text-primary student-status';
    }
}

function updateTotalInfo() {
    const rows = document.querySelectorAll('.note-row');
    let total = 0;

    rows.forEach(row => {
        const text = row.querySelector('.note-text');
        if (text && text.value.trim() !== '') {
            total++;
        }
    });

    totalInfo.textContent = total + ' catatan siap disimpan';

    if (rows.length > 0) {
        saveSection.classList.remove('hidden');
    } else {
        saveSection.classList.add('hidden');
    }
}

function simpanSemuaCatatan() {
    const rows = document.querySelectorAll('.note-row');

    if (rows.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Belum Ada Catatan',
            text: 'Tambahkan minimal satu catatan siswa terlebih dahulu.',
            confirmButtonColor: '#0f2942'
        });
        return;
    }

    const data = [];
    let invalid = false;

    rows.forEach(row => {
        const card = row.closest('.student-card');
        const studentId = card.dataset.studentId;
        const dateInput = row.querySelector('.note-date');
        const textInput = row.querySelector('.note-text');

        const tanggal = dateInput.value;
        const catatan = textInput.value.trim();

        if (!tanggal || !catatan) {
            invalid = true;
            row.classList.add('border-danger', 'ring-1', 'ring-danger');
            return;
        }

        row.classList.remove('border-danger', 'ring-1', 'ring-danger');

        data.push({
            student_id: parseInt(studentId),
            tanggal: tanggal,
            catatan: catatan
        });
    });

    if (invalid) {
        Swal.fire({
            icon: 'warning',
            title: 'Data Belum Lengkap',
            text: 'Pastikan seluruh baris yang ditambahkan memiliki tanggal dan isi catatan.',
            confirmButtonColor: '#0f2942'
        });
        return;
    }

    if (data.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Belum Ada Catatan',
            text: 'Isi minimal satu catatan terlebih dahulu.',
            confirmButtonColor: '#0f2942'
        });
        return;
    }

    Swal.fire({
        icon: 'question',
        title: 'Simpan Catatan Massal?',
        text: data.length + ' catatan siswa akan disimpan ke database.',
        showCancelButton: true,
        confirmButtonColor: '#0f2942',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'proses_bulk_catatan.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'data';
        input.value = JSON.stringify(data);

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
}

function escapeHtml(string) {
    const div = document.createElement('div');
    div.textContent = string;
    return div.innerHTML;
}

const bulkStatus = <?= json_encode($status) ?>;
const bulkTotal = <?= $total ?>;

if (bulkStatus === 'success') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Disimpan!',
        text: bulkTotal + ' catatan siswa berhasil disimpan ke sistem.',
        confirmButtonColor: '#0f2942',
        timer: 2500,
        timerProgressBar: true
    });
} else if (bulkStatus === 'invalid') {
    Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Lengkap',
        text: 'Sebagian data catatan belum lengkap atau format tidak sesuai.',
        confirmButtonColor: '#0f2942'
    });
} else if (bulkStatus === 'too_many') {
    Swal.fire({
        icon: 'error',
        title: 'Melebihi Batas',
        text: 'Maksimal 500 catatan dalam sekali proses penyimpanan.',
        confirmButtonColor: '#0f2942'
    });
} else if (bulkStatus === 'error') {
    Swal.fire({
        icon: 'error',
        title: 'Gagal Menyimpan',
        text: 'Terjadi kendala pada server saat menyimpan catatan. Silakan coba lagi.',
        confirmButtonColor: '#0f2942'
    });
}

if (window.history.replaceState && bulkStatus) {
    const url = new URL(window.location.href);
    url.searchParams.delete('status');
    url.searchParams.delete('total');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}
</script>

<?php require_once '../components/footer.php'; ?>