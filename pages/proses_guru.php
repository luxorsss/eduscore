<?php
session_start();
require_once '../config/auth.php';

// Wajib Login & Administrator
require_admin();

$current_user_id = (int)$_SESSION['user_id'];

// 1. TAMBAH GURU BARU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $role         = $_POST['role'] ?? 'guru';

    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        header("Location: guru.php?pesan=data_tidak_lengkap");
        exit();
    }

    if (!in_array($role, ['guru', 'admin'])) {
        $role = 'guru';
    }

    // Cek apakah username sudah dipakai
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt_cek->execute([$username]);
    if ($stmt_cek->fetchColumn() > 0) {
        header("Location: guru.php?pesan=username_terpakai");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt_insert = $pdo->prepare("INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt_insert->execute([$nama_lengkap, $username, $hashed_password, $role]);

    header("Location: guru.php?pesan=sukses_tambah");
    exit();
}

// 2. EDIT GURU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
    $target_id    = (int)($_POST['id'] ?? 0);
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $role         = $_POST['role'] ?? 'guru';

    if ($target_id <= 0 || empty($nama_lengkap) || empty($username)) {
        header("Location: guru.php?pesan=data_tidak_lengkap");
        exit();
    }

    // Tidak boleh menurunkan role diri sendiri dari admin jika sedang login
    if ($target_id === $current_user_id && $role !== 'admin') {
        $role = 'admin';
    }

    if (!in_array($role, ['guru', 'admin'])) {
        $role = 'guru';
    }

    // Cek keunikan username untuk user lain
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt_cek->execute([$username, $target_id]);
    if ($stmt_cek->fetchColumn() > 0) {
        header("Location: guru.php?pesan=username_terpakai");
        exit();
    }

    $stmt_update = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ?, role = ? WHERE id = ?");
    $stmt_update->execute([$nama_lengkap, $username, $role, $target_id]);

    header("Location: guru.php?pesan=sukses_edit");
    exit();
}

// 3. RESET PASSWORD GURU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'reset_password') {
    $target_id     = (int)($_POST['id'] ?? 0);
    $password_baru = $_POST['password_baru'] ?? '';

    if ($target_id <= 0 || empty($password_baru)) {
        header("Location: guru.php?pesan=data_tidak_lengkap");
        exit();
    }

    $hashed_password = password_hash($password_baru, PASSWORD_BCRYPT);
    $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_update->execute([$hashed_password, $target_id]);

    header("Location: guru.php?pesan=sukses_reset_pwd");
    exit();
}

// 4. HAPUS AKUN GURU (Beserta Penanganan Jadwal & Kelas Binaan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
    $target_id = (int)($_POST['id'] ?? 0);
    $tindakan_jadwal = $_POST['tindakan_jadwal'] ?? 'admin';
    $target_guru_id = (int)($_POST['target_guru_id'] ?? 0);

    if ($target_id <= 0) {
        header("Location: guru.php");
        exit();
    }

    // Larangan: Tidak boleh menghapus akun sendiri
    if ($target_id === $current_user_id) {
        header("Location: guru.php?pesan=gagal_hapus_diri");
        exit();
    }

    // Larangan: Akun dengan ID 1 tidak boleh dihapus
    if ($target_id === 1) {
        header("Location: guru.php?pesan=gagal_hapus_utama");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Cek apakah ada jadwal mengajar yang diampu guru ini
        $stmt_jdw = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE user_id = ?");
        $stmt_jdw->execute([$target_id]);
        $jumlah_jadwal = (int)$stmt_jdw->fetchColumn();

        if ($jumlah_jadwal > 0) {
            if ($tindakan_jadwal === 'kosong') {
                // Kosongkan pengampu agar jadwal berstatus slot terbuka
                $stmt_alih = $pdo->prepare("UPDATE teaching_schedules SET user_id = NULL, is_manual = 1 WHERE user_id = ?");
                $stmt_alih->execute([$target_id]);
            } elseif ($tindakan_jadwal === 'alihkan' && $target_guru_id > 0 && $target_guru_id !== $target_id) {
                // Alihkan ke guru tujuan
                $stmt_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt_role->execute([$target_guru_id]);
                $target_role = $stmt_role->fetchColumn();
                $is_manual = ($target_role === 'admin') ? 1 : 0;

                $stmt_alih = $pdo->prepare("UPDATE teaching_schedules SET user_id = ?, is_manual = ? WHERE user_id = ?");
                $stmt_alih->execute([$target_guru_id, $is_manual, $target_id]);
            } else {
                // Default: Alihkan semua jadwal ke Admin sebagai Titipan Manual agar nilai siswa tetap aman
                $admin_id = $current_user_id;
                $stmt_alih = $pdo->prepare("UPDATE teaching_schedules SET user_id = ?, is_manual = 1 WHERE user_id = ?");
                $stmt_alih->execute([$admin_id, $target_id]);
            }
        }

        // 2. Lepaskan penugasan Wali Kelas di tabel classes jika guru ini sedang membina kelas
        $stmt_wk = $pdo->prepare("UPDATE classes SET wali_kelas_id = NULL WHERE wali_kelas_id = ?");
        $stmt_wk->execute([$target_id]);

        // 3. Hapus akun pengguna dari database
        $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$target_id]);

        $pdo->commit();
        header("Location: guru.php?pesan=sukses_hapus");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: guru.php?pesan=gagal_hapus&error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Redirect fallback
header("Location: guru.php");
exit();
