-- Jalankan di SQL Console cPanel/phpMyAdmin untuk menambahkan Kelas TR.
INSERT INTO `kelas` (`tingkat_id`, `kode_kelas`, `nama_kelas`)
SELECT NULL, 'TR', 'Kelas TR'
WHERE NOT EXISTS (
    SELECT 1 FROM `kelas` WHERE `kode_kelas` = 'TR'
);
