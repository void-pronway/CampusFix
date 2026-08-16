CREATE DATABASE IF NOT EXISTS campusfix
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campusfix;

USE campusfix;

CREATE TABLE IF NOT EXISTS departments (
    dept_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    d_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_id INT UNSIGNED NULL,
    f_name VARCHAR(80) NOT NULL,
    l_name VARCHAR(80) NOT NULL,
    role ENUM('student', 'admin', 'staff') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    email VARCHAR(150) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_department
        FOREIGN KEY (dept_id)
        REFERENCES departments(dept_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_phones (
    user_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,

    PRIMARY KEY (user_id, phone),

    CONSTRAINT fk_user_phones_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS building (
    building_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(120) NOT NULL UNIQUE,
    building_code VARCHAR(20) NOT NULL UNIQUE
);



CREATE TABLE IF NOT EXISTS locations (
    l_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id INT UNSIGNED NOT NULL,
    location_name VARCHAR(120) NULL,
    floor VARCHAR(30) NULL,
    floor_type VARCHAR(50) NULL,
    room_no VARCHAR(30) NULL,

    CONSTRAINT uq_building_location
    UNIQUE (building_id, location_name),

    CONSTRAINT fk_locations_building
        FOREIGN KEY (building_id)
        REFERENCES building(building_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);



CREATE TABLE IF NOT EXISTS issue_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_id INT UNSIGNED NOT NULL,
    reported_by INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,

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
        REFERENCES users(user_id),

    CONSTRAINT fk_issues_location
        FOREIGN KEY (location_id)
        REFERENCES locations(l_id),

    INDEX idx_issues_category (category_id),
    INDEX idx_issues_reporter (reported_by),
    INDEX idx_issues_location (location_id),
    INDEX idx_issues_status (status),
    INDEX idx_issues_priority (priority),
    INDEX idx_issues_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_confirmations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    issue_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,

    confirmed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_issue_confirmations_issue_user
        UNIQUE (issue_id, user_id),

    CONSTRAINT fk_issue_confirmations_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_confirmations_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id),

    INDEX idx_issue_confirmations_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_status_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    issue_id INT UNSIGNED NOT NULL,
    changed_by INT UNSIGNED NOT NULL,

    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    note TEXT NULL,

    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_issue_status_logs_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_status_logs_changed_by
        FOREIGN KEY (changed_by)
        REFERENCES users(user_id),

    INDEX idx_issue_status_logs_issue (issue_id),
    INDEX idx_issue_status_logs_changed_at (changed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS issue_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    issue_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,

    assignment_note TEXT NULL,
    resolution_note TEXT NULL,

    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,

    CONSTRAINT fk_issue_assignments_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_issue_assignments_staff
        FOREIGN KEY (staff_id)
        REFERENCES users(user_id),

    CONSTRAINT fk_issue_assignments_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(user_id),

    INDEX idx_issue_assignments_issue (issue_id),
    INDEX idx_issue_assignments_staff (staff_id),
    INDEX idx_issue_assignments_assigned_by (assigned_by),
    INDEX idx_issue_assignments_assigned_at (assigned_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    issue_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,

    comment TEXT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comments_issue
        FOREIGN KEY (issue_id)
        REFERENCES issues(id),

    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id),

    INDEX idx_comments_issue (issue_id),
    INDEX idx_comments_user (user_id),
    INDEX idx_comments_created_at (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- CampusFix
-- Member 3: Lost & Found + Claims
-- File: database/schema/03_lostfound_tables.sql
-- =========================================================

-- This file is imported after:
-- 00_database.sql
-- 01_core_tables.sql
-- 02_issue_tables.sql


-- =========================================================
-- LOST & FOUND CATEGORIES
-- =========================================================

CREATE TABLE IF NOT EXISTS lost_found_categories (
    item_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL UNIQUE,

    description VARCHAR(255) DEFAULT NULL
);


-- =========================================================
-- LOST & FOUND ITEMS
-- =========================================================

CREATE TABLE IF NOT EXISTS lost_found_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    item_category_id INT UNSIGNED NOT NULL,
    posted_by INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,

    item_type ENUM(
        'Lost',
        'Found'
    ) NOT NULL,

    item_name VARCHAR(150) NOT NULL,

    description TEXT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Returned'
    ) NOT NULL DEFAULT 'Pending',

    item_date DATE NOT NULL,

    image_path VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_lostfound_category
        FOREIGN KEY (item_category_id)
        REFERENCES lost_found_categories(item_category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_lostfound_posted_by
        FOREIGN KEY (posted_by)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_lostfound_location
        FOREIGN KEY (location_id)
        REFERENCES locations(l_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_lostfound_status (status),
    INDEX idx_lostfound_type (item_type),
    INDEX idx_lostfound_category (item_category_id),
    INDEX idx_lostfound_user (posted_by),
    INDEX idx_lostfound_location (location_id),
    INDEX idx_lostfound_date (item_date)
);


-- =========================================================
-- CLAIMS
-- =========================================================

CREATE TABLE IF NOT EXISTS claims (
    claim_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    item_id INT UNSIGNED NOT NULL,

    submitted_by INT UNSIGNED NOT NULL,

    reviewed_by INT UNSIGNED DEFAULT NULL,

    claim_msg TEXT NOT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) NOT NULL DEFAULT 'Pending',

    admin_note TEXT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_claim_item
        FOREIGN KEY (item_id)
        REFERENCES lost_found_items(item_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_claim_submitter
        FOREIGN KEY (submitted_by)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_claim_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_claim_item (item_id),
    INDEX idx_claim_submitter (submitted_by),
    INDEX idx_claim_status (status)
);

USE campusfix;

INSERT IGNORE INTO departments (d_name) VALUES
('Computer Science and Engineering'),
('Software Engineering'),
('Electrical and Electronic Engineering'),
('Business Administration'),
('English');

INSERT IGNORE INTO building (building_name, building_code) VALUES
('Academic Building 1', 'AB1'),
('Academic Building 2', 'AB2'),
('Academic Building 3', 'AB3'),
('Academic Building 4', 'AB4'),
('Administrative Building', 'ADMIN'),
('Library Building', 'LIB'),
('Annex Building', 'ANX'),
('Central Mosque', 'CM'),
('Shadhinota Shommelon Kendro', 'SSK'),
('Fablab', 'FL'),
('Common Campus Areas', 'CCA');

INSERT INTO locations (building_id, location_name)
SELECT b.building_id, places.location_name
FROM building b
JOIN (
    SELECT 'Green Garden' AS location_name
    UNION ALL SELECT 'Green Garden 2'
    UNION ALL SELECT 'Lake Side'
    UNION ALL SELECT 'Transport Depot'
    UNION ALL SELECT 'Bonomaya'
    UNION ALL SELECT 'Bonokanta'
    UNION ALL SELECT 'Food Court'
    UNION ALL SELECT 'Super Shop'
    UNION ALL SELECT 'Gym Center'
) AS places
LEFT JOIN locations l
    ON l.building_id = b.building_id
    AND l.location_name = places.location_name
WHERE b.building_code = 'CCA'
  AND l.l_id IS NULL;

  INSERT IGNORE INTO issue_categories (name) VALUES
('Electrical'),
('Internet'),
('Cleaning'),
('Water'),
('Classroom Equipment'),
('Furniture'),
('Security'),
('Other');

USE campusfix;

INSERT IGNORE INTO users (
    dept_id,
    f_name,
    l_name,
    role,
    status,
    email,
    pass
)
SELECT
    dept_id,
    'CampusFix',
    'Admin',
    'admin',
    'active',
    'admin@campusfix.local',
    '$2y$10$nJpeqGemf2NHvCdocwGKjevqy5hnbsoqY13qyK7K.KXjW5YLoPxsK'
FROM departments
WHERE d_name = 'Computer Science and Engineering'
LIMIT 1;

INSERT IGNORE INTO users (
    dept_id,
    f_name,
    l_name,
    role,
    status,
    email,
    pass
)
SELECT
    dept_id,
    'Test',
    'Staff',
    'staff',
    'active',
    'staff@campusfix.local',
    '$2y$10$nJpeqGemf2NHvCdocwGKjevqy5hnbsoqY13qyK7K.KXjW5YLoPxsK'
FROM departments
WHERE d_name = 'Electrical and Electronic Engineering'
LIMIT 1;

INSERT INTO issues (
    category_id,
    reported_by,
    location_id,
    title,
    description,
    visibility,
    priority,
    status
)
SELECT
    1,
    1,
    1,
    'Broken light at Green Garden',
    'The light near Green Garden is not working properly.',
    'Public',
    'High',
    'Pending'
WHERE NOT EXISTS (
    SELECT 1
    FROM issues
    WHERE title = 'Broken light at Green Garden'
);

USE campusfix;

DROP TRIGGER IF EXISTS trg_issue_status_history;

DELIMITER $$

CREATE TRIGGER trg_issue_status_history
AFTER UPDATE ON issues
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO issue_status_logs (
            issue_id,
            changed_by,
            old_status,
            new_status,
            note
        )
        VALUES (
            NEW.id,
            COALESCE(@changed_by, NEW.reported_by),
            OLD.status,
            NEW.status,
            @status_note
        );
    END IF;
END$$

DELIMITER ;

USE campusfix;

DROP PROCEDURE IF EXISTS assign_issue_to_staff;

DELIMITER $$

CREATE PROCEDURE assign_issue_to_staff(
    IN p_issue_id INT UNSIGNED,
    IN p_staff_id INT UNSIGNED,
    IN p_admin_id INT UNSIGNED,
    IN p_assignment_note TEXT
)
BEGIN
    DECLARE v_issue_exists INT DEFAULT 0;
    DECLARE v_staff_exists INT DEFAULT 0;
    DECLARE v_admin_exists INT DEFAULT 0;
    DECLARE v_issue_status VARCHAR(50);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET @changed_by = NULL;
        SET @status_note = NULL;
        RESIGNAL;
    END;

    SELECT COUNT(*)
    INTO v_issue_exists
    FROM issues
    WHERE id = p_issue_id;

    IF v_issue_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Issue does not exist';
    END IF;

    SELECT COUNT(*)
    INTO v_staff_exists
    FROM users
    WHERE user_id = p_staff_id
      AND role = 'staff'
      AND status = 'active';

    IF v_staff_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Selected user is not an active staff member';
    END IF;

    SELECT COUNT(*)
    INTO v_admin_exists
    FROM users
    WHERE user_id = p_admin_id
      AND role = 'admin'
      AND status = 'active';

    IF v_admin_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Selected user is not an active admin';
    END IF;

    SELECT status
    INTO v_issue_status
    FROM issues
    WHERE id = p_issue_id;

    IF v_issue_status <> 'Pending' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only pending issues can be assigned';
    END IF;

    START TRANSACTION;

    INSERT INTO issue_assignments (
        issue_id,
        staff_id,
        assigned_by,
        assignment_note
    )
    VALUES (
        p_issue_id,
        p_staff_id,
        p_admin_id,
        p_assignment_note
    );

    SET @changed_by = p_admin_id;
    SET @status_note = p_assignment_note;

    UPDATE issues
    SET status = 'Assigned'
    WHERE id = p_issue_id;

    COMMIT;

    SET @changed_by = NULL;
    SET @status_note = NULL;
END$$

DELIMITER ;

USE campusfix;

DROP VIEW IF EXISTS vw_admin_issue_overview;

CREATE VIEW vw_admin_issue_overview AS
SELECT
    i.id AS issue_id,
    i.title,
    i.description,
    i.priority,
    i.status,
    i.created_at,

    CONCAT(u.f_name, ' ', u.l_name) AS reporter_name,
    u.email AS reporter_email,

    c.name AS category_name,

    b.building_name,
    l.location_name,
    l.floor,
    l.room_no,

    ia.staff_id,
    CONCAT(s.f_name, ' ', s.l_name) AS staff_name,

    ia.assigned_by,
    CONCAT(a.f_name, ' ', a.l_name) AS assigned_by_name,

    ia.assigned_at,
    ia.resolution_note,
    ia.resolved_at

FROM issues i

JOIN users u
    ON i.reported_by = u.user_id

JOIN issue_categories c
    ON i.category_id = c.id

JOIN locations l
    ON i.location_id = l.l_id

JOIN building b
    ON l.building_id = b.building_id

LEFT JOIN issue_assignments ia
    ON ia.issue_id = i.id

LEFT JOIN users s
    ON ia.staff_id = s.user_id

LEFT JOIN users a
    ON ia.assigned_by = a.user_id;

USE campusfix;

START TRANSACTION;

SET @changed_by = 3;
SET @status_note = 'Staff started work';

UPDATE issues
SET status = 'In Progress'
WHERE id = 1
  AND status = 'Assigned';

SELECT id, title, status
FROM issues
WHERE id = 1;

SELECT issue_id, changed_by, old_status, new_status, note
FROM issue_status_logs
WHERE issue_id = 1
ORDER BY id DESC
LIMIT 1;

ROLLBACK;

SET @changed_by = NULL;
SET @status_note = NULL;

USE campusfix;

START TRANSACTION;

INSERT INTO issue_categories (name)
VALUES ('Temporary Test Category');

SET @category_id = LAST_INSERT_ID();

SELECT id, name
FROM issue_categories
WHERE id = @category_id;

UPDATE issue_categories
SET name = 'Updated Test Category'
WHERE id = @category_id;

SELECT id, name
FROM issue_categories
WHERE id = @category_id;

DELETE FROM issue_categories
WHERE id = @category_id;

COMMIT;

USE campusfix;

SET @student_id = 1;

SELECT
    i.id,
    i.title,
    i.priority,
    i.status,
    c.name AS category,
    b.building_name,
    l.location_name,
    CONCAT(u.f_name, ' ', u.l_name) AS reporter,
    i.created_at
FROM issues i
JOIN issue_categories c
    ON i.category_id = c.id
JOIN locations l
    ON i.location_id = l.l_id
JOIN building b
    ON l.building_id = b.building_id
JOIN users u
    ON i.reported_by = u.user_id
WHERE i.reported_by = @student_id
ORDER BY i.created_at DESC;

SELECT
    i.id,
    i.title,
    i.status,
    i.priority,
    i.created_at
FROM issues i
ORDER BY i.created_at DESC
LIMIT 5;

-- =========================================================
-- CampusFix
-- Member 3: Lost & Found Query Demonstrations
-- File: database/queries/lostfound_queries.sql
-- =========================================================

-- These queries demonstrate:
-- SELECT
-- WHERE
-- ORDER BY
-- LIMIT
-- JOIN
-- GROUP BY
-- aggregate functions
-- subqueries
-- Lost & Found and Claim reporting


-- =========================================================
-- 1. VIEW ALL APPROVED LOST & FOUND ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_category_id,
    posted_by,
    location_id,
    item_type,
    item_name,
    description,
    status,
    item_date,
    image_path,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
ORDER BY created_at DESC;


-- =========================================================
-- 2. VIEW APPROVED LOST ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_name,
    description,
    location_id,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
  AND item_type = 'Lost'
ORDER BY item_date DESC;


-- =========================================================
-- 3. VIEW APPROVED FOUND ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_name,
    description,
    location_id,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
  AND item_type = 'Found'
ORDER BY item_date DESC;


-- =========================================================
-- 4. SEARCH LOST & FOUND ITEMS BY KEYWORD
-- WHERE + LIKE
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    description,
    status,
    item_date
FROM lost_found_items
WHERE status = 'Approved'
  AND (
        item_name LIKE '%wallet%'
        OR description LIKE '%wallet%'
      )
ORDER BY created_at DESC;


-- =========================================================
-- 5. SHOW MOST RECENT APPROVED ITEMS
-- ORDER BY + LIMIT
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
ORDER BY created_at DESC
LIMIT 10;


-- =========================================================
-- 6. JOIN ITEMS WITH THEIR CATEGORY AND LOCATION
-- INNER JOIN
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status,
    lfi.item_date,
    lfc.item_category_id,
    loc.l_id AS location_id
FROM lost_found_items AS lfi
INNER JOIN lost_found_categories AS lfc
    ON lfc.item_category_id = lfi.item_category_id
INNER JOIN locations AS loc
    ON loc.l_id = lfi.location_id
WHERE lfi.status = 'Approved'
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 7. COUNT ITEMS BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY status
ORDER BY total_items DESC;


-- =========================================================
-- 8. COUNT LOST AND FOUND ITEMS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    item_type,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY item_type
ORDER BY total_items DESC;


-- =========================================================
-- 9. COUNT ITEMS BY CATEGORY
-- JOIN + GROUP BY + COUNT
-- =========================================================

SELECT
    lfc.item_category_id,
    COUNT(lfi.item_id) AS total_items
FROM lost_found_categories AS lfc
LEFT JOIN lost_found_items AS lfi
    ON lfi.item_category_id = lfc.item_category_id
GROUP BY lfc.item_category_id
ORDER BY total_items DESC;


-- =========================================================
-- 10. VIEW ITEMS POSTED BY A SPECIFIC USER
-- WHERE
-- Change user_id value when testing.
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    status,
    item_date,
    created_at
FROM lost_found_items
WHERE posted_by = 1
ORDER BY created_at DESC;


-- =========================================================
-- 11. ADMIN: VIEW ITEMS WAITING FOR APPROVAL
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    posted_by,
    item_type,
    item_name,
    description,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Pending'
ORDER BY created_at ASC;


-- =========================================================
-- 12. VIEW CLAIMS WITH ITEM INFORMATION
-- INNER JOIN
-- =========================================================

SELECT
    c.claim_id,
    c.item_id,
    lfi.item_name,
    lfi.item_type,
    c.submitted_by,
    c.reviewed_by,
    c.claim_msg,
    c.status AS claim_status,
    lfi.status AS item_status,
    c.admin_note,
    c.created_at
FROM claims AS c
INNER JOIN lost_found_items AS lfi
    ON lfi.item_id = c.item_id
ORDER BY c.created_at DESC;


-- =========================================================
-- 13. ADMIN: VIEW PENDING CLAIMS
-- JOIN + WHERE + ORDER BY
-- =========================================================

SELECT
    c.claim_id,
    c.item_id,
    lfi.item_name,
    lfi.item_type,
    c.submitted_by,
    c.claim_msg,
    c.created_at
FROM claims AS c
INNER JOIN lost_found_items AS lfi
    ON lfi.item_id = c.item_id
WHERE c.status = 'Pending'
ORDER BY c.created_at ASC;


-- =========================================================
-- 14. COUNT CLAIMS BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;


-- =========================================================
-- 15. ITEMS THAT HAVE AT LEAST ONE CLAIM
-- SUBQUERY using EXISTS
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status
FROM lost_found_items AS lfi
WHERE EXISTS (
    SELECT 1
    FROM claims AS c
    WHERE c.item_id = lfi.item_id
)
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 16. APPROVED ITEMS WITH NO PENDING CLAIM
-- SUBQUERY using NOT EXISTS
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.item_date
FROM lost_found_items AS lfi
WHERE lfi.status = 'Approved'
  AND NOT EXISTS (
        SELECT 1
        FROM claims AS c
        WHERE c.item_id = lfi.item_id
          AND c.status = 'Pending'
      )
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 17. ITEMS WITH NUMBER OF CLAIMS
-- LEFT JOIN + GROUP BY + COUNT
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
LEFT JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status
ORDER BY total_claims DESC;


-- =========================================================
-- 18. ITEMS RECEIVING MORE THAN ONE CLAIM
-- GROUP BY + HAVING + aggregate
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
INNER JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name
HAVING COUNT(c.claim_id) > 1
ORDER BY total_claims DESC;


-- =========================================================
-- 19. RETURNED ITEMS WITH APPROVED CLAIM
-- JOIN + WHERE
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    c.claim_id,
    c.submitted_by,
    c.reviewed_by,
    c.admin_note,
    c.updated_at AS claim_reviewed_at
FROM lost_found_items AS lfi
INNER JOIN claims AS c
    ON c.item_id = lfi.item_id
WHERE lfi.status = 'Returned'
  AND c.status = 'Approved'
ORDER BY c.updated_at DESC;


-- =========================================================
-- 20. LOST & FOUND SUMMARY
-- AGGREGATE FUNCTIONS
-- =========================================================

SELECT
    COUNT(*) AS total_items,
    SUM(status = 'Pending') AS pending_items,
    SUM(status = 'Approved') AS approved_items,
    SUM(status = 'Rejected') AS rejected_items,
    SUM(status = 'Returned') AS returned_items,
    SUM(item_type = 'Lost') AS lost_items,
    SUM(item_type = 'Found') AS found_items
FROM lost_found_items;

SELECT
    COUNT(*) AS total_issues
FROM issues;


SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;


SELECT
    priority,
    COUNT(*) AS total_issues
FROM issues
GROUP BY priority
ORDER BY total_issues DESC;


SELECT
    ic.id AS category_id,
    ic.name AS category_name,
    COUNT(i.id) AS total_issues
FROM issue_categories AS ic
LEFT JOIN issues AS i
    ON i.category_id = ic.id
GROUP BY
    ic.id,
    ic.name
ORDER BY total_issues DESC;


SELECT
    l.l_id AS location_id,
    COUNT(i.id) AS total_issues
FROM locations AS l
LEFT JOIN issues AS i
    ON i.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_issues DESC;


SELECT
    location_id,
    COUNT(*) AS total_issues
FROM issues
GROUP BY location_id
ORDER BY total_issues DESC
LIMIT 5;


SELECT
    DATE(created_at) AS report_date,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE(created_at)
ORDER BY report_date ASC;


SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS report_month,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY report_month ASC;


SELECT
    COUNT(*) AS total_lostfound_items
FROM lost_found_items;


SELECT
    status,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY status
ORDER BY total_items DESC;


SELECT
    item_type,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY item_type
ORDER BY total_items DESC;


SELECT
    lfc.item_category_id,
    COUNT(lfi.item_id) AS total_items
FROM lost_found_categories AS lfc
LEFT JOIN lost_found_items AS lfi
    ON lfi.item_category_id = lfc.item_category_id
GROUP BY lfc.item_category_id
ORDER BY total_items DESC;


SELECT
    l.l_id AS location_id,
    COUNT(lfi.item_id) AS total_items
FROM locations AS l
LEFT JOIN lost_found_items AS lfi
    ON lfi.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_items DESC;


SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS activity_month,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY activity_month ASC;


SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;


SELECT
    COUNT(*) AS total_claims
FROM claims;


SELECT
    lfi.item_id,
    lfi.item_name,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
LEFT JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name
ORDER BY total_claims DESC
LIMIT 10;


SELECT
    COUNT(*) AS claimed_items
FROM lost_found_items AS lfi
WHERE EXISTS (
    SELECT 1
    FROM claims AS c
    WHERE c.item_id = lfi.item_id
);


SELECT
    COUNT(*) AS total_items,
    SUM(status = 'Pending') AS pending_items,
    SUM(status = 'Approved') AS approved_items,
    SUM(status = 'Rejected') AS rejected_items,
    SUM(status = 'Returned') AS returned_items,
    SUM(item_type = 'Lost') AS lost_items,
    SUM(item_type = 'Found') AS found_items
FROM lost_found_items;


SELECT
    COUNT(*) AS total_claims,
    SUM(status = 'Pending') AS pending_claims,
    SUM(status = 'Approved') AS approved_claims,
    SUM(status = 'Rejected') AS rejected_claims
FROM claims;


SELECT
    'Issues' AS module_name,
    COUNT(*) AS total_records
FROM issues

UNION ALL

SELECT
    'Lost & Found' AS module_name,
    COUNT(*) AS total_records
FROM lost_found_items

UNION ALL

SELECT
    'Claims' AS module_name,
    COUNT(*) AS total_records
FROM claims;


SELECT
    activity_date,
    SUM(issue_count) AS issue_count,
    SUM(lostfound_count) AS lostfound_count,
    SUM(claim_count) AS claim_count
FROM (
    SELECT
        DATE(created_at) AS activity_date,
        COUNT(*) AS issue_count,
        0 AS lostfound_count,
        0 AS claim_count
    FROM issues
    GROUP BY DATE(created_at)

    UNION ALL

    SELECT
        DATE(created_at) AS activity_date,
        0 AS issue_count,
        COUNT(*) AS lostfound_count,
        0 AS claim_count
    FROM lost_found_items
    GROUP BY DATE(created_at)

    UNION ALL

    SELECT
        DATE(created_at) AS activity_date,
        0 AS issue_count,
        0 AS lostfound_count,
        COUNT(*) AS claim_count
    FROM claims
    GROUP BY DATE(created_at)
) AS daily_activity
GROUP BY activity_date
ORDER BY activity_date ASC;


SELECT
    posted_by AS user_id,
    COUNT(*) AS total_posts
FROM lost_found_items
GROUP BY posted_by
ORDER BY total_posts DESC
LIMIT 10;


SELECT
    reported_by AS user_id,
    COUNT(*) AS total_reports
FROM issues
GROUP BY reported_by
ORDER BY total_reports DESC
LIMIT 10;


SELECT
    (SELECT COUNT(*) FROM issues) AS total_issues,

    (
        SELECT COUNT(*)
        FROM lost_found_items
    ) AS total_lostfound_items,

    (
        SELECT COUNT(*)
        FROM lost_found_items
        WHERE status = 'Pending'
    ) AS pending_lostfound_items,

    (
        SELECT COUNT(*)
        FROM claims
        WHERE status = 'Pending'
    ) AS pending_claims;

USE campusfix;

SELECT
    c.name AS category,
    COUNT(i.id) AS unresolved_issues
FROM issue_categories c
JOIN issues i
    ON i.category_id = c.id
WHERE i.status NOT IN ('Resolved', 'Rejected')
GROUP BY c.id, c.name
ORDER BY unresolved_issues DESC
LIMIT 1;

SELECT
    u.user_id,
    CONCAT(u.f_name, ' ', u.l_name) AS staff_name,
    COUNT(ia.id) AS assigned_issues
FROM users u
LEFT JOIN issue_assignments ia
    ON ia.staff_id = u.user_id
WHERE u.role = 'staff'
GROUP BY u.user_id, u.f_name, u.l_name
HAVING COUNT(ia.id) > (
    SELECT AVG(staff_total)
    FROM (
        SELECT
            u2.user_id,
            COUNT(ia2.id) AS staff_total
        FROM users u2
        LEFT JOIN issue_assignments ia2
            ON ia2.staff_id = u2.user_id
        WHERE u2.role = 'staff'
        GROUP BY u2.user_id
    ) AS staff_workloads
)
ORDER BY assigned_issues DESC;

