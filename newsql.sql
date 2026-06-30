-- Import this script in phpMyAdmin
-- Database: kampala_skin_clinic (existing database is reused)

CREATE DATABASE IF NOT EXISTS kampala_skin_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kampala_skin_clinic;

SET foreign_key_checks = 0;
DROP TABLE IF EXISTS role_dashboard_config;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS patient_photos;
DROP TABLE IF EXISTS lab_test_files;
DROP TABLE IF EXISTS drug_prescriptions;
DROP TABLE IF EXISTS drug_effectiveness;
DROP TABLE IF EXISTS lab_tests;
DROP TABLE IF EXISTS finances;
DROP TABLE IF EXISTS queue;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS drugs;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS settings;
SET foreign_key_checks = 1;

CREATE TABLE IF NOT EXISTS roles (
  role_key VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
  role_name VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(180) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  page_id INT NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 0,
  can_insert TINYINT(1) NOT NULL DEFAULT 0,
  can_update TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role) REFERENCES roles(role_key) ON DELETE CASCADE,
  FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
  UNIQUE KEY unique_role_page (role, page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_number VARCHAR(60) NOT NULL UNIQUE,
  full_name VARCHAR(180) NOT NULL,
  age INT NOT NULL,
  gender VARCHAR(24) NOT NULL,
  date_of_birth DATE NULL,
  residence VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(180) NULL,
  blood_type VARCHAR(20) NULL,
  sulfa_reactive TINYINT(1) NOT NULL DEFAULT 0,
  penicillin_allergy TINYINT(1) NOT NULL DEFAULT 0,
  latex_allergy TINYINT(1) NOT NULL DEFAULT 0,
  other_allergies TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Active',
  registered_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patient_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  photo_type VARCHAR(60) NOT NULL DEFAULT 'before',
  file_path VARCHAR(255) NOT NULL,
  taken_at DATE NOT NULL,
  uploaded_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NULL,
  visit_type VARCHAR(120) NOT NULL,
  chief_complaint TEXT NULL,
  visit_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NULL,
  visit_id INT NULL,
  queue_status ENUM('Waiting','In Consultation','Completed') NOT NULL DEFAULT 'Waiting',
  priority ENUM('Routine','Priority','Urgent') NOT NULL DEFAULT 'Routine',
  temp_file_number VARCHAR(120) NULL,
  temp_full_name VARCHAR(180) NULL,
  temp_age INT NULL,
  temp_gender VARCHAR(60) NULL,
  temp_residence VARCHAR(180) NULL,
  temp_phone VARCHAR(80) NULL,
  temp_email VARCHAR(180) NULL,
  temp_blood_type VARCHAR(60) NULL,
  temp_sulfa_reactive TINYINT(1) NULL,
  temp_penicillin_allergy TINYINT(1) NULL,
  temp_latex_allergy TINYINT(1) NULL,
  temp_other_allergies TEXT NULL,
  temp_visit_type VARCHAR(120) NULL,
  temp_chief_complaint TEXT NULL,
  assigned_doctor INT NULL,
  assigned_room VARCHAR(60) NULL,
  check_in_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  start_time DATETIME NULL,
  end_time DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_doctor) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drugs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  category VARCHAR(120) NOT NULL,
  description TEXT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_qty INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drug_prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  drug_id INT NOT NULL,
  prescribed_by INT NULL,
  dose VARCHAR(120) NOT NULL,
  duration VARCHAR(80) NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drug_effectiveness (
  id INT AUTO_INCREMENT PRIMARY KEY,
  drug_id INT NOT NULL,
  patient_id INT NOT NULL,
  effectiveness ENUM('Effective','Moderate','Ineffective') NOT NULL,
  result_text TEXT NULL,
  reviewed_at DATE NOT NULL DEFAULT (CURRENT_DATE),
  FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lab_tests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NULL,
  visit_id INT NULL,
  test_name VARCHAR(180) NOT NULL,
  result_status VARCHAR(80) NOT NULL DEFAULT 'Pending',
  result_notes TEXT NULL,
  file_path VARCHAR(255) NULL,
  uploaded_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lab_test_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab_test_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  uploaded_by INT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lab_test_id) REFERENCES lab_tests(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS finances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(120) NOT NULL UNIQUE,
  patient_name VARCHAR(180) NOT NULL,
  category VARCHAR(60) NULL,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(80) PRIMARY KEY,
  `value` TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_dashboard_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  role_key VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  section_key VARCHAR(64) NOT NULL,
  is_enabled BOOLEAN DEFAULT TRUE,
  section_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_key) REFERENCES roles(role_key) ON DELETE CASCADE,
  UNIQUE KEY unique_role_section (role_key, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (role_key, role_name, description) VALUES
('admin', 'Admin', 'Full system administrator'),
('doctor', 'Doctor', 'Clinical doctor access'),
('nurse', 'Nurse', 'Nurse access'),
('receptionist', 'Receptionist', 'Front desk and queue management'),
('records', 'Records', 'Medical records and reporting');

INSERT IGNORE INTO pages (page_key, label) VALUES
('dashboard', 'Dashboard'),
('queue', 'Queue'),
('patients', 'Patients'),
('lab_tests', 'Lab Tests'),
('drugs', 'Drugs'),
('finances', 'Finances'),
('analytics', 'Analytics'),
('permissions', 'Permissions'),
('users', 'Users'),
('settings', 'Settings');

INSERT IGNORE INTO users (full_name, email, password_hash, role, is_active) VALUES
('Clinic Administrator', 'admin@kampalaskin.ug', '$2y$10$bEu/vftW2RVXxaDKQw60Aeddvtwb3yRWcy8tClYq9eeZ24av5Nhyu', 'admin', 1),
('Dr. Sarah Nakato', 'doctor@kampalaskin.ug', '$2y$10$bEu/vftW2RVXxaDKQw60Aeddvtwb3yRWcy8tClYq9eeZ24av5Nhyu', 'doctor', 1),
('Nurse Amina', 'nurse@kampalaskin.ug', '$2y$10$bEu/vftW2RVXxaDKQw60Aeddvtwb3yRWcy8tClYq9eeZ24av5Nhyu', 'nurse', 1),
('Receptionist Mercy', 'receptionist@kampalaskin.ug', '$2y$10$bEu/vftW2RVXxaDKQw60Aeddvtwb3yRWcy8tClYq9eeZ24av5Nhyu', 'receptionist', 1);

INSERT IGNORE INTO role_permissions (role, page_id, can_view, can_insert, can_update, can_delete)
SELECT 'admin', id, 1, 1, 1, 1 FROM pages;

INSERT IGNORE INTO role_permissions (role, page_id, can_view, can_insert, can_update, can_delete)
SELECT 'doctor', id, 1, 1, 1, 0 FROM pages WHERE page_key IN ('dashboard','queue','patients','lab_tests','drugs','finances','analytics','settings');

INSERT IGNORE INTO role_permissions (role, page_id, can_view, can_insert, can_update, can_delete)
SELECT 'nurse', id, 1, 0, 1, 0 FROM pages WHERE page_key IN ('dashboard','queue','patients','lab_tests');

INSERT IGNORE INTO role_permissions (role, page_id, can_view, can_insert, can_update, can_delete)
SELECT 'receptionist', id, 1, 1, 1, 0 FROM pages WHERE page_key IN ('dashboard','queue','patients');

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('currency', 'UGX'),
('records_per_page', '25'),
('visible_columns', '[]');

INSERT IGNORE INTO role_dashboard_config (role_key, section_key, is_enabled, section_order) VALUES
('admin', 'stats_cards', 1, 0),
('admin', 'clinic_traffic_chart', 1, 1),
('admin', 'result_distribution_chart', 1, 2),
('admin', 'recent_tests_table', 1, 3),
('doctor', 'stats_cards', 1, 0),
('doctor', 'clinic_traffic_chart', 1, 1),
('doctor', 'result_distribution_chart', 1, 2),
('doctor', 'recent_tests_table', 1, 3),
('nurse', 'stats_cards', 1, 0),
('nurse', 'clinic_traffic_chart', 0, 1),
('nurse', 'result_distribution_chart', 1, 2),
('nurse', 'recent_tests_table', 1, 3),
('receptionist', 'stats_cards', 1, 0),
('receptionist', 'clinic_traffic_chart', 1, 1),
('receptionist', 'result_distribution_chart', 0, 2),
('receptionist', 'recent_tests_table', 0, 3),
('records', 'stats_cards', 1, 0),
('records', 'clinic_traffic_chart', 0, 1),
('records', 'result_distribution_chart', 1, 2),
('records', 'recent_tests_table', 1, 3);

INSERT IGNORE INTO patients (file_number, full_name, age, gender, residence, phone, email, blood_type, sulfa_reactive, penicillin_allergy, latex_allergy, other_allergies, status, registered_by) VALUES
('KSC-0001', 'John Mugisha', 34, 'Male', 'Kampala', '+256700111111', 'john@example.com', 'O+', 0, 0, 0, '', 'Active', (SELECT id FROM users WHERE email = 'admin@kampalaskin.ug' LIMIT 1)),
('KSC-0002', 'Aisha Namatovu', 28, 'Female', 'Wakiso', '+256700222222', 'aisha@example.com', 'A+', 1, 0, 0, 'Dust', 'Active', (SELECT id FROM users WHERE email = 'admin@kampalaskin.ug' LIMIT 1)),
('KSC-0003', 'David Okello', 41, 'Male', 'Mukono', '+256700333333', 'david@example.com', 'B+', 0, 1, 0, '', 'Active', (SELECT id FROM users WHERE email = 'admin@kampalaskin.ug' LIMIT 1));

INSERT IGNORE INTO visits (patient_id, doctor_id, visit_type, chief_complaint, visit_date, notes)
SELECT p.id, u.id, 'Skin Consultation', 'Rash and itching on arms', CURDATE(), 'Initial assessment performed'
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0001';

INSERT IGNORE INTO visits (patient_id, doctor_id, visit_type, chief_complaint, visit_date, notes)
SELECT p.id, u.id, 'Follow-Up', 'Recurring acne flare-ups', CURDATE(), 'Continue treatment plan'
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0002';

INSERT IGNORE INTO visits (patient_id, doctor_id, visit_type, chief_complaint, visit_date, notes)
SELECT p.id, u.id, 'Dermatology Review', 'Dry skin and irritation', CURDATE(), 'Patient advised hydration and moisturiser'
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0003';

INSERT IGNORE INTO queue (patient_id, queue_status, priority, temp_full_name, temp_age, temp_gender, temp_visit_type, temp_chief_complaint, assigned_doctor, check_in_time)
SELECT p.id, 'Waiting', 'Routine', p.full_name, p.age, p.gender, 'Skin Consultation', 'Rash and itching on arms', u.id, NOW()
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0001';

INSERT IGNORE INTO queue (patient_id, queue_status, priority, temp_full_name, temp_age, temp_gender, temp_visit_type, temp_chief_complaint, assigned_doctor, check_in_time)
SELECT p.id, 'In Consultation', 'Priority', p.full_name, p.age, p.gender, 'Follow-Up', 'Acne flare-ups', u.id, NOW()
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0002';

INSERT IGNORE INTO queue (patient_id, queue_status, priority, temp_full_name, temp_age, temp_gender, temp_visit_type, temp_chief_complaint, assigned_doctor, check_in_time)
SELECT p.id, 'Completed', 'Urgent', p.full_name, p.age, p.gender, 'Dermatology Review', 'Dry skin', u.id, NOW()
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0003';

INSERT IGNORE INTO drugs (name, category, description, unit_price, stock_qty, reorder_level, is_active) VALUES
('Hydrocortisone Cream', 'Topical', 'Anti-inflammatory cream for skin irritation', 12000.00, 25, 10, 1),
('Cetirizine', 'Oral', 'Antihistamine for allergy relief', 8500.00, 40, 15, 1),
('Retinoic Gel', 'Topical', 'Treatment for acne and skin renewal', 18000.00, 18, 8, 1);

INSERT IGNORE INTO drug_prescriptions (patient_id, drug_id, prescribed_by, dose, duration, notes)
SELECT p.id, d.id, u.id, 'Apply twice daily', '7 days', 'Use on irritated areas only'
FROM patients p
JOIN drugs d ON d.name = 'Hydrocortisone Cream'
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0001';

INSERT IGNORE INTO drug_prescriptions (patient_id, drug_id, prescribed_by, dose, duration, notes)
SELECT p.id, d.id, u.id, 'Apply nightly', '14 days', 'Avoid sun exposure'
FROM patients p
JOIN drugs d ON d.name = 'Retinoic Gel'
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0002';

INSERT IGNORE INTO drug_prescriptions (patient_id, drug_id, prescribed_by, dose, duration, notes)
SELECT p.id, d.id, u.id, '1 tablet daily', '5 days', 'Take after meals'
FROM patients p
JOIN drugs d ON d.name = 'Cetirizine'
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
WHERE p.file_number = 'KSC-0003';

INSERT IGNORE INTO lab_tests (patient_id, doctor_id, visit_id, test_name, result_status, result_notes, uploaded_by)
SELECT p.id, u.id, v.id, 'KOH Prep', 'Completed', 'No fungal elements seen', u.id
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
LEFT JOIN visits v ON v.patient_id = p.id AND v.visit_type = 'Skin Consultation'
WHERE p.file_number = 'KSC-0001';

INSERT IGNORE INTO lab_tests (patient_id, doctor_id, visit_id, test_name, result_status, result_notes, uploaded_by)
SELECT p.id, u.id, v.id, 'CBC', 'Pending', 'Awaiting result upload', u.id
FROM patients p
JOIN users u ON u.email = 'doctor@kampalaskin.ug'
LEFT JOIN visits v ON v.patient_id = p.id AND v.visit_type = 'Follow-Up'
WHERE p.file_number = 'KSC-0002';

INSERT IGNORE INTO finances (invoice_number, patient_name, category, amount_paid, amount_due, paid, notes) VALUES
('INV-1001', 'John Mugisha', 'Consultation', 50000.00, 0.00, 1, 'Paid consultation fee'),
('INV-1002', 'Aisha Namatovu', 'Consultation', 35000.00, 0.00, 1, 'Paid follow-up consultation'),
('INV-1003', 'David Okello', 'Consultation', 60000.00, 15000.00, 0, 'Pending balance');
