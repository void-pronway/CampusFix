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


