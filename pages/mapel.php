<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">book</span>
            </div>
            <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
            <span class="text-on-surface-variant ml-2 text-sm font-medium hidden md:block">| Mata Pelajaran</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-5xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Daftar Mata Pelajaran</h1>
            <p class="text-on-surface-variant text-sm">Kelola daftar mata pelajaran yang diajarkan di sekolah.</p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-[0px_4px_16px_rgba(26,28,30,0.04)] overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[500px]">
                <thead class="bg-surface-container-low text-xs uppercase font-semibold text-on-surface-variant border-b border-outline-variant/20">
                    <tr>
                        <th class="px-6 py-4 w-16 text-center">No</th>
                        <th class="px-6 py-4">Nama Mata Pelajaran</th>
                        <th class="px-6 py-4 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-on-surface divide-y divide-outline-variant/10">
                    
                    <tr class="hover:bg-surface-container-low/50 transition-colors group">
                        <td class="px-6 py-4 text-center font-medium text-on-surface-variant">1</td>
                        <td class="px-6 py-4 font-bold text-base">Fisika</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="w-8 h-8 rounded hover:bg-[#d6e3ff] text-primary flex items-center justify-center transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                <button class="w-8 h-8 rounded hover:bg-error-container text-error flex items-center justify-center transition-colors" title="Hapus">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr class="hover:bg-surface-container-low/50 transition-colors group">
                        <td class="px-6 py-4 text-center font-medium text-on-surface-variant">2</td>
                        <td class="px-6 py-4 font-bold text-base">Matematika Wajib</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="w-8 h-8 rounded hover:bg-[#d6e3ff] text-primary flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                <button class="w-8 h-8 rounded hover:bg-error-container text-error flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr class="bg-primary/5 border-t-2 border-primary/20">
                        <form action="proses_mapel.php" method="POST">
                            <td class="px-6 py-4 text-center">
                                <span class="material-symbols-outlined text-primary text-sm">add</span>
                            </td>
                            <td class="px-6 py-3">
                                <input type="text" name="nama_mapel" required class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 px-3 py-2 text-sm font-bold text-primary placeholder-primary/50" placeholder="Ketik Nama Mata Pelajaran Baru...">
                            </td>
                            <td class="px-6 py-3 text-center">
                                <button type="submit" class="bg-primary text-on-primary px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-primary-container transition-colors w-full">
                                    Simpan
                                </button>
                            </td>
                        </form>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once '../components/footer.php'; ?>