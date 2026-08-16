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