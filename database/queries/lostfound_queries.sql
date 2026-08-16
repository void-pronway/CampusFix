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
    item_id,
    item_type,
    item_name,
    status,
    item_date,
    created_at
FROM lost_found_items
WHERE posted_by = 1
ORDER BY created_at DESC;

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

SELECT
    status,
    COUNT(*) AS total_claims
FROM claims
GROUP BY status
ORDER BY total_claims DESC;

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

SELECT
    COUNT(*) AS total_items,
    SUM(status = 'Pending') AS pending_items,
    SUM(status = 'Approved') AS approved_items,
    SUM(status = 'Rejected') AS rejected_items,
    SUM(status = 'Returned') AS returned_items,
    SUM(item_type = 'Lost') AS lost_items,
    SUM(item_type = 'Found') AS found_items
FROM lost_found_items;