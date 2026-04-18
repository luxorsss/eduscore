<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi: Pastikan hanya user login yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. PROSES TAMBAH MAPEL (Metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nama_mapel'])) {
    $nama_mapel = trim($_POST['nama_mapel']);

    if (!empty($nama_mapel)) {
        try {
            $sql = "INSERT INTO subjects (nama_mapel) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama_mapel]);
            
            header("Location: mapel.php");
            exit();
        } catch (PDOException $e) {
            die("Gagal menambah data: " . $e->getMessage());
        }
    }
}

// 2. PROSES HAPUS MAPEL (Metode GET)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    try {
        // Eksekusi penghapusan berdasarkan ID
        $sql = "DELETE FROM subjects WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // Kembali ke halaman mapel
        header("Location: mapel.php");
        exit();
    } catch (PDOException $e) {
        // Jika gagal karena data sedang digunakan di tabel jadwal (Foreign Key Constraint)
        echo "<script>
                alert('Gagal menghapus! Mata pelajaran ini sedang digunakan dalam Jadwal Mengajar.');
                window.location.href = 'mapel.php';
              </script>";
    }
}

// Jika diakses tanpa parameter, kembalikan ke dashboard
header("Location: dashboard.php");
exit();