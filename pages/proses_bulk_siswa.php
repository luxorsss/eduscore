<?php
session_start();
require_once '../config/auth.php';

require_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi == 'edit_single') {
        $id_siswa = $_POST['id_siswa'] ?? 0;
        $nama_siswa = trim($_POST['nama_siswa'] ?? '');
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;

        if ($id_siswa > 0 && !empty($nama_siswa)) {
            try {
                $stmt = $pdo->prepare("UPDATE students SET nama = ?, class_id = ? WHERE id = ?");
                $stmt->execute([$nama_siswa, $class_id, $id_siswa]);
                
                echo "<script>
                        alert('Data siswa berhasil diperbarui!');
                        window.location.href = 'siswa.php';
                      </script>";
            } catch (PDOException $e) {
                die("Gagal memperbarui data: " . $e->getMessage());
            }
        }
        exit();
    }

    // Hapus massal siswa beserta data nilainya
    if ($aksi === 'hapus_massal') {
        $raw_ids = $_POST['id_hapus'] ?? [];

        if (!is_array($raw_ids)) {
            $raw_ids = [$raw_ids];
        }
        $ids = array_values(array_filter(array_map('intval', $raw_ids)));

        if (!empty($ids)) {
            try {
                $pdo->beginTransaction(); 
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                // Hapus nilai terkait terlebih dahulu sebelum data siswa
                $sql_nilai = "DELETE FROM grades WHERE student_id IN ($placeholders)";
                $stmt_nilai = $pdo->prepare($sql_nilai);
                $stmt_nilai->execute($ids);

                $sql_siswa = "DELETE FROM students WHERE id IN ($placeholders)";
                $stmt_siswa = $pdo->prepare($sql_siswa);
                $stmt_siswa->execute($ids);
                
                $pdo->commit();
                header("Location: siswa.php?pesan=berhasil_hapus");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                die("Gagal hapus massal: " . $e->getMessage());
            }
        } else {
            echo "<script>alert('Pilih minimal satu siswa untuk dihapus!'); window.history.back();</script>";
            exit();
        }
    }

    // Tambah siswa massal
    if ($aksi === 'simpan_massal') {
        $namas = $_POST['nama_siswa'] ?? [];
        $niss = $_POST['nis_siswa'] ?? [];
        $class_ids = $_POST['class_id_siswa'] ?? [];

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO students (nama, nis, class_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            foreach ($namas as $i => $nama) {
                if (!empty($nama)) {
                    $stmt->execute([$nama, $niss[$i], $class_ids[$i]]);
                }
            }

            $pdo->commit();
            header("Location: siswa.php?pesan=berhasil_tambah");
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Gagal tambah massal: " . $e->getMessage());
        }
    }

    // Impor data siswa dari teks berpemisah koma (Nama, Kelas)
    if ($aksi === 'copas_massal') {
        $data_copas = $_POST['data_copas'] ?? '';
        if (empty(trim($data_copas))) {
            header("Location: siswa.php");
            exit;
        }

        $stmt_kelas = $pdo->query("SELECT id, nama_kelas FROM classes");
        $kelas_map = [];
        while ($row = $stmt_kelas->fetch(PDO::FETCH_ASSOC)) {
            $kelas_map[strtolower(trim($row['nama_kelas']))] = $row['id'];
        }

        $lines = explode("\n", trim($data_copas));
        $berhasil = 0;
        $gagal = [];

        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO students (nis, nama, class_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $cols = explode(',', trim($line));

                if (count($cols) >= 2) {
                    $nama = trim($cols[0]);
                    $nama_kelas_input = strtolower(trim($cols[1]));

                    if (isset($kelas_map[$nama_kelas_input])) {
                        $class_id = $kelas_map[$nama_kelas_input];
                        
                        $nis_otomatis = 'S' . rand(100000, 999999);
                        
                        $stmt->execute([$nis_otomatis, $nama, $class_id]);
                        $berhasil++;
                    } else {
                        $gagal[] = "Siswa '$nama' gagal (Kelas '" . trim($cols[1]) . "' tidak ada)";
                    }
                } else {
                    $gagal[] = "Baris '$line' format salah (lupa tanda koma?)";
                }
            }
            $pdo->commit();

            $pesan = "Berhasil simpan: $berhasil siswa.\\n";
            if (count($gagal) > 0) {
                $pesan .= "Gagal: " . count($gagal) . " data. Cek penulisan nama kelas!";
            }
            
            echo "<script>alert('$pesan'); window.location.href='siswa.php';</script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error: " . $e->getMessage());
        }
    }

    // Pindah kelas massal
    if ($aksi === 'pindah_massal') {
        $ids = $_POST['id_hapus'] ?? [];
        $target_class = $_POST['target_class_id'] ?? '';

        if (!empty($ids) && !empty($target_class)) {
            try {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "UPDATE students SET class_id = ? WHERE id IN ($placeholders)";
                $params = array_merge([$target_class], $ids);

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $jumlah = count($ids);
                echo "<script>alert('Berhasil memindahkan $jumlah siswa ke kelas baru!'); window.location.href='siswa.php';</script>";
                exit;
            } catch (PDOException $e) {
                die("Gagal memindahkan siswa: " . $e->getMessage());
            }
        } else {
            echo "<script>alert('Pilih siswa yang dicentang DAN pilih kelas tujuan di dropdown terlebih dahulu!'); window.history.back();</script>";
            exit;
        }
    }
}

// Hapus siswa tunggal
if (isset($_GET['hapus_single'])) {
    $id_siswa = $_GET['hapus_single'];
    
    try {
        $pdo->beginTransaction();

        $stmt_hapus_nilai = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
        $stmt_hapus_nilai->execute([$id_siswa]);

        $stmt_hapus_siswa = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt_hapus_siswa->execute([$id_siswa]);

        $pdo->commit();
        header("Location: siswa.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Gagal menghapus siswa: " . $e->getMessage());
    }
}