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

$page_title = "EduScore - Mata Pelajaran";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary hidden md:flex">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">open_in_new</span>
            </div>
            <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
            <span class="text-on-surface-variant ml-2 text-sm font-medium hidden md:block">| Manajemen Mata Pelajaran</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-5xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Daftar Mata Pelajaran</h1>
        <p class="text-on-surface-variant text-sm">Kelola mata pelajaran yang akan dinilai di sistem.</p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-surface-container-low text-xs uppercase font-bold text-on-surface-variant border-b border-outline-variant/20">
                <tr>
                    <th class="px-6 py-4 w-16 text-center">No</th>
                    <th class="px-6 py-4">Nama Mata Pelajaran</th>
                    <th class="px-6 py-4 w-20 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-outline-variant/10">
                <?php $no = 1; foreach ($daftar_mapel as $m): ?>
                <tr class="hover:bg-surface-container-low/50 group">
                    <td class="px-6 py-4 text-center text-on-surface-variant"><?= $no++; ?></td>
                    <td class="px-6 py-4 font-bold"><?= htmlspecialchars($m['nama_mapel']); ?></td>
                    <td class="px-6 py-4 text-center">
                        <a href="proses_mapel.php?hapus=<?= $m['id']; ?>" onclick="return confirm('Hapus mapel?')" class="text-error opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>

                <tr class="bg-primary/5 border-t-2 border-primary/20">
                    <form action="proses_mapel.php" method="POST">
                        <td class="px-6 py-4 text-center text-primary"><span class="material-symbols-outlined">add</span></td>
                        <td class="px-6 py-3">
                            <input type="text" name="nama_mapel" required class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 px-2 py-2 text-sm font-bold text-primary placeholder-primary/30" placeholder="Ketik Nama Mapel Baru...">
                        </td>
                        <td class="px-6 py-3 text-center">
                            <button type="submit" class="bg-primary text-on-primary px-4 py-2 rounded-lg text-xs font-bold hover:bg-primary-container transition-all">Simpan</button>
                        </td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<?php require_once '../components/footer.php'; ?>