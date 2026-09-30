-- Migration: tambah tabel untuk fitur Monitoring Progres Laporan Magang
-- Jalankan di MySQL/MariaDB

CREATE TABLE IF NOT EXISTS uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  doc_key VARCHAR(100) DEFAULT NULL,
  chapter TINYINT NULL,
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(512) NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id),
  INDEX (doc_key),
  INDEX (chapter)
);

CREATE TABLE IF NOT EXISTS chapter_guides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chapter TINYINT NOT NULL,
  content TEXT NOT NULL,
  updated_by INT DEFAULT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (chapter)
);

CREATE TABLE IF NOT EXISTS revisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chapter TINYINT NOT NULL,
  note TEXT NOT NULL,
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS revision_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  revision_id INT NOT NULL,
  student_id INT NOT NULL,
  is_done TINYINT(1) DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (revision_id, student_id),
  FOREIGN KEY (revision_id) REFERENCES revisions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS final_uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chapter TINYINT NOT NULL,
  upload_id INT NOT NULL,
  uploaded_by INT NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS supporting_docs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doc_key VARCHAR(100) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  required TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS supporting_doc_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doc_id INT NOT NULL,
  student_id INT NOT NULL,
  is_checked TINYINT(1) DEFAULT 0,
  checked_at DATETIME NULL,
  UNIQUE KEY (doc_id, student_id),
  FOREIGN KEY (doc_id) REFERENCES supporting_docs(id) ON DELETE CASCADE
);