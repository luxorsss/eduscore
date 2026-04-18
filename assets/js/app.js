document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Fitur Check All / Uncheck All
    const checkAllBtn = document.getElementById('checkAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');

    if(checkAllBtn) {
        checkAllBtn.addEventListener('change', function() {
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // 2. Fitur Enter to Next Input (Penting untuk Input via HP)
    const nilaiInputs = document.querySelectorAll('.nilai-input');
    
    nilaiInputs.forEach((input, index) => {
        input.addEventListener('keydown', function(e) {
            // Jika user menekan tombol Enter
            if (e.key === 'Enter') {
                e.preventDefault(); // Mencegah form submit default
                
                // Fokus ke input berikutnya jika ada
                const nextInput = nilaiInputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                } else {
                    // Jika sudah di paling bawah, ubah tombol simpan jadi fokus
                    document.getElementById('btnSimpanMassal').focus();
                }
            }
        });
    });

});