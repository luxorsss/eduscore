<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? 0;
    
    // BUKA BUNGKUSAN JSON (Trik Anti-Firewall)
    $json_data = $_POST['data_nilai_json'] ?? '{}';
    $nilais = json_decode($json_data, true); // Mengubah teks JSON kembali menjadi Array PHP

    // Pastikan hasil decode benar-benar array
    if (!is_array($nilais)) {
        $nilais = [];
    }

    // KEAMANAN (White-listing): Pastikan kategori yang dikirim valid
    $allowed_categories = ['h_uts', 'uts', 'h_uas', 'uas', 'tambahan'];
    if (!in_array($kategori, $allowed_categories)) {
        die("Error: Kategori nilai tidak valid.");
    }

    if ($schedule_id == 0 || empty($nilais)) {
        echo "<script>alert('Tidak ada data nilai yang diisi!'); window.history.back();</script>";
        exit();
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO grades (student_id, schedule_id, $kategori) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE $kategori = VALUES($kategori)";
        
        $stmt = $pdo->prepare($sql);

        $berhasil = 0;
        foreach ($nilais as $student_id => $nilai) {
            if (trim($nilai) !== "") {
                $stmt->execute([$student_id, $schedule_id, $nilai]);
                $berhasil++;
            }
        }

        $pdo->commit();
        
        echo "<script>
                alert('Berhasil! $berhasil Data Nilai $kategori telah disimpan.');
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