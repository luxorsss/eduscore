<?php
session_start();

// PENJAGA PINTU: Tendang ke login jika belum ada tiket (session)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set judul halaman dan panggil komponen Header (yang sudah include Sidebar)
$page_title = "EduScore - Data Induk Siswa";
require_once '../components/header.php'; 
?>

    <nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-highest rounded-full transition-colors mr-1">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary hidden md:flex">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">school</span>
                </div>
                <span class="font-headline font-bold text-primary tracking-tight text-lg">EduScore</span>
                <span class="text-on-surface-variant ml-2 text-sm font-medium hidden md:block">| Data Induk Siswa</span>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-[#d6e3ff] text-primary flex items-center justify-center font-bold text-sm">BS</div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-6">
        
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-primary mb-1">Manajemen Siswa</h1>
                <p class="text-on-surface-variant text-sm">Kelola data induk, pindah kelas, atau lakukan pembaruan massal.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                    <input type="text" class="w-full bg-surface-container-lowest border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary rounded-lg pl-10 pr-4 py-2 text-sm font-medium shadow-sm transition-all" placeholder="Cari nama atau NIS...">
                </div>
                
                <select class="w-full sm:w-auto bg-surface-container-lowest text-on-surface text-sm rounded-lg border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary px-3 py-2 font-medium shadow-sm">
                    <option value="">Semua Kelas</option>
                    <option value="1">10 IPA 1</option>
                    <option value="2">10 IPA 2</option>
                </select>

                <button class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary text-on-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-container transition-colors shadow-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Tambah Siswa
                </button>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-[0px_4px_16px_rgba(26,28,30,0.04)] overflow-hidden flex flex-col relative z-10">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead class="bg-surface-container-low text-xs uppercase font-semibold text-on-surface-variant border-b border-outline-variant/20">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">
                                <input type="checkbox" id="checkAll" class="rounded text-primary focus:ring-primary border-outline-variant/50 bg-surface w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-4 py-3">Profil Siswa</th>
                            <th class="px-4 py-3 w-40">Nomor Induk</th>
                            <th class="px-4 py-3 w-32">Kelas</th>
                            <th class="px-4 py-3 w-20 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-on-surface divide-y divide-outline-variant/10">
                        
                        <tr class="hover:bg-surface-container-low/50 transition-colors group">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" class="cb-siswa rounded text-primary focus:ring-primary border-outline-variant/50 bg-surface w-4 h-4 cursor-pointer">
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-[#d9e3f8] text-primary flex items-center justify-center font-bold text-sm shrink-0">AF</div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-base">Ahmad Fulan</span>
                                        <span class="text-xs text-on-surface-variant">Laki-laki</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-medium text-on-surface-variant font-mono">10293847</td>
                            <td class="px-4 py-4">
                                <span class="bg-surface-container-highest px-2.5 py-1 rounded-md text-xs font-semibold text-on-surface-variant border border-outline-variant/30">
                                    10 IPA 1
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="w-8 h-8 rounded hover:bg-[#d6e3ff] text-primary flex items-center justify-center transition-colors" title="Edit Data">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr class="bg-primary/5 border-t-2 border-primary/20">
                            <td class="px-4 py-4 text-center">
                                <span class="material-symbols-outlined text-primary text-sm">add</span>
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" class="w-full bg-surface-container-lowest border-0 border-b-2 border-primary focus:ring-0 px-2 py-2 text-sm font-bold text-primary placeholder-primary/50" placeholder="Ketik Nama Siswa Baru...">
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" class="w-full bg-surface-container-lowest border-0 border-b-2 border-outline-variant/50 focus:border-primary focus:ring-0 px-2 py-2 text-sm font-medium font-mono" placeholder="NIS">
                            </td>
                            <td class="px-4 py-3">
                                <select class="w-full bg-surface-container-lowest border-0 border-b-2 border-outline-variant/50 focus:border-primary focus:ring-0 px-2 py-2 text-sm font-medium">
                                    <option>10 IPA 1</option>
                                    <option>10 IPA 2</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button class="bg-primary text-on-primary px-3 py-1.5 rounded text-xs font-bold shadow-sm hover:bg-primary-container">Simpan</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="bulkActionBar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-surface-container-lowest border border-outline-variant/30 shadow-[0px_16px_32px_rgba(26,28,30,0.12)] rounded-2xl px-5 py-3 flex items-center gap-5 transition-all duration-300 translate-y-24 opacity-0 z-50">
        
        <div class="flex items-center gap-2 border-r border-outline-variant/30 pr-5">
            <div class="w-6 h-6 rounded-full bg-primary text-on-primary flex items-center justify-center text-xs font-bold" id="selectedCount">0</div>
            <span class="text-sm font-bold text-on-surface">Siswa Terpilih</span>
        </div>

        <div class="flex items-center gap-2">
            <button class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-high rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">move_up</span>
                Pindah Kelas
            </button>
            <button class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-error hover:bg-error-container rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Hapus
            </button>
        </div>
        
        <button id="closeActionBar" class="ml-2 w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-highest text-on-surface-variant transition-colors">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.cb-siswa');
            const actionBar = document.getElementById('bulkActionBar');
            const selectedCountLabel = document.getElementById('selectedCount');
            const closeActionBarBtn = document.getElementById('closeActionBar');

            function updateActionBar() {
                const selectedCount = document.querySelectorAll('.cb-siswa:checked').length;
                if (selectedCount > 0) {
                    selectedCountLabel.innerText = selectedCount;
                    actionBar.classList.remove('translate-y-24', 'opacity-0');
                    actionBar.classList.add('translate-y-0', 'opacity-100');
                } else {
                    actionBar.classList.remove('translate-y-0', 'opacity-100');
                    actionBar.classList.add('translate-y-24', 'opacity-0');
                    checkAll.checked = false;
                }
            }

            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateActionBar();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateActionBar);
            });

            closeActionBarBtn.addEventListener('click', () => {
                checkboxes.forEach(cb => cb.checked = false);
                checkAll.checked = false;
                updateActionBar();
            });
        });
    </script>

<?php 
// 2. Panggil Footer
require_once '../components/footer.php'; 
?>