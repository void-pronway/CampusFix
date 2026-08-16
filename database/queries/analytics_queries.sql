USE campusfix;

SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;

SELECT
    c.name AS category,
    COUNT(i.id) AS total_issues
FROM issue_categories c
LEFT JOIN issues i
    ON i.category_id = c.id
GROUP BY c.id, c.name
ORDER BY total_issues DESC;

SELECT
    CONCAT(u.f_name, ' ', u.l_name) AS staff_name,
    COUNT(ia.id) AS assigned_issues
FROM users u
LEFT JOIN issue_assignments ia
    ON ia.staff_id = u.user_id
WHERE u.role = 'staff'
GROUP BY u.user_id, u.f_name, u.l_name
ORDER BY assigned_issues DESC;