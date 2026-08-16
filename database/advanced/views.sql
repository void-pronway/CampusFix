USE campusfix;

DROP VIEW IF EXISTS vw_admin_issue_overview;

CREATE VIEW vw_admin_issue_overview AS
SELECT
    i.id AS issue_id,
    i.title,
    i.description,
    i.priority,
    i.status,
    i.created_at,

    CONCAT(u.f_name, ' ', u.l_name) AS reporter_name,
    u.email AS reporter_email,

    c.name AS category_name,

    b.building_name,
    l.location_name,
    l.floor,
    l.room_no,

    ia.staff_id,
    CONCAT(s.f_name, ' ', s.l_name) AS staff_name,

    ia.assigned_by,
    CONCAT(a.f_name, ' ', a.l_name) AS assigned_by_name,

    ia.assigned_at,
    ia.resolution_note,
    ia.resolved_at

FROM issues i

JOIN users u
    ON i.reported_by = u.user_id

JOIN issue_categories c
    ON i.category_id = c.id

JOIN locations l
    ON i.location_id = l.l_id

JOIN building b
    ON l.building_id = b.building_id

LEFT JOIN issue_assignments ia
    ON ia.issue_id = i.id

LEFT JOIN users s
    ON ia.staff_id = s.user_id

LEFT JOIN users a
    ON ia.assigned_by = a.user_id;