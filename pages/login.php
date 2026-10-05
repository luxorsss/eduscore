<?php
require_once __DIR__ . '/../config/session.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$saved_username = $_COOKIE['eduscore_remember_user'] ?? '';
$saved_password = '';
if (!empty($_COOKIE['eduscore_remember_pass'])) {
    $decoded = base64_decode($_COOKIE['eduscore_remember_pass'], true);
    if ($decoded !== false) {
        $saved_password = str_rot13($decoded);
    }
}
$has_saved = !empty($saved_username);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Masuk - EduScore</title>
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

    <main class="w-full max-w-[420px]">
        <div class="bg-surface-card rounded-2xl border border-border-main shadow-sm p-7 sm:p-9 flex flex-col gap-6">
            
            <header class="flex flex-col items-center text-center gap-2">
                <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-xl shadow-xs">
                    E
                </div>
                <h1 class="text-xl font-bold tracking-tight text-primary">EduScore</h1>
                <p class="text-xs text-text-muted">Masuk ke sistem penilaian akademik</p>
            </header>

            <form action="proses_login.php" class="flex flex-col gap-4" method="POST">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-text-main" for="username">Username</label>
                    <input class="w-full bg-white text-text-main text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 px-3.5 py-2.5 transition-colors focus-ring placeholder:text-slate-400" id="username" name="username" placeholder="Masukkan username" required autocomplete="username" type="text" value="<?= htmlspecialchars($saved_username) ?>"/>
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-text-main" for="password">Kata Sandi</label>
                    </div>
                    <div class="relative">
                        <input class="w-full bg-white text-text-main text-sm rounded-lg border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 pl-3.5 pr-10 py-2.5 transition-colors focus-ring placeholder:text-slate-400" id="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password" type="password" value="<?= htmlspecialchars($saved_password) ?>"/>
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 flex items-center justify-center transition-colors" title="Lihat kata sandi">
                            <span class="material-symbols-outlined text-lg" id="eyeIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-0.5">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs text-text-muted hover:text-text-main">
                        <input type="checkbox" name="remember_me" id="remember_me" value="1" <?= ($has_saved || !isset($_COOKIE['eduscore_remember_user'])) ? 'checked' : '' ?> class="rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Ingat saya (isi otomatis setiap login)</span>
                    </label>
                </div>

                <button class="w-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold py-3 px-4 rounded-lg transition-colors focus-ring flex items-center justify-center gap-2 mt-1 shadow-xs min-h-[44px] cursor-pointer" type="submit">
                    <span>Masuk ke Sistem</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </button>
            </form>

            <div class="text-center pt-4 border-t border-border-main">
                <p class="text-xs text-text-muted">Belum memiliki akun pengajar? <a href="register.php" class="text-primary font-semibold hover:underline">Daftar di sini</a></p>
            </div>
        </div>
    </main>

    <script>
    function togglePasswordVisibility() {
        const passInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            eyeIcon.textContent = 'visibility_off';
        } else {
            passInput.type = 'password';
            eyeIcon.textContent = 'visibility';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const userField = document.getElementById('username');
        const passField = document.getElementById('password');
        const rememberBox = document.getElementById('remember_me');

        // Jika cookie PHP kosong tapi browser pernah simpan di localStorage
        if (!userField.value && localStorage.getItem('eduscore_remember_u')) {
            userField.value = localStorage.getItem('eduscore_remember_u');
        }
        if (!passField.value && localStorage.getItem('eduscore_remember_p')) {
            passField.value = localStorage.getItem('eduscore_remember_p');
        }

        const form = document.querySelector('form');
        form.addEventListener('submit', function() {
            if (rememberBox.checked) {
                localStorage.setItem('eduscore_remember_u', userField.value);
                localStorage.setItem('eduscore_remember_p', passField.value);
            } else {
                localStorage.removeItem('eduscore_remember_u');
                localStorage.removeItem('eduscore_remember_p');
            }
        });
    });
    </script>
</body>
</html>