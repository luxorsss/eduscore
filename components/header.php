<?php
// Pastikan session sudah aktif jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'EduScore - Portal Nilai Pengajar' ?></title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- EduScore Modular CSS -->
    <link rel="stylesheet" href="../assets/css/style.css"/>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#0f2942",
                        "primary-hover": "#1a3d60",
                        "primary-subtle": "#e8eef4",
                        "surface": "#f8fafc",
                        "surface-card": "#ffffff",
                        "text-main": "#0f172a",
                        "text-muted": "#475569",
                        "border-main": "#e2e8f0",
                        "success": "#047857",
                        "success-subtle": "#ecfdf5",
                        "warning": "#b45309",
                        "warning-subtle": "#fffbeb",
                        "danger": "#be123c",
                        "danger-subtle": "#fff1f2",
                    },
                    fontFamily: {
                        "body": ["Inter", "system-ui", "-apple-system", "sans-serif"],
                        "headline": ["Inter", "system-ui", "-apple-system", "sans-serif"],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-surface font-body text-text-main min-h-screen flex antialiased">
    
    <?php 
    // Tampilkan Sidebar HANYA jika sudah login
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/sidebar.php'; 
    }
    ?>

    <div class="flex-1 <?= isset($_SESSION['user_id']) ? 'md:ml-64' : '' ?> flex flex-col min-h-screen">
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Top App Bar Terpadu (Menghilangkan duplikasi navbar di tiap halaman) -->
        <header class="bg-surface-card border-b border-border-main sticky top-0 z-30 shadow-xs">
            <div class="w-full px-4 md:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleSidebar()" aria-label="Buka menu navigasi" class="md:hidden w-10 h-10 flex items-center justify-center text-text-muted hover:text-text-main hover:bg-slate-100 rounded-lg transition-colors focus-ring">
                        <span class="material-symbols-outlined text-2xl">menu</span>
                    </button>
                    <div>
                        <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">EduScore</span>
                        <h1 class="text-sm md:text-base font-semibold text-text-main truncate max-w-xs md:max-w-md">
                            <?= isset($page_heading) ? htmlspecialchars($page_heading) : (isset($page_title) ? htmlspecialchars($page_title) : 'Portal Pengajar') ?>
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 relative">
                    <!-- User Profile & Action Dropdown -->
                    <div class="relative">
                        <button type="button" id="userMenuBtn" onclick="toggleUserMenu()" class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-100 transition-colors focus-ring" aria-haspopup="true" aria-expanded="false">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'User', 0, 2)); ?>
                            </div>
                            <span class="text-xs font-medium text-text-main hidden sm:block max-w-[140px] truncate">
                                <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengajar'); ?>
                            </span>
                            <span class="material-symbols-outlined text-text-muted text-base">expand_more</span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-56 bg-surface-card rounded-xl border border-border-main shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b border-border-main">
                                <p class="text-xs text-text-muted">Masuk sebagai</p>
                                <p class="text-sm font-semibold text-text-main truncate"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengajar'); ?></p>
                                <p class="text-xs text-text-muted truncate">@<?= htmlspecialchars($_SESSION['username'] ?? 'guru'); ?></p>
                            </div>
                            <a href="dashboard.php" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-text-main hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-lg text-text-muted">dashboard</span>
                                Dashboard Utama
                            </a>
                            <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-danger hover:bg-danger-subtle transition-colors">
                                <span class="material-symbols-outlined text-lg">logout</span>
                                Keluar Akun
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <script>
            function toggleUserMenu() {
                const menu = document.getElementById('userDropdownMenu');
                const btn = document.getElementById('userMenuBtn');
                if (menu) {
                    const isHidden = menu.classList.contains('hidden');
                    if (isHidden) {
                        menu.classList.remove('hidden');
                        btn.setAttribute('aria-expanded', 'true');
                    } else {
                        menu.classList.add('hidden');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                }
            }

            // Tutup dropdown jika klik di luar
            document.addEventListener('click', function(event) {
                const menu = document.getElementById('userDropdownMenu');
                const btn = document.getElementById('userMenuBtn');
                if (menu && btn && !btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        </script>
        <?php endif; ?>