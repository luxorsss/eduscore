<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Data Kelas dari Database
$stmt = $pdo->query("SELECT * FROM classes ORDER BY jenjang DESC, nama_kelas ASC");
$daftar_kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manajemen Kelas - EduScore";
$page_heading = "Manajemen Rombongan Belajar";
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
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Daftar Rombel / Kelas</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Kelola jenjang pendidikan dan daftar rombongan belajar yang terdaftar di sekolah.
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
        <?php if ($_GET['pesan'] == 'ada_siswa'): ?>
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
                <span>Terjadi kendala pada database saat memproses penghapusan.</span>
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

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="nama_kelas">Nama Rombel / Kelas</label>
                <input id="nama_kelas" type="text" name="nama_kelas" required placeholder="Contoh: 10 IPA 1, 7A, 12 IPS 2..." class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3 py-2.5 font-medium min-h-[42px] placeholder:text-slate-400">
            </div>

            <div class="sm:col-span-3">
                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-4 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-xs min-h-[42px]">
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Simpan Kelas</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Kelas -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">
                Daftar Seluruh Kelas
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
                        <th class="px-4 py-3 w-28">Jenjang</th>
                        <th class="px-4 py-3">Nama Rombel / Kelas</th>
                        <th class="px-4 py-3 w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-main">
                    <?php if (empty($daftar_kelas)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-text-muted">
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
                            <td class="px-4 py-3.5 text-center">
                                <a href="proses_kelas.php?hapus=<?= $kelas['id']; ?>" 
                                   onclick="konfirmasiLink(event, this.href, 'Hapus rombel kelas <?= htmlspecialchars($kelas['nama_kelas']); ?>?')" 
                                   class="inline-flex items-center justify-center p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors" 
                                   title="Hapus Kelas">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php require_once '../components/footer.php'; ?>