<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "EduScore - Input Nilai";
require_once '../components/header.php'; 
?>

    <nav class="bg-surface-container-lowest shadow-sm border-b border-outline-variant/20 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="dashboard.php" class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center text-on-surface hover:bg-outline-variant/30 transition mr-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </a>
                <div class="flex flex-col">
                    <span class="font-bold text-sm md:text-base leading-tight">10 IPA 1 - Fisika</span>
                    <span class="text-[10px] md:text-xs text-primary font-semibold uppercase tracking-wider">Input Nilai UAS</span>
                </div>
            </div>
            
            <button id="btnSimpanAtas" class="flex items-center px-4 py-2 bg-primary text-on-primary text-sm font-medium rounded-lg hover:bg-primary-container transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px] md:mr-2">save</span>
                <span class="hidden md:inline">Simpan Data</span>
            </button>
        </div>
    </nav>

    <div class="bg-surface/90 backdrop-blur-md sticky top-16 z-40 border-b border-outline-variant/20 shadow-sm">
        <div class="max-w-4xl mx-auto p-4">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input type="text" id="cariSiswa" class="w-full bg-surface-container-lowest border border-outline-variant/50 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl pl-10 pr-10 py-3 text-sm font-medium shadow-inner transition-all" placeholder="Ketik nama siswa dari kertas ujian..." autocomplete="off">
                <button id="btnClearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-error hidden">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-4xl mx-auto w-full p-4 flex flex-col gap-3">
        
        <form id="formNilai" action="proses_simpan_nilai.php" method="POST">
            <div class="flex flex-col gap-3" id="daftarSiswa">

                <div class="student-card bg-surface-container-lowest rounded-xl p-4 shadow-[0px_2px_8px_rgba(26,28,30,0.04)] border border-outline-variant/10 flex items-center justify-between gap-4 transition-all">
                    
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-full bg-[#d9e3f8] text-[#1a365d] flex items-center justify-center font-bold text-sm shrink-0">AF</div>
                        <div class="overflow-hidden">
                            <h3 class="student-name font-bold text-on-surface text-sm md:text-base truncate">Ahmad Fulan</h3>
                            <p class="text-[11px] text-on-surface-variant font-medium mt-0.5 uppercase tracking-wider">NIS: 1001</p>
                        </div>
                    </div>

                    <div class="w-[80px] md:w-[100px] shrink-0">
                        <input type="number" name="nilai_uas[1001]" class="nilai-input w-full bg-surface-container-highest border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface rounded-t-md px-2 py-3 text-lg md:text-xl text-center font-black text-primary transition-colors" placeholder="0" min="0" max="100" />
                    </div>
                </div>

                <div class="student-card bg-surface-container-lowest rounded-xl p-4 shadow-[0px_2px_8px_rgba(26,28,30,0.04)] border border-outline-variant/10 flex items-center justify-between gap-4 transition-all">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-full bg-[#f4f3f7] text-on-surface-variant border border-outline-variant/30 flex items-center justify-center font-bold text-sm shrink-0">BS</div>
                        <div class="overflow-hidden">
                            <h3 class="student-name font-bold text-on-surface text-sm md:text-base truncate">Budi Santoso</h3>
                            <p class="text-[11px] text-on-surface-variant font-medium mt-0.5 uppercase tracking-wider">NIS: 1002</p>
                        </div>
                    </div>
                    <div class="w-[80px] md:w-[100px] shrink-0">
                        <input type="number" name="nilai_uas[1002]" class="nilai-input w-full bg-surface-container-highest border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface rounded-t-md px-2 py-3 text-lg md:text-xl text-center font-black text-primary transition-colors" placeholder="0" min="0" max="100" />
                    </div>
                </div>

                 <div class="student-card bg-surface-container-lowest rounded-xl p-4 shadow-[0px_2px_8px_rgba(26,28,30,0.04)] border border-outline-variant/10 flex items-center justify-between gap-4 transition-all">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-full bg-[#e9e7eb] text-on-surface-variant border border-outline-variant/30 flex items-center justify-center font-bold text-sm shrink-0">CK</div>
                        <div class="overflow-hidden">
                            <h3 class="student-name font-bold text-on-surface text-sm md:text-base truncate">Citra Kirana</h3>
                            <p class="text-[11px] text-on-surface-variant font-medium mt-0.5 uppercase tracking-wider">NIS: 1003</p>
                        </div>
                    </div>
                    <div class="w-[80px] md:w-[100px] shrink-0">
                        <input type="number" name="nilai_uas[1003]" class="nilai-input w-full bg-surface-container-highest border-0 border-b-2 border-transparent focus:border-primary focus:bg-surface rounded-t-md px-2 py-3 text-lg md:text-xl text-center font-black text-primary transition-colors" placeholder="0" min="0" max="100" />
                    </div>
                </div>

            </div>
            
            <div id="emptyState" class="hidden flex-col items-center justify-center py-12 text-center">
                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">search_off</span>
                <p class="font-medium text-on-surface-variant">Siswa tidak ditemukan</p>
            </div>
            
        </form>
    </main>

    <div class="md:hidden fixed bottom-0 left-0 w-full bg-surface-container-lowest border-t border-outline-variant/20 p-4 shadow-[0px_-4px_16px_rgba(0,0,0,0.05)] z-50">
        <button id="btnSimpanBawah" class="w-full py-3.5 bg-primary text-on-primary font-semibold rounded-lg shadow-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Simpan Nilai UAS
        </button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            
            const searchInput = document.getElementById('cariSiswa');
            const clearBtn = document.getElementById('btnClearSearch');
            const studentCards = document.querySelectorAll('.student-card');
            const emptyState = document.getElementById('emptyState');

            // 1. Fitur Live Search untuk Kertas Acak
            searchInput.addEventListener('input', function() {
                const keyword = this.value.toLowerCase();
                let hasVisibleCard = false;

                // Tampilkan tombol silang jika ada teks
                if(keyword.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }

                studentCards.forEach(card => {
                    const name = card.querySelector('.student-name').textContent.toLowerCase();
                    // Jika nama cocok dengan keyword, tampilkan. Jika tidak, sembunyikan.
                    if (name.includes(keyword)) {
                        card.style.display = 'flex';
                        hasVisibleCard = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Tampilkan indikator jika nama yang diketik tidak ada di daftar
                if(!hasVisibleCard) {
                    emptyState.style.display = 'flex';
                } else {
                    emptyState.style.display = 'none';
                }
            });

            // 2. Tombol Clear Search (Silang)
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input')); // Reset filter
                searchInput.focus(); // Kembalikan kursor ke pencarian
            });

            // 3. Trik "Enter" dari Input Nilai kembali ke Search Bar
            const allInputs = document.querySelectorAll('.nilai-input');
            allInputs.forEach(input => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        
                        // Setelah mengisi nilai, otomatis hapus teks pencarian
                        // dan kembalikan kursor ke kotak pencarian untuk kertas berikutnya
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                        searchInput.focus();
                    }
                });
            });

            // 4. Submit Handler
            const form = document.getElementById('formNilai');
            document.getElementById('btnSimpanAtas').addEventListener('click', () => form.submit());
            if(document.getElementById('btnSimpanBawah')){
                document.getElementById('btnSimpanBawah').addEventListener('click', () => form.submit());
            }
        });
    </script>
<?php 
require_once '../components/footer.php'; 
?>