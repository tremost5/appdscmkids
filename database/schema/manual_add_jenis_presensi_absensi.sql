ALTER TABLE absensi
  ADD COLUMN jenis_presensi VARCHAR(20) NOT NULL DEFAULT 'reguler' AFTER lokasi_text;

