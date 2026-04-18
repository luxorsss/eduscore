<?php
session_start();
require_once '../config/koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nilai_uas'])) {
    $nilai_uas = $_POST['nilai_uas']; // Mengambil array nilai dari form

    // Kita asumsikan schedule_id untuk "10 IPA 1 - Fisika" ini adalah 1 (nanti dibuat dinamis)
    $schedule_id = 1; 

    try {
        $pdo->beginTransaction();

        // Gunakan INSERT ... ON DUPLICATE KEY UPDATE 
        // Agar jika nilai siswa belum ada, maka ditambah (Insert). Jika sudah ada, maka diperbarui (Update).
        $sql = "INSERT INTO grades (student_id, schedule_id, uas) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE uas = VALUES(uas)";
        
        $stmt = $pdo->prepare($sql);

        // Lakukan perulangan untuk setiap nilai siswa yang diketik
        foreach ($nilai_uas as $student_id => $nilai) {
            // Hanya proses jika nilainya tidak kosong
            if ($nilai !== "") {
                $stmt->execute([$student_id, $schedule_id, $nilai]);
            }
        }

        $pdo->commit();
        
        echo "<script>
                alert('Data Nilai Berhasil Disimpan Massal!');
                window.location.href = 'input_nilai.php';
              </script>";
              
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal menyimpan nilai: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
}
?>