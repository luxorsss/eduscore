<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) exit;

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['aksi'] == 'tambah') {
    $jenjang = $_POST['jenjang'];
    $nama_kelas = trim($_POST['nama_kelas']);

    if (!empty($nama_kelas)) {
        $stmt = $pdo->prepare("INSERT INTO classes (jenjang, nama_kelas) VALUES (?, ?)");
        $stmt->execute([$jenjang, $nama_kelas]);
    }
    header("Location: kelas.php");
}

if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$_GET['hapus']]);
    header("Location: kelas.php");
}