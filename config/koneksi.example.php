<?php
// Template Konfigurasi Database EduScore
// Salin berkas ini menjadi config/koneksi.php dan sesuaikan kredensial server Anda.

$host = "localhost";
$user = "root";
$pass = "";
$db   = "eduscore_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
