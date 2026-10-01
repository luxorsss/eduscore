<?php
session_start();

// Redirect ke halaman Rekapitulasi Wali Kelas atau Analisa Nilai
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Arahkan ke rekapitulasi nilai utama (walikelas.php)
header("Location: walikelas.php");
exit();
