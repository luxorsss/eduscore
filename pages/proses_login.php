<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Cari user di database berdasarkan username
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Cek apakah user ada DAN password cocok
    if ($user && password_verify($password, $user['password'])) {
        
        // 3. Jika cocok, buat 'Kartu Pengenal' (Session) untuk keliling aplikasi
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username'] = $user['username'];

        // Arahkan ke Dashboard
        header("Location: dashboard.php");
        exit();
        
    } else {
        // Jika username atau password salah
        echo "<script>
                alert('Username atau password salah!');
                window.location.href = 'login.php';
              </script>";
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>