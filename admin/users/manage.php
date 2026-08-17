<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';

require_role(['admin']);

$stmt = $pdo->query(
    "SELECT
        u.user_id,
        u.f_name,
        u.l_name,
        u.email,
        u.role,
        u.status,
        u.created_at,
        d.d_name AS department_name
     FROM users AS u
     LEFT JOIN departments AS d
        ON d.dept_id = u.dept_id
     ORDER BY u.created_at DESC"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function escapeHtml(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Users | CampusFix</title>

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
        <a href="../issues/index.php">All Issues</a>
        <a href="../issues/categories.php">Categories</a>
        <a href="../lostfound/pending.php">Lost & Found</a>
        <a href="../lostfound/claims.php">Claims</a>
        <a href="../analytics.php">Analytics</a>
        <a href="manage.php" class="active">Users</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Manage Users</h1>
                <p>View registered CampusFix users.</p>
            </div>
        </div>

        <section class="panel">

            <h2>Registered Users</h2>

            <?php if ($users === []): ?>

                <p>No users found.</p>

            <?php else: ?>

                <table class="data-table">

                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($users as $user): ?>

                        <tr>
                            <td>
                                <?= (int) $user['user_id'] ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $user['f_name']
                                    . ' '
                                    . $user['l_name']
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($user['email']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $user['department_name']
                                    ?? 'Not Assigned'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    ucfirst($user['role'])
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    ucfirst($user['status'])
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    date(
                                        'M j, Y',
                                        strtotime(
                                            (string) $user['created_at']
                                        )
                                    )
                                ) ?>
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