<?php
// Penyimpanan catatan publik: TIDAK membutuhkan login.
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: catatan.php');
    exit;
}

$class_id = filter_input(INPUT_POST, 'kelas_id', FILTER_VALIDATE_INT);
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
$tanggal = trim($_POST['tanggal'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

$redirect = 'catatan.php';

if (!$class_id || !$student_id || !$tanggal || $catatan === '') {
    header('Location: ' . $redirect . '?status=invalid');
    exit;
}

$date = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$date || $date->format('Y-m-d') !== $tanggal || strlen($catatan) > 2000) {
    header('Location: ' . $redirect . '?status=invalid');
    exit;
}

// Pastikan siswa benar-benar milik kelas yang dipilih.
$stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND class_id = ?");
$stmt->execute([$student_id, $class_id]);

if (!$stmt->fetchColumn()) {
    header('Location: ' . $redirect . '?status=invalid_student');
    exit;
}

$stmt = $pdo->prepare("INSERT INTO student_notes (student_id, tanggal, catatan) VALUES (?, ?, ?)");
$stmt->execute([$student_id, $tanggal, $catatan]);

header('Location: ' . $redirect . '?status=success');
exit;
