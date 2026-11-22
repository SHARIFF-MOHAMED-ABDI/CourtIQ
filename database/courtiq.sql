-- ==========================================================
-- CourtIQ: A Web-Based Hostel Management System
-- Database Schema and Initial Seed Data
-- ==========================================================

-- ----------------------------------------------------------
-- 1. Users Table (Authentication & Role-Based Access Control)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(80) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'warden', 'administrator') NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `approval_decided_by` INT DEFAULT NULL,
    `approval_decided_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_approval_status` (`approval_status`, `role`),
    FOREIGN KEY (`approval_decided_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Development seed accounts use password hashes; reset them before deployment.
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'admin', 'admin@courtiq.local', '$2y$10$lhk7EUrx5NdgqC4xc8dPA.3ilqzqKPfKeCi6cfO5W6MNJutb4FAQO', 'administrator', 'active'),
(2, 'warden', 'warden@courtiq.local', '$2y$10$eo9SyBR6sNlCj4.3zwQl6utzep31OEJmX0Owyn1guoNIl3.M/t9hm', 'warden', 'active'),
(3, 'student', 'student@courtiq.local', '$2y$10$9uOWikshu2OyV.fB6LO33eZ3eI2hF3m0CfDjX42AgcoIMfmf3Fugi', 'student', 'active')
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

-- ----------------------------------------------------------
-- 2. Hostel Profiles Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `location` VARCHAR(180) DEFAULT NULL,
    `capacity` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `hostel_profiles` (`id`, `name`, `location`, `capacity`) VALUES
(1, 'Qwetu', 'North Residential Quad', 120),
(2, 'Periwana', 'East Residential Wing', 150),
(3, 'Queens', 'West Residential Complex', 90)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `location` = VALUES(`location`),
    `capacity` = VALUES(`capacity`);

-- ----------------------------------------------------------
-- 3. Rooms Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hostel_id` INT NOT NULL,
    `room_number` VARCHAR(50) NOT NULL,
    `room_type` VARCHAR(60) DEFAULT 'Standard',
    `capacity` INT NOT NULL DEFAULT 1,
    `occupied` INT NOT NULL DEFAULT 0,
    `status` ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`hostel_id`) REFERENCES `hostel_profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rooms` (`id`, `hostel_id`, `room_number`, `room_type`, `capacity`, `occupied`, `status`) VALUES
(1, 1, 'A-101', 'Single Deluxe', 1, 1, 'occupied'),
(2, 1, 'A-102', 'Double Room', 2, 1, 'available'),
(3, 1, 'A-103', 'Double Room', 2, 2, 'occupied'),
(4, 1, 'A-104', 'Single Standard', 1, 0, 'available'),
(5, 2, 'K-201', 'Standard Quad', 4, 3, 'available'),
(6, 2, 'K-202', 'Double Room', 2, 0, 'maintenance')
ON DUPLICATE KEY UPDATE
    `room_number` = VALUES(`room_number`),
    `status` = VALUES(`status`);

-- ----------------------------------------------------------
-- 4. Accommodation Applications Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accommodation_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `hostel_preference` VARCHAR(150) NOT NULL,
    `room_type` VARCHAR(60) NOT NULL,
    `application_status` ENUM('submitted', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `accommodation_applications` (`id`, `user_id`, `hostel_preference`, `room_type`, `application_status`, `notes`) VALUES
(1, 3, 'Qwetu', 'Single Standard', 'approved', 'Application approved for the current academic year.')
ON DUPLICATE KEY UPDATE
    `application_status` = VALUES(`application_status`);

-- ----------------------------------------------------------
-- 5. Maintenance Requests Table
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference_no` VARCHAR(100) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `hostel_id` INT DEFAULT NULL,
    `room_id` INT DEFAULT NULL,
    `hostel_name_snapshot` VARCHAR(150) DEFAULT NULL,
    `room_number_snapshot` VARCHAR(50) DEFAULT NULL,
    `title` VARCHAR(180) NOT NULL,
    `category` ENUM('Plumbing', 'Electrical', 'Furniture', 'Cleaning', 'Room Facilities', 'Security', 'Other') NOT NULL DEFAULT 'Other',
    `description` TEXT NOT NULL,
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
    `latest_response` TEXT DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`hostel_id`) REFERENCES `hostel_profiles`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL,
    INDEX `idx_maintenance_hostel` (`hostel_id`, `status`),
    INDEX `idx_maintenance_filters` (`status`, `category`, `hostel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `maintenance_requests` (`id`, `reference_no`, `user_id`, `hostel_id`, `room_id`, `hostel_name_snapshot`, `room_number_snapshot`, `title`, `category`, `description`, `priority`, `status`) VALUES
(1, 'CIQ-SEED-0001', 3, 1, 4, 'Qwetu', 'A-104', 'Study Light Bulb Flickering', 'Electrical', 'Ceiling study light bulb flickering in Room A-104 study desk.', 'medium', 'in_progress'),
(2, 'CIQ-SEED-0002', 3, 1, 1, 'Qwetu', 'A-101', 'Water Basin Tap Inspection', 'Plumbing', 'Bathroom basin tap has a minor drip after closing.', 'low', 'pending')
ON DUPLICATE KEY UPDATE
    `status` = VALUES(`status`),
    `hostel_id` = VALUES(`hostel_id`);

CREATE TABLE IF NOT EXISTS `maintenance_request_history` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `actor_id` INT DEFAULT NULL,
    `action` VARCHAR(40) NOT NULL,
    `previous_status` ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT NULL,
    `new_status` ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT NULL,
    `response` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_maintenance_history_request` (`request_id`, `created_at`),
    INDEX `idx_maintenance_history_actor` (`actor_id`),
    FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 6. Payment Records Table (Tracks Accommodation Payment Records)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `reference_no` VARCHAR(60) NOT NULL UNIQUE,
    `amount` DECIMAL(10, 2) NOT NULL,
    `balance` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('recorded', 'verified', 'pending_audit') NOT NULL DEFAULT 'recorded',
    `payment_date` DATE NOT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 7. Student profiles, assignments, notifications and audit
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `full_name` VARCHAR(150) DEFAULT NULL,
    `student_number` VARCHAR(50) DEFAULT NULL UNIQUE,
    `phone` VARCHAR(30) DEFAULT NULL,
    `course` VARCHAR(150) DEFAULT NULL,
    `year_of_study` TINYINT UNSIGNED DEFAULT NULL,
    `emergency_contact` VARCHAR(180) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `warden_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `warden_id` INT NOT NULL,
    `hostel_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `uq_active_warden_hostel` (`warden_id`, `hostel_id`, `active`),
    FOREIGN KEY (`warden_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`hostel_id`) REFERENCES `hostel_profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `warden_assignments` (`warden_id`, `hostel_id`, `active`) VALUES
(2, 1, 1)
ON DUPLICATE KEY UPDATE
    `active` = VALUES(`active`),
    `assigned_at` = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS `room_allocations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `allocated_by` INT NOT NULL,
    `status` ENUM('active', 'ended') NOT NULL DEFAULT 'active',
    `allocated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ended_at` TIMESTAMP NULL DEFAULT NULL,
    `active_student_id` INT GENERATED ALWAYS AS (IF(`status` = 'active', `student_id`, NULL)) STORED,
    UNIQUE KEY `uq_active_student` (`active_student_id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`allocated_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_allocations_room_status` (`room_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(80) NOT NULL,
    `entity_id` INT DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payment_records` (`id`, `user_id`, `reference_no`, `amount`, `balance`, `status`, `payment_date`, `notes`) VALUES
(1, 3, 'REC-2026-0042', 45000.00, 15000.00, 'recorded', '2026-09-01', 'Term 1 Accommodation Deposit')
ON DUPLICATE KEY UPDATE
    `amount` = VALUES(`amount`);
