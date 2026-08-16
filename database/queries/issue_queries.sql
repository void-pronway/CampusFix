USE campusfix;

SET @student_id = 1;
SET @issue_id = 1;
SET @staff_id = 1;
SET @filter_status = 'Pending';
SET @filter_priority = 'High';
SET @search_text = 'wifi';

SELECT
    id,
    title,
    category_id,
    location_id,
    priority,
    status,
    visibility,
    created_at,
    updated_at
FROM issues
WHERE reported_by = @student_id
ORDER BY created_at DESC;

SELECT
    id,
    reported_by,
    category_id,
    location_id,
    title,
    priority,
    status,
    created_at
FROM issues
ORDER BY created_at DESC;

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
WHERE status = @filter_status
ORDER BY created_at DESC;

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
WHERE priority = @filter_priority
ORDER BY created_at DESC;

SELECT
    COUNT(*) AS confirmation_count
FROM issue_confirmations
WHERE issue_id = @issue_id;

SELECT
    old_status,
    new_status,
    changed_by,
    note,
    changed_at
FROM issue_status_logs
WHERE issue_id = @issue_id
ORDER BY changed_at ASC;

SELECT
    user_id,
    comment,
    created_at
FROM comments
WHERE issue_id = @issue_id
ORDER BY created_at ASC;

SELECT
    ia.id AS assignment_id,
    ia.issue_id,
    i.title,
    i.priority,
    i.status,
    ia.assigned_at,
    ia.resolution_note,
    ia.resolved_at
FROM issue_assignments AS ia
INNER JOIN issues AS i
    ON i.id = ia.issue_id
WHERE ia.staff_id = @staff_id
ORDER BY ia.assigned_at DESC;

SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;

SELECT
    category_id,
    COUNT(*) AS total_issues
FROM issues
GROUP BY category_id
ORDER BY total_issues DESC;

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
ORDER BY created_at DESC
LIMIT 5;

SELECT
    category_id,
    COUNT(*) AS unresolved_issues
FROM issues
WHERE status NOT IN ('Resolved', 'Rejected')
GROUP BY category_id
ORDER BY unresolved_issues DESC;

SELECT
    location_id,
    COUNT(*) AS serious_issue_count
FROM issues
WHERE priority IN ('High', 'Emergency')
  AND status NOT IN ('Resolved', 'Rejected')
GROUP BY location_id
HAVING COUNT(*) >= 2
ORDER BY serious_issue_count DESC;

SELECT
    staff_id,
    COUNT(*) AS assignment_count
FROM issue_assignments
GROUP BY staff_id
ORDER BY assignment_count DESC;

SELECT
    staff_id,
    COUNT(*) AS assignment_count
FROM issue_assignments
GROUP BY staff_id
HAVING COUNT(*) > (
    SELECT AVG(staff_assignment_count)
    FROM (
        SELECT
            COUNT(*) AS staff_assignment_count
        FROM issue_assignments
        GROUP BY staff_id
    ) AS workload_summary
)
ORDER BY assignment_count DESC;

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
    COUNT(ic.id) AS confirmation_count
FROM issues AS i
LEFT JOIN issue_confirmations AS ic
    ON ic.issue_id = i.id
GROUP BY
    i.id,
    i.title
ORDER BY confirmation_count DESC;

SELECT
    id,
    title,
    description,
    priority,
    status,
    created_at
FROM issues
WHERE title LIKE CONCAT('%', @search_text, '%')
   OR description LIKE CONCAT('%', @search_text, '%')
ORDER BY created_at DESC;

SELECT
    AVG(confirmation_count) AS average_confirmations
FROM (
    SELECT
        issue_id,
        COUNT(*) AS confirmation_count
    FROM issue_confirmations
    GROUP BY issue_id
) AS confirmation_summary;

SELECT
    issue_id,
    staff_id,
    assigned_at,
    resolved_at,
    TIMESTAMPDIFF(
        HOUR,
        assigned_at,
        resolved_at
    ) AS resolution_hours
FROM issue_assignments
WHERE resolved_at IS NOT NULL
ORDER BY resolution_hours DESC;