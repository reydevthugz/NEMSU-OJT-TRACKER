CREATE TABLE IF NOT EXISTS adviser_student_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    adviser_user_id BIGINT UNSIGNED NOT NULL,
    student_profile_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_adviser_student_profile (student_profile_id),
    KEY idx_adviser_assignments_adviser_status (adviser_user_id, status),
    CONSTRAINT fk_adviser_assignments_adviser FOREIGN KEY (adviser_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_adviser_assignments_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_adviser_assignments_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    placement_id BIGINT UNSIGNED NOT NULL,
    previous_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_placement_history_placement_created (placement_id, created_at),
    KEY idx_placement_history_actor (actor_user_id),
    CONSTRAINT fk_placement_history_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_placement_history_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE attendance_records
    ADD COLUMN remarks VARCHAR(255) NULL AFTER verified_at;

ALTER TABLE evaluations
    ADD COLUMN remarks VARCHAR(255) NULL AFTER comments;
