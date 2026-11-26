# SIM Kurikulum & e-Rapor SMP Fase D (PHP & MySQL) - v11.0

Aplikasi Sistem Informasi Manajemen (SIM) Kurikulum Merdeka jenjang SMP (Fase D: Kelas VII, VIII, IX) dilengkapi pengelolaan Data Master, Perangkat Ajar, serta modul **Penilaian & e-Rapor KM** lengkap dengan **Import & Template CSV** dan **Rapor Kokurikuler 7KAIH terpisah**.

## Pembaruan Versi 11.0
1. **Menu Data Siswa (`siswa.php`):** Dilengkapi tombol **Download Template CSV** dan **Import Data Siswa (CSV)** untuk mengunggah atau memperbarui data siswa per kelas secara massal berdasarkan NIS.
2. **Cetak Leger Ukuran Kertas F4 (`leger.php`):** Dilengkapi pemilih orientasi **F4 Landscape (330 x 215 mm)** dan **F4 Portrait (215 x 330 mm)** yang otomatis menyesuaikan ukuran halaman `@page`, skala font tabel, kop resmi sekolah, serta kolom tanda tangan Kepala Sekolah dan Wali Kelas saat dicetak.
3. **Modul Penilaian & e-Rapor KM Lengkap:**
   - Input Rekap Nilai + Template & Import CSV (`rekap_nilai.php`)
   - Input Nilai Ekstrakurikuler + Template & Import CSV (`input_ekskul.php`)
   - Input Kokurikuler 7KAIH + Template & Import CSV (`input_kokurikuler.php`)
   - Rekap Kehadiran + Template & Import CSV (`rekap_kehadiran.php`)
   - Cetak e-Rapor KM (`eraport.php`) & Cetak Rapor Kokurikuler 7KAIH terpisah (`rapor_kokurikuler.php`)

## Cara Instalasi di Laragon / XAMPP
1. Ekstrak seluruh isi ZIP ini ke folder `C:\laragon\www\sim-kurikulum\`.
2. Pastikan service **Apache** dan **MySQL** aktif.
3. Buka `http://localhost/sim-kurikulum/` di browser.
4. Login dengan username **`admin`** dan password **`admin123`**.
