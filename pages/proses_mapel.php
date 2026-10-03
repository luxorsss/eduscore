<?php
session_start();
require_once '../config/auth.php';

// Proteksi: Hanya Admin yang bisa mengelola data mapel
require_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'bulk_tambah') {
        $mapel_list = $_POST['nama_mapel_bulk'] ?? [];
        if (!is_array($mapel_list)) {
            $mapel_list = [];
        }

        $stmt_existing = $pdo->query("SELECT LOWER(TRIM(nama_mapel)) FROM subjects");
        $existing = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);
        $existing_set = array_flip($existing);

        $inserted = 0;
        $skipped = [];
        $added = [];

        try {
            $pdo->beginTransaction();
            $stmt_insert = $pdo->prepare("INSERT INTO subjects (nama_mapel) VALUES (?)");

            foreach ($mapel_list as $item) {
                $clean_name = trim($item);
                if (empty($clean_name)) continue;

                $lower = strtolower($clean_name);
                if (isset($existing_set[$lower])) {
                    $skipped[] = $clean_name;
                } else {
                    $stmt_insert->execute([$clean_name]);
                    $existing_set[$lower] = true;
                    $added[] = $clean_name;
                    $inserted++;
                }
            }

            $pdo->commit();
            $_SESSION['bulk_status'] = [
                'success' => true,
                'inserted' => $inserted,
                'added' => $added,
                'skipped' => $skipped
            ];
            header("Location: mapel.php");
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("Gagal menambah mapel massal: " . $e->getMessage());
        }
    }

    $nama_mapel = isset($_POST['nama_mapel']) ? trim($_POST['nama_mapel']) : '';

    if (!empty($nama_mapel)) {
        try {
            if ($action === 'tambah') {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(nama_mapel) = LOWER(?)");
                $stmt_check->execute([$nama_mapel]);
                $is_exists = $stmt_check->fetchColumn();

                if ($is_exists > 0) {
                    echo "<script>
                            alert('Gagal! Mata pelajaran \"$nama_mapel\" sudah ada di dalam sistem.');
                            window.location.href = 'mapel.php';
                          </script>";
                    exit();
                }

                $sql = "INSERT INTO subjects (nama_mapel) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_mapel]);

            } elseif ($action === 'edit' && isset($_POST['id_mapel'])) {
                $id_mapel = $_POST['id_mapel'];

                // Cegah duplikat nama, abaikan ID mapel saat ini
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(nama_mapel) = LOWER(?) AND id != ?");
                $stmt_check->execute([$nama_mapel, $id_mapel]);
                $is_exists = $stmt_check->fetchColumn();

                if ($is_exists > 0) {
                    echo "<script>
                            alert('Gagal Edit! Nama mata pelajaran \"$nama_mapel\" sudah digunakan oleh mapel lain.');
                            window.location.href = 'mapel.php';
                          </script>";
                    exit();
                }

                $sql = "UPDATE subjects SET nama_mapel = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_mapel, $id_mapel]);
            }

            header("Location: mapel.php");
            exit();

        } catch (PDOException $e) {
            die("Gagal memproses data: " . $e->getMessage());
        }
    }
}

if (isset($_GET['hapus'])) {
    $id_mapel = $_GET['hapus'];

    try {
        // Cek apakah mapel terdaftar di jadwal penugasan
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM teaching_schedules WHERE subject_id = ?");
        $stmt_check->execute([$id_mapel]);
        $is_used = $stmt_check->fetchColumn();

        if ($is_used > 0) {
            echo "<script>
                    alert('Gagal menghapus! Mata pelajaran ini masih digunakan dalam jadwal mengajar Anda.');
                    window.location.href = 'mapel.php';
                  </script>";
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt_delete->execute([$id_mapel]);

            echo "<script>
                    alert('Mata pelajaran berhasil dihapus!');
                    window.location.href = 'mapel.php';
                  </script>";
        }
    } catch (PDOException $e) {
        die("Gagal menghapus data: " . $e->getMessage());
    }
    exit();
}

header("Location: dashboard.php");
exit();
?>