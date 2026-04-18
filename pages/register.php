<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>EduScore - Registrasi Pengajar</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#002045",
                        "primary-container": "#1a365d",
                        "on-primary": "#ffffff",
                        "surface": "#faf9fd",
                        "on-surface": "#1a1c1e",
                        "on-surface-variant": "#5b6577",
                        "surface-container-low": "#f4f3f7",
                        "surface-container-highest": "#e3e2e6",
                        "surface-container-lowest": "#ffffff",
                    },
                    fontFamily: {
                        "body": ["Inter", "sans-serif"],
                        "headline": ["Inter", "sans-serif"],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen w-full flex items-center justify-center relative overflow-hidden antialiased py-10">
    
    <div class="absolute inset-0 z-0 bg-gradient-to-br from-surface via-surface-container-low to-surface-container-highest flex items-center justify-center pointer-events-none">
        <div class="absolute bottom-[-10%] right-[-10%] w-[40vw] h-[40vw] rounded-full bg-primary opacity-[0.04] blur-[100px]"></div>
    </div>

    <main class="relative z-10 w-full max-w-[450px] px-6">
        <div class="bg-surface-container-lowest rounded-xl shadow-[0px_12px_32px_rgba(26,28,30,0.06)] p-10 flex flex-col gap-6">
            
            <header class="flex flex-col items-center text-center gap-2 mb-2">
                <h1 class="font-headline text-2xl font-bold tracking-tight text-primary">Buat Akun Baru</h1>
                <p class="font-body text-sm text-on-surface-variant">Lengkapi data di bawah untuk bergabung</p>
            </header>

            <form action="proses_register.php" class="flex flex-col gap-5" method="POST">
                
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant pl-1" for="nama_lengkap">Nama Lengkap (Sesuai Gelar)</label>
                    <input class="w-full bg-surface-container-highest text-on-surface text-sm rounded-t-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 transition-colors" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Budi Santoso, S.Pd" required type="text"/>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant pl-1" for="username">Username</label>
                    <input class="w-full bg-surface-container-highest text-on-surface text-sm rounded-t-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 transition-colors" id="username" name="username" placeholder="Buat username tanpa spasi" required type="text"/>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant pl-1" for="password">Password</label>
                    <input class="w-full bg-surface-container-highest text-on-surface text-sm rounded-t-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 transition-colors" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6" type="password"/>
                </div>

                <button class="w-full bg-primary text-on-primary text-sm font-medium tracking-wide py-4 rounded-lg shadow-sm hover:bg-primary-container active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-4" type="submit">
                    Daftar Sekarang
                    <span class="material-symbols-outlined text-lg">person_add</span>
                </button>
            </form>

            <div class="text-center mt-2">
                <p class="text-sm text-on-surface-variant">Sudah punya akun? <a href="login.php" class="text-primary font-semibold hover:underline">Masuk di sini</a></p>
            </div>
        </div>
    </main>
</body>
</html>