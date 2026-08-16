<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

require_role(['student']);

$userId = (int) $_SESSION['user_id'];

$repository = new IssueRepository($pdo);
$issues = $repository->findByReporter($userId);

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

    <title>My Issues | CampusFix</title>

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
        <a href="report.php">Report Issue</a>
        <a href="my_issues.php" class="active">My Issues</a>
        <a href="../lostfound/index.php">Lost & Found</a>
        <a href="../lostfound/create.php">Add Lost/Found Item</a>
        <a href="../../profile.php">My Profile</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>My Issues</h1>
                <p>
                    View the campus issues you have reported
                    and follow their current status.
                </p>
            </div>

            <a href="report.php" class="btn btn-primary">
                Report New Issue
            </a>
        </div>

        <section class="panel">

            <?php if ($issues === []): ?>

                <h2>No Issues Reported</h2>

                <p>
                    You have not reported any campus issues yet.
                </p>

                <a href="report.php" class="btn btn-primary">
                    Report Your First Issue
                </a>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Reported</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td>
                                <?= (int) $issue['id'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['title']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['priority']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['status']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['visibility']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    date(
                                        'M j, Y g:i A',
                                        strtotime(
                                            (string) $issue['created_at']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="detail.php?id=<?= (int) $issue['id'] ?>"
                                    class="btn btn-primary"
                                >
                                    View
                                </a>
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
