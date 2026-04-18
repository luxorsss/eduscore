<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? 0;
    
    // 1. TANGKAP DATA SANDI BASE64
    $base64_data = $_POST['data_nilai_json'] ?? '';
    
    // 2. BUKA SANDI BASE64 (Decrypt)
    $json_data = base64_decode($base64_data);
    
    // 3. UBAH JSON KEMBALI JADI ARRAY PHP
    $nilais = json_decode($json_data, true); 

    if (!is_array($nilais)) {
        $nilais = [];
    }

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