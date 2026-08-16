USE campusfix;

DROP VIEW IF EXISTS vw_admin_issue_overview;

CREATE VIEW vw_admin_issue_overview AS
SELECT
    i.id AS issue_id,
    i.title,
    i.description,
    ic.name AS category_name,
    CONCAT(r.f_name, ' ', r.l_name) AS reporter_name,
    b.building_name,
    l.location_name,
    i.priority,
    i.status,
    COUNT(DISTINCT conf.id) AS confirmation_count,
    CONCAT(s.f_name, ' ', s.l_name) AS assigned_staff,
    ia.assigned_at,
    ia.resolved_at,
    i.created_at,
    i.updated_at
FROM issues AS i
JOIN issue_categories AS ic
    ON i.category_id = ic.id
JOIN users AS r
    ON i.reported_by = r.user_id
JOIN locations AS l
    ON i.location_id = l.l_id
JOIN building AS b
    ON l.building_id = b.building_id
LEFT JOIN issue_confirmations AS conf
    ON i.id = conf.issue_id
LEFT JOIN issue_assignments AS ia
    ON i.id = ia.issue_id
LEFT JOIN users AS s
    ON ia.staff_id = s.user_id
GROUP BY
    i.id,
    i.title,
    i.description,
    ic.name,
    r.f_name,
    r.l_name,
    b.building_name,
    l.location_name,
    i.priority,
    i.status,
    s.f_name,
    s.l_name,
    ia.assigned_at,
    ia.resolved_at,
    i.created_at,
    i.updated_at;