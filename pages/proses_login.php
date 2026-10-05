<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = !empty($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'guru';

        // Penanganan "Ingat Saya": simpan username & password agar otomatis terisi berikutnya
        $cookie_time = time() + (86400 * 30); // 30 hari
        if ($remember) {
            setcookie('eduscore_remember_user', $username, $cookie_time, '/', '', false, true);
            $enc_pass = base64_encode(str_rot13($password));
            setcookie('eduscore_remember_pass', $enc_pass, $cookie_time, '/', '', false, true);
        } else {
            setcookie('eduscore_remember_user', '', time() - 3600, '/');
            setcookie('eduscore_remember_pass', '', time() - 3600, '/');
        }

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