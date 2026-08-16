USE campusfix;

START TRANSACTION;

SET @changed_by = 3;
SET @status_note = 'Staff started work';

UPDATE issues
SET status = 'In Progress'
WHERE id = 1
  AND status = 'Assigned';

SELECT id, title, status
FROM issues
WHERE id = 1;

SELECT issue_id, changed_by, old_status, new_status, note
FROM issue_status_logs
WHERE issue_id = 1
ORDER BY id DESC
LIMIT 1;

ROLLBACK;

SET @changed_by = NULL;
SET @status_note = NULL;