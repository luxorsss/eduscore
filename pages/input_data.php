<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_POST['kelas'] ?? null;
$mapel_id = $_POST['mapel'] ?? null;

if (!$class_id || !$mapel_id) {
    header("Location: dashboard.php");
    exit();
}

// Ambil Info Kelas & Mapel
$stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
$stmt_info->execute([$class_id, $mapel_id]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

// Cari ID Jadwal (schedule_id)
$stmt_sched = $pdo->prepare("SELECT id FROM teaching_schedules WHERE user_id = ? AND class_id = ? AND subject_id = ?");
$stmt_sched->execute([$user_id, $class_id, $mapel_id]);
$schedule = $stmt_sched->fetch(PDO::FETCH_ASSOC);
$schedule_id = $schedule['id'] ?? 0;

// Ambil Daftar Siswa BESERTA Seluruh Nilainya saat ini
$stmt_siswa = $pdo->prepare("
    SELECT st.id, st.nis, st.nama, 
           g.h_uts, g.uts, g.tambahan_uts, g.h_uas, g.uas, g.tambahan_uas
    FROM students st 
    LEFT JOIN grades g ON g.student_id = st.id AND g.schedule_id = ? 
    WHERE st.class_id = ? 
    ORDER BY st.nama ASC
");
$stmt_siswa->execute([$schedule_id, $class_id]);
$students = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

$page_title = "EduScore - Mode Input Super Cepat";
require_once '../components/header.php'; 
?>

<nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-2 md:px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-on-surface-variant"><span class="material-symbols-outlined">menu</span></button>
            <div class="flex flex-col">
                <span class="font-bold text-sm leading-tight"><?= $info['nama_kelas'] ?> - <?= $info['nama_mapel'] ?></span>
                <span class="text-[10px] text-primary font-bold uppercase">All-in-One Input</span>
            </div>
        </div>
        <button form="formNilai" type="submit" class="bg-primary text-on-primary px-4 py-2 md:px-6 rounded-lg text-xs md:text-sm font-bold shadow-sm hover:bg-primary-container transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">save</span> Simpan Semua
        </button>
    </div>
</nav>

<main class="max-w-6xl mx-auto w-full p-2 md:p-4 flex flex-col gap-4">
    
    <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/20 shadow-sm flex flex-col md:flex-row gap-3">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
            <input type="text" id="cariSiswa" class="w-full bg-surface border border-outline-variant/50 focus:ring-1 focus:ring-primary focus:border-primary rounded-lg pl-9 py-2 text-sm font-medium" placeholder="Ketik nama siswa lalu Enter...">
        </div>
        
        <div class="flex items-center gap-2 flex-1 md:max-w-xs">
            <label class="text-[10px] font-bold text-primary uppercase whitespace-nowrap bg-primary/10 px-2 py-2 rounded-md"><span class="material-symbols-outlined text-[14px] align-middle">my_location</span> Target:</label>
            <select id="targetKolom" class="w-full bg-surface border border-outline-variant/50 focus:ring-1 focus:ring-primary rounded-lg px-2 py-2 text-sm font-bold text-primary cursor-pointer">
                <option value="h_uts">H.UTS</option>
                <option value="uts">UTS</option>
                <option value="t_uts">Tambahan UTS</option>
                <option value="h_uas">H.UAS</option>
                <option value="uas">UAS</option>
                <option value="t_uas">Tambahan UAS</option>
            </select>
        </div>

        <div class="flex-1 md:max-w-xs relative">
            <textarea id="pasteBox" rows="1" class="w-full bg-surface-container-highest border-dashed border-2 border-primary/40 rounded-lg text-[11px] p-2 focus:ring-primary font-mono text-center h-[38px] leading-tight" placeholder="Klik & Ctrl+V di sini..."></textarea>
        </div>
    </div>

    <div class="bg-primary/5 rounded-xl border border-primary/20 overflow-hidden">
        <button onclick="document.getElementById('syncArea').classList.toggle('hidden')" class="w-full bg-primary/10 px-4 py-2 flex justify-between items-center text-primary font-bold text-xs hover:bg-primary/20 transition-colors">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">sync_alt</span> Samakan Urutan dengan Excel Guru Lain</div>
            <span class="material-symbols-outlined">expand_more</span>
        </button>
        <div id="syncArea" class="hidden p-3 flex flex-col gap-2">
            <textarea id="excelNames" rows="3" class="w-full bg-surface-container-lowest text-[10px] md:text-xs rounded border border-outline-variant/30 p-2 focus:ring-primary font-mono" placeholder="Paste khusus NAMA SISWA dari Excel di sini..."></textarea>
            <div class="flex justify-between items-center mt-1">
                <button type="button" onclick="hapusMemori()" class="text-error text-[10px] font-bold hover:underline px-2">Reset Urutan Asli</button>
                <button type="button" onclick="sesuaikanUrutan(false)" class="bg-primary text-on-primary px-4 py-1.5 rounded text-[11px] font-bold shadow hover:bg-primary-container">Sesuaikan Urutan Tabel</button>
            </div>
            <div id="warningBox" class="hidden bg-error-container text-error text-[11px] p-3 rounded-lg border border-error/20 font-medium"></div>
        </div>
    </div>

    <form id="formNilai" action="proses_simpan_data.php" method="POST" class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px] text-sm">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant text-[10px] md:text-xs uppercase tracking-wider">
                        <th class="p-3 font-bold border border-outline-variant/30 sticky left-0 z-20 bg-surface-container-low min-w-[150px] shadow-[2px_0_5px_rgba(0,0,0,0.05)]">Nama Siswa</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px]">H.UTS</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px]">UTS</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px] bg-primary/5 text-primary">T.UTS</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px]">H.UAS</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px]">UAS</th>
                        <th class="p-2 font-bold border border-outline-variant/30 text-center w-[80px] bg-primary/5 text-primary">T.UAS</th>
                    </tr>
                </thead>
                <tbody id="tabelNilai" class="text-on-surface text-sm">
                    <?php foreach ($students as $s): ?>
                    <tr class="hover:bg-surface-container-highest transition-colors data-row" data-nama="<?= htmlspecialchars(strtolower(trim($s['nama']))) ?>">
                        <td class="p-3 border border-outline-variant/30 font-bold text-xs md:text-sm sticky left-0 z-10 bg-surface-container-lowest shadow-[2px_0_5px_rgba(0,0,0,0.05)] truncate student-name">
                            <?= htmlspecialchars($s['nama']) ?>
                        </td>
                        
                        <td class="p-1 border border-outline-variant/30">
                            <input type="number" step="any" name="n_h_uts[<?= $s['id'] ?>]" value="<?= $s['h_uts'] ?>" class="nilai-input input-h_uts w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold" placeholder="-">
                        </td>
                        <td class="p-1 border border-outline-variant/30">
                            <input type="number" step="any" step="any" name="n_uts[<?= $s['id'] ?>]" value="<?= $s['uts'] ?>" class="nilai-input input-uts w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold" placeholder="-">
                        </td>
                        <td class="p-1 border border-outline-variant/30 bg-primary/5">
                            <input type="number" step="any" name="n_t_uts[<?= $s['id'] ?>]" value="<?= $s['tambahan_uts'] ?>" class="nilai-input input-t_uts w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold text-primary" placeholder="-">
                        </td>
                        
                        <td class="p-1 border border-outline-variant/30">
                            <input type="number" step="any" name="n_h_uas[<?= $s['id'] ?>]" value="<?= $s['h_uas'] ?>" class="nilai-input input-h_uas w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold" placeholder="-">
                        </td>
                        <td class="p-1 border border-outline-variant/30">
                            <input type="number" step="any" name="n_uas[<?= $s['id'] ?>]" value="<?= $s['uas'] ?>" class="nilai-input input-uas w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold" placeholder="-">
                        </td>
                        <td class="p-1 border border-outline-variant/30 bg-primary/5">
                            <input type="number" step="any" name="n_t_uas[<?= $s['id'] ?>]" value="<?= $s['tambahan_uas'] ?>" class="nilai-input input-t_uas w-full h-full p-2 bg-transparent border-0 focus:ring-2 focus:ring-primary text-center font-bold text-primary" placeholder="-">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</main>

<script>
    // 1. FILTER PENCARIAN
    const kolomPencarian = document.getElementById('cariSiswa');
    kolomPencarian.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('tr.data-row').forEach(row => {
            const name = row.querySelector('.student-name').innerText.toLowerCase();
            row.style.display = name.includes(q) ? '' : 'none';
        });
    });

    // 2. GAYA 1: SIKLUS ENTER DENGAN TARGET FOCUS
    const semuaInputNilai = document.querySelectorAll('.nilai-input');
    const targetDropdown = document.getElementById('targetKolom');

    // A. Saat Tekan Enter di Pencarian
    kolomPencarian.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const targetClass = targetDropdown.value;
            const siswaTampil = Array.from(document.querySelectorAll('tr.data-row'))
                                     .filter(row => row.style.display !== 'none');
            
            if (siswaTampil.length > 0) {
                // Tembak kursor khusus ke kolom yang ditargetkan di dropdown
                const inputTarget = siswaTampil[0].querySelector('.input-' + targetClass);
                if (inputTarget) {
                    inputTarget.focus();
                    inputTarget.select();
                }
            }
        }
    });

    // B. Saat Tekan Enter di Kotak Nilai
    semuaInputNilai.forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                kolomPencarian.focus();
                kolomPencarian.select(); 
            }
        });
    });

    // Fitur Auto-select Search
    kolomPencarian.addEventListener('focus', function() { this.select(); });
    kolomPencarian.addEventListener('mouseup', function(e) { e.preventDefault(); }, { once: true });

    // 3. FITUR SMART PASTE (Menyasar Target Dropdown)
    const pasteBox = document.getElementById('pasteBox');
    pasteBox.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const rows = text.split(/\r?\n/).filter(r => r.trim() !== "");
        
        const targetClass = targetDropdown.value;
        const barisTerlihat = Array.from(document.querySelectorAll('tr.data-row'))
                                   .filter(row => row.style.display !== 'none');

        let berhasilTerisi = 0;
        rows.forEach((value, index) => {
            if (barisTerlihat[index]) {
                const targetInput = barisTerlihat[index].querySelector('.input-' + targetClass);
                // Izinkan angka, koma, dan titik
                let cleanedValue = value.replace(/[^0-9,.]/g, ''); 
                                
                if(cleanedValue !== "" && targetInput) {
                    // Ubah koma jadi titik (format standar yang dibaca komputer)
                    cleanedValue = cleanedValue.replace(',', '.'); 
                    
                    // parseFloat akan otomatis mengubah "90.5" tetap "90.5"
                    // tetapi mengubah "80.0" menjadi "80"
                    cleanedValue = parseFloat(cleanedValue).toString(); 

                    targetInput.value = cleanedValue;
                    // Efek Visual berkedip hijau
                    targetInput.parentElement.classList.add('bg-tertiary-container');
                    setTimeout(() => targetInput.parentElement.classList.remove('bg-tertiary-container'), 1500);
                    berhasilTerisi++;
                }
            }
        });
        pasteBox.value = '';
        pasteBox.placeholder = `${berhasilTerisi} nilai masuk ke ${targetDropdown.options[targetDropdown.selectedIndex].text}!`;
        setTimeout(() => pasteBox.placeholder = "Klik & Ctrl+V di sini...", 3000);
    });

    // 4. CHAMELEON SORT (Memori Browser)
    const STORAGE_KEY = 'memori_input_eduscore';

    document.addEventListener('DOMContentLoaded', () => {
        const memori = sessionStorage.getItem(STORAGE_KEY);
        if (memori) {
            document.getElementById('excelNames').value = memori;
            document.getElementById('syncArea').classList.remove('hidden');
            sesuaikanUrutan(true);
        }
    });

    function hapusMemori() {
        sessionStorage.removeItem(STORAGE_KEY);
        document.getElementById('excelNames').value = '';
        alert("Memori terhapus! Refresh halaman untuk mengembalikan urutan abjad default.");
    }

    function sesuaikanUrutan(isAuto = false) {
        const teks = document.getElementById('excelNames').value;
        if (!teks.trim()) {
            if(!isAuto) alert("Kotak teks masih kosong!"); return;
        }

        sessionStorage.setItem(STORAGE_KEY, teks);

        const excelArray = teks.split(/\r?\n/).map(n => n.trim().toLowerCase()).filter(n => n);
        const tbody = document.getElementById('tabelNilai');
        const rows = Array.from(tbody.querySelectorAll('tr.data-row'));
        
        let barisCocok = [];
        let barisSisa = [...rows];
        let namaTidakDitemukan = [];

        excelArray.forEach(namaExcel => {
            const index = barisSisa.findIndex(r => r.getAttribute('data-nama') === namaExcel);
            if (index > -1) barisCocok.push(barisSisa.splice(index, 1)[0]);
            else namaTidakDitemukan.push(namaExcel);
        });

        tbody.innerHTML = '';
        
        barisCocok.forEach(row => tbody.appendChild(row));
        barisSisa.forEach(row => {
            row.classList.add('opacity-50', 'bg-surface-container-highest');
            tbody.appendChild(row);
        });

        const warningBox = document.getElementById('warningBox');
        if (namaTidakDitemukan.length > 0) {
            warningBox.classList.remove('hidden');
            warningBox.innerHTML = `<b>Peringatan:</b> ${namaTidakDitemukan.length} nama dari Excel ejaannya tidak ditemukan di sistem. Baris sisa ditaruh di bawah.`;
        } else {
            warningBox.classList.add('hidden');
        }
    }
</script>

<?php require_once '../components/footer.php'; ?>