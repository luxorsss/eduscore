<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/auth.php';

check_login();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$schedule_id = (int)($_POST['schedule_id'] ?? 0);

if (!$schedule_id || !can_edit_schedule_grades($pdo, $user_id, $schedule_id)) {
    header("Location: dashboard.php?pesan=akses_ditolak");
    exit();
}
$n_h_uts = $_POST['n_h_uts'] ?? [];
$n_uts = $_POST['n_uts'] ?? [];
$n_t_uts = $_POST['n_t_uts'] ?? [];
$n_h_uas = $_POST['n_h_uas'] ?? [];
$n_uas = $_POST['n_uas'] ?? [];
$n_t_uas = $_POST['n_t_uas'] ?? [];

$berhasil = 0;

try {
    $pdo->beginTransaction();

    $stmt_check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND schedule_id = ?");
    $stmt_delete = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    $stmt_update = $pdo->prepare("UPDATE grades SET h_uts=?, uts=?, tambahan_uts=?, h_uas=?, uas=?, tambahan_uas=? WHERE id = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO grades (student_id, schedule_id, h_uts, uts, tambahan_uts, h_uas, uas, tambahan_uas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($n_uts as $student_id => $val) {
        $val_h_uts = (isset($n_h_uts[$student_id]) && $n_h_uts[$student_id] !== '') ? str_replace(',', '.', $n_h_uts[$student_id]) : null;
        $val_uts   = (isset($n_uts[$student_id]) && $n_uts[$student_id] !== '') ? str_replace(',', '.', $n_uts[$student_id]) : null;
        $val_t_uts = (isset($n_t_uts[$student_id]) && $n_t_uts[$student_id] !== '') ? str_replace(',', '.', $n_t_uts[$student_id]) : null;
        $val_h_uas = (isset($n_h_uas[$student_id]) && $n_h_uas[$student_id] !== '') ? str_replace(',', '.', $n_h_uas[$student_id]) : null;
        $val_uas   = (isset($n_uas[$student_id]) && $n_uas[$student_id] !== '') ? str_replace(',', '.', $n_uas[$student_id]) : null;
        $val_t_uas = (isset($n_t_uas[$student_id]) && $n_t_uas[$student_id] !== '') ? str_replace(',', '.', $n_t_uas[$student_id]) : null;

        $stmt_check->execute([$student_id, $schedule_id]);
        $exists_id = $stmt_check->fetchColumn();

        $is_all_empty = ($val_h_uts === null && $val_uts === null && $val_t_uts === null && 
                         $val_h_uas === null && $val_uas === null && $val_t_uas === null);

        if ($exists_id) {
            if ($is_all_empty) {
                $stmt_delete->execute([$exists_id]);
            } else {
                $stmt_update->execute([$val_h_uts, $val_uts, $val_t_uts, $val_h_uas, $val_uas, $val_t_uas, $exists_id]);
            }
        } else {
            if (!$is_all_empty) {
                $stmt_insert->execute([$student_id, $schedule_id, $val_h_uts, $val_uts, $val_t_uts, $val_h_uas, $val_uas, $val_t_uas]);
            }
        }
        $berhasil++;
    }

    // Bersihkan seluruh baris nilai yang kosong jika ada record historis yang tersisa
    $stmt_clean = $pdo->prepare("DELETE FROM grades WHERE schedule_id = ? AND h_uts IS NULL AND uts IS NULL AND tambahan_uts IS NULL AND h_uas IS NULL AND uas IS NULL AND tambahan_uas IS NULL");
    $stmt_clean->execute([$schedule_id]);

    $pdo->commit();
    echo "<!DOCTYPE html><html><body style='background-color:#f8f9ff;'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
          <script>
            Swal.fire({ 
                icon: 'success', 
                title: 'Berhasil!', 
                text: 'Data nilai $berhasil siswa telah disimpan.', 
                showConfirmButton: false, 
                timer: 1500
            }).then(() => { 
                window.location.href = 'dashboard.php'; 
            });
          </script></body></html>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Gagal menyimpan data: " . $e->getMessage());
}
?>