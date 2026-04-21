<footer class="mt-auto py-6 text-center border-t border-outline-variant/20">
        <p class="text-xs text-on-surface-variant">
            &copy; <?= date('Y') ?> EduScore. Dikembangkan untuk Guru Hebat.
        </p>
    </footer>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // 1. Fungsi Pop-up Konfirmasi untuk Link Hapus (Tombol <a>)
            function konfirmasiLink(event, url, pesan) {
                event.preventDefault(); // Cegah link langsung pindah halaman
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: pesan,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ba1a1a', // Warna Merah Error Tailwind
                    cancelButtonColor: '#74777f', // Warna Abu-abu
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal',
                    background: '#f8f9ff',
                    customClass: { popup: 'rounded-2xl shadow-lg border border-outline-variant/20' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url; // Pindah halaman jika klik Ya
                    }
                });
            }

            // 2. Fungsi Pop-up Konfirmasi untuk Form (Tombol Submit)
            function konfirmasiForm(event, pesan) {
                event.preventDefault(); // Cegah form langsung terkirim
                const form = event.target.closest('form');
                const tombol = event.currentTarget; // Tangkap tombol yang diklik

                Swal.fire({
                    title: 'Peringatan Keras!',
                    text: pesan,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ba1a1a',
                    cancelButtonColor: '#74777f',
                    confirmButtonText: 'Ya, Eksekusi!',
                    cancelButtonText: 'Batal',
                    background: '#f8f9ff',
                    customClass: { popup: 'rounded-2xl shadow-lg border border-outline-variant/20' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Agar nilai dari tombol submit (seperti aksi=hapus_massal) tetap ikut terkirim
                        if(tombol.name && tombol.value) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden'; hiddenInput.name = tombol.name; hiddenInput.value = tombol.value;
                            form.appendChild(hiddenInput);
                        }
                        form.submit(); // Kirim form jika klik Ya
                    }
                });
            }
        </script>
    </body>
</html>