<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) exit;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST['aksi'] ?? '';

    // A. FITUR BULK DELETE (Hapus Massal)
    if ($aksi === 'hapus_massal') {
        $ids = $_POST['id_hapus'] ?? []; // Mengambil array ID yang dicentang
        if (!empty($ids)) {
            try {
                // Gunakan Transaction agar jika salah satu gagal, semuanya dibatalkan
                $pdo->beginTransaction(); 
                
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                
                // 1. HAPUS ANAK (Data Nilai di tabel grades) TERLEBIH DAHULU
                $sql_nilai = "DELETE FROM grades WHERE student_id IN ($placeholders)";
                $stmt_nilai = $pdo->prepare($sql_nilai);
                $stmt_nilai->execute($ids);

                // 2. BARU HAPUS INDUK (Data Siswa di tabel students)
                $sql_siswa = "DELETE FROM students WHERE id IN ($placeholders)";
                $stmt_siswa = $pdo->prepare($sql_siswa);
                $stmt_siswa->execute($ids);
                
                $pdo->commit(); // Simpan perubahan
                header("Location: siswa.php?pesan=berhasil_hapus");
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack(); // Batalkan semua jika error
                die("Gagal hapus massal: " . $e->getMessage());
            }
        }
    }

    // B. FITUR BULK INPUT (Tambah Banyak Sekaligus)
    if ($aksi === 'simpan_massal') {
        $namas = $_POST['nama_siswa'] ?? [];
        $niss = $_POST['nis_siswa'] ?? [];
        $class_ids = $_POST['class_id_siswa'] ?? [];

        try {
            $pdo->beginTransaction(); // Mulai transaksi agar data aman
            $sql = "INSERT INTO students (nama, nis, class_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            foreach ($namas as $i => $nama) {
                if (!empty($nama)) { // Hanya simpan jika nama tidak kosong
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

    // C. FITUR COPAS DARI EXCEL (Tanpa NIS)
    if ($aksi === 'copas_massal') {
        $data_copas = $_POST['data_copas'] ?? '';
        if (empty(trim($data_copas))) {
            header("Location: siswa.php");
            exit;
        }

        // Ambil data kelas untuk verifikasi
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

                // PEMISAH KOMA: Memecah data berdasarkan tanda koma saja
                $cols = explode(',', trim($line));

                if (count($cols) >= 2) {
                    $nama = trim($cols[0]); // Bagian sebelum koma
                    $nama_kelas_input = strtolower(trim($cols[1])); // Bagian setelah koma

                    if (isset($kelas_map[$nama_kelas_input])) {
                        $class_id = $kelas_map[$nama_kelas_input];
                        
                        // NIS Otomatis agar database tidak error
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

    // D. FITUR BULK MOVE (Pindah Kelas Massal)
    if ($aksi === 'pindah_massal') {
        $ids = $_POST['id_hapus'] ?? []; // Mengambil ID siswa yang dicentang
        $target_class = $_POST['target_class_id'] ?? ''; // Mengambil kelas tujuan

        if (!empty($ids) && !empty($target_class)) {
            try {
                // Membuat tanda tanya sebanyak jumlah ID yang dicentang (?,?,?)
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                
                // Siapkan SQL untuk Update (Ubah) class_id
                $sql = "UPDATE students SET class_id = ? WHERE id IN ($placeholders)";
                
                // Susun data yang mau dimasukkan: [ID Kelas Tujuan, ID Siswa 1, ID Siswa 2, ...]
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

// D. HAPUS SINGLE (Via tombol tong sampah)
if (isset($_GET['hapus_single'])) {
    $id_siswa = $_GET['hapus_single'];
    
    try {
        $pdo->beginTransaction();

        // 1. Hapus nilai (Anak)
        $stmt_hapus_nilai = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
        $stmt_hapus_nilai->execute([$id_siswa]);

        // 2. Hapus siswa (Induk)
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