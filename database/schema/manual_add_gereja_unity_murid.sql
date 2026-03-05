-- Jalankan di SQL Console cPanel (phpMyAdmin / MySQL console) jika server tidak bisa `php artisan migrate`.
ALTER TABLE `murid`
    ADD COLUMN `gereja_asal` VARCHAR(150) NULL AFTER `panggilan`,
    ADD COLUMN `unity` VARCHAR(50) NULL AFTER `gereja_asal`;

-- Opsional: normalisasi nilai unity di data lama agar tetap konsisten.
-- UPDATE `murid`
-- SET `unity` = NULL
-- WHERE `unity` NOT IN ('Unity Peter', 'Unity David', 'Unity Samuel', 'Unity Joshua');

-- Tambahkan Kelas TR jika belum ada.
INSERT INTO `kelas` (`tingkat_id`, `kode_kelas`, `nama_kelas`)
SELECT NULL, 'TR', 'Kelas TR'
WHERE NOT EXISTS (
    SELECT 1 FROM `kelas` WHERE `kode_kelas` = 'TR'
);
