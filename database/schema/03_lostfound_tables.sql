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