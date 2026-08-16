<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '') {
            $error = 'Please enter a category name.';
        } elseif (strlen($name) > 100) {
            $error = 'Category name must not exceed 100 characters.';
        } else {
            $checkStmt = $pdo->prepare(
                "SELECT id
                 FROM issue_categories
                 WHERE name = :name
                 LIMIT 1"
            );

            $checkStmt->execute([
                ':name' => $name,
            ]);

            if ($checkStmt->fetchColumn() !== false) {
                $error = 'That category already exists.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO issue_categories (name)
                         VALUES (:name)"
                    );

                    $stmt->execute([
                        ':name' => $name,
                    ]);

                    $success = 'Category added successfully.';
                } catch (Throwable $e) {
                    $error = 'Unable to add the category.';
                }
            }
        }
    }

    if ($action === 'rename') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($categoryId <= 0) {
            $error = 'Invalid category.';
        } elseif ($name === '') {
            $error = 'Please enter a category name.';
        } elseif (strlen($name) > 100) {
            $error = 'Category name must not exceed 100 characters.';
        } else {
            $checkStmt = $pdo->prepare(
                "SELECT id
                 FROM issue_categories
                 WHERE name = :name
                   AND id <> :id
                 LIMIT 1"
            );

            $checkStmt->execute([
                ':name' => $name,
                ':id' => $categoryId,
            ]);

            if ($checkStmt->fetchColumn() !== false) {
                $error = 'That category already exists.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE issue_categories
                         SET name = :name
                         WHERE id = :id"
                    );

                    $stmt->execute([
                        ':name' => $name,
                        ':id' => $categoryId,
                    ]);

                    $success = 'Category updated successfully.';
                } catch (Throwable $e) {
                    $error = 'Unable to update the category.';
                }
            }
        }
    }

    if ($action === 'delete') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($categoryId <= 0) {
            $error = 'Invalid category.';
        } else {
            try {
                $pdo->beginTransaction();

                $countStmt = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM issues
                     WHERE category_id = :category_id"
                );

                $countStmt->execute([
                    ':category_id' => $categoryId,
                ]);

                $issueCount = (int) $countStmt->fetchColumn();

                if ($issueCount > 0) {
                    $pdo->rollBack();
                    $error = 'A category in use cannot be deleted.';
                } else {
                    $deleteStmt = $pdo->prepare(
                        "DELETE FROM issue_categories
                         WHERE id = :id"
                    );

                    $deleteStmt->execute([
                        ':id' => $categoryId,
                    ]);

                    $pdo->commit();

                    $success = 'Category deleted successfully.';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'Unable to delete the category.';
            }
        }
    }
}

$categories = $pdo
    ->query(
        "SELECT
            ic.id,
            ic.name,
            COUNT(i.id) AS issue_count
         FROM issue_categories AS ic
         LEFT JOIN issues AS i
            ON i.category_id = ic.id
         GROUP BY ic.id, ic.name
         ORDER BY ic.name ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = csrf_token();

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue Categories | CampusFix</title>

    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="../dashboard.php">Dashboard</a>
        <a href="index.php">All Issues</a>
        <a href="categories.php" class="active">Categories</a>
        <a href="escalated.php">Escalated Issues</a>
        <a href="../lostfound/pending.php">Lost & Found</a>
        <a href="../lostfound/claims.php">Claims</a>
        <a href="../analytics.php">Analytics</a>
        <a href="../users/manage.php">Users</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Issue Categories</h1>
                <p>Manage the categories used for campus issue reports.</p>
            </div>
        </div>

        <section class="panel">

            <h2>Add Category</h2>

            <?php if ($error !== ''): ?>
                <p><?= escapeHtml($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <p><?= escapeHtml($success) ?></p>
            <?php endif; ?>

            <form action="categories.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

                <div class="form-group">
                    <label for="name">Category Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        maxlength="100"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Add Category
                </button>

            </form>

        </section>

        <section class="panel">

            <h2>Existing Categories</h2>

            <?php if ($categories === []): ?>

                <p>No issue categories are available.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Issues</th>
                        <th>Update</th>
                        <th>Delete</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <?= (int) $category['id'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($category['name']) ?>
                            </td>

                            <td>
                                <?= (int) $category['issue_count'] ?>
                            </td>

                            <td>
                                <form action="categories.php" method="POST">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= escapeHtml($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="rename"
                                    >

                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value="<?= (int) $category['id'] ?>"
                                    >

                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control"
                                        maxlength="100"
                                        value="<?= escapeHtml($category['name']) ?>"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        Rename
                                    </button>

                                </form>
                            </td>

                            <td>
                                <?php if ((int) $category['issue_count'] === 0): ?>

    <form
        action="categories.php"
        method="POST"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= escapeHtml($csrfToken) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="delete"
        >

        <input
            type="hidden"
            name="category_id"
            value="<?= (int) $category['id'] ?>"
        >

        <button
            type="submit"
            class="btn btn-danger"
        >
            Delete
        </button>
    </form>

<?php else: ?>

    In Use

<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
