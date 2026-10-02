<?php
session_start();
require_once '../config/auth.php';

check_login();

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: summary_catatan.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$tanggal = trim($_POST['tanggal'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

if (!$id || $tanggal === '' || $catatan === '') {
    header('Location: summary_catatan.php?status=edit_invalid');
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$date || $date->format('Y-m-d') !== $tanggal) {
    header('Location: summary_catatan.php?status=edit_invalid');
    exit;
}

if (strlen($catatan) > 2000) {
    header('Location: summary_catatan.php?status=edit_invalid');
    exit;
}

// Pastikan catatan ada dan milik siswa kelas binaan jika bukan admin
if ($is_admin) {
    $stmt = $pdo->prepare("SELECT id FROM student_notes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare("
        SELECT sn.id 
        FROM student_notes sn 
        JOIN students st ON sn.student_id = st.id 
        WHERE sn.id = ? AND st.class_id = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $wali_kelas['id']]);
}

if (!$stmt->fetchColumn()) {
    header('Location: summary_catatan.php?status=edit_not_found');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE student_notes
    SET
        tanggal = ?,
        catatan = ?
    WHERE id = ?
");

$stmt->execute([
    $tanggal,
    $catatan,
    $id
]);

header('Location: summary_catatan.php?status=edit_success');
exit;