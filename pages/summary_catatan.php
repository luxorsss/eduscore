<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

// Ambil data kelas
if ($is_admin) {
    $kelas_stmt = $pdo->query("
        SELECT id, jenjang, nama_kelas
        FROM classes
        ORDER BY jenjang, nama_kelas
    ");
    $kelas_list = $kelas_stmt->fetchAll(PDO::FETCH_ASSOC);
    $class_id = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT) ?: 0;
} else {
    $kelas_list = [$wali_kelas];
    $class_id = (int)$wali_kelas['id']; // Terkunci otomatis ke kelas binaan
}
$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT) ?: 0;
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$status = $_GET['status'] ?? '';

// Query catatan
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

// Data untuk copy
$copy_all_lines = [];
$copy_grouped = [];

foreach ($notes as $note) {
    $date_display = date('d/m/Y', strtotime($note['tanggal']));
    $clean_note = preg_replace('/\s+/', ' ', trim($note['catatan']));

    $copy_all_lines[] = $note['nama_siswa'] . ' - ' . $date_display . ' - ' . $clean_note;

    $student_key = (string) $note['student_id'];
    if (!isset($copy_grouped[$student_key])) {
        $copy_grouped[$student_key] = [
            'nama' => $note['nama_siswa'],
            'notes' => []
        ];
    }
    $copy_grouped[$student_key]['notes'][] = $date_display . ' - ' . $clean_note;
}

// Format copy per siswa
$copy_per_student_lines = [];
foreach ($copy_grouped as $group) {
    $copy_per_student_lines[] = strtoupper($group['nama']);
    foreach ($group['notes'] as $line) {
        $copy_per_student_lines[] = $line;
    }
    $copy_per_student_lines[] = '';
}
$copy_per_student_text = rtrim(implode("\n", $copy_per_student_lines));

$page_title = "Summary Catatan Siswa - EduScore";
$page_heading = "Summary Catatan Siswa";
require_once '../components/header.php';
?>

<main class="flex-grow max-w-6xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">

    <!-- Header & Ringkasan -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Rekapitulasi Jurnal Bimbingan</span>
            <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Summary Catatan Siswa</h2>
            <p class="text-xs text-text-muted mt-0.5">
                Pantau riwayat perilaku, tindak lanjut kedisiplinan, dan ekspor data untuk laporan wali kelas.
            </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 md:flex-none">
                <span class="text-[11px] font-medium text-text-muted block">Catatan Ditemukan</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= count($notes) ?></span>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center flex-1 md:flex-none">
                <span class="text-[11px] font-medium text-text-muted block">Siswa Tercatat</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= count($copy_grouped) ?></span>
            </div>
        </div>
    </div>

    <!-- Panel Filter -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs">
        <div class="mb-4 pb-3 border-b border-border-main">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-lg">filter_alt</span>
                Filter Catatan
            </h3>
        </div>

        <form method="GET" id="filterForm" class="flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Filter Kelas -->
                <div class="flex flex-col gap-1.5">
                    <label for="filterKelas" class="text-xs font-semibold text-text-main">
                        Kelas
                    </label>
                    <select
                        id="filterKelas"
                        name="kelas_id"
                        class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px]"
                    >
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= (int) $kelas['id'] ?>" <?= $class_id == $kelas['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?> (<?= htmlspecialchars($kelas['jenjang']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Siswa -->
                <div class="flex flex-col gap-1.5">
                    <label for="filterSiswa" class="text-xs font-semibold text-text-main">
                        Siswa
                    </label>
                    <select
                        id="filterSiswa"
                        name="student_id"
                        class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors cursor-pointer font-medium min-h-[44px] disabled:bg-slate-50 disabled:text-text-muted disabled:cursor-not-allowed"
                        <?= !$class_id ? 'disabled' : '' ?>
                    >
                        <option value="">Semua Siswa</option>
                    </select>
                </div>

                <!-- Tanggal Dari -->
                <div class="flex flex-col gap-1.5">
                    <label for="tanggalDari" class="text-xs font-semibold text-text-main">
                        Rentang Dari
                    </label>
                    <input
                        type="date"
                        id="tanggalDari"
                        name="dari"
                        value="<?= htmlspecialchars($dari) ?>"
                        class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors font-medium min-h-[44px]"
                    >
                </div>

                <!-- Tanggal Sampai -->
                <div class="flex flex-col gap-1.5">
                    <label for="tanggalSampai" class="text-xs font-semibold text-text-main">
                        Rentang Sampai
                    </label>
                    <input
                        type="date"
                        id="tanggalSampai"
                        name="sampai"
                        value="<?= htmlspecialchars($sampai) ?>"
                        class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 transition-colors font-medium min-h-[44px]"
                    >
                </div>
            </div>

            <!-- Tombol Filter -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button
                    type="submit"
                    class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-5 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-xs min-h-[44px]"
                >
                    <span class="material-symbols-outlined text-base">search</span>
                    <span>Terapkan Filter</span>
                </button>

                <a
                    href="summary_catatan.php"
                    class="px-4 py-2.5 rounded-lg border border-slate-200 text-xs font-semibold text-text-muted hover:text-text-main hover:bg-slate-50 transition-colors min-h-[44px] flex items-center justify-center"
                >
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Toolbar Ekspor & Salin Data -->
    <div class="flex flex-col sm:flex-row gap-3">
        <button
            type="button"
            onclick="copyText('all', this)"
            <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-surface-card hover:bg-slate-50 text-text-main border border-border-main px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-xs min-h-[44px]"
        >
            <span class="material-symbols-outlined text-base text-primary">content_copy</span>
            <span>Salin Seluruh Catatan (Teks Bersambung)</span>
        </button>

        <button
            type="button"
            onclick="copyText('student', this)"
            <?= !$notes ? 'disabled' : '' ?>
            class="flex-1 bg-surface-card hover:bg-slate-50 text-text-main border border-border-main px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-xs min-h-[44px]"
        >
            <span class="material-symbols-outlined text-base text-primary">groups</span>
            <span>Salin Dikelompokkan Per Siswa</span>
        </button>
    </div>

    <!-- Daftar Catatan -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">
                Daftar Catatan Terdata
            </h3>
            <span class="text-xs text-text-muted bg-white border border-slate-200 px-2.5 py-1 rounded-md tabular-nums">
                <?= count($notes) ?> Catatan
            </span>
        </div>

        <?php if (!$notes): ?>
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-2xl">inbox</span>
                </div>
                <p class="font-bold text-sm text-text-main">Tidak Ada Catatan</p>
                <p class="text-xs text-text-muted mt-1 max-w-sm mx-auto">
                    Tidak ditemukan data catatan siswa dengan kriteria filter yang dipilih.
                </p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-border-main">
                <?php foreach ($notes as $note): ?>
                    <div class="p-5 hover:bg-slate-50/60 transition-colors">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            
                            <!-- Identitas Siswa & Kelas -->
                            <div class="min-w-0 flex items-start gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 text-primary border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                    <?= strtoupper(substr($note['nama_siswa'], 0, 2)) ?>
                                </div>
                                <div>
                                    <div class="font-bold text-xs md:text-sm text-text-main">
                                        <?= htmlspecialchars($note['nama_siswa']) ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-text-muted mt-1">
                                        <span class="badge-grade-neutral px-2 py-0.5 rounded font-semibold text-[10px]">
                                            <?= htmlspecialchars($note['nama_kelas']) ?> (<?= htmlspecialchars($note['jenjang']) ?>)
                                        </span>
                                        <span>•</span>
                                        <span class="tabular-nums font-medium">
                                            <?= date('d M Y', strtotime($note['tanggal'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Isi Catatan & Aksi -->
                            <div class="flex flex-col md:items-end gap-3 md:max-w-xl w-full">
                                <div class="text-xs md:text-sm text-text-main bg-slate-50 border border-slate-200/80 rounded-lg p-3 w-full whitespace-pre-line break-words leading-relaxed font-sans">
                                    <?= htmlspecialchars($note['catatan']) ?>
                                </div>

                                <div class="flex items-center gap-2 self-start md:self-end">
                                    <button
                                        type="button"
                                        onclick='editCatatan(
                                            <?= (int) $note['id'] ?>,
                                            <?= json_encode($note['nama_siswa'], JSON_UNESCAPED_UNICODE) ?>,
                                            <?= json_encode($note['tanggal']) ?>,
                                            <?= json_encode($note['catatan'], JSON_UNESCAPED_UNICODE) ?>
                                        )'
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-200 bg-white text-text-main text-xs font-semibold hover:bg-slate-50 transition-colors min-h-[38px]"
                                    >
                                        <span class="material-symbols-outlined text-[18px] text-text-muted">edit</span>
                                        <span>Edit</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick='hapusCatatan(
                                            <?= (int) $note['id'] ?>,
                                            <?= json_encode($note['nama_siswa'], JSON_UNESCAPED_UNICODE) ?>
                                        )'
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-rose-200 bg-white text-danger text-xs font-semibold hover:bg-danger-subtle transition-colors min-h-[38px]"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                        <span>Hapus</span>
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
// Filter Siswa dinamis berdasarkan Kelas
const filterKelas = document.getElementById('filterKelas');
const filterSiswa = document.getElementById('filterSiswa');
const selectedStudent = <?= json_encode((string) $student_id) ?>;

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
            'siswa_catatan_api.php?class_id=' + encodeURIComponent(filterKelas.value),
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
        filterSiswa.innerHTML = '<option value="">Semua Siswa</option>';

        if (!data.success || !Array.isArray(data.students)) {
            return;
        }

        data.students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.nama;

            if (String(student.id) === selectedStudent) {
                option.selected = true;
            }

            filterSiswa.appendChild(option);
        });

        filterSiswa.disabled = false;

    } catch (error) {
        console.error('Gagal memuat siswa:', error);
        filterSiswa.innerHTML = '<option value="">Gagal memuat siswa</option>';
        filterSiswa.disabled = true;
    }
}

filterKelas.addEventListener('change', function () {
    filterSiswa.value = '';
    loadFilterStudents();
});

loadFilterStudents();

// Validasi rentang tanggal
const filterForm = document.getElementById('filterForm');
filterForm.addEventListener('submit', function (event) {
    const dari = document.getElementById('tanggalDari').value;
    const sampai = document.getElementById('tanggalSampai').value;

    if (dari && sampai && dari > sampai) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Rentang Tanggal Tidak Valid',
            text: 'Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai".',
            confirmButtonColor: '#0f2942'
        });
    }
});

// Fitur Salin Teks ke Clipboard
const copyAll = <?= json_encode(implode("\n", $copy_all_lines), JSON_UNESCAPED_UNICODE) ?>;
const copyStudent = <?= json_encode($copy_per_student_text, JSON_UNESCAPED_UNICODE) ?>;

async function copyText(type, button) {
    const text = (type === 'all') ? copyAll : copyStudent;
    if (!text) return;

    const original = button.innerHTML;

    try {
        await navigator.clipboard.writeText(text);
        button.innerHTML = `
            <span class="material-symbols-outlined text-base text-success">check</span>
            <span>Berhasil Disalin!</span>
        `;

        Swal.fire({
            icon: 'success',
            title: 'Teks Berhasil Disalin',
            text: type === 'all' ? 'Seluruh baris catatan berhasil disalin ke clipboard.' : 'Catatan terkelompok per siswa berhasil disalin.',
            timer: 1500,
            showConfirmButton: false
        });

        setTimeout(() => {
            button.innerHTML = original;
        }, 1800);

    } catch (error) {
        const area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.focus();
        area.select();

        try {
            const success = document.execCommand('copy');
            area.remove();

            if (success) {
                button.innerHTML = `
                    <span class="material-symbols-outlined text-base text-success">check</span>
                    <span>Berhasil Disalin!</span>
                `;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disalin',
                    text: 'Data telah disalin ke clipboard.',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => {
                    button.innerHTML = original;
                }, 1800);
            } else {
                throw new Error('Fallback clipboard failed.');
            }
        } catch (fallbackError) {
            area.remove();
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyalin',
                text: 'Browser tidak mengizinkan akses ke clipboard.',
                confirmButtonColor: '#0f2942'
            });
        }
    }
}

// Edit Catatan Modal
function editCatatan(id, nama, tanggal, catatan) {
    Swal.fire({
        title: 'Perbarui Catatan Siswa',
        html: `
            <div class="text-left flex flex-col gap-4 text-xs font-sans">
                <div>
                    <label class="block font-semibold text-text-main mb-1">Nama Siswa</label>
                    <input type="text" value="${escapeHtml(nama)}" disabled class="w-full px-3 py-2 text-xs rounded-lg bg-slate-100 border border-slate-200 text-text-muted font-medium cursor-not-allowed">
                </div>

                <div>
                    <label for="swalTanggal" class="block font-semibold text-text-main mb-1">Tanggal Kejadian</label>
                    <input id="swalTanggal" type="date" value="${tanggal}" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 font-medium">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="swalCatatan" class="block font-semibold text-text-main">Uraian Catatan</label>
                        <span id="editCharCount" class="text-[10px] text-text-muted tabular-nums">${catatan.length} / 2000</span>
                    </div>
                    <textarea id="swalCatatan" rows="5" maxlength="2000" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 resize-y leading-relaxed">${escapeHtml(catatan)}</textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan Perubahan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0f2942',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        customClass: {
            popup: 'rounded-xl border border-slate-200 shadow-lg text-sm'
        },
        didOpen: () => {
            const textarea = document.getElementById('swalCatatan');
            const counter = document.getElementById('editCharCount');
            textarea.addEventListener('input', () => {
                counter.textContent = textarea.value.length + ' / 2000';
            });
        },
        preConfirm: () => {
            const tanggalInput = document.getElementById('swalTanggal');
            const catatanInput = document.getElementById('swalCatatan');
            const tanggal = tanggalInput.value;
            const catatan = catatanInput.value.trim();

            if (!tanggal) {
                Swal.showValidationMessage('Tanggal wajib diisi.');
                return false;
            }
            if (!catatan) {
                Swal.showValidationMessage('Isi catatan tidak boleh kosong.');
                return false;
            }
            if (catatan.length > 2000) {
                Swal.showValidationMessage('Catatan maksimal 2000 karakter.');
                return false;
            }

            return { tanggal, catatan };
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'proses_edit_catatan.php';

        form.innerHTML = `
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="tanggal" value="${escapeHtml(result.value.tanggal)}">
            <textarea name="catatan" style="display:none">${escapeHtml(result.value.catatan)}</textarea>
        `;

        document.body.appendChild(form);
        form.submit();
    });
}

// Hapus Catatan Modal
function hapusCatatan(id, nama) {
    Swal.fire({
        title: 'Hapus Catatan Ini?',
        html: `
            <p class="text-xs text-text-muted">
                Catatan perilaku siswa <strong>${escapeHtml(nama)}</strong> akan dihapus permanen dari sistem.
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#be123c',
        cancelButtonColor: '#64748b',
        customClass: {
            popup: 'rounded-xl border border-slate-200 shadow-lg text-sm'
        }
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'proses_hapus_catatan.php';
        form.innerHTML = `<input type="hidden" name="id" value="${id}">`;

        document.body.appendChild(form);
        form.submit();
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

// Feedback status
const summaryStatus = <?= json_encode($status) ?>;

if (summaryStatus === 'edit_success') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Diperbarui',
        text: 'Catatan siswa telah berhasil diperbarui.',
        confirmButtonColor: '#0f2942',
        timer: 1600,
        showConfirmButton: false
    });
} else if (summaryStatus === 'delete_success') {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Dihapus',
        text: 'Catatan siswa telah dihapus dari sistem.',
        confirmButtonColor: '#0f2942',
        timer: 1600,
        showConfirmButton: false
    });
} else if (summaryStatus === 'edit_invalid') {
    Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Valid',
        text: 'Periksa kembali tanggal dan isi catatan.',
        confirmButtonColor: '#0f2942'
    });
} else if (summaryStatus === 'edit_not_found') {
    Swal.fire({
        icon: 'error',
        title: 'Catatan Tidak Ditemukan',
        text: 'Catatan yang ingin diedit sudah tidak tersedia.',
        confirmButtonColor: '#0f2942'
    });
} else if (summaryStatus === 'delete_invalid' || summaryStatus === 'delete_not_found') {
    Swal.fire({
        icon: 'error',
        title: 'Gagal Menghapus',
        text: 'Catatan tidak ditemukan atau ID tidak valid.',
        confirmButtonColor: '#0f2942'
    });
}

if (window.history.replaceState && summaryStatus) {
    const url = new URL(window.location.href);
    url.searchParams.delete('status');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}
</script>

<?php require_once '../components/footer.php'; ?>