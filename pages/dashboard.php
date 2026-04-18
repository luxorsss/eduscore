<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>EduScore - Dashboard Pengajar</title>
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
                        "outline-variant": "#c4c6cf",
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
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col antialiased">

    <nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">school</span>
                </div>
                <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-on-surface-variant hidden md:block">Budi Santoso, S.Pd</span>
                <div class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center text-primary font-bold text-sm cursor-pointer hover:bg-outline-variant/30 transition">BS</div>
                <a href="login.php" class="text-red-600 hover:bg-red-50 p-2 rounded-full transition" title="Logout">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center p-6 md:p-10">
        <form action="input_nilai.php" method="GET" class="w-full max-w-2xl bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0px_8px_24px_rgba(26,28,30,0.04)] border border-outline-variant/20">
            
            <div class="mb-8 text-center">
                <h1 class="text-2xl md:text-3xl font-headline font-bold text-primary tracking-tight mb-2">Pilih Kelas</h1>
                <p class="text-on-surface-variant text-sm md:text-base">Tentukan sasaran kelas dan mata pelajaran yang akan Anda kelola nilainya.</p>
            </div>

            <div class="flex flex-col gap-6 bg-surface-container-low rounded-xl p-6 border border-outline-variant/20">
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant">Jenjang Sekolah</label>
                    <div class="flex gap-3">
                        <label class="cursor-pointer flex-1">
                            <input checked class="peer sr-only" name="jenjang" type="radio" value="smp"/>
                            <div class="px-4 py-3 rounded-md border border-outline-variant/30 bg-surface-container-lowest text-center peer-checked:ring-2 peer-checked:ring-primary peer-checked:border-primary peer-checked:text-primary transition-all font-medium text-sm">
                                SMP
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1">
                            <input class="peer sr-only" name="jenjang" type="radio" value="sma"/>
                            <div class="px-4 py-3 rounded-md border border-outline-variant/30 bg-surface-container-lowest text-center peer-checked:ring-2 peer-checked:ring-primary peer-checked:border-primary peer-checked:text-primary transition-all font-medium text-sm">
                                SMA
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant" for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3.5 transition-colors cursor-pointer font-medium">
                        <option value="" disabled selected>-- Pilih Kelas --</option>
                        <option value="7a">7A Reguler</option>
                        <option value="7b">7B Reguler</option>
                        <option value="10ipa1">10 IPA 1</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant" for="mapel">Mata Pelajaran</label>
                    <select id="mapel" name="mapel" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3.5 transition-colors cursor-pointer font-medium">
                        <option value="" disabled selected>-- Pilih Mata Pelajaran --</option>
                        <option value="mtk">Matematika</option>
                        <option value="ipa">Ilmu Pengetahuan Alam</option>
                        <option value="fisika">Fisika</option>
                    </select>
                </div>

            </div>

            <div class="mt-8 pt-6 border-t border-outline-variant/20">
                <button type="submit" class="w-full bg-primary text-on-primary px-8 py-4 rounded-lg text-sm font-medium flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.98] transition-all shadow-sm">
                    Lanjutkan ke Pengisian Nilai
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
            </div>

        </form>
    </main>
</body>
</html>