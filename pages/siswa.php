<?php
session_start();
require_once '../config/koneksi.php';

// Penjaga Pintu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil Data Siswa + Nama Kelasnya
$sql = "SELECT s.*, c.nama_kelas 
        FROM students s 
        JOIN classes c ON s.class_id = c.id 
        ORDER BY c.nama_kelas ASC, s.nama ASC";
$stmt = $pdo->query($sql);
$daftar_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil Data Kelas untuk Dropdown Tambah Cepat
$stmt_kelas = $pdo->query("SELECT * FROM classes ORDER BY nama_kelas ASC");
$list_kelas = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Data Induk Siswa";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Manajemen Siswa</h1>
            <p class="text-on-surface-variant text-sm">Kelola data induk dan penempatan kelas siswa.</p>
        </div>
    </div>

    <form action="proses_bulk_siswa.php" method="POST">
        <tbody id="containerInputSiswa" class="text-sm divide-y divide-outline-variant/10">
            </tbody>

        <div class="p-4 bg-primary/5 flex justify-between items-center border-t border-primary/20">
            <button type="button" onclick="tambahBarisInput()" class="flex items-center gap-2 text-primary font-bold text-sm hover:underline">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                Tambah Baris Input
            </button>
            <button type="submit" name="aksi" value="simpan_massal" class="bg-primary text-on-primary px-6 py-2 rounded-xl font-bold shadow-md hover:scale-105 transition-transform">
                Simpan Semua Data Baru
            </button>
        </div>
    </form>
</main>

<script>
    // Fungsi untuk menambah baris input secara dinamis (BULK INPUT)
    function tambahBarisInput() {
        const container = document.getElementById('containerInputSiswa');
        const row = document.createElement('tr');
        row.className = "bg-white animate-in slide-in-from-left duration-300";
        row.innerHTML = `
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-error-variant hover:text-error">
                    <span class="material-symbols-outlined text-[18px]">remove_circle</span>
                </button>
            </td>
            <td class="px-4 py-3">
                <input type="text" name="nama_siswa[]" required class="w-full bg-surface-container-low border-0 border-b-2 border-primary focus:ring-0 text-sm font-bold" placeholder="Nama Siswa">
            </td>
            <td class="px-4 py-3">
                <input type="text" name="nis_siswa[]" required class="w-full bg-surface-container-low border-0 border-b-2 border-outline-variant focus:border-primary focus:ring-0 text-sm font-medium" placeholder="NIS">
            </td>
            <td class="px-4 py-3">
                <select name="class_id_siswa[]" class="w-full bg-surface-container-low border-0 border-b-2 border-outline-variant focus:border-primary focus:ring-0 text-xs font-bold">
                    <?php foreach($list_kelas as $lk): ?>
                        <option value="<?= $lk['id'] ?>"><?= $lk['nama_kelas'] ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
        `;
        container.appendChild(row);
    }

    // Panggil sekali saat halaman dimuat agar ada 1 baris awal
    window.onload = tambahBarisInput;
</script>

<?php require_once '../components/footer.php'; ?>