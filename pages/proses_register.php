<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];

    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->execute([$username]);
    
    if ($stmt_check->rowCount() > 0) {
        echo "<script>
                alert('Username sudah terdaftar! Silakan gunakan username lain.');
                window.location.href = 'register.php';
              </script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, 'guru')";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$nama_lengkap, $username, $hashed_password]);
        
        echo "<script>
                alert('Pendaftaran berhasil! Silakan login.');
                window.location.href = 'login.php';
              </script>";
        exit();
    } catch (PDOException $e) {
        die("Terjadi kesalahan saat menyimpan data: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit();
}
?>