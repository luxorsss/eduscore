<?php
session_start();
require_once '../config/auth.php';

check_login();

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

// Helper URL redirect
function getRedirectUrl($filter_kelas = null, $filter_guru = null, $pesan = null) {
    $url = 'jadwal.php';
    $queryParams = [];
    if (!empty($filter_kelas)) {
        $queryParams['filter_kelas'] = $filter_kelas;
    }
    if (!empty($filter_guru)) {
        $queryParams['filter_guru'] = $filter_guru;
    }
    if (!empty($pesan)) {
        $queryParams['pesan'] = $pesan;
    }
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    return $url;
}

// 0. ALIKHAN PENGAMPU (Khusus Admin: Transfer Jadwal & Nilai ke Guru Baru)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'alihkan_pengampu') {
    require_admin();
    $jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
    $new_user_id = (int)($_POST['new_user_id'] ?? 0);
    $redirect_filter_kelas = !empty($_POST['redirect_filter_kelas']) ? $_POST['redirect_filter_kelas'] : null;
    $redirect_filter_guru = !empty($_POST['redirect_filter_guru']) ? $_POST['redirect_filter_guru'] : null;

    if ($jadwal_id > 0 && $new_user_id > 0) {
        $stmt_update = $pdo->prepare("UPDATE teaching_schedules SET user_id = ? WHERE id = ?");
        $stmt_update->execute([$new_user_id, $jadwal_id]);
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'sukses_alihkan'));
        exit();
    }
    header("Location: jadwal.php");
    exit();
}

// 1. TAMBAH JADWAL (Single, Bulk Mapel per Kelas, & Bulk Kelas per Mapel)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $mode = $_POST['mode'] ?? 'kelas_to_mapel';
    $pairs = []; // Array kumpulan [class_id, subject_id]

    // Jika Admin, bisa menentukan penugasan untuk guru tertentu
    $assignee_id = $user_id;
    if ($is_admin && !empty($_POST['target_user_id'])) {
        $assignee_id = (int)$_POST['target_user_id'];
    }

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

        // Cek apakah kombinasi (class_id, subject_id) SUDAH diampu siapapun (Strictly 1 guru per mapel per kelas)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE class_id = ? AND subject_id = ?");
        $stmt_insert = $pdo->prepare("INSERT INTO teaching_schedules (user_id, class_id, subject_id) VALUES (?, ?, ?)");

        $berhasil = 0;
        $dilewati = 0;

        foreach ($pairs as $p) {
            $cid = $p[0];
            $sid = $p[1];

            $stmt_check->execute([$cid, $sid]);
            if ($stmt_check->fetchColumn() == 0) {
                $stmt_insert->execute([$assignee_id, $cid, $sid]);
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
    $redirect_filter_kelas = !empty($_POST['redirect_filter_kelas']) ? $_POST['redirect_filter_kelas'] : null;
    $redirect_filter_guru = !empty($_POST['redirect_filter_guru']) ? $_POST['redirect_filter_guru'] : null;
    $jadwal_ids = isset($_POST['jadwal_ids']) && is_array($_POST['jadwal_ids']) ? $_POST['jadwal_ids'] : [];

    if (empty($jadwal_ids)) {
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'kosong'));
        exit();
    }

    $sukses_hapus = 0;
    $gagal_terkait = 0;

    $stmt_check_grades = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE schedule_id = ?");
    
    // Admin bisa hapus semua jadwal; Guru hanya bisa hapus miliknya
    if ($is_admin) {
        $stmt_delete = $pdo->prepare("DELETE FROM teaching_schedules WHERE id = ?");
    } else {
        $stmt_delete = $pdo->prepare("DELETE FROM teaching_schedules WHERE id = ? AND user_id = ?");
    }

    foreach ($jadwal_ids as $jid) {
        $id = (int)$jid;

        // Cek apakah jadwal memiliki data nilai siswa
        $stmt_check_grades->execute([$id]);
        if ($stmt_check_grades->fetchColumn() > 0) {
            $gagal_terkait++;
            continue;
        }

        try {
            if ($is_admin) {
                $stmt_delete->execute([$id]);
            } else {
                $stmt_delete->execute([$id, $user_id]);
            }
            $sukses_hapus++;
        } catch (PDOException $e) {
            $gagal_terkait++;
        }
    }

    if ($gagal_terkait > 0) {
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'sebagian_gagal'));
    } else {
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'sukses_hapus'));
    }
    exit();
}

// 3. SINGLE DELETE JADWAL (Via GET)
if (isset($_GET['hapus'])) {
    $jadwal_id = (int)$_GET['hapus'];
    $redirect_filter_kelas = isset($_GET['redirect_filter_kelas']) ? $_GET['redirect_filter_kelas'] : null;
    $redirect_filter_guru = isset($_GET['redirect_filter_guru']) ? $_GET['redirect_filter_guru'] : null;

    // Cek keterkaitan dengan nilai di tabel grades
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE schedule_id = ?");
    $stmt_check->execute([$jadwal_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'gagal_nilai'));
        exit();
    }

    try {
        if ($is_admin) {
            $stmt = $pdo->prepare("DELETE FROM teaching_schedules WHERE id = ?");
            $stmt->execute([$jadwal_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM teaching_schedules WHERE id = ? AND user_id = ?");
            $stmt->execute([$jadwal_id, $user_id]);
        }
        
        header("Location: " . getRedirectUrl($redirect_filter_kelas, $redirect_filter_guru, 'sukses_hapus'));
        exit();
    } catch (PDOException $e) {
        die("Error menghapus jadwal: " . $e->getMessage());
    }
}

// Default fallback
header("Location: jadwal.php");
exit();