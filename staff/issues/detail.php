<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['staff']);

$staffId = (int) $_SESSION['user_id'];
$issueId = (int) ($_GET['id'] ?? 0);

if ($issueId <= 0) {
    http_response_code(400);
    exit('Invalid issue.');
}

$repository = new IssueRepository($pdo);
$issue = $repository->findById($issueId);

if ($issue === null) {
    http_response_code(404);
    exit('Issue not found.');
}

$assignment = $repository->getLatestAssignment($issueId);

if (
    $assignment === null
    || (int) $assignment['staff_id'] !== $staffId
) {
    http_response_code(403);
    exit('You are not assigned to this issue.');
}

$categoryStmt = $pdo->prepare(
    "SELECT name
     FROM issue_categories
     WHERE id = :id
     LIMIT 1"
);

$categoryStmt->execute([
    ':id' => (int) $issue['category_id'],
]);

$categoryName = $categoryStmt->fetchColumn();

$locationStmt = $pdo->prepare(
    "SELECT
        l.location_name,
        l.floor,
        l.room_no,
        b.building_name
     FROM locations AS l
     INNER JOIN building AS b
        ON b.building_id = l.building_id
     WHERE l.l_id = :id
     LIMIT 1"
);

$locationStmt->execute([
    ':id' => (int) $issue['location_id'],
]);

$location = $locationStmt->fetch(PDO::FETCH_ASSOC);

$reporterStmt = $pdo->prepare(
    "SELECT
        f_name,
        l_name,
        email
     FROM users
     WHERE user_id = :id
     LIMIT 1"
);

$reporterStmt->execute([
    ':id' => (int) $issue['reported_by'],
]);

$reporter = $reporterStmt->fetch(PDO::FETCH_ASSOC);

$statusHistory = $repository->getStatusHistory($issueId);
$comments = $repository->getComments($issueId);

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

    <title>Issue Details | CampusFix</title>

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
        <a href="assigned.php" class="active">Assigned Issues</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1><?= escapeHtml($issue['title']) ?></h1>
                <p>
                    Status:
                    <?= escapeHtml($issue['status']) ?>
                </p>
            </div>

            <a href="assigned.php" class="btn">
                Back to Assigned Issues
            </a>
        </div>

        <section class="panel">

            <h2>Issue Information</h2>

            <p>
                <strong>Category:</strong>
                <?= escapeHtml($categoryName ?: 'Unknown') ?>
            </p>

            <p>
                <strong>Priority:</strong>
                <?= escapeHtml($issue['priority']) ?>
            </p>

            <p>
                <strong>Visibility:</strong>
                <?= escapeHtml($issue['visibility']) ?>
            </p>

            <p>
                <strong>Location:</strong>
                <?php if ($location !== false): ?>
                    <?= escapeHtml(
                        $location['building_name']
                        . ' - '
                        . $location['location_name']
                    ) ?>
                <?php else: ?>
                    Unknown
                <?php endif; ?>
            </p>

            <?php if (
                $location !== false
                && $location['floor'] !== null
                && $location['floor'] !== ''
            ): ?>
                <p>
                    <strong>Floor:</strong>
                    <?= escapeHtml($location['floor']) ?>
                </p>
            <?php endif; ?>

            <?php if (
                $location !== false
                && $location['room_no'] !== null
                && $location['room_no'] !== ''
            ): ?>
                <p>
                    <strong>Room:</strong>
                    <?= escapeHtml($location['room_no']) ?>
                </p>
            <?php endif; ?>

            <p>
                <strong>Description:</strong><br>
                <?= nl2br(escapeHtml($issue['description'])) ?>
            </p>

            <p>
                <strong>Reported:</strong>
                <?= escapeHtml($issue['created_at']) ?>
            </p>

        </section>

        <section class="panel">

            <h2>Reporter</h2>

            <?php if ($reporter !== false): ?>

                <p>
                    <strong>Name:</strong>
                    <?= escapeHtml(
                        $reporter['f_name']
                        . ' '
                        . $reporter['l_name']
                    ) ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?= escapeHtml($reporter['email']) ?>
                </p>

            <?php else: ?>

                <p>Reporter information is unavailable.</p>

            <?php endif; ?>

        </section>

        <section class="panel">

            <h2>Assignment</h2>

            <p>
                <strong>Assigned:</strong>
                <?= escapeHtml($assignment['assigned_at']) ?>
            </p>

            <?php if (!empty($assignment['assignment_note'])): ?>
                <p>
                    <strong>Assignment Note:</strong><br>
                    <?= nl2br(
                        escapeHtml($assignment['assignment_note'])
                    ) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($assignment['resolution_note'])): ?>
                <p>
                    <strong>Resolution Note:</strong><br>
                    <?= nl2br(
                        escapeHtml($assignment['resolution_note'])
                    ) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($assignment['resolved_at'])): ?>
                <p>
                    <strong>Resolved:</strong>
                    <?= escapeHtml($assignment['resolved_at']) ?>
                </p>
            <?php endif; ?>

        </section>

        <?php if ((string) $issue['status'] === StatusService::ASSIGNED): ?>

            <section class="panel">
                <h2>Actions</h2>

                <a
                    href="start_work.php?id=<?= $issueId ?>"
                    class="btn btn-primary"
                >
                    Start Work
                </a>
            </section>

        <?php elseif (
            (string) $issue['status'] === StatusService::IN_PROGRESS
        ): ?>

            <section class="panel">
                <h2>Actions</h2>

                <a
                    href="progress.php?id=<?= $issueId ?>"
                    class="btn"
                >
                    Update Progress
                </a>

                <a
                    href="resolve.php?id=<?= $issueId ?>"
                    class="btn btn-primary"
                >
                    Resolve Issue
                </a>
            </section>

        <?php endif; ?>

        <section class="panel">

            <h2>Status History</h2>

            <?php if ($statusHistory === []): ?>

                <p>No status history is available.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Previous</th>
                        <th>New Status</th>
                        <th>Changed By</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($statusHistory as $log): ?>
                        <tr>
                            <td>
                                <?= escapeHtml(
                                    $log['old_status'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($log['new_status']) ?>
                            </td>

                            <td>
                                <?= (int) $log['changed_by'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($log['note'] ?? '') ?>
                            </td>

                            <td>
                                <?= escapeHtml($log['changed_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

            <?php endif; ?>

        </section>

        <section class="panel">

            <h2>Comments</h2>

            <?php if ($comments === []): ?>

                <p>No comments have been added.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td>
                                <?= (int) $comment['user_id'] ?>
                            </td>

                            <td>
                                <?= nl2br(
                                    escapeHtml($comment['comment'])
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($comment['created_at']) ?>
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
