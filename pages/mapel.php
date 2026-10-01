<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Data Mapel
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY nama_mapel ASC");
$daftar_mapel = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mata Pelajaran - EduScore";
$page_heading = "Manajemen Mata Pelajaran";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-5xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">book</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Master Data Kurikulum</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Daftar Mata Pelajaran</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Kelola mata pelajaran dan bidang studi yang dinilai dalam sistem.
                </p>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-center hidden sm:block">
            <span class="text-[11px] font-medium text-text-muted block">Total Mapel</span>
            <span class="text-base font-bold text-text-main tabular-nums"><?= count($daftar_mapel) ?></span>
        </div>
    </div>

    <!-- Form Tambah Mapel Baru -->
    <div class="bg-surface-card rounded-xl border border-border-main p-5 sm:p-6 shadow-xs">
        <h3 class="text-sm font-bold text-text-main mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-base">add_circle</span>
            Tambah Mata Pelajaran Baru
        </h3>

        <form action="proses_mapel.php" method="POST" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
            <input type="hidden" name="action" value="tambah"> 
            
            <div class="flex-1">
                <label class="block text-xs font-semibold text-text-main mb-1.5" for="nama_mapel_input">Nama Mata Pelajaran</label>
                <input id="nama_mapel_input" type="text" name="nama_mapel" required placeholder="Contoh: Matematika Wajib, Bahasa Indonesia, Biologi..." class="w-full bg-white text-text-main text-xs rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 font-medium min-h-[42px] placeholder:text-slate-400">
            </div>

            <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-5 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-colors shadow-xs min-h-[42px]">
                <span class="material-symbols-outlined text-base">add</span>
                <span>Simpan Mapel</span>
            </button>
        </form>
    </div>

    <!-- Tabel Daftar Mapel -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">
                Daftar Seluruh Mata Pelajaran
            </h3>
            <span class="text-xs text-text-muted bg-white border border-slate-200 px-2.5 py-1 rounded-md tabular-nums">
                <?= count($daftar_mapel) ?> Mapel
            </span>
        </div>

        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[11px] uppercase font-semibold text-text-muted border-b border-border-main">
                        <th class="px-4 py-3 w-16 text-center">No</th>
                        <th class="px-4 py-3">Nama Mata Pelajaran</th>
                        <th class="px-4 py-3 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-main">
                    <?php if (empty($daftar_mapel)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-text-muted">
                                Belum ada mata pelajaran yang didaftarkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftar_mapel as $m): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-4 py-3.5 text-center text-text-muted font-medium tabular-nums"><?= $no++; ?></td>
                            
                            <td class="px-4 py-3.5">
                                <!-- Mode Teks Normal -->
                                <div id="text_<?= $m['id']; ?>" class="font-bold text-xs md:text-sm text-text-main">
                                    <?= htmlspecialchars($m['nama_mapel']); ?>
                                </div>
                                
                                <!-- Mode Form Edit -->
                                <form id="form_<?= $m['id']; ?>" action="proses_mapel.php" method="POST" class="hidden items-center gap-2 w-full max-w-md">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id_mapel" value="<?= $m['id']; ?>">
                                    <input type="text" name="nama_mapel" value="<?= htmlspecialchars($m['nama_mapel']); ?>" required 
                                           class="w-full bg-white border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-lg px-3 py-1.5 text-xs font-semibold text-text-main">
                                    <button type="submit" class="p-1.5 rounded-lg bg-primary-subtle text-primary hover:bg-primary hover:text-white transition-colors" title="Simpan Perubahan">
                                        <span class="material-symbols-outlined text-lg">check</span>
                                    </button>
                                    <button type="button" onclick="toggleEdit(<?= $m['id']; ?>)" class="p-1.5 rounded-lg text-text-muted hover:bg-slate-200 transition-colors" title="Batal">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3.5 text-center space-x-1 whitespace-nowrap">
                                <!-- Tombol Edit -->
                                <button type="button" onclick="toggleEdit(<?= $m['id']; ?>)" class="p-1.5 rounded-lg text-text-muted hover:text-primary hover:bg-slate-100 transition-colors" title="Edit Mata Pelajaran">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <!-- Tombol Hapus -->
                                <a href="proses_mapel.php?hapus=<?= $m['id']; ?>" 
                                   onclick="konfirmasiLink(event, this.href, 'Hapus mata pelajaran <?= htmlspecialchars($m['nama_mapel']); ?>?')" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors inline-block" 
                                   title="Hapus Mapel">
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

<script>
function toggleEdit(id) {
    const textDiv = document.getElementById('text_' + id);
    const formDiv = document.getElementById('form_' + id);
    
    if (textDiv.classList.contains('hidden')) {
        textDiv.classList.remove('hidden');
        formDiv.classList.add('hidden');
        formDiv.classList.remove('flex');
    } else {
        textDiv.classList.add('hidden');
        formDiv.classList.remove('hidden');
        formDiv.classList.add('flex');
        const input = formDiv.querySelector('input[type="text"]');
        if (input) input.focus();
    }
}
</script>

<?php require_once '../components/footer.php'; ?>