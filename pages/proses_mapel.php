<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi: Pastikan hanya user login yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. PROSES TAMBAH & EDIT MAPEL (Metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama_mapel = trim($_POST['nama_mapel']);

    if (!empty($nama_mapel)) {
        try {
            if ($action === 'tambah') {
                // --- PROSES TAMBAH ---
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(nama_mapel) = LOWER(?)");
                $stmt_check->execute([$nama_mapel]);
                $is_exists = $stmt_check->fetchColumn();

                if ($is_exists > 0) {
                    echo "<script>
                            alert('Gagal! Mata pelajaran \"$nama_mapel\" sudah ada di dalam sistem.');
                            window.location.href = 'mapel.php';
                          </script>";
                    exit();
                }

                $sql = "INSERT INTO subjects (nama_mapel) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_mapel]);

            } elseif ($action === 'edit' && isset($_POST['id_mapel'])) {
                // --- PROSES EDIT ---
                $id_mapel = $_POST['id_mapel'];

                // Cek duplikat, tapi kecualikan ID mapel yang sedang diedit ini
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(nama_mapel) = LOWER(?) AND id != ?");
                $stmt_check->execute([$nama_mapel, $id_mapel]);
                $is_exists = $stmt_check->fetchColumn();

                if ($is_exists > 0) {
                    echo "<script>
                            alert('Gagal Edit! Nama mata pelajaran \"$nama_mapel\" sudah digunakan oleh mapel lain.');
                            window.location.href = 'mapel.php';
                          </script>";
                    exit();
                }

                $sql = "UPDATE subjects SET nama_mapel = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_mapel, $id_mapel]);
            }

            header("Location: mapel.php");
            exit();

        } catch (PDOException $e) {
            die("Gagal memproses data: " . $e->getMessage());
        }
    }
}

// 2. PROSES HAPUS MAPEL (Metode GET)
if (isset($_GET['hapus'])) {
    $id_mapel = $_GET['hapus'];

    try {
        // Cek dulu apakah mapel ini sedang digunakan di jadwal (teaching_schedules)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE subject_id = ?");
        $stmt_check->execute([$id_mapel]);
        $is_used = $stmt_check->fetchColumn();

        if ($is_used > 0) {
            // Jika masih dipakai, jangan dihapus agar data nilai tidak error/berantakan
            echo "<script>
                    alert('Gagal menghapus! Mata pelajaran ini masih digunakan dalam jadwal mengajar Anda.');
                    window.location.href = 'mapel.php';
                  </script>";
        } else {
            // Jika aman, eksekusi penghapusan
            $stmt_delete = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt_delete->execute([$id_mapel]);

            echo "<script>
                    alert('Mata pelajaran berhasil dihapus!');
                    window.location.href = 'mapel.php';
                  </script>";
        }
    } catch (PDOException $e) {
        die("Gagal menghapus data: " . $e->getMessage());
    }
    exit();
}

// Jika file ini diakses langsung tanpa parameter, kembalikan ke dashboard
header("Location: dashboard.php");
exit();
?>