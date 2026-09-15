<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Tambah Kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $jenjang = $_POST['jenjang'];
    $nama_kelas = trim($_POST['nama_kelas']);

    if (!empty($nama_kelas)) {
        $stmt = $pdo->prepare("INSERT INTO classes (jenjang, nama_kelas) VALUES (?, ?)");
        $stmt->execute([$jenjang, $nama_kelas]);
    }
    header("Location: kelas.php");
    exit();
}

// Hapus Kelas (Opsi A: Validasi manual + Safe try-catch)
if (isset($_GET['hapus'])) {
    $class_id = (int)$_GET['hapus'];

    try {
        // 1. Cek keterkaitan dengan tabel students
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
        $stmt->execute([$class_id]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: kelas.php?pesan=ada_siswa");
            exit();
        }

        // 2. Cek keterkaitan dengan tabel teaching_schedules (jadwal mengajar)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE class_id = ?");
        $stmt->execute([$class_id]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: kelas.php?pesan=ada_jadwal");
            exit();
        }

        // 3. Jika bersih, hapus kelas
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);

        header("Location: kelas.php?pesan=sukses_hapus");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            header("Location: kelas.php?pesan=gagal_terkait");
        } else {
            error_log($e->getMessage());
            header("Location: kelas.php?pesan=error_server");
        }
        exit();
    }
}

header("Location: kelas.php");
exit();