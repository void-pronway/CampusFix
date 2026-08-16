USE campusfix;

DROP PROCEDURE IF EXISTS assign_issue_to_staff;

DELIMITER $$

CREATE PROCEDURE assign_issue_to_staff(
    IN p_issue_id INT UNSIGNED,
    IN p_staff_id INT UNSIGNED,
    IN p_assigned_by INT UNSIGNED,
    IN p_assignment_note TEXT
)
BEGIN
    DECLARE current_status VARCHAR(50);

    SELECT status
    INTO current_status
    FROM issues
    WHERE id = p_issue_id;

    IF current_status IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Issue not found';
    END IF;

    IF current_status IN ('Resolved', 'Rejected') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Resolved or rejected issue cannot be assigned';
    END IF;

    INSERT INTO issue_assignments (
        issue_id,
        staff_id,
        assigned_by,
        assignment_note
    )
    VALUES (
        p_issue_id,
        p_staff_id,
        p_assigned_by,
        p_assignment_note
    );

    SET @campusfix_changed_by = p_assigned_by;
    SET @campusfix_status_note = p_assignment_note;

    UPDATE issues
    SET status = 'Assigned'
    WHERE id = p_issue_id;

    SET @campusfix_changed_by = NULL;
    SET @campusfix_status_note = NULL;
END$$

DELIMITER ;