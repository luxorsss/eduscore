<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap data dari Form standar HTML
    $kategori = $_POST['kategori'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? 0;
    $nilais = $_POST['nilai'] ?? []; // Mengambil array nilai secara langsung

    // Validasi Keamanan (Pastikan kategori tidak dimanipulasi)
    $allowed_categories = ['h_uts', 'uts', 'h_uas', 'uas', 'tambahan'];
    if (!in_array($kategori, $allowed_categories)) {
        die("Error: Kategori nilai tidak valid.");
    }

    if ($schedule_id == 0 || empty($nilais)) {
        echo "<script>alert('Data kosong atau tidak ada kelas yang dipilih.'); window.history.back();</script>";
        exit();
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO grades (student_id, schedule_id, $kategori) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE $kategori = VALUES($kategori)";
        
        $stmt = $pdo->prepare($sql);

        $berhasil = 0;
        
        // Looping murni standar PHP array
        foreach ($nilais as $student_id => $nilai) {
            // Abaikan jika kotaknya tidak diisi sama sekali
            if (trim($nilai) !== "") {
                $stmt->execute([$student_id, $schedule_id, $nilai]);
                $berhasil++;
            }
        }

        $pdo->commit();
        
        echo "<script>
                alert('Berhasil! $berhasil nilai telah disimpan ke database.');
                window.history.back(); 
              </script>";
              
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal menyimpan nilai: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
}
?>