<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. PROSES TAMBAH JADWAL (Via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];

    if (!empty($class_id) && !empty($subject_id)) {
        
        // Cek apakah jadwal ini sudah pernah diinput sebelumnya (mencegah duplikat)
        $cek_duplikat = $pdo->prepare("SELECT id FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
        $cek_duplikat->execute([$user_id, $class_id, $subject_id]);
        
        if ($cek_duplikat->rowCount() > 0) {
            echo "<script>alert('Jadwal ini sudah ada!'); window.location.href='jadwal.php';</script>";
            exit();
        }

        // Jika belum ada, simpan ke database
        try {
            $sql = "INSERT INTO teaching_schedules (user_id, class_id, subject_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $class_id, $subject_id]);
            
            header("Location: jadwal.php");
            exit();
        } catch (PDOException $e) {
            die("Error menyimpan jadwal: " . $e->getMessage());
        }
    }
}

// 2. PROSES HAPUS JADWAL (Via GET)
if (isset($_GET['hapus'])) {
    $jadwal_id = $_GET['hapus'];

    try {
        // Penting: Pastikan jadwal yang dihapus adalah benar milik user yang login (Security Check)
        $sql = "DELETE FROM teaching_schedules WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jadwal_id, $user_id]);
        
        header("Location: jadwal.php");
        exit();
    } catch (PDOException $e) {
        die("Error menghapus jadwal: " . $e->getMessage());
    }
}

// Jika diakses tanpa parameter yang jelas
header("Location: jadwal.php");
exit();
?>