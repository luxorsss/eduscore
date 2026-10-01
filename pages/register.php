<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pendaftaran Pengajar - EduScore</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#0f2942",
                        "primary-hover": "#1a3d60",
                        "surface": "#f8fafc",
                        "surface-card": "#ffffff",
                        "text-main": "#0f172a",
                        "text-muted": "#475569",
                        "border-main": "#e2e8f0",
                    },
                    fontFamily: {
                        "body": ["Inter", "system-ui", "sans-serif"],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface font-body text-text-main min-h-screen w-full flex items-center justify-center p-4 antialiased">

    <main class="w-full max-w-[420px] py-8">
        <div class="bg-surface-card rounded-xl border border-border-main shadow-xs p-8 sm:p-10 flex flex-col gap-6">
            
            <header class="flex flex-col items-center text-center gap-2">
                <div class="w-12 h-12 rounded-lg bg-primary text-white flex items-center justify-center font-bold text-xl shadow-xs">
                    E
                </div>
                <h1 class="text-xl font-bold tracking-tight text-primary">Pendaftaran Pengajar</h1>
                <p class="text-xs text-text-muted">Buat akun untuk mengelola kelas dan nilai siswa</p>
            </header>

            <form action="proses_register.php" class="flex flex-col gap-4" method="POST">
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="nama_lengkap">Nama Lengkap (Beserta Gelar)</label>
                    <input class="w-full bg-white text-text-main text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors focus-ring placeholder:text-slate-400" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Budi Santoso, S.Pd" required type="text"/>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="username">Username</label>
                    <input class="w-full bg-white text-text-main text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors focus-ring placeholder:text-slate-400" id="username" name="username" placeholder="Pilih username tanpa spasi" required type="text"/>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="password">Kata Sandi</label>
                    <input class="w-full bg-white text-text-main text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors focus-ring placeholder:text-slate-400" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" type="password"/>
                </div>

                <button class="w-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold py-3 px-4 rounded-lg transition-colors focus-ring flex items-center justify-center gap-2 mt-2 shadow-xs min-h-[44px]" type="submit">
                    Daftar Akun Pengajar
                </button>
            </form>

            <div class="text-center pt-4 border-t border-border-main">
                <p class="text-xs text-text-muted">Sudah memiliki akun? <a href="login.php" class="text-primary font-semibold hover:underline">Masuk di sini</a></p>
            </div>
        </div>
    </main>

</body>
</html>