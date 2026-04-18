<?php
// Mulai sesi (Session) untuk mengecek apakah user sudah login atau belum
session_start();

// Cek apakah ada variabel 'user_id' di dalam sesi
if (isset($_SESSION['user_id'])) {
    // Jika SUDAH login, arahkan ke halaman Dashboard
    header("Location: pages/dashboard.php");
    exit(); // Hentikan eksekusi script lebih lanjut
} else {
    // Jika BELUM login, arahkan ke halaman Login
    header("Location: pages/login.php");
    exit();
}
?>