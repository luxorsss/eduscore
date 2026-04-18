<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>EduScore - Login</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
<body class="bg-surface font-body text-on-surface h-screen w-full flex items-center justify-center relative overflow-hidden antialiased">
    
    <div class="absolute inset-0 z-0 bg-gradient-to-br from-surface via-surface-container-low to-surface-container-highest flex items-center justify-center pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-primary opacity-[0.03] blur-[120px]"></div>
    </div>

    <main class="relative z-10 w-full max-w-[400px] px-6">
        <div class="bg-surface-container-lowest rounded-xl shadow-[0px_12px_32px_rgba(26,28,30,0.06)] p-10 flex flex-col gap-8">
            
            <header class="flex flex-col items-center text-center gap-2">
                <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center mb-2 shadow-sm">
                    <span class="material-symbols-outlined text-3xl text-primary" style="font-variation-settings: 'FILL' 1;">school</span>
                </div>
                <h1 class="font-headline text-3xl font-bold tracking-tight text-primary">EduScore</h1>
                <p class="font-body text-sm text-on-surface-variant">Portal Pengajar</p>
            </header>

            <form action="proses_login.php" class="flex flex-col gap-6" method="POST">
                <div class="flex flex-col gap-5">
                    
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant pl-1" for="username">Username</label>
                        <input class="w-full bg-surface-container-highest text-on-surface text-sm rounded-t-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 transition-colors" id="username" name="username" placeholder="Masukkan username" required type="text"/>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant pl-1" for="password">Password</label>
                        <input class="w-full bg-surface-container-highest text-on-surface text-sm rounded-t-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface focus:ring-0 px-4 py-3.5 transition-colors" id="password" name="password" placeholder="••••••••" required type="password"/>
                    </div>

                </div>

                <button class="w-full bg-gradient-to-r from-primary to-primary-container text-on-primary text-sm font-medium tracking-wide py-4 rounded-lg shadow-sm hover:shadow-lg active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-2" type="submit">
                    Masuk
                    <span class="material-symbols-outlined text-lg">login</span>
                </button>
            </form>

            <div class="text-center mt-2">
                <p class="text-sm text-on-surface-variant">Belum punya akun? <a href="register.php" class="text-primary font-semibold hover:underline">Daftar di sini</a></p>
            </div>
        </div>
    </main>
</body>
</html>