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