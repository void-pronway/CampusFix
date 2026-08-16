USE campusfix;

INSERT IGNORE INTO users
(dept_id, f_name, l_name, role, status, email, pass)
VALUES
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Arafat', 'Rahman', 'student', 'active', 'arafat.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Nusrat', 'Jahan', 'student', 'active', 'nusrat.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Software Engineering'), 'Tanvir', 'Ahmed', 'student', 'active', 'tanvir.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Software Engineering'), 'Maliha', 'Islam', 'student', 'active', 'maliha.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Electrical and Electronic Engineering'), 'Sakib', 'Hasan', 'student', 'active', 'sakib.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Electrical and Electronic Engineering'), 'Farzana', 'Akter', 'student', 'active', 'farzana.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Business Administration'), 'Rafi', 'Chowdhury', 'student', 'active', 'rafi.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Business Administration'), 'Anika', 'Sultana', 'student', 'active', 'anika.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='English'), 'Mehedi', 'Karim', 'student', 'active', 'mehedi.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='English'), 'Sadia', 'Noor', 'student', 'active', 'sadia.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Nafis', 'Hossain', 'student', 'active', 'nafis.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Software Engineering'), 'Tania', 'Rahman', 'student', 'active', 'tania.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Imran', 'Kabir', 'student', 'active', 'imran.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Business Administration'), 'Raisa', 'Ahmed', 'student', 'active', 'raisa.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='English'), 'Fahim', 'Islam', 'student', 'active', 'fahim.student@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Kamal', 'Uddin', 'staff', 'active', 'kamal.staff@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Electrical and Electronic Engineering'), 'Salma', 'Khatun', 'staff', 'active', 'salma.staff@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Software Engineering'), 'Mahmud', 'Hasan', 'staff', 'active', 'mahmud.staff@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Computer Science and Engineering'), 'Admin', 'One', 'admin', 'active', 'admin1@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'),
((SELECT dept_id FROM departments WHERE d_name='Software Engineering'), 'Admin', 'Two', 'admin', 'active', 'admin2@campusfix.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');

INSERT IGNORE INTO issue_categories (name) VALUES
('Electrical'),
('Plumbing'),
('Internet'),
('Cleanliness'),
('Furniture'),
('Safety');
INSERT INTO issues (
    category_id,
    reported_by,
    location_id,
    title,
    description,
    visibility,
    priority,
    status
)
SELECT
    ic.id,
    u.user_id,
    l.l_id,
    s.title,
    s.description,
    s.visibility,
    s.priority,
    s.status
FROM (
    SELECT
        'Electrical' AS category_name,
        'arafat.student@campusfix.test' AS reporter_email,
        'Green Garden' AS location_name,
        'Broken light near Green Garden' AS title,
        'The light near the walking area is not working at night.' AS description,
        'Public' AS visibility,
        'Medium' AS priority,
        'Pending' AS status

    UNION ALL SELECT
        'Electrical',
        'nusrat.student@campusfix.test',
        'Food Court',
        'Loose electrical socket at Food Court',
        'One electrical socket near the seating area is loose and unsafe.',
        'Public',
        'High',
        'Pending'

    UNION ALL SELECT
        'Plumbing',
        'tanvir.student@campusfix.test',
        'Gym Center',
        'Water leakage near Gym Center',
        'Water is continuously leaking from a pipe beside the Gym Center.',
        'Public',
        'High',
        'Pending'

    UNION ALL SELECT
        'Cleanliness',
        'maliha.student@campusfix.test',
        'Lake Side',
        'Garbage accumulation beside Lake Side',
        'Garbage has accumulated beside the Lake Side sitting area.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Internet',
        'sakib.student@campusfix.test',
        'Green Garden 2',
        'Weak WiFi coverage at Green Garden 2',
        'Students are unable to maintain a stable campus WiFi connection.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Safety',
        'farzana.student@campusfix.test',
        'Transport Depot',
        'Damaged pavement at Transport Depot',
        'A broken section of pavement may cause students to trip.',
        'Public',
        'High',
        'Pending'

    UNION ALL SELECT
        'Furniture',
        'rafi.student@campusfix.test',
        'Food Court',
        'Broken chair at Food Court',
        'One of the student chairs has a broken leg and cannot be used safely.',
        'Public',
        'Low',
        'Pending'

    UNION ALL SELECT
        'Cleanliness',
        'anika.student@campusfix.test',
        'Bonomaya',
        'Overflowing waste bin at Bonomaya',
        'The waste bin has not been emptied and garbage is spreading nearby.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Safety',
        'mehedi.student@campusfix.test',
        'Bonokanta',
        'Exposed cable near Bonokanta',
        'An exposed electrical cable can be seen beside the student pathway.',
        'Public',
        'Emergency',
        'Pending'

    UNION ALL SELECT
        'Plumbing',
        'sadia.student@campusfix.test',
        'Super Shop',
        'Blocked drain near Super Shop',
        'The nearby drain is blocked and water is collecting on the walkway.',
        'Public',
        'High',
        'Pending'

    UNION ALL SELECT
        'Internet',
        'nafis.student@campusfix.test',
        'Gym Center',
        'Campus WiFi unavailable near Gym Center',
        'Campus WiFi cannot be detected around the Gym Center area.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Electrical',
        'tania.student@campusfix.test',
        'Lake Side',
        'Flickering light near Lake Side',
        'A pathway light is continuously flickering during the evening.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Furniture',
        'imran.student@campusfix.test',
        'Green Garden',
        'Damaged bench at Green Garden',
        'A campus bench has a damaged wooden section and needs repair.',
        'Public',
        'Low',
        'Pending'

    UNION ALL SELECT
        'Cleanliness',
        'raisa.student@campusfix.test',
        'Transport Depot',
        'Dirty waiting area at Transport Depot',
        'The student waiting area requires cleaning and waste removal.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Safety',
        'fahim.student@campusfix.test',
        'Food Court',
        'Slippery floor at Food Court',
        'Water on the floor is creating a slipping hazard for students.',
        'Public',
        'Emergency',
        'Pending'

    UNION ALL SELECT
        'Electrical',
        'arafat.student@campusfix.test',
        'Super Shop',
        'Outdoor light not working near Super Shop',
        'The outdoor light beside the Super Shop is completely off.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Plumbing',
        'nusrat.student@campusfix.test',
        'Green Garden 2',
        'Water pipe leakage at Green Garden 2',
        'A small water pipe is leaking continuously near the garden.',
        'Public',
        'High',
        'Pending'

    UNION ALL SELECT
        'Furniture',
        'tanvir.student@campusfix.test',
        'Bonomaya',
        'Damaged outdoor table at Bonomaya',
        'The outdoor table surface is damaged and requires repair.',
        'Public',
        'Low',
        'Pending'

    UNION ALL SELECT
        'Internet',
        'maliha.student@campusfix.test',
        'Bonokanta',
        'Slow internet connection at Bonokanta',
        'Campus internet speed is extremely slow in this area.',
        'Public',
        'Medium',
        'Pending'

    UNION ALL SELECT
        'Cleanliness',
        'sakib.student@campusfix.test',
        'Gym Center',
        'Gym surroundings need cleaning',
        'Waste and dirt have accumulated around the Gym Center entrance.',
        'Public',
        'Medium',
        'Pending'
) AS s
JOIN issue_categories AS ic
    ON ic.name = s.category_name
JOIN users AS u
    ON u.email = s.reporter_email
JOIN building AS b
    ON b.building_code = 'CCA'
JOIN locations AS l
    ON l.building_id = b.building_id
   AND l.location_name = s.location_name
LEFT JOIN issues AS existing_issue
    ON existing_issue.title = s.title
   AND existing_issue.reported_by = u.user_id
WHERE existing_issue.id IS NULL;
INSERT IGNORE INTO issue_confirmations (issue_id, user_id)
SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'nusrat.student@campusfix.test'
WHERE i.title = 'Broken light near Green Garden'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'tanvir.student@campusfix.test'
WHERE i.title = 'Broken light near Green Garden'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'arafat.student@campusfix.test'
WHERE i.title = 'Loose electrical socket at Food Court'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'maliha.student@campusfix.test'
WHERE i.title = 'Water leakage near Gym Center'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'sakib.student@campusfix.test'
WHERE i.title = 'Exposed cable near Bonokanta'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'farzana.student@campusfix.test'
WHERE i.title = 'Exposed cable near Bonokanta'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'sadia.student@campusfix.test'
WHERE i.title = 'Slippery floor at Food Court'

UNION ALL

SELECT i.id, u.user_id
FROM issues i
JOIN users u ON u.email = 'nafis.student@campusfix.test'
WHERE i.title = 'Slippery floor at Food Court';


INSERT INTO issue_assignments (
    issue_id,
    staff_id,
    assigned_by,
    assignment_note
)
SELECT
    i.id,
    staff.user_id,
    admin_user.user_id,
    x.assignment_note
FROM (
    SELECT
        'Loose electrical socket at Food Court' AS issue_title,
        'kamal.staff@campusfix.test' AS staff_email,
        'admin1@campusfix.test' AS admin_email,
        'Electrical issue assigned for inspection' AS assignment_note

    UNION ALL SELECT
        'Water leakage near Gym Center',
        'salma.staff@campusfix.test',
        'admin1@campusfix.test',
        'Plumbing issue assigned for repair'

    UNION ALL SELECT
        'Damaged pavement at Transport Depot',
        'mahmud.staff@campusfix.test',
        'admin2@campusfix.test',
        'Safety issue assigned for inspection'

    UNION ALL SELECT
        'Exposed cable near Bonokanta',
        'kamal.staff@campusfix.test',
        'admin1@campusfix.test',
        'Emergency electrical safety issue'

    UNION ALL SELECT
        'Blocked drain near Super Shop',
        'salma.staff@campusfix.test',
        'admin2@campusfix.test',
        'Drainage problem assigned for repair'

    UNION ALL SELECT
        'Flickering light near Lake Side',
        'kamal.staff@campusfix.test',
        'admin1@campusfix.test',
        'Lighting issue assigned for inspection'

    UNION ALL SELECT
        'Slippery floor at Food Court',
        'mahmud.staff@campusfix.test',
        'admin2@campusfix.test',
        'Emergency safety issue assigned'

    UNION ALL SELECT
        'Water pipe leakage at Green Garden 2',
        'salma.staff@campusfix.test',
        'admin1@campusfix.test',
        'Water leakage assigned for repair'
) AS x
JOIN issues AS i
    ON i.title = x.issue_title
JOIN users AS staff
    ON staff.email = x.staff_email
JOIN users AS admin_user
    ON admin_user.email = x.admin_email
WHERE NOT EXISTS (
    SELECT 1
    FROM issue_assignments AS ia
    WHERE ia.issue_id = i.id
      AND ia.staff_id = staff.user_id
);


SET @campusfix_changed_by = (
    SELECT user_id
    FROM users
    WHERE email = 'admin1@campusfix.test'
);

SET @campusfix_status_note = 'Sample data status setup';

UPDATE issues
SET status = 'Assigned'
WHERE title IN (
    'Loose electrical socket at Food Court',
    'Damaged pavement at Transport Depot',
    'Blocked drain near Super Shop',
    'Flickering light near Lake Side',
    'Water pipe leakage at Green Garden 2'
);

UPDATE issues
SET status = 'In Progress'
WHERE title IN (
    'Water leakage near Gym Center',
    'Exposed cable near Bonokanta',
    'Slippery floor at Food Court'
);

SET @campusfix_changed_by = NULL;
SET @campusfix_status_note = NULL;