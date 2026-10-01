# EduScore Design Direction & System

> Dokumen acuan arah visual, identitas, dan sistem desain untuk EduScore.

## 1. Identitas & Karakter
- **Produk**: EduScore — Sistem Pencatatan dan Analisis Nilai Pengajar.
- **Sasaran Pengguna**: Guru dan wali kelas di jenjang SMP dan SMA.
- **Karakter**: Fungsional, tenang, presisi akademik, andal, tanpa distraksi visual.
- **Prinsip**: Desain berfokus pada kecepatan input data, keterbacaan angka (*tabular data*), dan kejelasan navigasi di perangkat desktop maupun seluler.

## 2. Antislop Dials
- **ENERGY: 2** (Fokus dan teratur; bukan template bento-box atau glow pesta teknologi, melainkan antarmuka kerja harian yang nyaman di mata berjam-jam).
- **RHYTHM: 2** (Hierarki jelas: bilah navigasi konsisten, area kerja utama tabel/formulir, dan panel rangkuman terpisah).
- **MOTION: 1** (Transisi fungsional 150-200ms pada hover dan modal/drawer; tidak ada animasi loop, pulse, atau floating).

## 3. Palet Warna (Restrained Palette)
- **Primary (Deep Navy)**: `#0f2942` — Dipakai untuk branding, bilah navigasi aktif, dan tombol aksi utama.
- **Primary Hover**: `#1a3d60`
- **Surface**: `#f8fafc` (Slate 50) — Latar belakang aplikasi yang bersih dan tidak melelahkan mata.
- **Surface Container (Card / Panel)**: `#ffffff` — Permukaan kartu konten dengan garis batas tegas.
- **Border / Outline**: `#e2e8f0` (Slate 200) — Garis pembatas struktur.
- **Text Dominant**: `#0f172a` (Slate 900) — Teks utama dengan kontras tinggi (> 10:1).
- **Text Muted**: `#475569` (Slate 600) — Label dan teks sekunder (kontras > 5:1, lulus WCAG AA).
- **Status & Semantik**:
  - **Tuntas / Di Atas KKM**: Emerald (`#047857`, latar `#ecfdf5`, border `#a7f3d0`).
  - **Di Bawah KKM / Remedial**: Amber/Orange (`#b45309`, latar `#fffbeb`, border `#fde68a`).
  - **Peringatan / Hapus / Nol**: Rose (`#be123c`, latar `#fff1f2`, border `#fecdd3`).

## 4. Tipografi
- **Family**: Inter, system-ui, -apple-system, sans-serif.
- **Bobot**: Regular (400) untuk badan teks, Medium (500) untuk label kontrol, Semibold (600) untuk judul dan status, Bold (700) khusus angka rekap.
- **Tabular Figures**: Mengaktifkan `font-feature-settings: 'tnum'` untuk seluruh kolom angka nilai agar tersusun rata vertikal saat dibandingkan.

## 5. Tata Letak & Komponen
- **Header & Sidebar**: Terpusat dan konsisten di seluruh halaman aplikasi.
- **Border Radius**: 
  - `rounded-md` (6px) untuk input form dan tag kecil.
  - `rounded-lg` (8px) untuk tombol dan item menu navigasi.
  - `rounded-xl` (12px) untuk kartu panel dan tabel kontainer.
  - Menghindari bentuk kapsul/pill seragam yang berlebihan.
- **Elevasi & Bayangan**: Menggunakan border border-slate-200 dengan `shadow-xs` / `shadow-sm`. Menghilangkan bayangan mengambang berlebihan (overly soft floating shadows).
- **Aksesibilitas Mobile**: Target sentuh tombol dan baris aksi minimal 44x44px. Tabel lebar memiliki indikator geser horizontal dengan kolom nama siswa tetap mudah dipantau.
