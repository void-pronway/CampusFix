<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['admin']);

$stmt = $pdo->query(
    "SELECT
        i.id,
        i.title,
        i.priority,
        i.status,
        i.updated_at,
        ic.name AS category_name,
        u.f_name,
        u.l_name,
        l.location_name,
        b.building_name
     FROM issues AS i
     INNER JOIN issue_categories AS ic
        ON ic.id = i.category_id
     INNER JOIN users AS u
        ON u.user_id = i.reported_by
     INNER JOIN locations AS l
        ON l.l_id = i.location_id
     INNER JOIN building AS b
        ON b.building_id = l.building_id
     WHERE i.status IN ('Pending', 'Assigned', 'In Progress')
     ORDER BY i.updated_at ASC"
);

$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTimeImmutable();
$escalatedIssues = [];

foreach ($issues as $issue) {
    $lastUpdated = new DateTimeImmutable(
        (string) $issue['updated_at']
    );

    if (
        StatusService::isOverdue(
            (string) $issue['status'],
            $lastUpdated,
            $now
        )
    ) {
        $issue['escalation_reason'] =
            StatusService::getEscalationReason(
                (string) $issue['status'],
                $lastUpdated,
                $now
            );

        $issue['days_elapsed'] = (int) $lastUpdated
            ->diff($now)
            ->format('%a');

        $escalatedIssues[] = $issue;
    }
}

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

    <title>Escalated Issues | CampusFix</title>

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
        <a href="categories.php">Categories</a>
        <a href="escalated.php" class="active">Escalated Issues</a>
        <a href="../lostfound/pending.php">Lost & Found</a>
        <a href="../lostfound/claims.php">Claims</a>
        <a href="../analytics.php">Analytics</a>
        <a href="../users/manage.php">Users</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Escalated Issues</h1>
                <p>
                    Issues that have remained in their current
                    workflow stage longer than allowed.
                </p>
            </div>
        </div>

        <section class="panel">

            <?php if ($escalatedIssues === []): ?>

                <p>No issues currently require escalation.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Reporter</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($escalatedIssues as $issue): ?>
                        <tr>
                            <td>
                                <?= (int) $issue['id'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['title']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['category_name']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $issue['building_name']
                                    . ' - '
                                    . $issue['location_name']
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $issue['f_name']
                                    . ' '
                                    . $issue['l_name']
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['priority']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['status']) ?>
                            </td>

                            <td>
                                <?= (int) $issue['days_elapsed'] ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $issue['escalation_reason']
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="detail.php?id=<?= (int) $issue['id'] ?>"
                                    class="btn"
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
