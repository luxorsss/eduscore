<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Helper URL redirect
function getRedirectUrl($filter = null, $pesan = null) {
    $url = 'jadwal.php';
    $queryParams = [];
    if (!empty($filter)) {
        $queryParams['filter_kelas'] = $filter;
    }
    if (!empty($pesan)) {
        $queryParams['pesan'] = $pesan;
    }
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    return $url;
}

// 1. TAMBAH JADWAL (Single, Bulk Mapel per Kelas, & Bulk Kelas per Mapel)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $mode = $_POST['mode'] ?? 'kelas_to_mapel';
    $pairs = []; // Array kumpulan [class_id, subject_id]

    if ($mode === 'kelas_to_mapel') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $subject_ids = isset($_POST['subject_ids']) && is_array($_POST['subject_ids']) ? array_map('intval', $_POST['subject_ids']) : [];
        
        if ($class_id > 0 && !empty($subject_ids)) {
            foreach ($subject_ids as $sid) {
                if ($sid > 0) $pairs[] = [$class_id, $sid];
            }
        }
    } elseif ($mode === 'mapel_to_kelas') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $class_ids = isset($_POST['class_ids']) && is_array($_POST['class_ids']) ? array_map('intval', $_POST['class_ids']) : [];

        if ($subject_id > 0 && !empty($class_ids)) {
            foreach ($class_ids as $cid) {
                if ($cid > 0) $pairs[] = [$cid, $subject_id];
            }
        }
    }

    if (empty($pairs)) {
        echo "<script>alert('Harap pilih data dan centang minimal satu pilihan!'); window.history.back();</script>";
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
        $stmt_insert = $pdo->prepare("INSERT INTO teaching_schedules (user_id, class_id, subject_id) VALUES (?, ?, ?)");

        $berhasil = 0;
        $dilewati = 0;

        foreach ($pairs as $p) {
            $cid = $p[0];
            $sid = $p[1];

            $stmt_check->execute([$user_id, $cid, $sid]);
            if ($stmt_check->fetchColumn() == 0) {
                $stmt_insert->execute([$user_id, $cid, $sid]);
                $berhasil++;
            } else {
                $dilewati++;
            }
        }

        $pdo->commit();

        $pesan = "sukses_tambah&added=" . $berhasil . "&skipped=" . $dilewati;
        header("Location: jadwal.php?pesan=" . $pesan);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error menyimpan jadwal: " . $e->getMessage());
    }
}

// 2. BULK DELETE JADWAL (Via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'bulk_delete') {
    $redirect_filter = !empty($_POST['redirect_filter']) ? $_POST['redirect_filter'] : null;
    $jadwal_ids = isset($_POST['jadwal_ids']) && is_array($_POST['jadwal_ids']) ? $_POST['jadwal_ids'] : [];

    if (empty($jadwal_ids)) {
        header("Location: " . getRedirectUrl($redirect_filter, 'kosong'));
        exit();
    }

    $sukses_hapus = 0;
    $gagal_terkait = 0;

    $stmt_check_grades = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE schedule_id = ?");
    $stmt_delete = $pdo->prepare("DELETE FROM teaching_schedules WHERE id = ? AND user_id = ?");

    foreach ($jadwal_ids as $jid) {
        $id = (int)$jid;

        // Cek apakah jadwal memiliki data nilai siswa
        $stmt_check_grades->execute([$id]);
        if ($stmt_check_grades->fetchColumn() > 0) {
            $gagal_terkait++;
            continue;
        }

        try {
            $stmt_delete->execute([$id, $user_id]);
            $sukses_hapus++;
        } catch (PDOException $e) {
            $gagal_terkait++;
        }
    }

    if ($gagal_terkait > 0) {
        header("Location: " . getRedirectUrl($redirect_filter, 'sebagian_gagal'));
    } else {
        header("Location: " . getRedirectUrl($redirect_filter, 'sukses_hapus'));
    }
    exit();
}

// 3. SINGLE DELETE JADWAL (Via GET)
if (isset($_GET['hapus'])) {
    $jadwal_id = (int)$_GET['hapus'];
    $redirect_filter = isset($_GET['redirect_filter']) ? $_GET['redirect_filter'] : null;

    // Cek keterkaitan dengan nilai di tabel grades
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE schedule_id = ?");
    $stmt_check->execute([$jadwal_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: " . getRedirectUrl($redirect_filter, 'gagal_nilai'));
        exit();
    }

    try {
        $sql = "DELETE FROM teaching_schedules WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jadwal_id, $user_id]);
        
        header("Location: " . getRedirectUrl($redirect_filter, 'sukses_hapus'));
        exit();
    } catch (PDOException $e) {
        die("Error menghapus jadwal: " . $e->getMessage());
    }
}

// Default fallback
header("Location: jadwal.php");
exit();