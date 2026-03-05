-- Jalankan di SQL Console cPanel/phpMyAdmin jika server tanpa SSH.
INSERT INTO `lokasi_ibadah` (`nama_lokasi`)
SELECT 'Online'
WHERE NOT EXISTS (
    SELECT 1 FROM `lokasi_ibadah` WHERE LOWER(`nama_lokasi`) = 'online'
);
