<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'guru';

        header("Location: dashboard.php");
        exit();
    } else {
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