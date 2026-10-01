<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = $_POST['kelas'] ?? $_GET['kelas'] ?? null;
$mapel_id = $_POST['mapel'] ?? $_GET['mapel'] ?? null;

if (!$class_id || !$mapel_id) {
    header("Location: dashboard.php");
    exit();
}

// Ambil Info Kelas & Mapel
$stmt_info = $pdo->prepare("SELECT c.nama_kelas, s.nama_mapel FROM classes c, subjects s WHERE c.id = ? AND s.id = ?");
$stmt_info->execute([$class_id, $mapel_id]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    header("Location: dashboard.php");
    exit();
}

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

$page_title = "Input Nilai: " . htmlspecialchars($info['nama_kelas']) . " - EduScore";
$page_heading = htmlspecialchars($info['nama_kelas']) . " - " . htmlspecialchars($info['nama_mapel']);
require_once '../components/header.php'; 
?>

<main class="max-w-6xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Top Action & Informasi Kelas -->
    <div class="bg-surface-card p-5 rounded-xl border border-border-main shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Entri Nilai Kelas</span>
                <span class="text-slate-300">•</span>
                <span class="text-xs font-bold text-primary"><?= htmlspecialchars($info['nama_kelas']) ?></span>
            </div>
            <h2 class="text-lg font-bold text-text-main mt-0.5"><?= htmlspecialchars($info['nama_mapel']) ?></h2>
            <p class="text-xs text-text-muted">Total <?= count($students) ?> siswa terdaftar. Nilai langsung tersimpan saat menekan tombol simpan.</p>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <button form="formNilai" type="submit" class="w-full sm:w-auto bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors flex items-center justify-center gap-2 min-h-[44px]">
                <span class="material-symbols-outlined text-base">save</span> Simpan Semua Nilai
            </button>
        </div>
    </div>

    <!-- Toolbar: Cari Siswa, Target Kolom, & Paste Nilai -->
    <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-base">search</span>
            <input type="text" id="cariSiswa" class="w-full bg-white border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-lg pl-9 pr-3.5 py-2 text-xs font-medium placeholder:text-slate-400 min-h-[40px]" placeholder="Ketik nama siswa lalu Enter untuk fokus...">
        </div>
        
        <div class="flex items-center gap-2 flex-1 md:max-w-xs">
            <label for="targetKolom" class="text-xs font-semibold text-text-muted whitespace-nowrap">Target:</label>
            <select id="targetKolom" class="w-full bg-white border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary rounded-lg px-3 py-2 text-xs font-semibold text-text-main cursor-pointer min-h-[40px]">
                <option value="h_uts">Harian UTS (H.UTS)</option>
                <option value="uts">Ujian Tengah Semester (UTS)</option>
                <option value="t_uts">Tambahan / Remedial UTS</option>
                <option value="h_uas">Harian UAS (H.UAS)</option>
                <option value="uas">Ujian Akhir Semester (UAS)</option>
                <option value="t_uas">Tambahan / Remedial UAS</option>
            </select>
        </div>

        <div class="flex-1 md:max-w-xs relative">
            <textarea id="pasteBox" rows="1" class="w-full bg-slate-50 border-dashed border border-slate-300 focus:border-primary focus:bg-white rounded-lg text-xs p-2 text-center font-mono placeholder:text-slate-400 min-h-[40px] resize-none" placeholder="Klik & Tempel (Ctrl+V) deret nilai..."></textarea>
        </div>
    </div>

    <!-- Opsi Penyesuaian Urutan Excel -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <button type="button" onclick="document.getElementById('syncArea').classList.toggle('hidden')" class="w-full px-4 py-3 flex justify-between items-center text-text-main font-semibold text-xs hover:bg-slate-50 transition-colors border-b border-transparent focus-ring">
            <div class="flex items-center gap-2 text-text-muted">
                <span class="material-symbols-outlined text-base text-primary">sync_alt</span> 
                <span>Sinkronisasi Urutan Siswa dengan Lembar Excel</span>
            </div>
            <span class="material-symbols-outlined text-text-muted text-base">expand_more</span>
        </button>
        <div id="syncArea" class="hidden p-4 bg-slate-50/50 border-t border-border-main flex flex-col gap-3">
            <label for="excelNames" class="text-xs font-semibold text-text-muted">Tempel daftar nama siswa dari kolom Excel di bawah:</label>
            <textarea id="excelNames" rows="3" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Budi Santoso&#10;Citra Lestari&#10;Dewi Anggraeni..."></textarea>
            <div class="flex flex-wrap justify-between items-center gap-2">
                <button type="button" onclick="hapusMemori()" class="text-danger text-xs font-semibold hover:underline">Reset Urutan Abjad Asli</button>
                <button type="button" onclick="sesuaikanUrutan(false)" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-xs transition-colors">Sesuaikan Urutan Tabel</button>
            </div>
            <div id="warningBox" class="hidden bg-danger-subtle text-danger text-xs p-3 rounded-lg border border-danger/20 font-medium"></div>
        </div>
    </div>

    <!-- Tabel Pengisian Nilai -->
    <form id="formNilai" action="proses_simpan_data.php" method="POST" class="bg-surface-card rounded-xl shadow-xs border border-border-main overflow-hidden">
        <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-text-muted uppercase text-[11px] font-semibold tracking-wider border-b border-border-main">
                        <th class="p-3 font-semibold border-r border-border-main sticky left-0 z-20 bg-slate-50 min-w-[140px] md:min-w-[180px] shadow-xs">
                            Nama Siswa
                        </th>
                        <th class="kolom-dinamis kolom-h_uts p-2.5 font-semibold border-r border-border-main text-center w-[85px]">H.UTS</th>
                        <th class="kolom-dinamis kolom-uts p-2.5 font-semibold border-r border-border-main text-center w-[85px]">UTS</th>
                        <th class="kolom-dinamis kolom-t_uts p-2.5 font-semibold border-r border-border-main text-center w-[85px] bg-primary-subtle/40 text-primary">T.UTS</th>
                        <th class="kolom-dinamis kolom-h_uas p-2.5 font-semibold border-r border-border-main text-center w-[85px]">H.UAS</th>
                        <th class="kolom-dinamis kolom-uas p-2.5 font-semibold border-r border-border-main text-center w-[85px]">UAS</th>
                        <th class="kolom-dinamis kolom-t_uas p-2.5 font-semibold text-center w-[85px] bg-primary-subtle/40 text-primary">T.UAS</th>
                    </tr>
                </thead>
                <tbody id="tabelNilai" class="divide-y divide-border-main">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-text-muted text-xs">
                                Belum ada siswa yang ditempatkan di kelas ini. Silakan tambahkan siswa terlebih dahulu di menu Data Siswa.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $s): ?>
                        <tr class="hover:bg-slate-50 transition-colors data-row" data-index="<?= $index ?>" data-nama="<?= htmlspecialchars(strtolower(trim($s['nama']))) ?>">
                            <td class="p-3 border-r border-border-main font-semibold text-text-main sticky left-0 z-10 bg-surface-card truncate max-w-[140px] md:max-w-[180px] student-name shadow-xs" title="<?= htmlspecialchars($s['nama']) ?>">
                                <?= htmlspecialchars($s['nama']) ?>
                            </td>
                            
                            <td class="kolom-dinamis kolom-h_uts p-1 border-r border-border-main">
                                <input type="number" step="any" min="0" max="100" name="n_h_uts[<?= $s['id'] ?>]" value="<?= $s['h_uts'] ?>" data-col="h_uts" data-row="<?= $index ?>" class="nilai-input input-h_uts w-full p-2 bg-transparent text-center font-semibold text-text-main border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                            <td class="kolom-dinamis kolom-uts p-1 border-r border-border-main">
                                <input type="number" step="any" min="0" max="100" name="n_uts[<?= $s['id'] ?>]" value="<?= $s['uts'] ?>" data-col="uts" data-row="<?= $index ?>" class="nilai-input input-uts w-full p-2 bg-transparent text-center font-semibold text-text-main border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                            <td class="kolom-dinamis kolom-t_uts p-1 border-r border-border-main bg-primary-subtle/10">
                                <input type="number" step="any" min="0" max="100" name="n_t_uts[<?= $s['id'] ?>]" value="<?= $s['tambahan_uts'] ?>" data-col="t_uts" data-row="<?= $index ?>" class="nilai-input input-t_uts w-full p-2 bg-transparent text-center font-bold text-primary border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                            
                            <td class="kolom-dinamis kolom-h_uas p-1 border-r border-border-main">
                                <input type="number" step="any" min="0" max="100" name="n_h_uas[<?= $s['id'] ?>]" value="<?= $s['h_uas'] ?>" data-col="h_uas" data-row="<?= $index ?>" class="nilai-input input-h_uas w-full p-2 bg-transparent text-center font-semibold text-text-main border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                            <td class="kolom-dinamis kolom-uas p-1 border-r border-border-main">
                                <input type="number" step="any" min="0" max="100" name="n_uas[<?= $s['id'] ?>]" value="<?= $s['uas'] ?>" data-col="uas" data-row="<?= $index ?>" class="nilai-input input-uas w-full p-2 bg-transparent text-center font-semibold text-text-main border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                            <td class="kolom-dinamis kolom-t_uas p-1 bg-primary-subtle/10">
                                <input type="number" step="any" min="0" max="100" name="n_t_uas[<?= $s['id'] ?>]" value="<?= $s['tambahan_uas'] ?>" data-col="t_uas" data-row="<?= $index ?>" class="nilai-input input-t_uas w-full p-2 bg-transparent text-center font-bold text-primary border-0 focus:ring-2 focus:ring-primary/20 focus:bg-white rounded tabular-nums" placeholder="-">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50 border-t border-border-main flex justify-between items-center">
            <span class="text-xs text-text-muted">Gunakan tombol panah keyboard atau Enter untuk berpindah baris nilai.</span>
            <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg text-xs font-semibold shadow-xs transition-colors min-h-[40px]">
                Simpan Semua Nilai
            </button>
        </div>
    </form>
</main>

<script>
    // 1. FILTER PENCARIAN SISWA
    const kolomPencarian = document.getElementById('cariSiswa');
    if (kolomPencarian) {
        kolomPencarian.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('tr.data-row').forEach(row => {
                const nameEl = row.querySelector('.student-name');
                const name = nameEl ? nameEl.innerText.toLowerCase() : '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    // 2. SIKLUS ENTER & NAVIGASI KEYBOARD (R-32)
    const targetDropdown = document.getElementById('targetKolom');
    const colsOrder = ['h_uts', 'uts', 't_uts', 'h_uas', 'uas', 't_uas'];

    // A. Navigasi keyboard pada input nilai:
    // - Enter: Lompat kembali ke kolom pencarian nama siswa (Siklus Enter cepat)
    // - Panah Bawah/Atas: Pindah baris pada kolom yang sama
    // - Panah Kanan/Kiri: Pindah antar kolom nilai
    document.querySelectorAll('.nilai-input').forEach(input => {
        input.addEventListener('keydown', function(e) {
            const currentRow = parseInt(this.getAttribute('data-row'));
            const currentCol = this.getAttribute('data-col');
            const colIndex = colsOrder.indexOf(currentCol);

            if (e.key === 'Enter') {
                // Sesuai siklus asli: kembali ke kolom pencarian nama
                e.preventDefault();
                if (kolomPencarian) {
                    kolomPencarian.focus();
                    kolomPencarian.select();
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextRow = document.querySelector(`.data-row[data-index="${currentRow + 1}"] .input-${currentCol}`);
                if (nextRow) {
                    nextRow.focus();
                    nextRow.select();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevRow = document.querySelector(`.data-row[data-index="${currentRow - 1}"] .input-${currentCol}`);
                if (prevRow) {
                    prevRow.focus();
                    prevRow.select();
                }
            } else if (e.key === 'ArrowRight' && this.selectionEnd === this.value.length) {
                if (colIndex < colsOrder.length - 1) {
                    const nextColInput = document.querySelector(`.data-row[data-index="${currentRow}"] .input-${colsOrder[colIndex + 1]}`);
                    if (nextColInput) {
                        e.preventDefault();
                        nextColInput.focus();
                        nextColInput.select();
                    }
                }
            } else if (e.key === 'ArrowLeft' && this.selectionStart === 0) {
                if (colIndex > 0) {
                    const prevColInput = document.querySelector(`.data-row[data-index="${currentRow}"] .input-${colsOrder[colIndex - 1]}`);
                    if (prevColInput) {
                        e.preventDefault();
                        prevColInput.focus();
                        prevColInput.select();
                    }
                }
            }
        });
    });

    // B. Saat Tekan Enter di Kolom Pencarian Nama Siswa -> Tembak kursor ke kolom nilai target
    if (kolomPencarian) {
        kolomPencarian.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const targetCol = targetDropdown.value;
                const visibleRows = Array.from(document.querySelectorAll('tr.data-row'))
                                         .filter(row => row.style.display !== 'none');
                if (visibleRows.length > 0) {
                    const targetInput = visibleRows[0].querySelector('.input-' + targetCol);
                    if (targetInput) {
                        targetInput.focus();
                        targetInput.select();
                    }
                }
            }
        });

        // Fitur Auto-select Search saat fokus
        kolomPencarian.addEventListener('focus', function() { this.select(); });
        kolomPencarian.addEventListener('mouseup', function(e) { e.preventDefault(); }, { once: true });
    }

    // 3. FITUR SMART PASTE
    const pasteBox = document.getElementById('pasteBox');
    if (pasteBox) {
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
                    let cleanedValue = value.replace(/[^0-9,.]/g, ''); 
                                    
                    if (cleanedValue !== "" && targetInput) {
                        cleanedValue = cleanedValue.replace(',', '.'); 
                        cleanedValue = parseFloat(cleanedValue).toString(); 

                        targetInput.value = cleanedValue;
                        targetInput.parentElement.classList.add('bg-success-subtle');
                        setTimeout(() => targetInput.parentElement.classList.remove('bg-success-subtle'), 1200);
                        berhasilTerisi++;
                    }
                }
            });
            pasteBox.value = '';
            pasteBox.placeholder = `${berhasilTerisi} nilai terisi di ${targetDropdown.options[targetDropdown.selectedIndex].text}!`;
            setTimeout(() => pasteBox.placeholder = "Klik & Tempel (Ctrl+V) deret nilai...", 3000);
        });
    }

    // 4. CHAMELEON SORT
    const STORAGE_KEY = 'memori_input_eduscore';

    document.addEventListener('DOMContentLoaded', () => {
        const memori = sessionStorage.getItem(STORAGE_KEY);
        if (memori) {
            const excelEl = document.getElementById('excelNames');
            if (excelEl) excelEl.value = memori;
            const syncEl = document.getElementById('syncArea');
            if (syncEl) syncEl.classList.remove('hidden');
            sesuaikanUrutan(true);
        }
    });

    function hapusMemori() {
        sessionStorage.removeItem(STORAGE_KEY);
        const excelEl = document.getElementById('excelNames');
        if (excelEl) excelEl.value = '';
        alert("Urutan disetel ulang ke urutan abjad default.");
        location.reload();
    }

    function sesuaikanUrutan(isAuto = false) {
        const excelEl = document.getElementById('excelNames');
        if (!excelEl) return;
        const teks = excelEl.value;
        if (!teks.trim()) {
            if (!isAuto) alert("Kotak teks masih kosong!");
            return;
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
            row.classList.add('opacity-50', 'bg-slate-100');
            tbody.appendChild(row);
        });

        const warningBox = document.getElementById('warningBox');
        if (warningBox) {
            if (namaTidakDitemukan.length > 0) {
                warningBox.classList.remove('hidden');
                warningBox.innerHTML = `<b>Peringatan:</b> ${namaTidakDitemukan.length} nama dari Excel tidak cocok dengan data sistem. Baris selebihnya ditempatkan di bawah.`;
            } else {
                warningBox.classList.add('hidden');
            }
        }
    }

    // 5. FITUR RESPONSIF MOBILE
    function sesuaikanKolomMobile() {
        const isMobile = window.innerWidth < 768;
        const targetClass = targetDropdown ? targetDropdown.value : 'h_uts';
        const semuaKolom = document.querySelectorAll('.kolom-dinamis');

        semuaKolom.forEach(kolom => {
            if (isMobile) {
                if (kolom.classList.contains('kolom-' + targetClass)) {
                    kolom.style.display = '';
                } else {
                    kolom.style.display = 'none';
                }
            } else {
                kolom.style.display = ''; 
            }
        });
    }

    if (targetDropdown) targetDropdown.addEventListener('change', sesuaikanKolomMobile);
    document.addEventListener('DOMContentLoaded', sesuaikanKolomMobile);
    window.addEventListener('resize', sesuaikanKolomMobile);
</script>

<?php require_once '../components/footer.php'; ?>