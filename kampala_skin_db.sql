-- Kampala Skin Clinic SQL Schema
-- Run this script in MySQL to create the database structure.

CREATE DATABASE IF NOT EXISTS kampala_skin_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kampala_skin_clinic;

CREATE TABLE IF NOT EXISTS roles (
    role_key VARCHAR(32) PRIMARY KEY,
    role_name VARCHAR(64) NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(64) NOT NULL UNIQUE,
    label VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(32) NOT NULL,
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

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(180) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL,
    avatar VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role) REFERENCES roles(role_key) ON DELETE SET NULL
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
    photo_type ENUM('before','after') NOT NULL,
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
    visit_date DATE NOT NULL DEFAULT CURRENT_DATE,
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
    drug_name VARCHAR(180) NOT NULL,
    sku VARCHAR(80) NOT NULL UNIQUE,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    status VARCHAR(80) NOT NULL DEFAULT 'In Stock',
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE SET NULL,
    FOREIGN KEY (prescribed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drug_effectiveness (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drug_id INT NOT NULL,
    patient_id INT NOT NULL,
    effectiveness ENUM('Effective','Moderate','Ineffective') NOT NULL,
    result_text TEXT NULL,
    reviewed_at DATE NOT NULL DEFAULT CURRENT_DATE,
    FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NULL,
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
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_due DECIMAL(12,2) NOT NULL DEFAULT 0,
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
    role_key VARCHAR(32) NOT NULL,
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
('drugs', 'Drugs'),
('finances', 'Finances'),
('analytics', 'Analytics'),
('settings', 'Settings'),
('permissions', 'Permissions'),
('users', 'Users');

INSERT IGNORE INTO users (full_name, email, password_hash, role, is_active) VALUES
('Clinic Administrator', 'admin@kampalaskin.ug', '$2y$10$bEu/vftW2RVXxaDKQw60Aeddvtwb3yRWcy8tClYq9eeZ24av5Nhyu', 'admin', 1);

INSERT IGNORE INTO role_permissions (role, page_id, can_view, can_insert, can_update, can_delete)
SELECT 'admin', id, 1, 1, 1, 1 FROM pages;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('currency', 'UGX'),
('records_per_page', '25'),
('visible_columns', '[]');

-- Dashboard configuration defaults for each role
-- Insert defaults only if the table exists (safe for older installs)
INSERT IGNORE INTO role_dashboard_config (role_key, section_key, is_enabled, section_order)
SELECT t.role_key, t.section_key, t.is_enabled, t.section_order FROM (
    SELECT 'admin' AS role_key, 'stats_cards' AS section_key, 1 AS is_enabled, 0 AS section_order
    UNION ALL SELECT 'admin','clinic_traffic_chart',1,1
    UNION ALL SELECT 'admin','result_distribution_chart',1,2
    UNION ALL SELECT 'admin','recent_tests_table',1,3
    UNION ALL SELECT 'doctor','stats_cards',1,0
    UNION ALL SELECT 'doctor','clinic_traffic_chart',1,1
    UNION ALL SELECT 'doctor','result_distribution_chart',1,2
    UNION ALL SELECT 'doctor','recent_tests_table',1,3
    UNION ALL SELECT 'nurse','stats_cards',1,0
    UNION ALL SELECT 'nurse','clinic_traffic_chart',0,1
    UNION ALL SELECT 'nurse','result_distribution_chart',1,2
    UNION ALL SELECT 'nurse','recent_tests_table',1,3
    UNION ALL SELECT 'receptionist','stats_cards',1,0
    UNION ALL SELECT 'receptionist','clinic_traffic_chart',1,1
    UNION ALL SELECT 'receptionist','result_distribution_chart',0,2
    UNION ALL SELECT 'receptionist','recent_tests_table',0,3
    UNION ALL SELECT 'records','stats_cards',1,0
    UNION ALL SELECT 'records','clinic_traffic_chart',0,1
    UNION ALL SELECT 'records','result_distribution_chart',1,2
    UNION ALL SELECT 'records','recent_tests_table',1,3
) AS t
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'role_dashboard_config');
