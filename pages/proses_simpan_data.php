<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit();
}

$schedule_id = $_POST['schedule_id'] ?? 0;
$n_h_uts = $_POST['n_h_uts'] ?? [];
$n_uts = $_POST['n_uts'] ?? [];
$n_t_uts = $_POST['n_t_uts'] ?? [];
$n_h_uas = $_POST['n_h_uas'] ?? [];
$n_uas = $_POST['n_uas'] ?? [];
$n_t_uas = $_POST['n_t_uas'] ?? [];

$berhasil = 0;

try {
    $pdo->beginTransaction();

    foreach ($n_uts as $student_id => $val) {
        // Ambil nilai, jadikan null jika kosong
        $val_h_uts = ($n_h_uts[$student_id] !== '') ? str_replace(',', '.', $n_h_uts[$student_id]) : null;
        $val_uts   = ($n_uts[$student_id] !== '') ? str_replace(',', '.', $n_uts[$student_id]) : null;
        $val_t_uts = ($n_t_uts[$student_id] !== '') ? str_replace(',', '.', $n_t_uts[$student_id]) : null;
        $val_h_uas = ($n_h_uas[$student_id] !== '') ? str_replace(',', '.', $n_h_uas[$student_id]) : null;
        $val_uas   = ($n_uas[$student_id] !== '') ? str_replace(',', '.', $n_uas[$student_id]) : null;
        $val_t_uas = ($n_t_uas[$student_id] !== '') ? str_replace(',', '.', $n_t_uas[$student_id]) : null;

        // Cek apakah data nilai siswa ini di mapel ini sudah ada
        $stmt_check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND schedule_id = ?");
        $stmt_check->execute([$student_id, $schedule_id]);
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Update jika sudah ada
            $stmt_update = $pdo->prepare("UPDATE grades SET h_uts=?, uts=?, tambahan_uts=?, h_uas=?, uas=?, tambahan_uas=? WHERE student_id=? AND schedule_id=?");
            $stmt_update->execute([$val_h_uts, $val_uts, $val_t_uts, $val_h_uas, $val_uas, $val_t_uas, $student_id, $schedule_id]);
        } else {
            // Jika belum pernah ada nilainya sama sekali tapi ada kotak yang diisi
            if ($val_h_uts !== null || $val_uts !== null || $val_t_uts !== null || $val_h_uas !== null || $val_uas !== null || $val_t_uas !== null) {
                $stmt_insert = $pdo->prepare("INSERT INTO grades (student_id, schedule_id, h_uts, uts, tambahan_uts, h_uas, uas, tambahan_uas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert->execute([$student_id, $schedule_id, $val_h_uts, $val_uts, $val_t_uts, $val_h_uas, $val_uas, $val_t_uas]);
            }
        }
        $berhasil++;
    }

    $pdo->commit();
    echo "<!DOCTYPE html><html><body style='background-color:#f8f9ff;'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
          <script>
            Swal.fire({ 
                icon: 'success', 
                title: 'Berhasil!', 
                text: 'Data nilai $berhasil siswa telah disimpan.', 
                showConfirmButton: false, 
                timer: 1500 // Pop-up otomatis hilang dalam 1,5 detik
            }).then(() => { 
                window.location.href = 'dashboard.php'; 
            });
          </script></body></html>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Gagal menyimpan data: " . $e->getMessage());
}
?>