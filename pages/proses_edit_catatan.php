<?php

session_start();

// Edit catatan wajib login
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
// AMBIL DATA
// =====================================================

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$tanggal = trim($_POST['tanggal'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');


// =====================================================
// VALIDASI DASAR
// =====================================================

if (!$id || $tanggal === '' || $catatan === '') {

    header(
        'Location: summary_catatan.php?status=edit_invalid'
    );

    exit;
}


// =====================================================
// VALIDASI TANGGAL
// =====================================================

$date = DateTime::createFromFormat(
    'Y-m-d',
    $tanggal
);

if (
    !$date ||
    $date->format('Y-m-d') !== $tanggal
) {

    header(
        'Location: summary_catatan.php?status=edit_invalid'
    );

    exit;
}


// =====================================================
// BATAS CATATAN
// =====================================================

if (strlen($catatan) > 2000) {

    header(
        'Location: summary_catatan.php?status=edit_invalid'
    );

    exit;
}


// =====================================================
// PASTIKAN CATATAN ADA
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
        'Location: summary_catatan.php?status=edit_not_found'
    );

    exit;
}


// =====================================================
// UPDATE
// =====================================================

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


// =====================================================
// SELESAI
// =====================================================

header(
    'Location: summary_catatan.php?status=edit_success'
);

exit;