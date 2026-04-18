<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) exit;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST['aksi'] ?? '';

    // A. FITUR BULK DELETE (Hapus Massal)
    if ($aksi === 'hapus_massal') {
        $ids = $_POST['id_hapus'] ?? []; // Mengambil array ID yang dicentang
        if (!empty($ids)) {
            try {
                // Membuat string placeholder ?,?,? sebanyak jumlah ID
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "DELETE FROM students WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
                
                header("Location: siswa.php?pesan=berhasil_hapus");
            } catch (PDOException $e) {
                die("Gagal hapus massal: " . $e->getMessage());
            }
        }
    }

    // B. FITUR BULK INPUT (Tambah Banyak Sekaligus)
    if ($aksi === 'simpan_massal') {
        $namas = $_POST['nama_siswa'] ?? [];
        $niss = $_POST['nis_siswa'] ?? [];
        $class_ids = $_POST['class_id_siswa'] ?? [];

        try {
            $pdo->beginTransaction(); // Mulai transaksi agar data aman
            $sql = "INSERT INTO students (nama, nis, class_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            foreach ($namas as $i => $nama) {
                if (!empty($nama)) { // Hanya simpan jika nama tidak kosong
                    $stmt->execute([$nama, $niss[$i], $class_ids[$i]]);
                }
            }

            $pdo->commit();
            header("Location: siswa.php?pesan=berhasil_tambah");
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Gagal tambah massal: " . $e->getMessage());
        }
    }
}

// C. HAPUS SINGLE (Via tombol tong sampah)
if (isset($_GET['hapus_single'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['hapus_single']]);
    header("Location: siswa.php");
}