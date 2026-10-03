<?php
session_start();
require_once '../config/auth.php';

// Proteksi keamanan: Hanya Admin yang bisa mereset nilai
require_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_semua_nilai'])) {
    try {
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