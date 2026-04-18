<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kategori = $_POST['kategori'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? 0;
    $nilais = $_POST['nilai'] ?? []; // Array dari form

    // KEAMANAN (White-listing): Pastikan kategori yang dikirim valid dan sesuai dengan nama kolom di database
    $allowed_categories = ['h_uts', 'uts', 'h_uas', 'uas', 'tambahan'];
    if (!in_array($kategori, $allowed_categories)) {
        die("Error: Kategori nilai tidak valid/dikenali sistem.");
    }

    if ($schedule_id == 0 || empty($nilais)) {
        header("Location: dashboard.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Query Dinamis: ON DUPLICATE KEY UPDATE memungkinkan kita menambah baris baru 
        // atau MEMPERBARUI nilai jika siswa tersebut sudah pernah diinput sebelumnya.
        $sql = "INSERT INTO grades (student_id, schedule_id, $kategori) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE $kategori = VALUES($kategori)";
        
        $stmt = $pdo->prepare($sql);

        $berhasil = 0;
        foreach ($nilais as $student_id => $nilai) {
            // Hanya simpan jika kotaknya diisi (tidak kosong)
            if (trim($nilai) !== "") {
                $stmt->execute([$student_id, $schedule_id, $nilai]);
                $berhasil++;
            }
        }

        $pdo->commit();
        
        echo "<script>
                alert('Berhasil! $berhasil Data Nilai $kategori telah disimpan.');
                // Kembalikan ke halaman sebelumnya agar guru bisa mengecek lagi
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