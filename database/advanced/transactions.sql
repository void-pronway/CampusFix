USE campusfix;

START TRANSACTION;

SET @claim_id = (
    SELECT claim_id
    FROM claims
    WHERE status = 'Pending'
    ORDER BY claim_id
    LIMIT 1
);

SET @item_id = (
    SELECT item_id
    FROM claims
    WHERE claim_id = @claim_id
);

UPDATE claims
SET
    status = 'Approved',
    admin_note = 'Claim approved through transaction demonstration'
WHERE claim_id = @claim_id;

UPDATE lost_found_items
SET status = 'Returned'
WHERE item_id = @item_id;

ROLLBACK;