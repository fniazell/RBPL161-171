CREATE DATABASE IF NOT EXISTS logistik_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE logistik_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','forwarder','pelayaran','beacukai','gudang','jict','vendor') NOT NULL,
    nama_lengkap VARCHAR(200),
    perusahaan VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE dokumen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    jenis_dokumen VARCHAR(100) NOT NULL,
    nama_file VARCHAR(300) NOT NULL,
    path_file VARCHAR(500) NOT NULL,
    tipe_kiriman ENUM('Ekspor (PEB)','Impor (PIB)') DEFAULT 'Ekspor (PEB)',
    status_sistem ENUM('Auto-Check Pending','Auto-Check Valid','Dok. Hilang','Gagal','Terverifikasi') DEFAULT 'Auto-Check Pending',
    status_verifikasi_forwarder ENUM('Pending','Valid','Perlu Perbaikan','Approved','Hold') DEFAULT 'Pending',
    status_peb_pib ENUM('Belum Diajukan','Menunggu Persetujuan','Pending Jaringan','Approved','Ditolak') DEFAULT 'Belum Diajukan',
    status_bl_final ENUM('Belum Terbit','Diproses','Terbit','Ditolak') DEFAULT 'Belum Terbit',
    catatan_forwarder TEXT,
    forwarder_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (forwarder_id) REFERENCES users(id)
);

CREATE TABLE manifest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dokumen_id INT NOT NULL,
    forwarder_id INT NOT NULL,
    nama_file_manifest VARCHAR(300),
    path_file_manifest VARCHAR(500),
    no_kontainer VARCHAR(50),
    segel_kontainer VARCHAR(100),
    jenis_kontainer VARCHAR(100),
    status ENUM('Pending','Terkirim','Diterima','Ditolak') DEFAULT 'Pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dokumen_id) REFERENCES dokumen(id),
    FOREIGN KEY (forwarder_id) REFERENCES users(id)
);

CREATE TABLE bill_of_lading (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dokumen_id INT NOT NULL,
    manifest_id INT,
    pelayaran_id INT NOT NULL,
    no_bl VARCHAR(100) UNIQUE,
    status ENUM('Draft','Terbit','Ditolak') DEFAULT 'Draft',
    tanggal_terbit DATE,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dokumen_id) REFERENCES dokumen(id),
    FOREIGN KEY (manifest_id) REFERENCES manifest(id),
    FOREIGN KEY (pelayaran_id) REFERENCES users(id)
);

CREATE TABLE tracking_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dokumen_id INT NOT NULL,
    status_label VARCHAR(200) NOT NULL,
    keterangan TEXT,
    actor_id INT,
    actor_role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dokumen_id) REFERENCES dokumen(id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
);

CREATE TABLE pemeriksaan_fisik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dokumen_id INT NOT NULL,
    manifest_id INT,
    no_kontainer VARCHAR(50),
    ref_id VARCHAR(50),
    status_beacukai ENUM('Menunggu','Approved','Ditolak') DEFAULT 'Menunggu',
    konfirmasi_gudang TINYINT(1) DEFAULT 0,
    konfirmasi_jict TINYINT(1) DEFAULT 0,
    konfirmasi_muat_kapal TINYINT(1) DEFAULT 0,
    gudang_id INT,
    jict_id INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dokumen_id) REFERENCES dokumen(id),
    FOREIGN KEY (gudang_id) REFERENCES users(id),
    FOREIGN KEY (jict_id) REFERENCES users(id)
);

CREATE TABLE beacukai_keputusan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dokumen_id INT NOT NULL,
    beacukai_id INT NOT NULL,
    keputusan ENUM('Approved','Hold/Review') NOT NULL,
    catatan TEXT,
    waktu_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dokumen_id) REFERENCES dokumen(id),
    FOREIGN KEY (beacukai_id) REFERENCES users(id)
);

INSERT INTO users (username, email, password, role, nama_lengkap, perusahaan) VALUES
('customer1',  'customer@demo.com',  '62cc2d8b4bf2d8728120d052163a77df', 'customer',  'Fania Azelia',    'PT Sinar Jaya'),
('customer2',  'customer2@demo.com', '62cc2d8b4bf2d8728120d052163a77df', 'customer',  'Carlos Sainz',     'CV Makmur Abadi'),
('forwarder1', 'forwarder@demo.com', '62cc2d8b4bf2d8728120d052163a77df', 'forwarder', 'Azahrah Forwarder', 'PT Samudra Raya'),
('pelayaran1', 'pelayaran@demo.com', '62cc2d8b4bf2d8728120d052163a77df', 'pelayaran', 'Abil Pelayaran',  'PT Global Sentosa'),
('beacukai1',  'beacukai@demo.com',  '62cc2d8b4bf2d8728120d052163a77df', 'beacukai',  'Hendra BC',       'Bea Cukai Tanjung Priok'),
('gudang1',    'gudang@demo.com',    '62cc2d8b4bf2d8728120d052163a77df', 'gudang',    'Roni Gudang',     'Gudang TPS Jakarta'),
('jict1',      'jict@demo.com',      '62cc2d8b4bf2d8728120d052163a77df', 'jict',      'Tono JICT',       'JICT Terminal'),
('vendor1',    'vendor@demo.com',    '62cc2d8b4bf2d8728120d052163a77df', 'vendor',    'Vendor Logistik', 'PT Vendor Express');

INSERT INTO dokumen (ref_id, customer_id, jenis_dokumen, nama_file, path_file, tipe_kiriman, status_sistem, status_verifikasi_forwarder, status_peb_pib, status_bl_final, forwarder_id) VALUES
('EXP-2025-001', 1, 'Commercial Invoice', 'invoice_001.pdf', 'uploads/invoice_001.pdf', 'Ekspor (PEB)', 'Auto-Check Pending', 'Pending', 'Belum Diajukan', 'Belum Terbit', 3),
('EXP-2025-002', 1, 'Packing List', 'packing_002.pdf', 'uploads/packing_002.pdf', 'Ekspor (PEB)', 'Auto-Check Valid', 'Valid', 'Menunggu Persetujuan', 'Belum Terbit', 3),
('IMP-2025-003', 2, 'Bill of Lading', 'bl_003.pdf', 'uploads/bl_003.pdf', 'Impor (PIB)', 'Dok. Hilang', 'Perlu Perbaikan', 'Belum Diajukan', 'Belum Terbit', 3),
('EXP-2025-004', 1, 'Certificate of Origin', 'coo_004.pdf', 'uploads/coo_004.pdf', 'Ekspor (PEB)', 'Auto-Check Valid', 'Approved', 'Approved', 'Terbit', 3),
('IMP-2025-005', 2, 'Customs Declaration', 'customs_005.pdf', 'uploads/customs_005.pdf', 'Impor (PIB)', 'Gagal', 'Hold', 'Ditolak', 'Ditolak', 3);

INSERT INTO tracking_log (dokumen_id, status_label, keterangan, actor_id, actor_role) VALUES
(1, 'Dokumen Diunggah', 'Customer mengunggah dokumen', 1, 'customer'),
(1, 'Menunggu Verifikasi Forwarder', 'Dokumen dalam antrian verifikasi', 3, 'forwarder'),
(2, 'Dokumen Diunggah', 'Customer mengunggah dokumen', 1, 'customer'),
(2, 'Auto-Check Valid', 'Sistem auto-check berhasil', NULL, 'system'),
(2, 'Verifikasi Forwarder: Valid', 'Forwarder menyatakan dokumen valid', 3, 'forwarder'),
(2, 'Pengajuan PEB Dikirim ke Bea Cukai', 'Forwarder mengajukan PEB', 3, 'forwarder'),
(4, 'Dokumen Diunggah', 'Customer mengunggah dokumen', 1, 'customer'),
(4, 'Auto-Check Valid', 'Sistem auto-check berhasil', NULL, 'system'),
(4, 'Verifikasi Forwarder: Approved', 'Dokumen disetujui', 3, 'forwarder'),
(4, 'Bea Cukai: Approved', 'Pengajuan disetujui bea cukai', 5, 'beacukai'),
(4, 'Bill of Lading Terbit', 'BL final telah diterbitkan', 4, 'pelayaran');

INSERT INTO manifest (dokumen_id, forwarder_id, no_kontainer, segel_kontainer, jenis_kontainer, status) VALUES
(4, 3, 'TCLU 123784', 'SG-99887', '20 feet Dry', 'Diterima'),
(2, 3, 'MSCU 456789', 'SG-11223', '40 feet High Cube', 'Terkirim');

INSERT INTO bill_of_lading (dokumen_id, manifest_id, pelayaran_id, no_bl, status, tanggal_terbit) VALUES
(4, 1, 4, 'BL-2025-001', 'Terbit', '2025-01-15');

INSERT INTO pemeriksaan_fisik (dokumen_id, manifest_id, no_kontainer, ref_id, status_beacukai, konfirmasi_gudang, konfirmasi_jict) VALUES
(4, 1, 'TCLU 123784', 'EXP-2025-004', 'Approved', 1, 1),
(3, NULL, 'FBLU 983672', 'IMP-2025-003', 'Menunggu', 0, 0);

INSERT INTO beacukai_keputusan (dokumen_id, beacukai_id, keputusan, waktu_pengajuan) VALUES
(4, 5, 'Approved', '2025-01-12 10:00:00'),
(2, 5, 'Approved', '2025-01-12 09:30:00'),
(5, 5, 'Hold/Review', '2025-01-12 08:00:00');