<?php

session_start();

require_once '../config/koneksi.php';

$page_title = 'Bulk Catatan Siswa';
$status = $_GET['status'] ?? '';
$total = (int) ($_GET['total'] ?? 0);

// =====================================================
// AMBIL DATA KELAS
// =====================================================

$kelas_stmt = $pdo->query("
    SELECT
        id,
        jenjang,
        nama_kelas
    FROM classes
    ORDER BY jenjang, nama_kelas
");

$kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once '../components/header.php'; ?>

<main class="flex-1">

    <!-- HEADER -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 lg:py-8">

        <div class="max-w-6xl mx-auto">

            <!-- TITLE -->
            <div class="mb-6">

                <div class="flex items-center gap-3 mb-2">

                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">

                        <span class="material-symbols-outlined text-primary">
                            playlist_add
                        </span>

                    </div>

                    <div>

                        <h1 class="text-2xl font-black tracking-tight">
                            Bulk Catatan Siswa
                        </h1>

                        <p class="text-sm text-on-surface-variant">
                            Masukkan beberapa catatan siswa sekaligus.
                        </p>

                    </div>

                </div>

            </div>


            <!-- PILIH KELAS -->
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 p-5 mb-6">

                <label
                    for="kelas_id"
                    class="block text-sm font-semibold mb-2"
                >
                    Kelas
                </label>

                <select
                    id="kelas_id"
                    class="w-full px-4 py-3 rounded-xl bg-surface-container-low border-0 focus:ring-2 focus:ring-primary/20 text-sm"
                >

                    <option value="">
                        Pilih kelas terlebih dahulu
                    </option>

                    <?php foreach ($kelas_list as $kelas): ?>

                        <option value="<?= (int) $kelas['id'] ?>">

                            <?= htmlspecialchars($kelas['jenjang']) ?>
                            -
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- AREA SISWA -->
            <div
                id="studentsContainer"
                class="space-y-3"
            >

                <!-- Empty state -->
                <div
                    id="emptyState"
                    class="bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant/60 p-10 text-center"
                >

                    <span class="material-symbols-outlined text-4xl text-on-surface-variant/50 mb-3">
                        school
                    </span>

                    <p class="font-semibold text-sm">
                        Pilih kelas terlebih dahulu
                    </p>

                    <p class="text-xs text-on-surface-variant mt-1">
                        Daftar siswa akan muncul setelah kelas dipilih.
                    </p>

                </div>

            </div>


            <!-- FOOTER ACTION -->
            <div
                id="saveSection"
                class="hidden mt-6"
            >

                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-4 sm:p-5">

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                        <div>

                            <p
                                id="totalInfo"
                                class="text-sm font-semibold"
                            >
                                0 catatan siap disimpan
                            </p>

                            <p class="text-xs text-on-surface-variant mt-1">
                                Pastikan tanggal dan catatan sudah benar.
                            </p>

                        </div>

                        <button
                            type="button"
                            id="saveAllButton"
                            onclick="simpanSemuaCatatan()"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-primary text-on-primary text-sm font-bold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >

                            <span class="material-symbols-outlined text-[19px]">
                                save
                            </span>

                            Simpan Semua

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>


<script>

// =====================================================
// ELEMENT
// =====================================================

const kelasSelect =
    document.getElementById('kelas_id');

const studentsContainer =
    document.getElementById('studentsContainer');

const emptyState =
    document.getElementById('emptyState');

const saveSection =
    document.getElementById('saveSection');

const totalInfo =
    document.getElementById('totalInfo');


// =====================================================
// DATA SISWA
// =====================================================

let studentsData = [];


// =====================================================
// PILIH KELAS
// =====================================================

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
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-8 text-center">

            <span class="material-symbols-outlined animate-spin text-3xl text-primary">
                progress_activity
            </span>

            <p class="text-sm font-semibold mt-3">
                Memuat daftar siswa...
            </p>

        </div>
    `;

    fetch(
        'siswa_catatan_api.php?class_id='
        + encodeURIComponent(classId),
        {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }
    )
    .then(response => {

        if (!response.ok) {
            throw new Error('Gagal mengambil data siswa.');
        }

        return response.json();

    })
    .then(data => {

        if (!data.success) {
            throw new Error(
                data.message || 'Gagal mengambil data siswa.'
            );
        }

        studentsData = data.students || [];

        renderStudents();

    })
    .catch(error => {

        console.error(error);

        studentsContainer.innerHTML = `
            <div class="bg-error-container rounded-2xl p-6 text-center">

                <span class="material-symbols-outlined text-error text-3xl">
                    error
                </span>

                <p class="text-sm font-semibold text-error mt-2">
                    Gagal memuat siswa
                </p>

                <p class="text-xs text-error/80 mt-1">
                    Silakan coba lagi.
                </p>

            </div>
        `;

    });

});


// =====================================================
// RENDER SISWA
// =====================================================

function renderStudents() {

    studentsContainer.innerHTML = '';

    if (studentsData.length === 0) {

        studentsContainer.innerHTML = `
            <div class="bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant/60 p-10 text-center">

                <span class="material-symbols-outlined text-4xl text-on-surface-variant/50">
                    person_off
                </span>

                <p class="font-semibold text-sm mt-3">
                    Tidak ada siswa
                </p>

                <p class="text-xs text-on-surface-variant mt-1">
                    Belum ada siswa pada kelas ini.
                </p>

            </div>
        `;

        return;
    }


    studentsData.forEach(student => {

        const studentCard =
            document.createElement('div');

        studentCard.className =
            'student-card bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden';

        studentCard.dataset.studentId =
            student.id;


        studentCard.innerHTML = `

            <!-- HEADER SISWA -->

            <div
                class="student-header flex items-center justify-between gap-3 px-4 sm:px-5 py-4 cursor-pointer hover:bg-surface-container-low transition"
                onclick="toggleStudent(${student.id})"
            >

                <div class="flex items-center gap-3 min-w-0">

                    <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">

                        <span class="material-symbols-outlined text-primary text-[19px]">
                            person
                        </span>

                    </div>

                    <div class="min-w-0">

                        <p class="font-bold text-sm truncate">
                            ${escapeHtml(student.nama)}
                        </p>

                        <p class="text-xs text-on-surface-variant student-status">
                            Belum ada input
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="add-student-button shrink-0 w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center hover:opacity-90 transition"
                    onclick="event.stopPropagation(); tambahCatatan(${student.id})"
                    title="Tambah catatan"
                >

                    <span class="material-symbols-outlined text-[20px]">
                        add
                    </span>

                </button>

            </div>


            <!-- AREA INPUT -->

            <div
                class="student-input-area hidden border-t border-outline-variant/20 px-4 sm:px-5 py-4 bg-surface-container-low/40"
            >

                <div class="notes-list space-y-3">
                </div>

            </div>

        `;

        studentsContainer.appendChild(studentCard);

    });

}


// =====================================================
// TOGGLE SISWA
// =====================================================

function toggleStudent(studentId) {

    const card =
        document.querySelector(
            `.student-card[data-student-id="${studentId}"]`
        );

    if (!card) {
        return;
    }

    const inputArea =
        card.querySelector('.student-input-area');

    inputArea.classList.toggle('hidden');

}


// =====================================================
// TAMBAH CATATAN
// =====================================================

function tambahCatatan(studentId) {

    const card =
        document.querySelector(
            `.student-card[data-student-id="${studentId}"]`
        );

    if (!card) {
        return;
    }

    const inputArea =
        card.querySelector('.student-input-area');

    const notesList =
        card.querySelector('.notes-list');

    inputArea.classList.remove('hidden');


    const row =
        document.createElement('div');

    row.className =
        'note-row flex flex-col lg:flex-row gap-3 items-stretch lg:items-start';


    row.innerHTML = `

        <!-- TANGGAL -->

        <div class="w-full lg:w-44 shrink-0">

            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">
                Tanggal
            </label>

            <input
                type="date"
                class="note-date w-full px-3 py-3 rounded-xl bg-surface-container-low border-0 focus:ring-2 focus:ring-primary/20 text-sm"
                value="<?= date('Y-m-d') ?>"
            >

        </div>


        <!-- CATATAN -->

        <div class="flex-1">

            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">
                Catatan
            </label>

            <textarea
                class="note-text w-full px-3 py-3 rounded-xl bg-surface-container-low border-0 focus:ring-2 focus:ring-primary/20 text-sm resize-y"
                rows="1"
                maxlength="2000"
                placeholder="Tulis catatan..."
            ></textarea>

            <div class="text-right text-[11px] text-on-surface-variant mt-1 character-count">
                0 / 2000
            </div>

        </div>


        <!-- HAPUS BARIS -->

        <div class="flex items-end">

            <button
                type="button"
                onclick="hapusBarisCatatan(this)"
                class="w-10 h-10 rounded-xl bg-error-container text-error flex items-center justify-center hover:opacity-80 transition"
                title="Hapus baris"
            >

                <span class="material-symbols-outlined text-[19px]">
                    delete
                </span>

            </button>

        </div>

    `;


    notesList.appendChild(row);


    const textarea =
        row.querySelector('.note-text');

    const counter =
        row.querySelector('.character-count');


    textarea.addEventListener(
        'input',
        function () {

            counter.textContent =
                this.value.length
                + ' / 2000';

            updateTotalInfo();

        }
    );


    updateStudentStatus(card);

    updateTotalInfo();


    // Fokus ke catatan
    textarea.focus();

}


// =====================================================
// HAPUS BARIS CATATAN
// =====================================================

function hapusBarisCatatan(button) {

    const row =
        button.closest('.note-row');

    if (!row) {
        return;
    }

    const card =
        row.closest('.student-card');

    row.remove();

    updateStudentStatus(card);

    updateTotalInfo();


    // Kalau sudah tidak ada baris,
    // tutup area input.

    const notesList =
        card.querySelector('.notes-list');

    if (notesList.children.length === 0) {

        card.querySelector(
            '.student-input-area'
        ).classList.add('hidden');

    }

}


// =====================================================
// STATUS SISWA
// =====================================================

function updateStudentStatus(card) {

    if (!card) {
        return;
    }

    const rows =
        card.querySelectorAll('.note-row');

    const status =
        card.querySelector('.student-status');


    if (rows.length === 0) {

        status.textContent =
            'Belum ada input';

        return;
    }


    status.textContent =
        rows.length
        + (rows.length === 1
            ? ' catatan'
            : ' catatan'
        );

}


// =====================================================
// TOTAL CATATAN
// =====================================================

function updateTotalInfo() {

    const rows =
        document.querySelectorAll('.note-row');

    let total = 0;

    rows.forEach(row => {

        const text =
            row.querySelector('.note-text');

        if (
            text &&
            text.value.trim() !== ''
        ) {
            total++;
        }

    });


    totalInfo.textContent =
        total
        + (total === 1
            ? ' catatan siap disimpan'
            : ' catatan siap disimpan'
        );


    if (rows.length > 0) {

        saveSection.classList.remove('hidden');

    } else {

        saveSection.classList.add('hidden');

    }

}


// =====================================================
// SIMPAN SEMUA
// =====================================================

function simpanSemuaCatatan() {

    const rows =
        document.querySelectorAll('.note-row');


    if (rows.length === 0) {

        Swal.fire({
            icon: 'warning',
            title: 'Belum ada catatan',
            text: 'Tambahkan minimal satu catatan terlebih dahulu.',
            confirmButtonText: 'OK'
        });

        return;
    }


    const data = [];

    let invalid = false;


    rows.forEach((row, index) => {

        const card =
            row.closest('.student-card');

        const studentId =
            card.dataset.studentId;

        const dateInput =
            row.querySelector('.note-date');

        const textInput =
            row.querySelector('.note-text');

        const tanggal =
            dateInput.value;

        const catatan =
            textInput.value.trim();


        if (!tanggal || !catatan) {

            invalid = true;

            row.classList.add(
                'ring-2',
                'ring-error'
            );

            return;
        }


        row.classList.remove(
            'ring-2',
            'ring-error'
        );


        data.push({
            student_id: parseInt(studentId),
            tanggal: tanggal,
            catatan: catatan
        });

    });


    if (invalid) {

        Swal.fire({
            icon: 'warning',
            title: 'Data belum lengkap',
            text: 'Pastikan semua baris memiliki tanggal dan catatan.',
            confirmButtonText: 'OK'
        });

        return;
    }


    if (data.length === 0) {

        Swal.fire({
            icon: 'warning',
            title: 'Belum ada catatan',
            text: 'Isi minimal satu catatan terlebih dahulu.',
            confirmButtonText: 'OK'
        });

        return;
    }


    Swal.fire({

        icon: 'question',

        title: 'Simpan semua catatan?',

        text:
            data.length
            + ' catatan akan disimpan.',

        showCancelButton: true,

        confirmButtonText: 'Ya, Simpan',

        cancelButtonText: 'Batal',

        reverseButtons: true

    }).then(result => {

        if (!result.isConfirmed) {
            return;
        }


        const form =
            document.createElement('form');

        form.method = 'POST';
        form.action = 'proses_bulk_catatan.php';

        const input =
            document.createElement('input');

        input.type = 'hidden';
        input.name = 'data';
        input.value = JSON.stringify(data);

        form.appendChild(input);

        document.body.appendChild(form);

        form.submit();

    });

}


// =====================================================
// ESCAPE HTML
// =====================================================

function escapeHtml(value) {

    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;

}

// =====================================================
// STATUS BULK CATATAN
// =====================================================

const bulkStatus =
    <?= json_encode($status) ?>;

const bulkTotal =
    <?= $total ?>;


if (bulkStatus === 'success') {

    Swal.fire({

        icon: 'success',

        title: 'Berhasil!',

        text:
            bulkTotal
            + ' catatan berhasil disimpan.',

        confirmButtonText: 'OK'

    });

}


if (bulkStatus === 'invalid') {

    Swal.fire({

        icon: 'warning',

        title: 'Data tidak valid',

        text: 'Data catatan tidak valid atau kosong.',

        confirmButtonText: 'OK'

    });

}


if (bulkStatus === 'too_many') {

    Swal.fire({

        icon: 'warning',

        title: 'Terlalu banyak data',

        text: 'Maksimal 500 catatan dalam sekali proses.',

        confirmButtonText: 'OK'

    });

}


if (bulkStatus === 'error') {

    Swal.fire({

        icon: 'error',

        title: 'Gagal menyimpan',

        text: 'Catatan tidak berhasil disimpan. Silakan coba lagi.',

        confirmButtonText: 'OK'

    });

}


// =====================================================
// BERSIHKAN STATUS DARI URL
// =====================================================

if (
    window.history.replaceState &&
    bulkStatus
) {

    const url =
        new URL(window.location.href);

    url.searchParams.delete('status');

    url.searchParams.delete('total');

    window.history.replaceState(
        {},
        document.title,
        url.pathname + url.search
    );

}

</script>


<?php require_once '../components/footer.php'; ?>