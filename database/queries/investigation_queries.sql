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