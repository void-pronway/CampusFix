USE campusfix;

DROP PROCEDURE IF EXISTS assign_issue_to_staff;

DELIMITER $$

CREATE PROCEDURE assign_issue_to_staff(
    IN p_issue_id INT UNSIGNED,
    IN p_staff_id INT UNSIGNED,
    IN p_admin_id INT UNSIGNED,
    IN p_assignment_note TEXT
)
BEGIN
    DECLARE v_issue_exists INT DEFAULT 0;
    DECLARE v_staff_exists INT DEFAULT 0;
    DECLARE v_admin_exists INT DEFAULT 0;
    DECLARE v_issue_status VARCHAR(50);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET @changed_by = NULL;
        SET @status_note = NULL;
        RESIGNAL;
    END;

    SELECT COUNT(*)
    INTO v_issue_exists
    FROM issues
    WHERE id = p_issue_id;

    IF v_issue_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Issue does not exist';
    END IF;

    SELECT COUNT(*)
    INTO v_staff_exists
    FROM users
    WHERE user_id = p_staff_id
      AND role = 'staff'
      AND status = 'active';

    IF v_staff_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Selected user is not an active staff member';
    END IF;

    SELECT COUNT(*)
    INTO v_admin_exists
    FROM users
    WHERE user_id = p_admin_id
      AND role = 'admin'
      AND status = 'active';

    IF v_admin_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Selected user is not an active admin';
    END IF;

    SELECT status
    INTO v_issue_status
    FROM issues
    WHERE id = p_issue_id;

    IF v_issue_status <> 'Pending' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only pending issues can be assigned';
    END IF;

    START TRANSACTION;

    INSERT INTO issue_assignments (
        issue_id,
        staff_id,
        assigned_by,
        assignment_note
    )
    VALUES (
        p_issue_id,
        p_staff_id,
        p_admin_id,
        p_assignment_note
    );

    SET @changed_by = p_admin_id;
    SET @status_note = p_assignment_note;

    UPDATE issues
    SET status = 'Assigned'
    WHERE id = p_issue_id;

    COMMIT;

    SET @changed_by = NULL;
    SET @status_note = NULL;
END$$

DELIMITER ;