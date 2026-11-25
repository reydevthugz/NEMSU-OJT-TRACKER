CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'adviser', 'coordinator', 'supervisor', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_programs_code (code),
    UNIQUE KEY uq_programs_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    program_id BIGINT UNSIGNED NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    year_level TINYINT UNSIGNED NOT NULL,
    preferred_location VARCHAR(180) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_profiles_user (user_id),
    UNIQUE KEY uq_student_profiles_number (student_number),
    KEY idx_student_profiles_program (program_id),
    CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_profiles_program FOREIGN KEY (program_id) REFERENCES programs(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_student_profiles_year CHECK (year_level BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(180) NOT NULL,
    address VARCHAR(255) NULL,
    contact_name VARCHAR(120) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(50) NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_name (name),
    KEY idx_companies_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_supervisors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    supervisor_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_company_supervisor (company_id, supervisor_user_id),
    KEY idx_company_supervisors_user (supervisor_user_id),
    CONSTRAINT fk_company_supervisors_company FOREIGN KEY (company_id) REFERENCES companies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_company_supervisors_user FOREIGN KEY (supervisor_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_positions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    available_slots SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('open', 'closed', 'inactive') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_company_positions_company_status (company_id, status),
    CONSTRAINT fk_company_positions_company FOREIGN KEY (company_id) REFERENCES companies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_company_positions_slots CHECK (available_slots >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_skills_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_profile_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,
    proficiency TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_skills (student_profile_id, skill_id),
    KEY idx_student_skills_skill (skill_id),
    CONSTRAINT fk_student_skills_profile FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_student_skills_proficiency CHECK (proficiency IS NULL OR proficiency BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS position_skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    position_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,
    is_required BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_position_skills (position_id, skill_id),
    KEY idx_position_skills_skill (skill_id),
    CONSTRAINT fk_position_skills_position FOREIGN KEY (position_id) REFERENCES company_positions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_position_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_profile_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    priority TINYINT UNSIGNED NOT NULL,
    status ENUM('active', 'withdrawn') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_preference_priority (student_profile_id, priority),
    KEY idx_student_preferences_company (company_id),
    KEY idx_student_preferences_position (position_id),
    CONSTRAINT fk_student_preferences_profile FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_student_preferences_company FOREIGN KEY (company_id) REFERENCES companies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_student_preferences_position FOREIGN KEY (position_id) REFERENCES company_positions(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_student_preferences_priority CHECK (priority BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_profile_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    required_hours SMALLINT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('pending', 'approved', 'rejected', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_placements_student_status (student_profile_id, status),
    KEY idx_placements_position_status (position_id, status),
    KEY idx_placements_assigned_by (assigned_by),
    KEY idx_placements_approved_by (approved_by),
    CONSTRAINT fk_placements_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_placements_position FOREIGN KEY (position_id) REFERENCES company_positions(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_placements_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_placements_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_placements_hours CHECK (required_hours BETWEEN 1 AND 3000),
    CONSTRAINT chk_placements_dates CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requirement_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_requirement_types_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requirement_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_profile_id BIGINT UNSIGNED NOT NULL,
    requirement_type_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('submitted', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
    reviewed_by BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_requirement_submissions_student_status (student_profile_id, status),
    KEY idx_requirement_submissions_type_status (requirement_type_id, status),
    KEY idx_requirement_submissions_reviewed_by (reviewed_by),
    CONSTRAINT fk_requirement_submissions_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_requirement_submissions_type FOREIGN KEY (requirement_type_id) REFERENCES requirement_types(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_requirement_submissions_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    placement_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME NULL,
    time_out TIME NULL,
    total_hours DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_placement_date (placement_id, attendance_date),
    KEY idx_attendance_status_date (status, attendance_date),
    KEY idx_attendance_verified_by (verified_by),
    CONSTRAINT fk_attendance_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_attendance_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_attendance_hours CHECK (total_hours <= 24.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    placement_id BIGINT UNSIGNED NOT NULL,
    supervisor_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comments TEXT NULL,
    evaluation_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluations_placement_supervisor (placement_id, supervisor_id),
    KEY idx_evaluations_supervisor_date (supervisor_id, evaluation_date),
    CONSTRAINT fk_evaluations_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluations_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_evaluations_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user_read (user_id, read_at),
    KEY idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
