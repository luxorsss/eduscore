<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi keamanan
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_semua_nilai'])) {
    try {
        // TRUNCATE akan mengosongkan seluruh isi tabel grades dalam sepersekian detik
        // dan mereset auto-increment ID-nya kembali ke 1.
        $pdo->exec("TRUNCATE TABLE grades");

        echo "<script>
                alert('Berhasil! Seluruh data nilai telah dibersihkan. Sistem siap untuk semester baru!');
                window.location.href = 'dashboard.php';
              </script>";
    } catch (PDOException $e) {
        die("Gagal mengosongkan nilai: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
}
?>