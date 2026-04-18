<aside class="hidden md:flex h-screen w-64 fixed left-0 top-0 overflow-y-auto bg-surface-container-high flex-col py-8 z-40 border-r border-outline-variant/20">
    <div class="px-8 mb-8 flex items-center space-x-3">
        <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-black shadow-lg">E</div>
        <div>
            <div class="text-xl font-black text-primary uppercase tracking-tighter">EduScore</div>
            <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Portal Guru</div>
        </div>
    </div>

    <div class="flex-1 space-y-1 mt-4 px-4 font-medium text-sm">
        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= ($page_title == 'EduScore - Dashboard Pengajar') ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a href="siswa.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= ($page_title == 'EduScore - Data Induk Siswa') ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined">group</span>
            <span>Data Siswa</span>
        </a>
        <a href="input_nilai.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= ($page_title == 'EduScore - Input Nilai') ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined">edit_square</span>
            <span>Input Nilai</span>
        </a>
        <a href="analisa.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all <?= ($page_title == 'EduScore - Analisis & Rekapitulasi') ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-highest' ?>">
            <span class="material-symbols-outlined">analytics</span>
            <span>Analisa Nilai</span>
        </a>
    </div>

    <div class="px-4 mt-auto">
        <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-error hover:bg-error-container/20 transition-all">
            <span class="material-symbols-outlined">logout</span>
            <span>Keluar</span>
        </a>
    </div>
</aside>