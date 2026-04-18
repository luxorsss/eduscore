<?php
// Mulai session untuk mengirim pesan notifikasi
session_start();

// Panggil koneksi database (pastikan path-nya benar)
require_once '../config/koneksi.php';

// Cek apakah data dikirim melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];

    // 1. Cek apakah username sudah dipakai orang lain
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->execute([$username]);
    
    if ($stmt_check->rowCount() > 0) {
        // Jika username sudah ada, kembalikan ke halaman register dengan error
        echo "<script>
                alert('Username sudah terdaftar! Silakan gunakan username lain.');
                window.location.href = 'register.php';
              </script>";
        exit();
    }

    // 2. Enkripsi password (Hashing) - JANGAN PERNAH simpan password dalam bentuk teks asli!
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Simpan ke database menggunakan Prepared Statement (Mencegah SQL Injection)
    $sql = "INSERT INTO users (nama_lengkap, username, password) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$nama_lengkap, $username, $hashed_password]);
        
        // Jika berhasil, arahkan ke halaman login
        echo "<script>
                alert('Pendaftaran berhasil! Silakan login.');
                window.location.href = 'login.php';
              </script>";
        exit();
    } catch (PDOException $e) {
        die("Terjadi kesalahan saat menyimpan data: " . $e->getMessage());
    }
} else {
    // Jika ada yang mencoba akses file ini langsung dari URL, tendang ke login
    header("Location: login.php");
    exit();
}
?>