CREATE DATABASE IF NOT EXISTS db_bukutamu;
USE db_bukutamu;

CREATE TABLE IF NOT EXISTS buku_tamu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    instansi VARCHAR(150) NOT NULL,
    tujuan TEXT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL
);

INSERT INTO buku_tamu (nama, instansi, tujuan, tanggal, waktu) VALUES
('Budi Santoso', 'PT Maju Bersama', 'Diskusi kerja sama akademik', '2026-06-10', '08:30:00'),
('Siti Rahma', 'SMK Nusantara', 'Kunjungan kampus dan konsultasi penerimaan mahasiswa', '2026-06-10', '09:15:00'),
('Andi Pratama', 'Universitas Mandiri', 'Studi banding sistem pembelajaran digital', '2026-06-09', '13:45:00');