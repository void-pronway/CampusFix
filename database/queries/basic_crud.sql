USE campusfix;

START TRANSACTION;

INSERT INTO issue_categories (name)
VALUES ('Temporary Test Category');

SET @category_id = LAST_INSERT_ID();

SELECT id, name
FROM issue_categories
WHERE id = @category_id;

UPDATE issue_categories
SET name = 'Updated Test Category'
WHERE id = @category_id;

SELECT id, name
FROM issue_categories
WHERE id = @category_id;

DELETE FROM issue_categories
WHERE id = @category_id;

COMMIT;