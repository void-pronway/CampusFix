USE campusfix;

DROP TRIGGER IF EXISTS trg_issue_status_history;

DELIMITER $$

CREATE TRIGGER trg_issue_status_history
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
            COALESCE(@changed_by, NEW.reported_by),
            OLD.status,
            NEW.status,
            @status_note
        );
    END IF;
END$$

DELIMITER ;