<?php
session_start();
require_once '../config/auth.php';

// Proteksi Khusus Administrator
require_admin();

$current_user_id = (int)$_SESSION['user_id'];

// 1. Ambil Semua Pengguna beserta info kelas binaan dan jumlah jadwal mengajar
$stmt_users = $pdo->query("
    SELECT u.id, u.nama_lengkap, u.username, u.role,
           c.id as class_id, c.nama_kelas, c.jenjang,
           (SELECT COUNT(*) FROM teaching_schedules ts WHERE ts.user_id = u.id) as total_jadwal
    FROM users u
    LEFT JOIN classes c ON c.wali_kelas_id = u.id
    ORDER BY (u.role = 'admin') DESC, u.nama_lengkap ASC
");
$daftar_guru = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// 2. Guru-guru untuk target pengalihan saat hapus akun
$semua_guru_tujuan = [];
foreach ($daftar_guru as $dg) {
    $semua_guru_tujuan[] = [
        'id' => (int)$dg['id'],
        'nama_lengkap' => $dg['nama_lengkap'],
        'role' => $dg['role']
    ];
}

// 3. Statistik Ringkas
$total_semua_guru = 0;
$total_wali_kelas = 0;
$total_admin = 0;

foreach ($daftar_guru as $u) {
    if ($u['role'] === 'guru') {
        $total_semua_guru++;
    } else {
        $total_admin++;
    }
    if (!empty($u['nama_kelas'])) {
        $total_wali_kelas++;
    }
}

$page_title = "Manajemen Akun Guru - EduScore";
$page_heading = "Data & Akun Guru";
require_once '../components/header.php'; 
?>

<main class="flex-grow max-w-6xl mx-auto w-full p-4 md:p-8 flex flex-col gap-6">
    
    <!-- Header Section -->
    <div class="bg-surface-card rounded-xl border border-border-main p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">badge</span>
            </div>
            <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block">Master Akun Pengajar</span>
                <h2 class="text-lg md:text-xl font-bold text-text-main mt-0.5">Manajemen Akun Guru</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Kelola profil, penugasan, reset password, dan penghapusan akun dewan guru.
                </p>
            </div>
        </div>

        <button type="button" onclick="bukaModalTambah()" class="px-4 py-2.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs flex items-center gap-2 transition-colors cursor-pointer shrink-0">
            <span class="material-symbols-outlined text-base">person_add</span>
            <span>Tambah Guru Baru</span>
        </button>
    </div>

    <!-- Alert Feedback Notifikasi -->
    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'sukses_tambah'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Akun guru baru berhasil ditambahkan! Guru sekarang dapat login ke sistem.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'sukses_edit'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Data profil akun guru berhasil diperbarui.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'sukses_reset_pwd'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Password guru berhasil direset! Silakan berikan password baru ke guru bersangkutan.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'sukses_hapus'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-emerald-200 bg-success-subtle text-success">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Akun guru berhasil dihapus. Jadwal mengajar dan nilai siswa telah diamankan.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'username_terpakai'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-rose-200 bg-danger-subtle text-danger">
                <span class="material-symbols-outlined text-base">error</span>
                <span>Username tersebut sudah digunakan oleh akun lain. Silakan pilih username yang berbeda.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'gagal_hapus_diri'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-rose-200 bg-danger-subtle text-danger">
                <span class="material-symbols-outlined text-base">lock</span>
                <span>Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.</span>
            </div>
        <?php elseif ($_GET['pesan'] === 'gagal_hapus_utama'): ?>
            <div class="flex items-center gap-3 p-4 text-xs font-medium rounded-xl border border-rose-200 bg-danger-subtle text-danger">
                <span class="material-symbols-outlined text-base">lock</span>
                <span>Akun Administrator Utama sistem (ID 1) tidak dapat dihapus.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">school</span>
            </div>
            <div>
                <span class="text-[11px] text-text-muted font-medium block">Total Guru Pengajar</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= $total_semua_guru ?></span>
            </div>
        </div>
        <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                <span class="material-symbols-outlined text-xl">assignment_ind</span>
            </div>
            <div>
                <span class="text-[11px] text-text-muted font-medium block">Menjabat Wali Kelas</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= $total_wali_kelas ?></span>
            </div>
        </div>
        <div class="bg-surface-card p-4 rounded-xl border border-border-main shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                <span class="material-symbols-outlined text-xl">shield_person</span>
            </div>
            <div>
                <span class="text-[11px] text-text-muted font-medium block">Akun Administrator</span>
                <span class="text-base font-bold text-text-main tabular-nums"><?= $total_admin ?></span>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Guru -->
    <div class="bg-surface-card rounded-xl border border-border-main shadow-xs overflow-hidden">
        <div class="p-4 border-b border-border-main bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-xs md:text-sm text-text-main">Daftar Akun Pengajar Terdaftar</h3>
            <span class="text-xs text-text-muted tabular-nums"><?= count($daftar_guru) ?> akun</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-border-main bg-slate-50/70 text-text-muted font-bold text-[11px] uppercase tracking-wider">
                        <th class="p-3.5 pl-6 w-12 text-center">No</th>
                        <th class="p-3.5">Nama Guru & Username</th>
                        <th class="p-3.5">Peran Akun</th>
                        <th class="p-3.5">Tugas Wali Kelas</th>
                        <th class="p-3.5 text-center">Jadwal Mengajar</th>
                        <th class="p-3.5 pr-6 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-main text-text-main">
                    <?php if (empty($daftar_guru)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-text-muted">Belum ada akun guru yang terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftar_guru as $u): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-3.5 pl-6 text-center font-medium text-text-muted tabular-nums"><?= $no++ ?></td>
                                <td class="p-3.5">
                                    <div class="font-bold text-text-main"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                    <div class="text-[11px] text-text-muted font-mono mt-0.5">@<?= htmlspecialchars($u['username']) ?></div>
                                </td>
                                <td class="p-3.5">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="material-symbols-outlined text-[12px]">security</span>
                                            Administrator
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                            <span class="material-symbols-outlined text-[12px]">person</span>
                                            Guru Pengajar
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5">
                                    <?php if (!empty($u['nama_kelas'])): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-primary bg-primary-subtle px-2 py-0.5 rounded">
                                            <span class="material-symbols-outlined text-[13px]">assignment_ind</span>
                                            Wali Kelas <?= htmlspecialchars($u['jenjang']) ?> - <?= htmlspecialchars($u['nama_kelas']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-text-muted text-[11px]">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 text-center">
                                    <?php if ((int)$u['total_jadwal'] > 0): ?>
                                        <a href="jadwal.php?filter_guru=<?= $u['id'] ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition-colors" title="Lihat Jadwal Guru Ini">
                                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                                            <span class="tabular-nums"><?= $u['total_jadwal'] ?> Mapel</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-text-muted text-[11px]">0 Mapel</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 pr-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        
                                        <!-- Tombol Edit Profil -->
                                        <button type="button" 
                                                onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($u)) ?>)" 
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-primary hover:bg-slate-100 transition-colors cursor-pointer" 
                                                title="Edit Profil Guru">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </button>

                                        <!-- Tombol Reset Password -->
                                        <button type="button" 
                                                onclick="bukaModalResetPwd(<?= htmlspecialchars(json_encode($u)) ?>)" 
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer" 
                                                title="Reset Password Guru">
                                            <span class="material-symbols-outlined text-base">key</span>
                                        </button>

                                        <!-- Tombol Hapus Akun -->
                                        <?php if ($u['id'] == $current_user_id || $u['id'] == 1): ?>
                                            <span class="p-1.5 text-slate-300 cursor-not-allowed" title="Akun utama / akun sendiri tidak dapat dihapus">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </span>
                                        <?php else: ?>
                                            <button type="button" 
                                                    onclick="bukaModalHapus(<?= htmlspecialchars(json_encode($u)) ?>)" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-danger hover:bg-danger-subtle transition-colors cursor-pointer" 
                                                    title="Hapus Akun Guru">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal Tambah Guru Baru -->
<div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">person_add</span>
                Tambah Akun Guru Baru
            </h3>
            <button type="button" onclick="tutupModalTambah()" class="text-text-muted hover:text-danger p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_guru.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="tambah">

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="tambah_nama_lengkap">Nama Lengkap & Gelar</label>
                <input type="text" id="tambah_nama_lengkap" name="nama_lengkap" placeholder="Contoh: Ustadz Ahmad Fauzi, Lc." class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-medium" required>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="tambah_username">Username Login</label>
                <input type="text" id="tambah_username" name="username" placeholder="Contoh: ahmad.fauzi" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-mono" required>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="tambah_password">Password Awal</label>
                <input type="password" id="tambah_password" name="password" placeholder="Minimal 6 karakter..." class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5" required>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="tambah_role">Peran / Hak Akses</label>
                <select id="tambah_role" name="role" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-medium cursor-pointer" required>
                    <option value="guru" selected>Guru Pengajar</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalTambah()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors cursor-pointer">
                    Simpan Guru Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Guru -->
<div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">edit</span>
                Edit Profil Akun
            </h3>
            <button type="button" onclick="tutupModalEdit()" class="text-text-muted hover:text-danger p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_guru.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="edit_nama_lengkap">Nama Lengkap & Gelar</label>
                <input type="text" id="edit_nama_lengkap" name="nama_lengkap" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-medium" required>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="edit_username">Username</label>
                <input type="text" id="edit_username" name="username" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-mono" required>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="edit_role">Peran / Hak Akses</label>
                <select id="edit_role" name="role" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5 font-medium cursor-pointer" required>
                    <option value="guru">Guru Pengajar</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalEdit()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-primary-hover text-white transition-colors cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Password -->
<div id="modalResetPwd" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-base">key</span>
                Reset Password Akun
            </h3>
            <button type="button" onclick="tutupModalResetPwd()" class="text-text-muted hover:text-danger p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_guru.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="reset_password">
            <input type="hidden" name="id" id="reset_pwd_id">

            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p class="text-[11px] text-text-muted font-medium">Akun Guru:</p>
                <p class="text-xs font-bold text-text-main" id="reset_pwd_nama">-</p>
                <p class="text-[11px] text-text-muted font-mono" id="reset_pwd_user">-</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-text-main" for="reset_pwd_input">Password Baru</label>
                <input type="password" id="reset_pwd_input" name="password_baru" placeholder="Masukkan password baru..." class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2.5" required>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalResetPwd()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors cursor-pointer">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus Guru (Beserta Pilihan Alihkan Jadwal) -->
<div id="modalHapus" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 hidden p-4">
    <div class="bg-surface-card rounded-2xl border border-border-main shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-border-main mb-4">
            <h3 class="text-sm font-bold text-danger flex items-center gap-2">
                <span class="material-symbols-outlined text-base">warning</span>
                Hapus Akun Guru
            </h3>
            <button type="button" onclick="tutupModalHapus()" class="text-text-muted hover:text-danger p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="proses_guru.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" id="hapus_id">

            <div class="bg-rose-50 border border-rose-200 p-3.5 rounded-xl">
                <p class="text-xs font-bold text-rose-900">
                    Apakah Anda yakin ingin menghapus akun guru <span id="hapus_nama">-</span>?
                </p>
                <p class="text-[11px] text-rose-800 mt-1">
                    Tindakan ini permanen. Jika guru sedang membina kelas, tugas wali kelas akan otomatis dinonaktifkan.
                </p>
            </div>

            <!-- Opsi Penanganan Jadwal jika Ada -->
            <div id="box_penanganan_jadwal" class="flex flex-col gap-2.5">
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                    <span class="material-symbols-outlined text-sm align-middle text-amber-700">info</span>
                    Guru ini sedang mengampu <strong id="hapus_total_jadwal">0</strong> jadwal mata pelajaran. Pilih penanganan jadwal:
                </div>

                <div class="flex flex-col gap-2">
                    <label class="flex items-start gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="tindakan_jadwal" value="kosong" checked class="mt-0.5 text-primary focus:ring-primary" onchange="togglePilihGuruLain(false)">
                        <div class="text-[11px]">
                            <strong class="text-text-main block">🟡 Kosongkan Jadwal (Slot Terbuka / Tersedia untuk Diambil)</strong>
                            <span class="text-text-muted block">Jadwal menjadi terbuka tanpa pengampu sehingga bisa langsung diambil mandiri oleh rekan guru lain. Nilai siswa tetap aman.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="tindakan_jadwal" value="admin" class="mt-0.5 text-primary focus:ring-primary" onchange="togglePilihGuruLain(false)">
                        <div class="text-[11px]">
                            <strong class="text-text-main block">Alihkan ke Admin (Titipan Manual)</strong>
                            <span class="text-text-muted block">Jadwal dialihkan ke Admin sebagai Titipan Manual. Wali kelas berhak mengisi nilai di kelas binaannya.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="tindakan_jadwal" value="alihkan" class="mt-0.5 text-primary focus:ring-primary" onchange="togglePilihGuruLain(true)">
                        <div class="text-[11px]">
                            <strong class="text-text-main block">Alihkan ke Guru Lain</strong>
                            <span class="text-text-muted block">Pilih rekan guru lain untuk langsung mengambil alih jadwal mengajar ini.</span>
                        </div>
                    </label>
                </div>

                <div id="box_guru_lain" class="hidden mt-1">
                    <label class="block text-[11px] font-semibold text-text-main mb-1" for="target_guru_id">Pilih Guru Pengganti</label>
                    <select id="target_guru_id" name="target_guru_id" class="w-full bg-white text-xs rounded-lg border border-slate-300 p-2 font-medium cursor-pointer">
                        <option value="" disabled selected>-- Pilih Guru Pengganti --</option>
                        <?php foreach ($semua_guru_tujuan as $gt): ?>
                            <option value="<?= $gt['id'] ?>" class="opt-tujuan" data-gid="<?= $gt['id'] ?>">
                                <?= htmlspecialchars($gt['nama_lengkap']) ?> <?= ($gt['role'] === 'admin') ? '(Admin)' : '(Guru)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border-main mt-2">
                <button type="button" onclick="tutupModalHapus()" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-text-muted transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-danger hover:bg-rose-700 text-white transition-colors cursor-pointer">
                    Ya, Hapus Akun
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalTambah() {
    document.getElementById('modalTambah').classList.remove('hidden');
}
function tutupModalTambah() {
    document.getElementById('modalTambah').classList.add('hidden');
}

function bukaModalEdit(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_nama_lengkap').value = u.nama_lengkap;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('modalEdit').classList.remove('hidden');
}
function tutupModalEdit() {
    document.getElementById('modalEdit').classList.add('hidden');
}

function bukaModalResetPwd(u) {
    document.getElementById('reset_pwd_id').value = u.id;
    document.getElementById('reset_pwd_nama').textContent = u.nama_lengkap;
    document.getElementById('reset_pwd_user').textContent = '@' + u.username;
    document.getElementById('reset_pwd_input').value = '';
    document.getElementById('modalResetPwd').classList.remove('hidden');
}
function tutupModalResetPwd() {
    document.getElementById('modalResetPwd').classList.add('hidden');
}

function bukaModalHapus(u) {
    document.getElementById('hapus_id').value = u.id;
    document.getElementById('hapus_nama').textContent = u.nama_lengkap + ' (@' + u.username + ')';
    const totalJadwal = parseInt(u.total_jadwal) || 0;
    document.getElementById('hapus_total_jadwal').textContent = totalJadwal;

    const boxJadwal = document.getElementById('box_penanganan_jadwal');
    if (totalJadwal > 0) {
        boxJadwal.classList.remove('hidden');
    } else {
        boxJadwal.classList.add('hidden');
    }

    // Sembunyikan option yang merupakan guru yang sedang dihapus itu sendiri
    document.querySelectorAll('.opt-tujuan').forEach(opt => {
        if (parseInt(opt.getAttribute('data-gid')) === parseInt(u.id)) {
            opt.classList.add('hidden');
            opt.disabled = true;
        } else {
            opt.classList.remove('hidden');
            opt.disabled = false;
        }
    });

    togglePilihGuruLain(false);
    document.getElementById('modalHapus').classList.remove('hidden');
}
function tutupModalHapus() {
    document.getElementById('modalHapus').classList.add('hidden');
}

function togglePilihGuruLain(tampilkan) {
    const box = document.getElementById('box_guru_lain');
    const select = document.getElementById('target_guru_id');
    if (tampilkan) {
        box.classList.remove('hidden');
        select.required = true;
    } else {
        box.classList.add('hidden');
        select.required = false;
    }
}
</script>

<?php require_once '../components/footer.php'; ?>
