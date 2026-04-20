<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? 0;

    // Validasi Keamanan Kategori
    $allowed_categories = ['h_uts', 'uts', 'h_uas', 'uas', 'tambahan'];
    if (!in_array($kategori, $allowed_categories)) {
        die("Error: Kategori nilai tidak valid.");
    }

    if ($schedule_id == 0) {
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
        
        // BACA SEMUA DATA POST, CARI YANG NAMANYA BERAWALAN "n_"
        foreach ($_POST as $key => $nilai) {
            // Jika nama input-nya dimulai dengan "n_" (contoh: n_12)
            if (strpos($key, 'n_') === 0) {
                // Ambil ID siswanya saja (buang huruf "n_")
                $student_id = str_replace('n_', '', $key);
                
                // Simpan jika nilainya tidak kosong
                if (trim($nilai) !== "") {
                    $stmt->execute([$student_id, $schedule_id, $nilai]);
                    $berhasil++;
                }
            }
        }

        $pdo->commit();
        
        echo "<script>
                alert('Berhasil! $berhasil nilai telah disimpan ke database.');
                window.location.href = 'dashboard.php';
              </script>";
              
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal menyimpan nilai: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
}
?>