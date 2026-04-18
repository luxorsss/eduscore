<?php
session_start();

// PENJAGA PINTU: Tendang ke login jika belum ada tiket (session)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set judul halaman dan panggil komponen Header (yang sudah include Sidebar)
$page_title = "EduScore - Analisis & Rekapitulasi";
require_once '../components/header.php'; 
?>

    <nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="md:hidden w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center text-on-surface hover:bg-outline-variant/30 transition mr-2" title="Kembali ke Dashboard">
                    <span class="material-symbols-outlined text-sm">home</span>
                </a>
                <span class="font-headline font-bold text-primary tracking-tight text-lg md:hidden">EduScore</span>
                <span class="font-headline font-bold text-primary tracking-tight text-lg hidden md:block">Buku Nilai & Analisis</span>
            </div>
            
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-on-surface-variant hidden md:block">
                    <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>
                </span>
                <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm cursor-pointer">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 2)); ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-2">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-2">Statistik Kelas</h1>
                <p class="text-on-surface-variant text-sm">Tinjauan komprehensif performa akademik siswa berdasarkan kelas.</p>
            </div>
            
            <div class="flex items-center gap-2 w-full md:w-auto">
                <select class="bg-surface-container-lowest text-on-surface text-sm rounded-lg border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary px-3 py-2.5 font-medium shadow-sm flex-1 md:flex-none">
                    <option>10 IPA 1 - Fisika</option>
                    <option>10 IPA 2 - Fisika</option>
                    <option>12 IPS 1 - Matematika</option>
                </select>
                <button class="flex items-center gap-2 bg-tertiary text-on-primary px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-tertiary/90 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    <span class="hidden md:inline">Ekspor Excel</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/20 shadow-sm flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase text-on-surface-variant tracking-wider">Total Siswa</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-black text-primary">36</span>
                    <span class="text-sm font-medium text-on-surface-variant mb-1">Siswa</span>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/20 shadow-sm flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase text-on-surface-variant tracking-wider">Rata-Rata Kelas</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-black text-primary">82.4</span>
                    <span class="text-xs font-bold text-tertiary bg-tertiary-container px-1.5 py-0.5 rounded mb-1.5">B+</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/20 shadow-sm flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase text-on-surface-variant tracking-wider">Nilai Tertinggi</span>
                <div class="flex items-end gap-2">
                    <span class="text-3xl font-black text-tertiary">98</span>
                    <span class="text-sm font-medium text-on-surface-variant mb-1">(Citra)</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest p-5 rounded-xl border border-error-container shadow-sm flex flex-col gap-1 relative overflow-hidden">
                <div class="absolute right-[-10px] bottom-[-10px] text-error-container opacity-50">
                    <span class="material-symbols-outlined text-8xl">warning</span>
                </div>
                <span class="text-xs font-semibold uppercase text-error tracking-wider z-10">Remedial (< 75)</span>
                <div class="flex items-end gap-2 z-10">
                    <span class="text-3xl font-black text-error">4</span>
                    <span class="text-sm font-medium text-error mb-1">Siswa</span>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden flex flex-col">
            
            <div class="p-4 border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
                <h2 class="font-bold text-on-surface">Detail Nilai Akhir</h2>
                <span class="text-xs font-medium bg-surface-container-highest px-2 py-1 rounded text-on-surface-variant">KKM: 75</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="bg-surface-container-highest text-xs uppercase font-semibold text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center border-b border-outline-variant/30">No</th>
                            <th class="px-4 py-3 border-b border-outline-variant/30">NIS</th>
                            <th class="px-4 py-3 border-b border-outline-variant/30">Nama Siswa</th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30">H-UTS <span class="text-[10px] font-normal block">(20%)</span></th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30">UTS <span class="text-[10px] font-normal block">(30%)</span></th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30">H-UAS <span class="text-[10px] font-normal block">(20%)</span></th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30">UAS <span class="text-[10px] font-normal block">(30%)</span></th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30 bg-[#d6e3ff]/30">+ Tamb.</th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30 bg-primary text-on-primary">Akhir</th>
                            <th class="px-4 py-3 text-center border-b border-outline-variant/30">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm font-medium text-on-surface">
                        
                        <tr class="hover:bg-surface-container-low transition-colors border-b border-outline-variant/10">
                            <td class="px-4 py-4 text-center text-on-surface-variant">1</td>
                            <td class="px-4 py-4 text-on-surface-variant">1001</td>
                            <td class="px-4 py-4 font-bold">Ahmad Fulan</td>
                            <td class="px-4 py-4 text-center">85</td>
                            <td class="px-4 py-4 text-center">90</td>
                            <td class="px-4 py-4 text-center">88</td>
                            <td class="px-4 py-4 text-center">92</td>
                            <td class="px-4 py-4 text-center bg-[#d6e3ff]/10">-</td>
                            <td class="px-4 py-4 text-center font-black text-primary bg-primary/5 text-base">89.2</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 bg-tertiary-container text-tertiary px-2 py-1 rounded text-xs font-bold">
                                    LULUS
                                </span>
                            </td>
                        </tr>

                        <tr class="hover:bg-error-container/20 transition-colors border-b border-outline-variant/10 bg-error-container/5">
                            <td class="px-4 py-4 text-center text-on-surface-variant">2</td>
                            <td class="px-4 py-4 text-on-surface-variant">1002</td>
                            <td class="px-4 py-4 font-bold">Budi Santoso</td>
                            <td class="px-4 py-4 text-center">65</td>
                            <td class="px-4 py-4 text-center">70</td>
                            <td class="px-4 py-4 text-center">75</td>
                            <td class="px-4 py-4 text-center">72</td>
                            <td class="px-4 py-4 text-center bg-[#d6e3ff]/10">5</td>
                            <td class="px-4 py-4 text-center font-black text-error bg-error/5 text-base">70.6</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 bg-error-container text-error px-2 py-1 rounded text-xs font-bold">
                                    REMEDIAL
                                </span>
                            </td>
                        </tr>

                        <tr class="hover:bg-surface-container-low transition-colors border-b border-outline-variant/10">
                            <td class="px-4 py-4 text-center text-on-surface-variant">3</td>
                            <td class="px-4 py-4 text-on-surface-variant">1003</td>
                            <td class="px-4 py-4 font-bold">Citra Kirana</td>
                            <td class="px-4 py-4 text-center">95</td>
                            <td class="px-4 py-4 text-center">100</td>
                            <td class="px-4 py-4 text-center">95</td>
                            <td class="px-4 py-4 text-center">98</td>
                            <td class="px-4 py-4 text-center bg-[#d6e3ff]/10">2</td>
                            <td class="px-4 py-4 text-center font-black text-primary bg-primary/5 text-base">98.0</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1 bg-tertiary-container text-tertiary px-2 py-1 rounded text-xs font-bold">
                                    LULUS
                                </span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-outline-variant/20 bg-surface text-sm text-on-surface-variant flex justify-between items-center">
                <span>Menampilkan 3 dari 36 siswa</span>
                <div class="flex gap-1">
                    <button class="w-8 h-8 rounded flex items-center justify-center border border-outline-variant/50 hover:bg-surface-container-high disabled:opacity-50" disabled>
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </button>
                    <button class="w-8 h-8 rounded flex items-center justify-center bg-primary text-on-primary">1</button>
                    <button class="w-8 h-8 rounded flex items-center justify-center border border-outline-variant/50 hover:bg-surface-container-high">2</button>
                    <button class="w-8 h-8 rounded flex items-center justify-center border border-outline-variant/50 hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </button>
                </div>
            </div>

        </div>
    </main>

<?php 
// Panggil Komponen Footer
require_once '../components/footer.php'; 
?>