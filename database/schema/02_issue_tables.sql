-- =========================================================
-- CampusFix - Issue Management Tables
-- Member 2
-- =========================================================

CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,
    reported_by INT NOT NULL,
    location_id INT NOT NULL,

    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,

    visibility ENUM('Public', 'Private')
        NOT NULL DEFAULT 'Public',

    priority ENUM('Low', 'Medium', 'High', 'Emergency')
        NOT NULL DEFAULT 'Medium',

    status ENUM(
        'Pending',
        'Assigned',
        'In Progress',
        'Resolved',
        'Rejected'
    ) NOT NULL DEFAULT 'Pending',

    image_path VARCHAR(255) NULL,
    rejection_note TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_issues_category
        FOREIGN KEY (category_id)
        REFERENCES issue_categories(id),

    CONSTRAINT fk_issues_reporter
        FOREIGN KEY (reported_by)
        REFERENCES users(id),

    CONSTRAINT fk_issues_location
        FOREIGN KEY (location_id)
        REFERENCES locations(location_id),

    INDEX idx_issues_category (category_id),
    INDEX idx_issues_reporter (reported_by),
    INDEX idx_issues_location (location_id),
    INDEX idx_issues_status (status),
    INDEX idx_issues_priority (priority),
    INDEX idx_issues_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_confirmations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,
    user_id INT NOT NULL,

    confirmed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_issue_confirmations_issue_user
        UNIQUE (issue_id, user_id),

    CONSTRAINT fk_issue_confirmations_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_confirmations_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    INDEX idx_issue_confirmations_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,
    changed_by INT NOT NULL,

    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    note TEXT NULL,

    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_issue_status_logs_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_status_logs_changed_by
        FOREIGN KEY (changed_by)
        REFERENCES users(id),

    INDEX idx_issue_status_logs_issue (issue_id),
    INDEX idx_issue_status_logs_changed_at (changed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,
    staff_id INT NOT NULL,
    assigned_by INT NOT NULL,

    assignment_note TEXT NULL,
    resolution_note TEXT NULL,

    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,

    CONSTRAINT fk_issue_assignments_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_assignments_staff
        FOREIGN KEY (staff_id)
        REFERENCES users(id),

    CONSTRAINT fk_issue_assignments_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(id),

    INDEX idx_issue_assignments_issue (issue_id),
    INDEX idx_issue_assignments_staff (staff_id),
    INDEX idx_issue_assignments_assigned_by (assigned_by),
    INDEX idx_issue_assignments_assigned_at (assigned_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,
    user_id INT NOT NULL,

    comment TEXT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comments_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    INDEX idx_comments_issue (issue_id),
    INDEX idx_comments_user (user_id),
    INDEX idx_comments_created_at (created_at)
) ENGINE=InnoDB;
