<?php
session_start();
require_once '../config/auth.php';

// Hanya Admin yang berhak mengelola kelas & penugasan wali kelas
require_admin();

// 1. Tambah Kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $jenjang = $_POST['jenjang'] ?? 'SMA';
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');
    $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;

    if (!empty($nama_kelas)) {
        // Cek jika wali_kelas_id dipilih, pastikan belum membina kelas lain
        if ($wali_kelas_id) {
            $stmt_check = $pdo->prepare("SELECT id, nama_kelas FROM classes WHERE wali_kelas_id = ?");
            $stmt_check->execute([$wali_kelas_id]);
            $assigned = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if ($assigned) {
                header("Location: kelas.php?pesan=wali_sudah_ditugaskan");
                exit();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO classes (jenjang, nama_kelas, wali_kelas_id) VALUES (?, ?, ?)");
        $stmt->execute([$jenjang, $nama_kelas, $wali_kelas_id]);
        header("Location: kelas.php?pesan=sukses_tambah");
        exit();
    }
    header("Location: kelas.php");
    exit();
}

// 2. Edit / Perbarui Wali Kelas & Data Kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $jenjang = $_POST['jenjang'] ?? 'SMA';
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');
    $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;

    if ($class_id > 0 && !empty($nama_kelas)) {
        // Cek jika wali_kelas_id dipilih, pastikan belum membina kelas LAIN
        if ($wali_kelas_id) {
            $stmt_check = $pdo->prepare("SELECT id, nama_kelas FROM classes WHERE wali_kelas_id = ? AND id != ?");
            $stmt_check->execute([$wali_kelas_id, $class_id]);
            $assigned = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if ($assigned) {
                header("Location: kelas.php?pesan=wali_sudah_ditugaskan");
                exit();
            }
        }

        $stmt = $pdo->prepare("UPDATE classes SET jenjang = ?, nama_kelas = ?, wali_kelas_id = ? WHERE id = ?");
        $stmt->execute([$jenjang, $nama_kelas, $wali_kelas_id, $class_id]);
        header("Location: kelas.php?pesan=sukses_edit");
        exit();
    }
    header("Location: kelas.php");
    exit();
}

// 3. Hapus Kelas (Validasi keterkaitan)
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