USE campusfix;

INSERT INTO departments (d_name)
VALUES ('DBMS Demo Department');

SELECT *
FROM departments
WHERE d_name = 'DBMS Demo Department';

SELECT
    user_id,
    f_name,
    l_name,
    role,
    email,
    status
FROM users
WHERE status = 'active';

SELECT
    user_id,
    f_name,
    l_name,
    role,
    email,
    created_at
FROM users
ORDER BY created_at DESC;

SELECT
    user_id,
    f_name,
    l_name,
    role,
    email,
    created_at
FROM users
ORDER BY created_at DESC
LIMIT 5;

UPDATE departments
SET d_name = 'DBMS Demo Department Updated'
WHERE d_name = 'DBMS Demo Department';

SELECT *
FROM departments
WHERE d_name = 'DBMS Demo Department Updated';

SELECT
    u.user_id,
    CONCAT(u.f_name, ' ', u.l_name) AS full_name,
    u.email,
    u.role,
    u.status,
    d.d_name AS department
FROM users AS u
LEFT JOIN departments AS d
    ON u.dept_id = d.dept_id
ORDER BY u.user_id ASC;

SELECT
    role,
    COUNT(*) AS total_users
FROM users
GROUP BY role
ORDER BY total_users DESC;

SELECT
    d.dept_id,
    d.d_name,
    COUNT(u.user_id) AS total_users
FROM departments AS d
LEFT JOIN users AS u
    ON d.dept_id = u.dept_id
GROUP BY
    d.dept_id,
    d.d_name
ORDER BY total_users DESC;

SELECT
    dept_id,
    d_name
FROM departments
WHERE dept_id IN (
    SELECT DISTINCT dept_id
    FROM users
    WHERE dept_id IS NOT NULL
);

DELETE FROM departments
WHERE d_name = 'DBMS Demo Department Updated';

SELECT *
FROM departments
WHERE d_name = 'DBMS Demo Department Updated';