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