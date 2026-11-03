CREATE DATABASE IF NOT EXISTS peminjaman_alat_2401020168
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE peminjaman_alat_2401020168;

DROP TABLE IF EXISTS peminjaman_alat;
DROP TABLE IF EXISTS alat;

CREATE TABLE alat (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_alat VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE peminjaman_alat (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_peminjaman CHAR(10) NOT NULL UNIQUE,
  nama_peminjam VARCHAR(100) NOT NULL,
  nomor_identitas VARCHAR(30) NOT NULL UNIQUE,
  jumlah_alat TINYINT UNSIGNED NOT NULL,
  alat_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_jumlah_alat CHECK (jumlah_alat BETWEEN 1 AND 10),
  CONSTRAINT fk_peminjaman_alat FOREIGN KEY (alat_id) REFERENCES alat (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO alat (nama_alat) VALUES ('Kamera'), ('Proyektor');

INSERT INTO peminjaman_alat (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id) VALUES
 ('PM00000001', 'Rina Kurnia',  'MHS-2201001', 2, (SELECT id FROM alat WHERE nama_alat = 'Kamera')),
 ('PM00000002', 'Dedi Pranata', 'MHS-2201002', 1, (SELECT id FROM alat WHERE nama_alat = 'Proyektor')),
 ('PM00000003', 'Mira Ananda',  'MHS-2201003', 3, (SELECT id FROM alat WHERE nama_alat = 'Kamera'));