<?php
session_start();
require_once '../config/auth.php';

// Proteksi Login & Admin
require_admin();

// Ambil Data Kelas dari Database beserta info Wali Kelas
$stmt = $pdo->query("
    SELECT c.*, u.nama_lengkap as nama_wali, u.username as username_wali 
    FROM classes c
    LEFT JOIN users u ON c.wali_kelas_id = u.id
    ORDER BY c.jenjang DESC, c.nama_kelas ASC
");
$daftar_kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil Semua Guru untuk Dropdown Wali Kelas
$stmt_guru = $pdo->query("SELECT id, nama_lengkap, username FROM users WHERE role = 'guru' ORDER BY nama_lengkap ASC");
$semua_guru = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);

// Map guru yang sudah jadi wali kelas di kelas mana
$assigned_wali = [];
foreach ($daftar_kelas as $k) {
    if (!empty($k['wali_kelas_id'])) {
        $assigned_wali[$k['wali_kelas_id']] = $k['nama_kelas'];
    }
}

$page_title = "Manajemen Kelas & Wali - EduScore";
$page_heading = "Manajemen Rombel & Wali Kelas";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-5xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">folder_open</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Master Data Akademik</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Daftar Rombel & Wali Kelas</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Kelola jenjang pendidikan, daftar kelas, dan penugasan wali kelas (1 Guru untuk 1 Kelas).
                </p>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center hidden sm:block">
            <span class="text-[11px] font-medium text-text-muted block">Total Rombel</span>
            <span class="text-base font-bold text-text-main tabular-nums"><?= count($daftar_kelas) ?></span>
        </div>
    </div>

    <!-- Alert Notifikasi Status Aksi -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] == 'wali_sudah_ditugaskan'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">warning</span>
                <span><strong>Gagal menyimpan:</strong> Guru yang Anda pilih sudah ditugaskan sebagai Wali Kelas di kelas lain. 1 Guru hanya boleh membina 1 kelas.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_tambah'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Rombel kelas baru berhasil ditambahkan!</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_edit'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Data kelas dan penugasan wali kelas berhasil diperbarui!</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'ada_siswa'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">warning</span>
                <span><strong>Gagal menghapus:</strong> Masih ada data siswa yang terdaftar di kelas ini. Pindahkan atau hapus siswa terlebih dahulu.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'ada_jadwal'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">schedule</span>
                <span><strong>Gagal menghapus:</strong> Kelas ini masih digunakan dalam <strong>Jadwal Pelajaran</strong>. Hapus jadwal terkait terlebih dahulu.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'gagal_terkait'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-amber-200 bg-warning-subtle text-warning">
                <span class="material-symbols-outlined text-base">error</span>
                <span><strong>Gagal menghapus:</strong> Data kelas ini masih terikat dengan data nilai atau catatan akademik lainnya.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'sukses_hapus'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Kelas berhasil dihapus dari sistem.</span>
            </div>
        <?php elseif ($_GET['pesan'] == 'error_server'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-rose-200 bg-danger-subtle text-danger">
                <span class="material-symbols-outlined text-base">report</span>
                <span>Terjadi kendala pada database saat memproses data kelas.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Form Tambah Kelas Baru -->
    <div class="bg-surface-card rounded-xl border border-border-main p-5 sm:p-6 shadow-xs">
        <h3 class="text-sm font-bold text-text-main mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">add_circle</span>
            Tambah Rombel / Kelas Baru
        </h3>

        <form action="proses_kelas.php" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <input type="hidden" name="aksi" value="tambah">
            
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="jenjang">Jenjang</label>
                <select id="jenjang" name="jenjang" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 font-medium cursor-pointer min-h-[42px]" required>
                    <option value="SMP">SMP</option>
                    <option value="SMA" selected>SMA</option>
                </select>
            </div>

            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="nama_kelas">Nama Rombel / Kelas</label>
                <input id="nama_kelas" type="text" name="nama_kelas" required placeholder="Contoh: 10 IPA 1, 7A, 12..." class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 font-medium min-h-[42px] placeholder:text-slate-400">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="wali_kelas_id">Wali Kelas (Opsional)</label>
                <select id="wali_kelas_id" name="wali_kelas_id" class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 font-medium cursor-pointer min-h-[42px]">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php foreach ($semua_guru as $guru): ?>
                        <?php 
                        $is_assigned = isset($assigned_wali[$guru['id']]);
                        ?>
                        <option value="<?= $guru['id'] ?>" <?= $is_assigned ? 'disabled class="text-slate-400 bg-slate-50"' : '' ?>>
                            <?= htmlspecialchars($guru['nama_lengkap']) ?> <?= $is_assigned ? '(Wali ' . htmlspecialchars($assigned_wali[$guru['id']]) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-4 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-xs min-h-[42px]">
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Kelas -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">
                Daftar Seluruh Kelas & Wali
            </h3>
            <span class="text-xs text-text-muted bg-white border border-slate-200 px-2.5 py-1 rounded-md tabular-nums">
                <?= count($daftar_kelas) ?> Kelas
            </span>
        </div>

        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[11px] uppercase font-semibold text-text-muted border-b border-border-main">
                        <th class="px-4 py-3 w-16 text-center">No</th>
                        <th class="px-4 py-3 w-24">Jenjang</th>
                        <th class="px-4 py-3">Nama Rombel / Kelas</th>
                        <th class="px-4 py-3">Wali Kelas</th>
                        <th class="px-4 py-3 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-main">
                    <?php if (empty($daftar_kelas)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-text-muted">
                                Belum ada data kelas yang didaftarkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftar_kelas as $kelas): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3.5 text-center text-text-muted font-medium tabular-nums"><?= $no++; ?></td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $kelas['jenjang'] == 'SMA' ? 'bg-primary-subtle text-primary border border-primary/20' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
                                    <?= htmlspecialchars($kelas['jenjang']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-xs md:text-sm text-text-main">
                                <?= htmlspecialchars($kelas['nama_kelas']); ?>
                            </td>
                            <td class="px-4 py-3.5">
                                <?php if (!empty($kelas['nama_wali'])): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-600 text-base">verified_user</span>
                                        <div>
                                            <span class="font-semibold text-text-main block"><?= htmlspecialchars($kelas['nama_wali']) ?></span>
                                            <span class="text-[10px] text-text-muted block">@<?= htmlspecialchars($kelas['username_wali']) ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        <span class="material-symbols-outlined text-[14px]">help_outline</span>
                                        Belum Ada Wali
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($kelas)) ?>)" 
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors" 
                                            title="Edit Kelas & Wali Kelas">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <a href="proses_kelas.php?hapus=<?= $kelas['id']; ?>" 
                                       onclick="konfirmasiLink(event, this.href, 'Hapus rombel kelas <?= htmlspecialchars($kelas['nama_kelas']); ?>?')" 
                                       class="inline-flex items-center justify-center p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors" 
                                       title="Hapus Kelas">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal Edit Kelas & Wali Kelas -->
<div id="modalEditKelas" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">edit</span>
                Edit Kelas & Wali Kelas
            </h3>
            <button type="button" onclick="tutupModalEdit()" class="text-text-muted hover:text-danger p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_kelas.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="class_id" id="edit_class_id">

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="edit_jenjang">Jenjang</label>
                <select id="edit_jenjang" name="jenjang" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5" required>
                    <option value="SMP">SMP</option>
                    <option value="SMA">SMA</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="edit_nama_kelas">Nama Rombel / Kelas</label>
                <input type="text" id="edit_nama_kelas" name="nama_kelas" required class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5">
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-main mb-1" for="edit_wali_kelas_id">Wali Kelas</label>
                <select id="edit_wali_kelas_id" name="wali_kelas_id" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5">
                    <option value="">-- Belum Ada Wali Kelas --</option>
                    <?php foreach ($semua_guru as $guru): ?>
                        <option value="<?= $guru['id'] ?>" id="opt_guru_<?= $guru['id'] ?>">
                            <?= htmlspecialchars($guru['nama_lengkap']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-text-muted mt-1 block">1 Guru hanya boleh menjadi wali kelas untuk 1 kelas.</span>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalEdit()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const assignedWaliMap = <?= json_encode($assigned_wali) ?>;

    function bukaModalEdit(kelas) {
        document.getElementById('edit_class_id').value = kelas.id;
        document.getElementById('edit_jenjang').value = kelas.jenjang;
        document.getElementById('edit_nama_kelas').value = kelas.nama_kelas;
        
        const selectWali = document.getElementById('edit_wali_kelas_id');
        
        // Reset disabled states for options based on current assignment
        Array.from(selectWali.options).forEach(opt => {
            if (!opt.value) return;
            const gid = opt.value;
            const assignedClass = assignedWaliMap[gid];
            // If assigned to ANOTHER class, disable it
            if (assignedClass && parseInt(kelas.wali_kelas_id) !== parseInt(gid)) {
                opt.disabled = true;
                opt.text = opt.text.split(' (Wali')[0] + ' (Wali ' + assignedClass + ')';
            } else {
                opt.disabled = false;
                opt.text = opt.text.split(' (Wali')[0];
            }
        });

        selectWali.value = kelas.wali_kelas_id || '';
        document.getElementById('modalEditKelas').classList.remove('hidden');
    }

    function tutupModalEdit() {
        document.getElementById('modalEditKelas').classList.add('hidden');
    }

    function konfirmasiLink(event, url, message) {
        event.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Tindakan',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#be123c',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

<?php require_once '../components/footer.php'; ?>