<?php
session_start();
require_once '../config/auth.php';

check_login();

$is_admin = is_admin();
$wali_kelas = require_wali_kelas_or_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulk_catatan.php');
    exit;
}

$rawData = $_POST['data'] ?? '';

if ($rawData === '') {
    header('Location: bulk_catatan.php?status=invalid');
    exit;
}

$data = json_decode($rawData, true);

if (!is_array($data) || empty($data)) {
    header('Location: bulk_catatan.php?status=invalid');
    exit;
}

if (count($data) > 500) {
    header('Location: bulk_catatan.php?status=too_many');
    exit;
}

// Siapkan query verifikasi siswa
if ($is_admin) {
    $studentStmt = $pdo->prepare("SELECT id FROM students WHERE id = ? LIMIT 1");
} else {
    $studentStmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND class_id = ? LIMIT 1");
}

$insertStmt = $pdo->prepare("
    INSERT INTO student_notes (student_id, tanggal, catatan)
    VALUES (?, ?, ?)
");

try {
    $pdo->beginTransaction();
    $totalInserted = 0;

    foreach ($data as $item) {
        if (!is_array($item)) {
            throw new Exception('Format data tidak valid.');
        }

        $studentId = filter_var($item['student_id'] ?? null, FILTER_VALIDATE_INT);
        $tanggal = trim($item['tanggal'] ?? '');
        $catatan = trim($item['catatan'] ?? '');

        if (!$studentId || $tanggal === '' || $catatan === '') {
            throw new Exception('Ada data yang belum lengkap.');
        }

        $date = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$date || $date->format('Y-m-d') !== $tanggal) {
            throw new Exception('Ada tanggal yang tidak valid.');
        }

        if (strlen($catatan) > 2000) {
            throw new Exception('Ada catatan yang melebihi 2000 karakter.');
        }

        // Verifikasi kepemilikan siswa
        if ($is_admin) {
            $studentStmt->execute([$studentId]);
        } else {
            $studentStmt->execute([$studentId, $wali_kelas['id']]);
        }

        if (!$studentStmt->fetchColumn()) {
            throw new Exception('Ada siswa yang tidak valid atau di luar kelas binaan.');
        }

        $insertStmt->execute([$studentId, $tanggal, $catatan]);
        $totalInserted++;
    }

    $pdo->commit();
    header('Location: bulk_catatan.php?status=success&total=' . $totalInserted);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: bulk_catatan.php?status=error');
    exit;
}