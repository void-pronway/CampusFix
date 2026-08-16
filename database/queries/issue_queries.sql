-- =========================================================
-- CampusFix - Issue Management Queries
-- Member 2
-- =========================================================


-- ---------------------------------------------------------
-- Demo parameters
-- Change these values to match available sample data.
-- ---------------------------------------------------------

SET @student_id = 1;
SET @issue_id = 1;
SET @staff_id = 1;
SET @filter_status = 'Pending';
SET @filter_priority = 'High';


-- ---------------------------------------------------------
-- 1. Student: View own issues
-- ---------------------------------------------------------

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


-- ---------------------------------------------------------
-- 2. Admin: View all issues
-- ---------------------------------------------------------

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


-- ---------------------------------------------------------
-- 3. Filter issues by status
-- ---------------------------------------------------------

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
WHERE status = @filter_status
ORDER BY created_at DESC;


-- ---------------------------------------------------------
-- 4. Filter issues by priority
-- ---------------------------------------------------------

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
WHERE priority = @filter_priority
ORDER BY created_at DESC;


-- ---------------------------------------------------------
-- 5. Count confirmations for an issue
-- ---------------------------------------------------------

SELECT
    COUNT(*) AS confirmation_count
FROM issue_confirmations
WHERE issue_id = @issue_id;


-- ---------------------------------------------------------
-- 6. View issue status history
-- ---------------------------------------------------------

SELECT
    old_status,
    new_status,
    changed_by,
    note,
    changed_at
FROM issue_status_logs
WHERE issue_id = @issue_id
ORDER BY changed_at ASC;


-- ---------------------------------------------------------
-- 7. View comments for an issue
-- ---------------------------------------------------------

SELECT
    user_id,
    comment,
    created_at
FROM comments
WHERE issue_id = @issue_id
ORDER BY created_at ASC;


-- ---------------------------------------------------------
-- 8. View assignments for a staff member
-- ---------------------------------------------------------

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


-- ---------------------------------------------------------
-- 9. Issue counts grouped by status
-- ---------------------------------------------------------

SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;


-- ---------------------------------------------------------
-- 10. Issue counts grouped by category
-- ---------------------------------------------------------

SELECT
    category_id,
    COUNT(*) AS total_issues
FROM issues
GROUP BY category_id
ORDER BY total_issues DESC;


-- ---------------------------------------------------------
-- 11. Show the 5 most recent issues
-- Demonstrates LIMIT
-- ---------------------------------------------------------

SELECT
    id,
    title,
    priority,
    status,
    created_at
FROM issues
ORDER BY created_at DESC
LIMIT 5;


-- ---------------------------------------------------------
-- 12. Unresolved issue count by category
-- Investigation: Which category has the most unresolved issues?
-- ---------------------------------------------------------

SELECT
    category_id,
    COUNT(*) AS unresolved_issues
FROM issues
WHERE status NOT IN ('Resolved', 'Rejected')
GROUP BY category_id
ORDER BY unresolved_issues DESC;


-- ---------------------------------------------------------
-- 13. Locations with multiple high/emergency issues
-- Demonstrates GROUP BY + HAVING
-- ---------------------------------------------------------

SELECT
    location_id,
    COUNT(*) AS serious_issue_count
FROM issues
WHERE priority IN ('High', 'Emergency')
  AND status NOT IN ('Resolved', 'Rejected')
GROUP BY location_id
HAVING COUNT(*) >= 2
ORDER BY serious_issue_count DESC;


-- ---------------------------------------------------------
-- 14. Staff workload
-- Shows number of assignments handled by each staff member
-- ---------------------------------------------------------

SELECT
    staff_id,
    COUNT(*) AS assignment_count
FROM issue_assignments
GROUP BY staff_id
ORDER BY assignment_count DESC;


-- ---------------------------------------------------------
-- 15. Staff whose workload is above the average
-- Demonstrates a subquery
-- ---------------------------------------------------------

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


-- ---------------------------------------------------------
-- 16. Issues with the highest community confirmations
-- ---------------------------------------------------------

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


-- ---------------------------------------------------------
-- 17. Search issue titles and descriptions
-- ---------------------------------------------------------

SET @search_text = 'wifi';

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


-- ---------------------------------------------------------
-- 18. Average number of confirmations per confirmed issue
-- Demonstrates aggregate AVG
-- ---------------------------------------------------------

SELECT
    AVG(confirmation_count) AS average_confirmations
FROM (
    SELECT
        issue_id,
        COUNT(*) AS confirmation_count
    FROM issue_confirmations
    GROUP BY issue_id
) AS confirmation_summary;


-- ---------------------------------------------------------
-- 19. Resolved assignment duration in hours
-- ---------------------------------------------------------

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
