SELECT
    COUNT(*) AS total_issues
FROM issues;

SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;

SELECT
    priority,
    COUNT(*) AS total_issues
FROM issues
GROUP BY priority
ORDER BY total_issues DESC;

SELECT
    ic.id AS category_id,
    ic.name AS category_name,
    COUNT(i.id) AS total_issues
FROM issue_categories AS ic
LEFT JOIN issues AS i
    ON i.category_id = ic.id
GROUP BY
    ic.id,
    ic.name
ORDER BY total_issues DESC;

SELECT
    l.l_id AS location_id,
    COUNT(i.id) AS total_issues
FROM locations AS l
LEFT JOIN issues AS i
    ON i.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_issues DESC;

SELECT
    location_id,
    COUNT(*) AS total_issues
FROM issues
GROUP BY location_id
ORDER BY total_issues DESC
LIMIT 5;

SELECT
    DATE(created_at) AS report_date,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE(created_at)
ORDER BY report_date ASC;

SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS report_month,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY report_month ASC;

SELECT
    COUNT(*) AS total_lostfound_items
FROM lost_found_items;

SELECT
    status,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY status
ORDER BY total_items DESC;

SELECT
    item_type,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY item_type
ORDER BY total_items DESC;

SELECT
    lfc.item_category_id,
    COUNT(lfi.item_id) AS total_items
FROM lost_found_categories AS lfc
LEFT JOIN lost_found_items AS lfi
    ON lfi.item_category_id = lfc.item_category_id
GROUP BY lfc.item_category_id
ORDER BY total_items DESC;

SELECT
    l.l_id AS location_id,
    COUNT(lfi.item_id) AS total_items
FROM locations AS l
LEFT JOIN lost_found_items AS lfi
    ON lfi.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_items DESC;

SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS activity_month,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY activity_month ASC;

SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;

SELECT
    COUNT(*) AS total_claims
FROM claims;

SELECT
    lfi.item_id,
    lfi.item_name,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
LEFT JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name
ORDER BY total_claims DESC
LIMIT 10;

SELECT
    COUNT(*) AS claimed_items
FROM lost_found_items AS lfi
WHERE EXISTS (
    SELECT 1
    FROM claims AS c
    WHERE c.item_id = lfi.item_id
);

SELECT
    COUNT(*) AS total_items,
    SUM(status = 'Pending') AS pending_items,
    SUM(status = 'Approved') AS approved_items,
    SUM(status = 'Rejected') AS rejected_items,
    SUM(status = 'Returned') AS returned_items,
    SUM(item_type = 'Lost') AS lost_items,
    SUM(item_type = 'Found') AS found_items
FROM lost_found_items;

SELECT
    COUNT(*) AS total_claims,
    SUM(status = 'Pending') AS pending_claims,
    SUM(status = 'Approved') AS approved_claims,
    SUM(status = 'Rejected') AS rejected_claims
FROM claims;

SELECT
    'Issues' AS module_name,
    COUNT(*) AS total_records
FROM issues

UNION ALL

SELECT
    'Lost & Found' AS module_name,
    COUNT(*) AS total_records
FROM lost_found_items

UNION ALL

SELECT
    'Claims' AS module_name,
    COUNT(*) AS total_records
FROM claims;

SELECT
    activity_date,
    SUM(issue_count) AS issue_count,
    SUM(lostfound_count) AS lostfound_count,
    SUM(claim_count) AS claim_count
FROM (
    SELECT
        DATE(created_at) AS activity_date,
        COUNT(*) AS issue_count,
        0 AS lostfound_count,
        0 AS claim_count
    FROM issues
    GROUP BY DATE(created_at)

    UNION ALL

    SELECT
        DATE(created_at) AS activity_date,
        0 AS issue_count,
        COUNT(*) AS lostfound_count,
        0 AS claim_count
    FROM lost_found_items
    GROUP BY DATE(created_at)

    UNION ALL

    SELECT
        DATE(created_at) AS activity_date,
        0 AS issue_count,
        0 AS lostfound_count,
        COUNT(*) AS claim_count
    FROM claims
    GROUP BY DATE(created_at)
) AS daily_activity
GROUP BY activity_date
ORDER BY activity_date ASC;

SELECT
    posted_by AS user_id,
    COUNT(*) AS total_posts
FROM lost_found_items
GROUP BY posted_by
ORDER BY total_posts DESC
LIMIT 10;

SELECT
    reported_by AS user_id,
    COUNT(*) AS total_reports
FROM issues
GROUP BY reported_by
ORDER BY total_reports DESC
LIMIT 10;

SELECT
    (SELECT COUNT(*) FROM issues) AS total_issues,
    (
        SELECT COUNT(*)
        FROM lost_found_items
    ) AS total_lostfound_items,
    (
        SELECT COUNT(*)
        FROM lost_found_items
        WHERE status = 'Pending'
    ) AS pending_lostfound_items,
    (
        SELECT COUNT(*)
        FROM claims
        WHERE status = 'Pending'
    ) AS pending_claims;