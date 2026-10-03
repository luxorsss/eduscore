    <footer class="mt-auto py-6 px-4 text-center border-t border-border-main bg-surface-card">
        <p class="text-xs text-text-muted">
            &copy; <?= date('Y') ?> EduScore. Sistem Pengelolaan Nilai & Akademik Siswa.
        </p>
    </footer>

    </div> <!-- Penutup .flex-1 dari header.php -->

    <script>
        // Konfirmasi tautan hapus
        function konfirmasiLink(event, url, pesan) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Tindakan',
                text: pesan,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0f2942',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                customClass: { 
                    popup: 'rounded-xl shadow-md border border-slate-200 text-sm font-sans',
                    confirmButton: 'rounded-lg text-xs font-semibold px-4 py-2.5',
                    cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

        // Konfirmasi submit form
        function konfirmasiForm(event, pesan) {
            event.preventDefault();
            const form = event.target.closest('form');
            const tombol = event.currentTarget;

            Swal.fire({
                title: 'Konfirmasi Penghapusan',
                text: pesan,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#be123c',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Jalankan',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                customClass: { 
                    popup: 'rounded-xl shadow-md border border-slate-200 text-sm font-sans',
                    confirmButton: 'rounded-lg text-xs font-semibold px-4 py-2.5',
                    cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (tombol && tombol.name && tombol.value) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden'; 
                        hiddenInput.name = tombol.name; 
                        hiddenInput.value = tombol.value;
                        form.appendChild(hiddenInput);
                    }
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>