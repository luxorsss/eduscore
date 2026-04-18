<div id="sidebarOverlay" class="fixed inset-0 bg-on-surface/40 z-40 hidden opacity-0 transition-opacity duration-300 md:hidden backdrop-blur-sm"></div>

<aside id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 bg-surface-container-high flex flex-col py-8 z-50 border-r border-outline-variant/20 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
    
    <div class="px-6 mb-8 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-black shadow-md">E</div>
            <div>
                <div class="text-xl font-black text-primary uppercase tracking-tighter">EduScore</div>
            </div>
        </div>
        <button id="closeSidebarBtn" class="md:hidden text-on-surface-variant hover:text-error bg-surface-container-highest w-8 h-8 rounded-full flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>

    <div class="flex-1 px-4 space-y-1">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        
        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'dashboard.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            <span class="font-medium text-sm">Dashboard</span>
        </a>
        <a href="siswa.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'siswa.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">group</span>
            <span class="font-medium text-sm">Data Siswa</span>
        </a>
        <a href="input_nilai.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'input_nilai.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">edit_square</span>
            <span class="font-medium text-sm">Input Nilai</span>
        </a>
        <a href="analisa.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'analisa.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">analytics</span>
            <span class="font-medium text-sm">Analisa Nilai</span>
        </a>
        <a href="jadwal.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'jadwal.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">calendar_month</span>
            <span class="font-medium text-sm">Jadwal & Mapel</span>
        </a>
        <a href="mapel.php" class="flex items-center space-x-3 px-4 py-3.5 rounded-xl transition-all <?= ($current_page == 'mapel.php') ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined text-[20px]">book</span>
            <span class="font-medium text-sm">Mata Pelajaran</span>
        </a>
    </div>

    <div class="px-6 border-t border-outline-variant/20 pt-6 mt-auto">
        <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 text-error hover:bg-error-container/50 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="font-bold text-sm">Keluar Akun</span>
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            // Animasi Buka
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden'; // Kunci scroll layar belakang
        } else {
            // Animasi Tutup
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
            document.body.style.overflow = 'auto'; // Buka scroll
        }
    }

    // Tutup saat area gelap diklik atau tombol silang diklik
    document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);
    document.getElementById('closeSidebarBtn').addEventListener('click', toggleSidebar);
</script>