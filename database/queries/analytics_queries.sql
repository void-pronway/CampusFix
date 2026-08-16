-- =========================================================
-- CampusFix
-- Member 3: Dashboard & Analytics Query Demonstrations
-- File: database/queries/analytics_queries.sql
-- =========================================================

-- These queries provide backend data for dashboard analytics.
-- They demonstrate:
-- COUNT
-- SUM
-- GROUP BY
-- ORDER BY
-- LIMIT
-- JOIN
-- LEFT JOIN
-- subqueries
-- UNION ALL
-- DATE / DATE_FORMAT


-- =========================================================
-- 1. OVERALL ISSUE COUNT
-- =========================================================

SELECT
    COUNT(*) AS total_issues
FROM issues;


-- =========================================================
-- 2. ISSUE COUNT BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_issues
FROM issues
GROUP BY status
ORDER BY total_issues DESC;


-- =========================================================
-- 3. ISSUE COUNT BY PRIORITY
-- GROUP BY + COUNT
-- =========================================================

SELECT
    priority,
    COUNT(*) AS total_issues
FROM issues
GROUP BY priority
ORDER BY total_issues DESC;


-- =========================================================
-- 4. ISSUE COUNT BY CATEGORY
-- JOIN + GROUP BY + COUNT
-- =========================================================

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


-- =========================================================
-- 5. ISSUE COUNT BY LOCATION
-- JOIN + GROUP BY + COUNT
-- =========================================================

SELECT
    l.l_id AS location_id,
    COUNT(i.id) AS total_issues
FROM locations AS l
LEFT JOIN issues AS i
    ON i.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_issues DESC;


-- =========================================================
-- 6. TOP 5 LOCATIONS WITH MOST ISSUES
-- GROUP BY + ORDER BY + LIMIT
-- =========================================================

SELECT
    location_id,
    COUNT(*) AS total_issues
FROM issues
GROUP BY location_id
ORDER BY total_issues DESC
LIMIT 5;


-- =========================================================
-- 7. DAILY ISSUE CREATION TREND
-- DATE + GROUP BY
-- =========================================================

SELECT
    DATE(created_at) AS report_date,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE(created_at)
ORDER BY report_date ASC;


-- =========================================================
-- 8. MONTHLY ISSUE CREATION TREND
-- DATE_FORMAT + GROUP BY
-- =========================================================

SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS report_month,
    COUNT(*) AS total_issues
FROM issues
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY report_month ASC;


-- =========================================================
-- 9. LOST & FOUND TOTAL ITEM COUNT
-- =========================================================

SELECT
    COUNT(*) AS total_lostfound_items
FROM lost_found_items;


-- =========================================================
-- 10. LOST & FOUND ITEMS BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY status
ORDER BY total_items DESC;


-- =========================================================
-- 11. LOST VS FOUND ITEM COUNT
-- GROUP BY + COUNT
-- =========================================================

SELECT
    item_type,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY item_type
ORDER BY total_items DESC;


-- =========================================================
-- 12. LOST & FOUND ITEMS BY CATEGORY
-- JOIN + GROUP BY
-- =========================================================

SELECT
    lfc.item_category_id,
    COUNT(lfi.item_id) AS total_items
FROM lost_found_categories AS lfc
LEFT JOIN lost_found_items AS lfi
    ON lfi.item_category_id = lfc.item_category_id
GROUP BY lfc.item_category_id
ORDER BY total_items DESC;


-- =========================================================
-- 13. LOST & FOUND ITEMS BY LOCATION
-- JOIN + GROUP BY
-- =========================================================

SELECT
    l.l_id AS location_id,
    COUNT(lfi.item_id) AS total_items
FROM locations AS l
LEFT JOIN lost_found_items AS lfi
    ON lfi.location_id = l.l_id
GROUP BY l.l_id
ORDER BY total_items DESC;


-- =========================================================
-- 14. MONTHLY LOST & FOUND ACTIVITY
-- DATE_FORMAT + GROUP BY
-- =========================================================

SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS activity_month,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY activity_month ASC;


-- =========================================================
-- 15. CLAIM COUNT BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;


-- =========================================================
-- 16. TOTAL NUMBER OF CLAIMS
-- =========================================================

SELECT
    COUNT(*) AS total_claims
FROM claims;


-- =========================================================
-- 17. ITEMS WITH MOST CLAIMS
-- JOIN + GROUP BY + ORDER BY + LIMIT
-- =========================================================

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


-- =========================================================
-- 18. COUNT OF ITEMS THAT HAVE AT LEAST ONE CLAIM
-- SUBQUERY + EXISTS
-- =========================================================

SELECT
    COUNT(*) AS claimed_items
FROM lost_found_items AS lfi
WHERE EXISTS (
    SELECT 1
    FROM claims AS c
    WHERE c.item_id = lfi.item_id
);


-- =========================================================
-- 19. LOST & FOUND SUMMARY KPI DATA
-- AGGREGATE FUNCTIONS
-- =========================================================

SELECT
    COUNT(*) AS total_items,
    SUM(status = 'Pending') AS pending_items,
    SUM(status = 'Approved') AS approved_items,
    SUM(status = 'Rejected') AS rejected_items,
    SUM(status = 'Returned') AS returned_items,
    SUM(item_type = 'Lost') AS lost_items,
    SUM(item_type = 'Found') AS found_items
FROM lost_found_items;


-- =========================================================
-- 20. CLAIM SUMMARY KPI DATA
-- =========================================================

SELECT
    COUNT(*) AS total_claims,
    SUM(status = 'Pending') AS pending_claims,
    SUM(status = 'Approved') AS approved_claims,
    SUM(status = 'Rejected') AS rejected_claims
FROM claims;


-- =========================================================
-- 21. TOTAL CAMPUSFIX ACTIVITY
-- UNION ALL
-- =========================================================

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


-- =========================================================
-- 22. DAILY ACTIVITY ACROSS MODULES
-- UNION ALL + GROUP BY
-- =========================================================

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


-- =========================================================
-- 23. MOST ACTIVE LOST & FOUND POSTERS
-- GROUP BY + ORDER BY + LIMIT
-- =========================================================

SELECT
    posted_by AS user_id,
    COUNT(*) AS total_posts
FROM lost_found_items
GROUP BY posted_by
ORDER BY total_posts DESC
LIMIT 10;


-- =========================================================
-- 24. USERS WITH MOST ISSUE REPORTS
-- GROUP BY + ORDER BY + LIMIT
-- =========================================================

SELECT
    reported_by AS user_id,
    COUNT(*) AS total_reports
FROM issues
GROUP BY reported_by
ORDER BY total_reports DESC
LIMIT 10;


-- =========================================================
-- 25. ADMIN DASHBOARD QUICK SUMMARY
-- SCALAR SUBQUERIES
-- =========================================================

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