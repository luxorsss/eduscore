<?php
// config/auth.php
require_once __DIR__ . '/session.php';

require_once __DIR__ . '/koneksi.php';

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin() {
    check_login();
    if (!is_admin()) {
        header("Location: dashboard.php?pesan=akses_ditolak");
        exit();
    }
}

function get_user_wali_kelas($pdo, $user_id) {
    static $cache = [];
    if (isset($cache[$user_id])) {
        return $cache[$user_id];
    }
    $stmt = $pdo->prepare("SELECT id, jenjang, nama_kelas FROM classes WHERE wali_kelas_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $cache[$user_id] = $res ?: false;
    return $cache[$user_id];
}

function require_wali_kelas_or_admin($pdo) {
    check_login();
    if (is_admin()) {
        return null;
    }
    $wk = get_user_wali_kelas($pdo, $_SESSION['user_id']);
    if (!$wk) {
        header("Location: dashboard.php?pesan=bukan_walikelas");
        exit();
    }
    return $wk;
}

function can_edit_schedule_grades($pdo, $user_id, $schedule_id) {
    if (is_admin()) {
        return true;
    }
    $stmt = $pdo->prepare("
        SELECT ts.user_id as teacher_id, ts.class_id, ts.is_manual, c.wali_kelas_id, u.role as owner_role
        FROM teaching_schedules ts
        JOIN classes c ON ts.class_id = c.id
        LEFT JOIN users u ON ts.user_id = u.id
        WHERE ts.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$schedule_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    // Jika user adalah guru pengampu jadwal tersebut
    if (!empty($row['teacher_id']) && (int)$row['teacher_id'] === (int)$user_id) {
        return true;
    }
    // Jika user adalah wali kelas dari kelas ini, dan mapel ini belum ada pengampu (kosong) atau ditandai titipan manual
    if (!empty($row['wali_kelas_id']) && (int)$row['wali_kelas_id'] === (int)$user_id) {
        if ($row['teacher_id'] === null || (int)$row['is_manual'] === 1) {
            return true;
        }
    }
    return false;
}

