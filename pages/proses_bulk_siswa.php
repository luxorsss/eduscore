<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST['aksi'];

    if ($aksi == 'simpan') {
        $namas = $_POST['nama']; // Ini adalah array
        $niss  = $_POST['nis'];  // Ini adalah array

        try {
            $pdo->beginTransaction(); // Mulai transaksi
            
            $sql = "INSERT INTO students (nama, nis, class_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            foreach ($namas as $index => $nama) {
                if (!empty($nama)) {
                    // Sementara class_id kita set statis dulu atau ambil dari session/input
                    $stmt->execute([$nama, $niss[$index], 1]); 
                }
            }

            $pdo->commit(); // Simpan permanen jika semua ok
            echo "<script>alert('Data berhasil disimpan!'); window.location.href='siswa.php';</script>";
        } catch (Exception $e) {
            $pdo->rollBack(); // Batalkan semua jika ada 1 saja yang error
            die("Gagal simpan: " . $e->getMessage());
        }

    } elseif ($aksi == 'hapus') {
        $ids = $_POST['id_hapus']; // Array ID yang dicentang

        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "DELETE FROM students WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
        }
        header("Location: siswa.php");
    }
}