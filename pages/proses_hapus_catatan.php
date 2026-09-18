<?php

session_start();

// Hapus catatan wajib login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/koneksi.php';


// =====================================================
// HANYA TERIMA POST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: summary_catatan.php');
    exit;
}


// =====================================================
// AMBIL ID
// =====================================================

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);


// =====================================================
// VALIDASI
// =====================================================

if (!$id) {

    header(
        'Location: summary_catatan.php?status=delete_invalid'
    );

    exit;
}


// =====================================================
// CEK CATATAN
// =====================================================

$stmt = $pdo->prepare("
    SELECT id
    FROM student_notes
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

if (!$stmt->fetchColumn()) {

    header(
        'Location: summary_catatan.php?status=delete_not_found'
    );

    exit;
}


// =====================================================
// HAPUS
// =====================================================

$stmt = $pdo->prepare("
    DELETE FROM student_notes
    WHERE id = ?
");

$stmt->execute([$id]);


// =====================================================
// SELESAI
// =====================================================

header(
    'Location: summary_catatan.php?status=delete_success'
);

exit;