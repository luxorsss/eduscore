<div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/40 z-40 hidden opacity-0 transition-opacity duration-200 md:hidden"></div>

<aside id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 bg-surface-card flex flex-col py-6 z-50 border-r border-border-main transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out">
    
    <div class="px-6 mb-6 flex items-center justify-between">
        <a href="dashboard.php" class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center text-white font-bold text-base shadow-xs group-hover:bg-primary-hover transition-colors">
                E
            </div>
            <div>
                <span class="text-base font-bold text-primary tracking-tight block">EduScore</span>
                <span class="text-[11px] text-text-muted block -mt-1 font-medium">Nilai & Akademik</span>
            </div>
        </a>
        <button id="closeSidebarBtn" aria-label="Tutup navigasi sidebar" class="md:hidden text-text-muted hover:text-danger hover:bg-slate-100 w-11 h-11 rounded-lg flex items-center justify-center transition-colors focus-ring">
            <span class="material-symbols-outlined text-xl">close</span>
        </button>
    </div>

    <div class="flex-1 px-3 space-y-1 overflow-y-auto custom-scroll">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        require_once __DIR__ . '/../config/auth.php';
        $user_role = $_SESSION['role'] ?? 'guru';
        $is_user_admin = ($user_role === 'admin');
        $user_wk = get_user_wali_kelas($pdo, $_SESSION['user_id']);
        $has_wali_kelas = ($user_wk !== false && $user_wk !== null);
        ?>
        
        <!-- Dashboard -->
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'dashboard.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            <span class="text-xs">Dashboard</span>
        </a>

        <!-- Jadwal Mengajar (Pengajar & Admin) -->
        <a href="jadwal.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'jadwal.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">calendar_month</span>
            <span class="text-xs">Jadwal Mengajar</span>
        </a>

        <div class="pt-3 pb-1 px-3">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Penilaian Mapel</span>
        </div>

        <!-- Input Nilai -->
        <a href="input_data.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'input_data.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">edit_square</span>
            <span class="text-xs">Input Nilai</span>
        </a>

        <!-- Analisa Nilai -->
        <a href="analisa.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'analisa.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">analytics</span>
            <span class="text-xs">Analisa Nilai</span>
        </a>

        <?php if ($is_user_admin || $has_wali_kelas): ?>
        <div class="pt-3 pb-1 px-3">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">
                <?= $is_user_admin ? 'Wali Kelas & Catatan' : 'Kelas Binaan (' . htmlspecialchars($user_wk['nama_kelas']) . ')' ?>
            </span>
        </div>

        <!-- Wali Kelas -->
        <a href="walikelas.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'walikelas.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">assignment_ind</span>
            <span class="text-xs">Rekap Wali Kelas</span>
        </a>

        <!-- Catatan Siswa -->
        <a href="catatan.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'catatan.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">edit_note</span>
            <span class="text-xs">Catatan Siswa</span>
        </a>

        <!-- Bulk Catatan -->
        <a href="bulk_catatan.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'bulk_catatan.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">playlist_add</span>
            <span class="text-xs">Bulk Catatan</span>
        </a>

        <!-- Summary Catatan -->
        <a href="summary_catatan.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'summary_catatan.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">summarize</span>
            <span class="text-xs">Summary Catatan</span>
        </a>
        <?php endif; ?>

        <?php if ($is_user_admin): ?>
        <div class="pt-3 pb-1 px-3">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Master Data (Admin)</span>
        </div>

        <!-- Data Siswa -->
        <a href="siswa.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'siswa.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">group</span>
            <span class="text-xs">Data Siswa</span>
        </a>

        <!-- Mata Pelajaran -->
        <a href="mapel.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'mapel.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">book</span>
            <span class="text-xs">Mata Pelajaran</span>
        </a>

        <!-- Kelas -->
        <a href="kelas.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-lg transition-colors <?= ($current_page == 'kelas.php') ? 'bg-primary text-white font-semibold' : 'text-text-muted hover:bg-slate-100 hover:text-text-main font-medium' ?>">
            <span class="material-symbols-outlined text-[20px]">folder_open</span>
            <span class="text-xs">Daftar Kelas & Wali</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="px-3 border-t border-border-main pt-4 mt-auto">
        <a href="logout.php" class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] text-danger hover:bg-danger-subtle rounded-lg transition-colors font-medium">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="text-xs">Keluar Akun</span>
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (!sidebar || !overlay) return;

        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
            }, 10);
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 200);
            document.body.style.overflow = 'auto';
        }
    }

    const overlayEl = document.getElementById('sidebarOverlay');
    if (overlayEl) overlayEl.addEventListener('click', toggleSidebar);

    const closeBtnEl = document.getElementById('closeSidebarBtn');
    if (closeBtnEl) closeBtnEl.addEventListener('click', toggleSidebar);
</script>