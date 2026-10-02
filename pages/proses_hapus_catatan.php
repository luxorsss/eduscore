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

if (!$id) {
    header('Location: summary_catatan.php?status=delete_invalid');
    exit;
}

// Cek keberadaan dan kepemilikan catatan
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
    header('Location: summary_catatan.php?status=delete_not_found');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM student_notes WHERE id = ?");
$stmt->execute([$id]);

header('Location: summary_catatan.php?status=delete_success');
exit;