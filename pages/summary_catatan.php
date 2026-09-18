<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/koneksi.php';

// =====================================================
// DATA KELAS
// =====================================================

$kelas_stmt = $pdo->query("
    SELECT id, jenjang, nama_kelas
    FROM classes
    ORDER BY jenjang, nama_kelas
");

$kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// FILTER
// =====================================================

$class_id = filter_input(
    INPUT_GET,
    'kelas_id',
    FILTER_VALIDATE_INT
) ?: 0;

$student_id = filter_input(
    INPUT_GET,
    'student_id',
    FILTER_VALIDATE_INT
) ?: 0;

$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$status = $_GET['status'] ?? '';


// =====================================================
// QUERY CATATAN
// =====================================================

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

$sql = "
    SELECT
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
    JOIN classes c ON c.id = s.class_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= "
    ORDER BY
        sn.tanggal DESC,
        s.nama ASC,
        sn.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// DATA UNTUK COPY
// =====================================================

$copy_all_lines = [];
$copy_grouped = [];

foreach ($notes as $note) {

    $date_display = date(
        'd/m/Y',
        strtotime($note['tanggal'])
    );

    $clean_note = preg_replace(
        '/\s+/',
        ' ',
        trim($note['catatan'])
    );

    // Format:
    // Nama - Tanggal - Catatan
    $copy_all_lines[] =
        $note['nama_siswa']
        . ' - '
        . $date_display
        . ' - '
        . $clean_note;


    // Kelompokkan berdasarkan siswa
    $student_key = (string) $note['student_id'];

    if (!isset($copy_grouped[$student_key])) {

        $copy_grouped[$student_key] = [
            'nama' => $note['nama_siswa'],
            'notes' => []
        ];
    }

    $copy_grouped[$student_key]['notes'][] =
        $date_display
        . ' - '
        . $clean_note;
}


// =====================================================
// FORMAT COPY PER SISWA
// =====================================================

$copy_per_student_lines = [];

foreach ($copy_grouped as $group) {

    $copy_per_student_lines[] =
        strtoupper($group['nama']);

    foreach ($group['notes'] as $line) {
        $copy_per_student_lines[] = $line;
    }

    $copy_per_student_lines[] = '';
}

$copy_per_student_text = rtrim(
    implode("\n", $copy_per_student_lines)
);


// =====================================================
// PAGE TITLE
// =====================================================

$page_title = "EduScore - Summary Catatan";

require_once '../components/header.php';

?>

<!-- =====================================================
     TOP BAR
===================================================== -->

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-30">

    <div class="max-w-6xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">

        <div class="flex items-center gap-3">

            <button
                onclick="toggleSidebar()"
                class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full"
            >
                <span class="material-symbols-outlined">
                    menu
                </span>
            </button>

            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary">

                <span class="material-symbols-outlined text-sm">
                    analytics
                </span>

            </div>

            <div>

                <span class="font-bold text-primary tracking-tight text-lg">
                    Summary Catatan
                </span>

                <span class="text-on-surface-variant ml-2 text-sm hidden md:inline">
                    | Riwayat siswa
                </span>

            </div>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="flex-grow max-w-6xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">


    <!-- HEADER -->

    <div>

        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary">
            Summary Catatan Siswa
        </h1>

        <p class="text-sm text-on-surface-variant mt-1">
            Gunakan filter lalu salin data sesuai kebutuhan.
        </p>

    </div>


    <!-- =================================================
         FILTER
    ================================================== -->

    <form
        method="GET"
        id="filterForm"
        class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm p-5 md:p-6"
    >

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">


            <!-- KELAS -->

            <div>

                <label
                    for="filterKelas"
                    class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >
                    Kelas
                </label>

                <select
                    id="filterKelas"
                    name="kelas_id"
                    class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-2 focus:ring-primary/20 px-3 py-3"
                >

                    <option value="">
                        Semua Kelas
                    </option>

                    <?php foreach ($kelas_list as $kelas): ?>

                        <option
                            value="<?= (int) $kelas['id'] ?>"
                            <?= $class_id == $kelas['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            (<?= htmlspecialchars($kelas['jenjang']) ?>)
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- SISWA -->

            <div>

                <label
                    for="filterSiswa"
                    class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >
                    Siswa
                </label>

                <select
                    id="filterSiswa"
                    name="student_id"
                    class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-2 focus:ring-primary/20 px-3 py-3"
                    <?= !$class_id ? 'disabled' : '' ?>
                >

                    <option value="">
                        Semua Siswa
                    </option>

                </select>

            </div>


            <!-- DARI -->

            <div>

                <label
                    for="tanggalDari"
                    class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >
                    Dari
                </label>

                <input
                    type="date"
                    id="tanggalDari"
                    name="dari"
                    value="<?= htmlspecialchars($dari) ?>"
                    class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-2 focus:ring-primary/20 px-3 py-3"
                >

            </div>


            <!-- SAMPAI -->

            <div>

                <label
                    for="tanggalSampai"
                    class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2"
                >
                    Sampai
                </label>

                <input
                    type="date"
                    id="tanggalSampai"
                    name="sampai"
                    value="<?= htmlspecialchars($sampai) ?>"
                    class="w-full bg-surface-container-highest rounded-lg border-0 focus:ring-2 focus:ring-primary/20 px-3 py-3"
                >

            </div>

        </div>


        <!-- BUTTON FILTER -->

        <div class="flex flex-col sm:flex-row gap-3 mt-5">

            <button
                type="submit"
                class="bg-primary text-on-primary px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 hover:opacity-90 transition"
            >

                <span class="material-symbols-outlined text-[20px]">
                    filter_alt
                </span>

                Tampilkan

            </button>


            <a
                href="summary_catatan.php"
                class="px-6 py-3 rounded-lg bg-surface-container-highest text-on-surface font-semibold text-center hover:opacity-80 transition"
            >
                Reset
            </a>

        </div>

    </form>


    <!-- =================================================
         COPY BUTTONS
    ================================================== -->

    <div class="flex flex-col sm:flex-row gap-3">

        <button
            type="button"
            onclick="copyText('all', this)"
            <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-primary text-on-primary px-5 py-3.5 rounded-xl font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition"
        >

            <span class="material-symbols-outlined">
                content_copy
            </span>

            Copy Semua Data

        </button>


        <button
            type="button"
            onclick="copyText('student', this)"
            <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-surface-container-lowest text-primary border border-primary/20 px-5 py-3.5 rounded-xl font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-surface-container-low transition"
        >

            <span class="material-symbols-outlined">
                content_copy
            </span>

            Copy Per Siswa

        </button>

    </div>


    <!-- =================================================
         DATA
    ================================================== -->

    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">


        <!-- DATA HEADER -->

        <div class="px-5 md:px-6 py-4 border-b border-outline-variant/20 flex items-center justify-between">

            <div>

                <h2 class="font-bold text-primary">
                    Data Catatan
                </h2>

                <p class="text-xs text-on-surface-variant mt-1">

                    <?php if (count($notes) === 0): ?>

                        Tidak ada catatan

                    <?php elseif (count($notes) === 1): ?>

                        1 catatan ditemukan

                    <?php else: ?>

                        <?= number_format(count($notes), 0, ',', '.') ?>
                        catatan ditemukan

                    <?php endif; ?>

                </p>

            </div>

        </div>


        <!-- EMPTY STATE -->

        <?php if (!$notes): ?>

            <div class="p-10 text-center text-on-surface-variant">

                <span class="material-symbols-outlined text-5xl">
                    inbox
                </span>

                <p class="mt-3 font-semibold">
                    Belum ada catatan
                </p>

                <p class="text-sm mt-1">
                    Tidak ada catatan yang sesuai dengan filter.
                </p>

            </div>


        <!-- DATA LIST -->

        <?php else: ?>

            <div class="divide-y divide-outline-variant/10">

                <?php foreach ($notes as $note): ?>

                    <div class="p-5 hover:bg-surface-container-low/50 transition">

                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">


                            <!-- IDENTITAS SISWA -->

                            <div class="min-w-0">

                                <div class="font-bold text-on-surface">
                                    <?= htmlspecialchars($note['nama_siswa']) ?>
                                </div>

                                <div class="text-xs text-on-surface-variant mt-1">

                                    <?= htmlspecialchars($note['nama_kelas']) ?>

                                    ·

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($note['tanggal'])
                                    ) ?>

                                </div>

                            </div>


                            <!-- CATATAN -->

                            <div class="flex flex-col md:items-end gap-3 md:max-w-2xl">

                                <div class="text-sm md:text-right whitespace-pre-line break-words">
                                    <?= htmlspecialchars($note['catatan']) ?>
                                </div>

                                <div class="flex items-center gap-2">

                                    <!-- EDIT -->
                                    <button
                                        type="button"
                                        onclick='editCatatan(
                                            <?= (int) $note['id'] ?>,
                                            <?= json_encode($note['nama_siswa'], JSON_UNESCAPED_UNICODE) ?>,
                                            <?= json_encode($note['tanggal']) ?>,
                                            <?= json_encode($note['catatan'], JSON_UNESCAPED_UNICODE) ?>
                                        )'
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-highest text-on-surface text-xs font-semibold hover:opacity-80 transition"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">
                                            edit
                                        </span>

                                        Edit
                                    </button>


                                    <!-- HAPUS -->
                                    <button
                                        type="button"
                                        onclick='hapusCatatan(
                                            <?= (int) $note['id'] ?>,
                                            <?= json_encode($note['nama_siswa'], JSON_UNESCAPED_UNICODE) ?>
                                        )'
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-error-container text-error text-xs font-semibold hover:opacity-80 transition"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">
                                            delete
                                        </span>

                                        Hapus
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</main>


<script>

// =====================================================
// FILTER SISWA
// =====================================================

const filterKelas = document.getElementById('filterKelas');
const filterSiswa = document.getElementById('filterSiswa');

const selectedStudent =
    <?= json_encode((string) $student_id) ?>;


async function loadFilterStudents() {

    filterSiswa.innerHTML = '<option value="">Semua Siswa</option>';

    if (!filterKelas.value) {

        filterSiswa.disabled = true;

        return;
    }

    filterSiswa.disabled = true;

    const loadingOption = document.createElement('option');

    loadingOption.value = '';
    loadingOption.textContent = 'Memuat siswa...';

    filterSiswa.appendChild(loadingOption);


    try {

        const response = await fetch(
            'siswa_catatan_api.php?class_id='
            + encodeURIComponent(filterKelas.value),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );


        if (!response.ok) {
            throw new Error('Gagal mengambil data siswa.');
        }


        const data = await response.json();


        filterSiswa.innerHTML =
            '<option value="">Semua Siswa</option>';


        if (
            !data.success ||
            !Array.isArray(data.students)
        ) {

            return;
        }


        data.students.forEach(student => {

            const option =
                document.createElement('option');

            option.value = student.id;

            // Nama saja — tanpa NIS
            option.textContent = student.nama;


            if (
                String(student.id) === selectedStudent
            ) {

                option.selected = true;
            }


            filterSiswa.appendChild(option);

        });


        filterSiswa.disabled = false;


    } catch (error) {

        console.error(
            'Gagal memuat siswa:',
            error
        );

        filterSiswa.innerHTML =
            '<option value="">Gagal memuat siswa</option>';

        filterSiswa.disabled = true;

    }

}


// Ketika kelas berubah,
// pilihan siswa di-reset.

filterKelas.addEventListener(
    'change',
    function () {

        filterSiswa.value = '';

        loadFilterStudents();

    }
);


// Load siswa saat halaman pertama kali dibuka

loadFilterStudents();


// =====================================================
// VALIDASI RENTANG TANGGAL
// =====================================================

const filterForm =
    document.getElementById('filterForm');

filterForm.addEventListener(
    'submit',
    function (event) {

        const dari =
            document.getElementById('tanggalDari').value;

        const sampai =
            document.getElementById('tanggalSampai').value;


        if (dari && sampai && dari > sampai) {

            event.preventDefault();

            Swal.fire({
                icon: 'warning',
                title: 'Tanggal tidak valid',
                text: 'Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai".',
                confirmButtonText: 'OK'
            });

        }

    }
);


// =====================================================
// DATA COPY
// =====================================================

const copyAll =
    <?= json_encode(
        implode("\n", $copy_all_lines),
        JSON_UNESCAPED_UNICODE
    ) ?>;


const copyStudent =
    <?= json_encode(
        $copy_per_student_text,
        JSON_UNESCAPED_UNICODE
    ) ?>;


// =====================================================
// COPY FUNCTION
// =====================================================

async function copyText(type, button) {

    const text =
        type === 'all'
            ? copyAll
            : copyStudent;


    if (!text) {
        return;
    }


    const original =
        button.innerHTML;


    try {

        await navigator.clipboard.writeText(text);


        button.innerHTML = `
            <span class="material-symbols-outlined">
                check
            </span>
            Berhasil Dicopy
        `;


        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: type === 'all'
                ? 'Semua data berhasil disalin.'
                : 'Data per siswa berhasil disalin.',
            timer: 1400,
            showConfirmButton: false
        });


        setTimeout(() => {

            button.innerHTML = original;

        }, 1600);


    } catch (error) {

        // Fallback untuk browser
        // yang tidak mendukung Clipboard API

        const area =
            document.createElement('textarea');

        area.value = text;

        area.style.position = 'fixed';
        area.style.left = '-9999px';

        document.body.appendChild(area);

        area.focus();
        area.select();


        try {

            const success =
                document.execCommand('copy');


            area.remove();


            if (success) {

                button.innerHTML = `
                    <span class="material-symbols-outlined">
                        check
                    </span>
                    Berhasil Dicopy
                `;


                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data sudah disalin ke clipboard.',
                    timer: 1400,
                    showConfirmButton: false
                });


                setTimeout(() => {

                    button.innerHTML = original;

                }, 1600);

            } else {

                throw new Error(
                    'Clipboard gagal.'
                );

            }


        } catch (fallbackError) {

            area.remove();

            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyalin',
                text: 'Browser tidak mengizinkan penyalinan otomatis.',
                confirmButtonText: 'OK'
            });

        }

    }

}

</script>

<script>

// =====================================================
// EDIT CATATAN
// =====================================================

function editCatatan(id, nama, tanggal, catatan) {

    Swal.fire({

        title: 'Edit Catatan',

        html: `
            <div class="text-left">

                <div class="mb-4">

                    <label class="block text-sm font-semibold mb-2">
                        Siswa
                    </label>

                    <input
                        type="text"
                        value="${escapeHtml(nama)}"
                        disabled
                        class="w-full px-3 py-3 rounded-lg bg-surface-container-highest border-0 text-sm opacity-70"
                    >

                </div>


                <div class="mb-4">

                    <label
                        for="swalTanggal"
                        class="block text-sm font-semibold mb-2"
                    >
                        Tanggal
                    </label>

                    <input
                        id="swalTanggal"
                        type="date"
                        value="${tanggal}"
                        class="w-full px-3 py-3 rounded-lg bg-surface-container-highest border-0 focus:ring-2 focus:ring-primary/20"
                    >

                </div>


                <div>

                    <label
                        for="swalCatatan"
                        class="block text-sm font-semibold mb-2"
                    >
                        Catatan
                    </label>

                    <textarea
                        id="swalCatatan"
                        rows="5"
                        maxlength="2000"
                        class="w-full px-3 py-3 rounded-lg bg-surface-container-highest border-0 focus:ring-2 focus:ring-primary/20 resize-y"
                    >${escapeHtml(catatan)}</textarea>

                    <div
                        id="editCharCount"
                        class="text-xs text-on-surface-variant text-right mt-1"
                    >
                        ${catatan.length} / 2000
                    </div>

                </div>

            </div>
        `,

        showCancelButton: true,

        confirmButtonText: 'Simpan Perubahan',

        cancelButtonText: 'Batal',

        reverseButtons: true,

        focusConfirm: false,

        didOpen: () => {

            const textarea =
                document.getElementById('swalCatatan');

            const counter =
                document.getElementById('editCharCount');


            textarea.addEventListener(
                'input',
                () => {

                    counter.textContent =
                        textarea.value.length
                        + ' / 2000';

                }
            );

        },

        preConfirm: () => {

            const tanggalInput =
                document.getElementById('swalTanggal');

            const catatanInput =
                document.getElementById('swalCatatan');

            const tanggal =
                tanggalInput.value;

            const catatan =
                catatanInput.value.trim();


            if (!tanggal) {

                Swal.showValidationMessage(
                    'Tanggal wajib diisi.'
                );

                return false;
            }


            if (!catatan) {

                Swal.showValidationMessage(
                    'Catatan wajib diisi.'
                );

                return false;
            }


            if (catatan.length > 2000) {

                Swal.showValidationMessage(
                    'Catatan maksimal 2000 karakter.'
                );

                return false;
            }


            return {
                tanggal: tanggal,
                catatan: catatan
            };

        }

    }).then(result => {

        if (!result.isConfirmed) {
            return;
        }


        const form =
            document.createElement('form');

        form.method = 'POST';
        form.action = 'proses_edit_catatan.php';

        form.innerHTML = `
            <input
                type="hidden"
                name="id"
                value="${id}"
            >

            <input
                type="hidden"
                name="tanggal"
                value="${escapeHtml(result.value.tanggal)}"
            >

            <textarea
                name="catatan"
                style="display:none"
            >${escapeHtml(result.value.catatan)}</textarea>
        `;

        document.body.appendChild(form);

        form.submit();

    });

}


// =====================================================
// HAPUS CATATAN
// =====================================================

function hapusCatatan(id, nama) {

    Swal.fire({

        icon: 'warning',

        title: 'Hapus Catatan?',

        html: `
            <p class="text-sm">
                Catatan milik
                <strong>${escapeHtml(nama)}</strong>
                akan dihapus secara permanen.
            </p>

            <p class="text-xs text-on-surface-variant mt-2">
                Tindakan ini tidak dapat dibatalkan.
            </p>
        `,

        showCancelButton: true,

        confirmButtonText: 'Ya, Hapus',

        cancelButtonText: 'Batal',

        confirmButtonColor: '#ba1a1a',

        reverseButtons: true

    }).then(result => {

        if (!result.isConfirmed) {
            return;
        }


        const form =
            document.createElement('form');

        form.method = 'POST';

        form.action =
            'proses_hapus_catatan.php';


        form.innerHTML = `
            <input
                type="hidden"
                name="id"
                value="${id}"
            >
        `;


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
// STATUS DARI PROSES EDIT / HAPUS
// =====================================================

const summaryStatus =
    <?= json_encode($status) ?>;


if (summaryStatus === 'edit_success') {

    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Catatan berhasil diperbarui.',
        timer: 1500,
        showConfirmButton: false
    });

}


if (summaryStatus === 'delete_success') {

    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Catatan berhasil dihapus.',
        timer: 1500,
        showConfirmButton: false
    });

}


if (summaryStatus === 'edit_invalid') {

    Swal.fire({
        icon: 'warning',
        title: 'Data tidak valid',
        text: 'Periksa kembali tanggal dan catatan.',
        confirmButtonText: 'OK'
    });

}


if (summaryStatus === 'edit_not_found') {

    Swal.fire({
        icon: 'error',
        title: 'Catatan tidak ditemukan',
        text: 'Catatan yang ingin diedit sudah tidak tersedia.',
        confirmButtonText: 'OK'
    });

}


if (summaryStatus === 'delete_invalid') {

    Swal.fire({
        icon: 'warning',
        title: 'Data tidak valid',
        text: 'ID catatan tidak valid.',
        confirmButtonText: 'OK'
    });

}


if (summaryStatus === 'delete_not_found') {

    Swal.fire({
        icon: 'error',
        title: 'Catatan tidak ditemukan',
        text: 'Catatan yang ingin dihapus sudah tidak tersedia.',
        confirmButtonText: 'OK'
    });

}


// =====================================================
// BERSIHKAN STATUS DARI URL
// =====================================================

if (
    window.history.replaceState &&
    summaryStatus
) {

    const url =
        new URL(window.location.href);

    url.searchParams.delete('status');

    window.history.replaceState(
        {},
        document.title,
        url.pathname + url.search
    );

}

</script>

<?php require_once '../components/footer.php'; ?>