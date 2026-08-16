-- =========================================================
-- CampusFix
-- Member 3: Lost & Found Query Demonstrations
-- File: database/queries/lostfound_queries.sql
-- =========================================================

-- These queries demonstrate:
-- SELECT
-- WHERE
-- ORDER BY
-- LIMIT
-- JOIN
-- GROUP BY
-- aggregate functions
-- subqueries
-- Lost & Found and Claim reporting


-- =========================================================
-- 1. VIEW ALL APPROVED LOST & FOUND ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_category_id,
    posted_by,
    location_id,
    item_type,
    item_name,
    description,
    status,
    item_date,
    image_path,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
ORDER BY created_at DESC;


-- =========================================================
-- 2. VIEW APPROVED LOST ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_name,
    description,
    location_id,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
  AND item_type = 'Lost'
ORDER BY item_date DESC;


-- =========================================================
-- 3. VIEW APPROVED FOUND ITEMS
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    item_name,
    description,
    location_id,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
  AND item_type = 'Found'
ORDER BY item_date DESC;


-- =========================================================
-- 4. SEARCH LOST & FOUND ITEMS BY KEYWORD
-- WHERE + LIKE
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    description,
    status,
    item_date
FROM lost_found_items
WHERE status = 'Approved'
  AND (
        item_name LIKE '%wallet%'
        OR description LIKE '%wallet%'
      )
ORDER BY created_at DESC;


-- =========================================================
-- 5. SHOW MOST RECENT APPROVED ITEMS
-- ORDER BY + LIMIT
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Approved'
ORDER BY created_at DESC
LIMIT 10;


-- =========================================================
-- 6. JOIN ITEMS WITH THEIR CATEGORY AND LOCATION
-- INNER JOIN
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status,
    lfi.item_date,
    lfc.item_category_id,
    loc.l_id AS location_id
FROM lost_found_items AS lfi
INNER JOIN lost_found_categories AS lfc
    ON lfc.item_category_id = lfi.item_category_id
INNER JOIN locations AS loc
    ON loc.l_id = lfi.location_id
WHERE lfi.status = 'Approved'
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 7. COUNT ITEMS BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY status
ORDER BY total_items DESC;


-- =========================================================
-- 8. COUNT LOST AND FOUND ITEMS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    item_type,
    COUNT(*) AS total_items
FROM lost_found_items
GROUP BY item_type
ORDER BY total_items DESC;


-- =========================================================
-- 9. COUNT ITEMS BY CATEGORY
-- JOIN + GROUP BY + COUNT
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
-- 10. VIEW ITEMS POSTED BY A SPECIFIC USER
-- WHERE
-- Change user_id value when testing.
-- =========================================================

SELECT
    item_id,
    item_type,
    item_name,
    status,
    item_date,
    created_at
FROM lost_found_items
WHERE posted_by = 1
ORDER BY created_at DESC;


-- =========================================================
-- 11. ADMIN: VIEW ITEMS WAITING FOR APPROVAL
-- WHERE + ORDER BY
-- =========================================================

SELECT
    item_id,
    posted_by,
    item_type,
    item_name,
    description,
    item_date,
    created_at
FROM lost_found_items
WHERE status = 'Pending'
ORDER BY created_at ASC;


-- =========================================================
-- 12. VIEW CLAIMS WITH ITEM INFORMATION
-- INNER JOIN
-- =========================================================

SELECT
    c.claim_id,
    c.item_id,
    lfi.item_name,
    lfi.item_type,
    c.submitted_by,
    c.reviewed_by,
    c.claim_msg,
    c.status AS claim_status,
    lfi.status AS item_status,
    c.admin_note,
    c.created_at
FROM claims AS c
INNER JOIN lost_found_items AS lfi
    ON lfi.item_id = c.item_id
ORDER BY c.created_at DESC;


-- =========================================================
-- 13. ADMIN: VIEW PENDING CLAIMS
-- JOIN + WHERE + ORDER BY
-- =========================================================

SELECT
    c.claim_id,
    c.item_id,
    lfi.item_name,
    lfi.item_type,
    c.submitted_by,
    c.claim_msg,
    c.created_at
FROM claims AS c
INNER JOIN lost_found_items AS lfi
    ON lfi.item_id = c.item_id
WHERE c.status = 'Pending'
ORDER BY c.created_at ASC;


-- =========================================================
-- 14. COUNT CLAIMS BY STATUS
-- GROUP BY + COUNT
-- =========================================================

SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;


-- =========================================================
-- 15. ITEMS THAT HAVE AT LEAST ONE CLAIM
-- SUBQUERY using EXISTS
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status
FROM lost_found_items AS lfi
WHERE EXISTS (
    SELECT 1
    FROM claims AS c
    WHERE c.item_id = lfi.item_id
)
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 16. APPROVED ITEMS WITH NO PENDING CLAIM
-- SUBQUERY using NOT EXISTS
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.item_date
FROM lost_found_items AS lfi
WHERE lfi.status = 'Approved'
  AND NOT EXISTS (
        SELECT 1
        FROM claims AS c
        WHERE c.item_id = lfi.item_id
          AND c.status = 'Pending'
      )
ORDER BY lfi.created_at DESC;


-- =========================================================
-- 17. ITEMS WITH NUMBER OF CLAIMS
-- LEFT JOIN + GROUP BY + COUNT
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
LEFT JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    lfi.status
ORDER BY total_claims DESC;


-- =========================================================
-- 18. ITEMS RECEIVING MORE THAN ONE CLAIM
-- GROUP BY + HAVING + aggregate
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    COUNT(c.claim_id) AS total_claims
FROM lost_found_items AS lfi
INNER JOIN claims AS c
    ON c.item_id = lfi.item_id
GROUP BY
    lfi.item_id,
    lfi.item_name
HAVING COUNT(c.claim_id) > 1
ORDER BY total_claims DESC;


-- =========================================================
-- 19. RETURNED ITEMS WITH APPROVED CLAIM
-- JOIN + WHERE
-- =========================================================

SELECT
    lfi.item_id,
    lfi.item_name,
    lfi.item_type,
    c.claim_id,
    c.submitted_by,
    c.reviewed_by,
    c.admin_note,
    c.updated_at AS claim_reviewed_at
FROM lost_found_items AS lfi
INNER JOIN claims AS c
    ON c.item_id = lfi.item_id
WHERE lfi.status = 'Returned'
  AND c.status = 'Approved'
ORDER BY c.updated_at DESC;


-- =========================================================
-- 20. LOST & FOUND SUMMARY
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