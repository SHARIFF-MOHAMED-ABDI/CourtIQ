-- CourtIQ migration 002: student account approval and maintenance history.
-- Safe to re-run on the XAMPP MariaDB schema; existing records are retained.

DELIMITER //
DROP PROCEDURE IF EXISTS courtiq_migration_002//
CREATE PROCEDURE courtiq_migration_002()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approval_status'
    ) THEN
        ALTER TABLE users
            ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'
            AFTER status;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approval_decided_by'
    ) THEN
        ALTER TABLE users ADD COLUMN approval_decided_by INT NULL AFTER approval_status;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approval_decided_at'
    ) THEN
        ALTER TABLE users ADD COLUMN approval_decided_at DATETIME NULL AFTER approval_decided_by;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_profiles' AND COLUMN_NAME = 'full_name'
    ) THEN
        ALTER TABLE student_profiles ADD COLUMN full_name VARCHAR(150) NULL AFTER user_id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_profiles'
          AND COLUMN_NAME = 'student_number' AND NON_UNIQUE = 0
    ) THEN
        ALTER TABLE student_profiles ADD UNIQUE KEY uq_student_profiles_student_number (student_number);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'approval_status' AND INDEX_NAME = 'idx_users_approval_status'
    ) THEN
        ALTER TABLE users ADD INDEX idx_users_approval_status (approval_status, role);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'approval_decided_by' AND REFERENCED_TABLE_NAME = 'users'
    ) THEN
        ALTER TABLE users
            ADD CONSTRAINT fk_users_approval_decider
            FOREIGN KEY (approval_decided_by) REFERENCES users(id) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'hostel_id'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN hostel_id INT NULL AFTER user_id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'room_id'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN room_id INT NULL AFTER hostel_id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'hostel_name_snapshot'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN hostel_name_snapshot VARCHAR(150) NULL AFTER room_id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'room_number_snapshot'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN room_number_snapshot VARCHAR(50) NULL AFTER hostel_name_snapshot;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'category'
    ) THEN
        ALTER TABLE maintenance_requests
            ADD COLUMN category ENUM('Plumbing', 'Electrical', 'Furniture', 'Cleaning', 'Room Facilities', 'Security', 'Other')
            NOT NULL DEFAULT 'Other' AFTER title;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'reference_no'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN reference_no VARCHAR(100) NULL AFTER id;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'latest_response'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN latest_response TEXT NULL AFTER status;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests' AND COLUMN_NAME = 'resolution_notes'
    ) THEN
        ALTER TABLE maintenance_requests ADD COLUMN resolution_notes TEXT NULL AFTER latest_response;
    END IF;

    UPDATE users SET approval_status = 'approved' WHERE approval_status IS NULL;
    UPDATE maintenance_requests
       SET reference_no = CONCAT('CIQ-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 8, '0'))
     WHERE reference_no IS NULL OR reference_no = '';

    ALTER TABLE maintenance_requests
        MODIFY COLUMN reference_no VARCHAR(100) NOT NULL,
        MODIFY COLUMN status ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending';

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'
          AND COLUMN_NAME = 'reference_no' AND NON_UNIQUE = 0
    ) THEN
        ALTER TABLE maintenance_requests ADD UNIQUE KEY uq_maintenance_reference (reference_no);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'
          AND INDEX_NAME = 'idx_maintenance_filters'
    ) THEN
        ALTER TABLE maintenance_requests ADD INDEX idx_maintenance_filters (status, category, hostel_id);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'
          AND COLUMN_NAME = 'hostel_id' AND REFERENCED_TABLE_NAME = 'hostel_profiles'
    ) THEN
        ALTER TABLE maintenance_requests
            ADD CONSTRAINT fk_maintenance_hostel
            FOREIGN KEY (hostel_id) REFERENCES hostel_profiles(id) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'
          AND COLUMN_NAME = 'room_id' AND REFERENCED_TABLE_NAME = 'rooms'
    ) THEN
        ALTER TABLE maintenance_requests
            ADD CONSTRAINT fk_maintenance_room
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL;
    END IF;

    CREATE TABLE IF NOT EXISTS maintenance_request_history (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        actor_id INT NULL,
        action VARCHAR(40) NOT NULL,
        previous_status ENUM('pending', 'in_progress', 'resolved', 'rejected') NULL,
        new_status ENUM('pending', 'in_progress', 'resolved', 'rejected') NULL,
        response TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_maintenance_history_request (request_id, created_at),
        INDEX idx_maintenance_history_actor (actor_id),
        CONSTRAINT fk_maintenance_history_request
            FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
        CONSTRAINT fk_maintenance_history_actor
            FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    INSERT INTO maintenance_request_history
        (request_id, actor_id, action, previous_status, new_status, response)
    SELECT m.id, NULL, 'baseline', NULL, m.status, 'Current status recorded when maintenance history was enabled.'
    FROM maintenance_requests m
    WHERE NOT EXISTS (
        SELECT 1 FROM maintenance_request_history h
        WHERE h.request_id = m.id AND h.action = 'baseline'
    );
END//
DELIMITER ;

CALL courtiq_migration_002();
DROP PROCEDURE courtiq_migration_002;
