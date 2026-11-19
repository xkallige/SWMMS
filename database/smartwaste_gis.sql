CREATE DATABASE IF NOT EXISTS smartwaste_gis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartwaste_gis;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('resident','officer','admin') NOT NULL DEFAULT 'resident',
    phone VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_reset_user (user_id),
    INDEX idx_password_reset_expires (expires_at)
) ENGINE=InnoDB;

CREATE TABLE waste_categories (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE communities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    lga VARCHAR(100) NULL,
    description TEXT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    boundary POLYGON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_community_lga_name (lga, name)
) ENGINE=InnoDB;

CREATE TABLE facilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    facility_type ENUM('collection_point','public_bin','disposal_site','recycling_facility','transfer_station') NOT NULL,
    description TEXT NULL,
    address VARCHAR(255) NULL,
    community_id INT UNSIGNED NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    location POINT NOT NULL,
    operational_status ENUM('operational','maintenance','inactive') NOT NULL DEFAULT 'operational',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_facilities_location (location),
    CONSTRAINT fk_facilities_community FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE waste_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reported_by BIGINT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    community_id INT UNSIGNED NULL,
    description TEXT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    location POINT NOT NULL,
    photograph_path VARCHAR(255) NULL,
    status ENUM('pending','under_review','assigned','in_progress','resolved','rejected','duplicate') NOT NULL DEFAULT 'pending',
    assigned_officer_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reports_status_created (status, created_at),
    INDEX idx_reports_reporter (reported_by),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reported_by) REFERENCES users(id),
    CONSTRAINT fk_reports_category FOREIGN KEY (category_id) REFERENCES waste_categories(id),
    CONSTRAINT fk_reports_community FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_officer FOREIGN KEY (assigned_officer_id) REFERENCES users(id) ON DELETE SET NULL,
    SPATIAL INDEX idx_reports_location (location)
) ENGINE=InnoDB;

CREATE TABLE report_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    remarks TEXT NULL,
    cleared_site_photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_updates_report FOREIGN KEY (report_id) REFERENCES waste_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_updates_user FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_updates_report_created (report_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE collection_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    officer_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_tasks_report FOREIGN KEY (report_id) REFERENCES waste_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_officer FOREIGN KEY (officer_id) REFERENCES users(id),
    INDEX idx_tasks_officer_status (officer_id, status)
) ENGINE=InnoDB;

CREATE TABLE collection_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    officer_id BIGINT UNSIGNED NOT NULL,
    collection_date DATE NOT NULL,
    quantity DECIMAL(10,2) NULL,
    quantity_unit VARCHAR(30) NULL,
    remarks TEXT NULL,
    completion_status ENUM('partial','completed') NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_records_task FOREIGN KEY (task_id) REFERENCES collection_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_records_officer FOREIGN KEY (officer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, read_at)
) ENGINE=InnoDB;

CREATE TABLE system_activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_logs_created (created_at)
) ENGINE=InnoDB;

INSERT INTO waste_categories (name) VALUES
('Illegal dumping'), ('Overflowing waste bins'), ('Uncollected household waste'),
('Plastic waste accumulation'), ('Blocked drainage caused by waste'),
('Commercial waste accumulation'), ('Other');

-- Create the first administrator from a local PHP command or phpMyAdmin:
-- INSERT INTO users (full_name, email, password_hash, role)
-- VALUES ('System Administrator', 'admin@example.com', '<PASTE_PASSWORD_HASH>', 'admin');
