USE campusfix;

DROP TRIGGER IF EXISTS trg_issue_status_change;

DELIMITER $$

CREATE TRIGGER trg_issue_status_change
AFTER UPDATE ON issues
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO issue_status_logs (
            issue_id,
            changed_by,
            old_status,
            new_status,
            note
        )
        VALUES (
            NEW.id,
            COALESCE(@campusfix_changed_by, NEW.reported_by),
            OLD.status,
            NEW.status,
            @campusfix_status_note
        );
    END IF;
END$$

DELIMITER ;