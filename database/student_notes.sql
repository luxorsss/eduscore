-- EduScore: fitur Catatan Siswa
-- Jalankan sekali pada database EduScore.

CREATE TABLE IF NOT EXISTS student_notes (
    id INT NOT NULL AUTO_INCREMENT,
    student_id INT NOT NULL,
    tanggal DATE NOT NULL,
    catatan TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_student_notes_student (student_id),
    INDEX idx_student_notes_tanggal (tanggal),
    CONSTRAINT fk_student_notes_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
