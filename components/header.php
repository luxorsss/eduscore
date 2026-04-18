<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= isset($page_title) ? $page_title : 'EduScore - Manajemen Nilai' ?></title>
    
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
                        "tertiary": "#00522f",
                        "tertiary-container": "#91f8b8",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
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
<body class="bg-surface font-body text-on-surface min-h-screen flex antialiased">
    
    <?php 
    // Tampilkan Sidebar HANYA jika bukan di halaman Login atau Register
    if (isset($_SESSION['user_id'])) {
        require_once 'sidebar.php'; 
    }
    ?>

    <div class="flex-1 <?= isset($_SESSION['user_id']) ? 'md:ml-64' : '' ?> flex flex-col min-h-screen">