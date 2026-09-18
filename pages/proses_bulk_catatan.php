<?php

session_start();

require_once '../config/koneksi.php';

// =====================================================
// HANYA TERIMA POST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulk_catatan.php');
    exit;
}


// =====================================================
// AMBIL DATA JSON
// =====================================================

$rawData = $_POST['data'] ?? '';

if ($rawData === '') {
    header('Location: bulk_catatan.php?status=invalid');
    exit;
}


$data = json_decode($rawData, true);


// =====================================================
// VALIDASI JSON
// =====================================================

if (
    !is_array($data) ||
    empty($data)
) {
    header('Location: bulk_catatan.php?status=invalid');
    exit;
}


// Batasi jumlah data dalam sekali proses.
if (count($data) > 500) {
    header('Location: bulk_catatan.php?status=too_many');
    exit;
}


// =====================================================
// SIAPKAN STATEMENT
// =====================================================

$studentStmt = $pdo->prepare("
    SELECT id
    FROM students
    WHERE id = ?
    LIMIT 1
");

$insertStmt = $pdo->prepare("
    INSERT INTO student_notes (
        student_id,
        tanggal,
        catatan
    )
    VALUES (?, ?, ?)
");


// =====================================================
// VALIDASI + TRANSACTION
// =====================================================

try {

    $pdo->beginTransaction();

    $totalInserted = 0;


    foreach ($data as $item) {

        // ---------------------------------------------
        // VALIDASI STRUKTUR
        // ---------------------------------------------

        if (!is_array($item)) {
            throw new Exception(
                'Format data tidak valid.'
            );
        }


        $studentId =
            filter_var(
                $item['student_id'] ?? null,
                FILTER_VALIDATE_INT
            );

        $tanggal =
            trim(
                $item['tanggal'] ?? ''
            );

        $catatan =
            trim(
                $item['catatan'] ?? ''
            );


        // ---------------------------------------------
        // VALIDASI FIELD
        // ---------------------------------------------

        if (
            !$studentId ||
            $tanggal === '' ||
            $catatan === ''
        ) {
            throw new Exception(
                'Ada data yang belum lengkap.'
            );
        }


        // ---------------------------------------------
        // VALIDASI TANGGAL
        // ---------------------------------------------

        $date =
            DateTime::createFromFormat(
                'Y-m-d',
                $tanggal
            );

        if (
            !$date ||
            $date->format('Y-m-d') !== $tanggal
        ) {
            throw new Exception(
                'Ada tanggal yang tidak valid.'
            );
        }


        // ---------------------------------------------
        // VALIDASI PANJANG CATATAN
        // ---------------------------------------------

        if (strlen($catatan) > 2000) {
            throw new Exception(
                'Ada catatan yang melebihi 2000 karakter.'
            );
        }


        // ---------------------------------------------
        // PASTIKAN SISWA ADA
        // ---------------------------------------------

        $studentStmt->execute([
            $studentId
        ]);

        if (!$studentStmt->fetchColumn()) {

            throw new Exception(
                'Ada siswa yang tidak ditemukan.'
            );

        }


        // ---------------------------------------------
        // INSERT
        // ---------------------------------------------

        $insertStmt->execute([
            $studentId,
            $tanggal,
            $catatan
        ]);


        $totalInserted++;

    }


    // =================================================
    // COMMIT
    // =================================================

    $pdo->commit();


    header(
        'Location: bulk_catatan.php?status=success&total='
        . $totalInserted
    );

    exit;


} catch (Throwable $e) {

    // =================================================
    // ROLLBACK
    // =================================================

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    // Jangan tampilkan detail error database
    // kepada pengguna.

    header(
        'Location: bulk_catatan.php?status=error'
    );

    exit;
}