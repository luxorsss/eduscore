<?php
session_start();
require_once '../config/koneksi.php'; // Panggil koneksi database

// Pastikan hanya user yang login yang bisa menambah data
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap data dari input form
    $nama_mapel = trim($_POST['nama_mapel']);

    if (!empty($nama_mapel)) {
        try {
            // Siapkan query INSERT
            $sql = "INSERT INTO subjects (nama_mapel) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            
            // Eksekusi penyimpanan ke database
            $stmt->execute([$nama_mapel]);
            
            // Arahkan kembali ke halaman mapel setelah sukses
            header("Location: mapel.php");
            exit();
            
        } catch (PDOException $e) {
            die("Error menyimpan data: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('Nama mata pelajaran tidak boleh kosong!'); window.location.href='mapel.php';</script>";
    }
} else {
    header("Location: mapel.php");
}
?>