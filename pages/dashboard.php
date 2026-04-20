<?php
session_start();

// PENJAGA PINTU: Tendang ke login jika belum ada tiket (session)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// 1. Panggil koneksi database
require_once '../config/koneksi.php'; 
$user_id = $_SESSION['user_id'];

// 2. Tarik Data KELAS & JENJANG berdasarkan Jadwal Guru ini
$stmt_kelas = $pdo->prepare("
    SELECT DISTINCT c.id, c.nama_kelas, c.jenjang 
    FROM teaching_schedules ts 
    JOIN classes c ON ts.class_id = c.id 
    WHERE ts.user_id = ?
");
$stmt_kelas->execute([$user_id]);
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// 3. Tarik Pemetaan Kelas -> Mapel berdasarkan Jadwal Guru ini
// Ini untuk JavaScript agar tahu kelas mana punya mapel apa
$stmt_jadwal = $pdo->prepare("
    SELECT ts.class_id, s.id as subject_id, s.nama_mapel 
    FROM teaching_schedules ts 
    JOIN subjects s ON ts.subject_id = s.id 
    WHERE ts.user_id = ?
");
$stmt_jadwal->execute([$user_id]);
$jadwal_list = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data (Format JSON) untuk dibaca oleh JavaScript
$kelas_json = json_encode($kelas_list);

// Kelompokkan mapel ke dalam array berdasarkan ID Kelas
$mapel_per_kelas = [];
foreach ($jadwal_list as $row) {
    $mapel_per_kelas[$row['class_id']][] = [
        'id' => $row['subject_id'],
        'nama' => $row['nama_mapel']
    ];
}
$mapel_json = json_encode($mapel_per_kelas);

$page_title = "EduScore - Dashboard Pengajar";
require_once '../components/header.php';
?>

    <nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">

                <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">school</span>
                </div>
                <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-on-surface-variant hidden md:block">
                    <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>
                </span>
                
                <div class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center text-primary font-bold text-sm cursor-pointer hover:bg-outline-variant/30 transition">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                </div>
                
                <a href="logout.php" class="text-red-600 hover:bg-red-50 p-2 rounded-full transition" title="Logout">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex flex-col items-center py-12 px-6 gap-10">
        <form action="input_data.php" method="POST" class="w-full max-w-2xl bg-surface-container-lowest rounded-xl p-8 md:p-10 shadow-[0px_8px_24px_rgba(26,28,30,0.04)] border border-outline-variant/20">
            
            <div class="mb-8 text-center">
                <h1 class="text-2xl md:text-3xl font-headline font-bold text-primary tracking-tight mb-2">Pilih Kelas</h1>
                <p class="text-on-surface-variant text-sm md:text-base">Tentukan sasaran kelas dan mata pelajaran yang akan Anda kelola nilainya.</p>
            </div>

            <div class="flex flex-col gap-6 bg-surface-container-low rounded-xl p-6 border border-outline-variant/20">
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant">Jenjang Sekolah</label>
                    <div class="flex gap-3">
                        <label class="cursor-pointer flex-1">
                            <input checked class="peer sr-only" name="jenjang" type="radio" value="smp"/>
                            <div class="px-4 py-3 rounded-md border border-outline-variant/30 bg-surface-container-lowest text-center peer-checked:ring-2 peer-checked:ring-primary peer-checked:border-primary peer-checked:text-primary transition-all font-medium text-sm">
                                SMP
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1">
                            <input class="peer sr-only" name="jenjang" type="radio" value="sma"/>
                            <div class="px-4 py-3 rounded-md border border-outline-variant/30 bg-surface-container-lowest text-center peer-checked:ring-2 peer-checked:ring-primary peer-checked:border-primary peer-checked:text-primary transition-all font-medium text-sm">
                                SMA
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant" for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3.5 transition-colors cursor-pointer font-medium" required>
                        <option value="" disabled selected>-- Pilih Kelas --</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant" for="mapel">Mata Pelajaran</label>
                    <select id="mapel" name="mapel" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3.5 transition-colors cursor-pointer font-medium" required>
                        <option value="" disabled selected>-- Pilih Mata Pelajaran --</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant" for="kategori">Kategori Nilai</label>
                    <select id="kategori" name="kategori" class="w-full bg-surface-container-highest text-on-surface text-sm rounded-md border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface-container-lowest focus:ring-0 px-4 py-3.5 transition-colors cursor-pointer font-medium" required>
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <option value="h_uts">Nilai Harian UTS</option>
                        <option value="uts">Ujian Tengah Semester (UTS)</option>
                        <option value="h_uas">Nilai Harian UAS</option>
                        <option value="uas">Ujian Akhir Semester (UAS)</option>
                        <option value="tambahan">Nilai Tambahan</option>
                    </select>
                </div>

            </div>

            <div class="mt-8 pt-6 border-t border-outline-variant/20">
                <button type="submit" class="w-full bg-primary text-on-primary px-8 py-4 rounded-lg text-sm font-medium flex items-center justify-center gap-2 hover:bg-primary-container active:scale-[0.98] transition-all shadow-sm">
                    Lanjutkan ke Pengisian Nilai
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
            </div>

        </form>

        <div class="w-full max-w-2xl border-t border-outline-variant/30 pt-10 mt-4">
            <div class="bg-surface-container-low rounded-2xl p-6 border border-error/10 flex flex-col md:flex-row items-center justify-between gap-6 transition-all hover:border-error/30">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-error/5 flex items-center justify-center text-error shrink-0">
                        <span class="material-symbols-outlined text-2xl">history_edu</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-on-surface">Persiapan Semester Baru</h2>
                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                            Hapus seluruh data nilai untuk memulai periode baru. <br>
                            <span class="text-error/70 font-medium">Data siswa dan jadwal Anda tetap aman.</span>
                        </p>
                    </div>
                </div>
                
                <form action="proses_reset.php" method="POST" class="w-full md:w-auto">
                    <input type="hidden" name="reset_semua_nilai" value="1">
                    <button type="submit" 
                        onclick="return confirm('PERINGATAN!\n\nSemua data nilai akan dihapus permanen.\n\nLanjutkan?')" 
                        class="w-full md:w-auto bg-surface-container-highest text-error px-5 py-2.5 rounded-lg text-xs font-bold border border-error/20 hover:bg-error hover:text-on-primary transition-all">
                        Kosongkan Nilai
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Ambil data JSON dari PHP
        const dataKelas = <?= $kelas_json ?>;
        const dataMapel = <?= $mapel_json ?>;

        const radioJenjang = document.querySelectorAll('input[name="jenjang"]');
        const selectKelas = document.getElementById('kelas');
        const selectMapel = document.getElementById('mapel');

        // Fungsi 1: Filter Kelas saat Jenjang (SMP/SMA) diklik
        function updateDropdownKelas() {
            // Cari radio button yang sedang aktif
            const jenjangTerpilih = document.querySelector('input[name="jenjang"]:checked').value.toUpperCase();
            
            // Reset/Kosongkan dropdown kelas dan mapel
            selectKelas.innerHTML = '<option value="" disabled selected>-- Pilih Kelas --</option>';
            selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mata Pelajaran --</option>';
            
            let adaKelas = false;

            // Looping data kelas, jika jenjang cocok, tambahkan ke dropdown
            dataKelas.forEach(kelas => {
                if (kelas.jenjang === jenjangTerpilih) {
                    const option = document.createElement('option');
                    option.value = kelas.id;
                    option.textContent = kelas.nama_kelas;
                    selectKelas.appendChild(option);
                    adaKelas = true;
                }
            });

            // Pesan error jika guru belum punya jadwal di jenjang tersebut
            if (!adaKelas) {
                selectKelas.innerHTML = '<option value="" disabled selected>Belum ada jadwal kelas ' + jenjangTerpilih + '</option>';
            }
        }

        // Fungsi 2: Filter Mapel saat Kelas dipilih
        function updateDropdownMapel() {
            const idKelasTerpilih = selectKelas.value;
            
            // Reset/Kosongkan dropdown mapel
            selectMapel.innerHTML = '<option value="" disabled selected>-- Pilih Mata Pelajaran --</option>';
            
            // Cek apakah ada mapel yang tersimpan untuk kelas yang dipilih
            if (idKelasTerpilih && dataMapel[idKelasTerpilih]) {
                dataMapel[idKelasTerpilih].forEach(mapel => {
                    const option = document.createElement('option');
                    option.value = mapel.id;
                    option.textContent = mapel.nama;
                    selectMapel.appendChild(option);
                });
            } else {
                selectMapel.innerHTML = '<option value="" disabled selected>Belum ada jadwal mapel.</option>';
            }
        }

        // Pasang "Detektor" (Event Listener) ke elemen HTML
        radioJenjang.forEach(radio => {
            radio.addEventListener('change', updateDropdownKelas);
        });
        
        selectKelas.addEventListener('change', updateDropdownMapel);

        // Jalankan fungsi satu kali saat halaman pertama kali dibuka
        updateDropdownKelas();
    </script>
<?php 
require_once '../components/footer.php'; 
?>