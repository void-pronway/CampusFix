USE campusfix;

SELECT
    ic.name AS category_name,
    COUNT(i.id) AS unresolved_issues
FROM issues AS i
JOIN issue_categories AS ic
    ON i.category_id = ic.id
WHERE i.status NOT IN ('Resolved', 'Rejected')
GROUP BY ic.id, ic.name
ORDER BY unresolved_issues DESC
LIMIT 1;


SELECT
    b.building_name,
    l.location_name,
    COUNT(i.id) AS high_priority_issues
FROM issues AS i
JOIN locations AS l
    ON i.location_id = l.l_id
JOIN building AS b
    ON l.building_id = b.building_id
WHERE i.priority IN ('High', 'Emergency')
GROUP BY
    b.building_id,
    b.building_name,
    l.l_id,
    l.location_name
ORDER BY high_priority_issues DESC
LIMIT 1;


SELECT
    u.user_id,
    CONCAT(u.f_name, ' ', u.l_name) AS staff_name,
    COUNT(ia.id) AS active_assignments
FROM issue_assignments AS ia
JOIN users AS u
    ON ia.staff_id = u.user_id
JOIN issues AS i
    ON ia.issue_id = i.id
WHERE u.role = 'staff'
  AND i.status NOT IN ('Resolved', 'Rejected')
GROUP BY
    u.user_id,
    u.f_name,
    u.l_name
ORDER BY active_assignments DESC
LIMIT 1;


SELECT
    status,
    COUNT(*) AS total_claims,
    ROUND(
        COUNT(*) * 100.0 /
        NULLIF((SELECT COUNT(*) FROM claims), 0),
        2
    ) AS percentage
FROM claims
GROUP BY status
ORDER BY total_claims DESC;