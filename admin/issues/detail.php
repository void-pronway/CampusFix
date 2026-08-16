<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['admin']);

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

$confirmationCount = $repository->countConfirmations($issueId);
$statusHistory = $repository->getStatusHistory($issueId);
$comments = $repository->getComments($issueId);
$assignment = $repository->getLatestAssignment($issueId);

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
        b.building_name,
        l.location_name,
        l.floor,
        l.room_no
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
        user_id,
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

$assignedStaff = null;

if ($assignment !== null) {
    $staffStmt = $pdo->prepare(
        "SELECT
            user_id,
            f_name,
            l_name,
            email
         FROM users
         WHERE user_id = :id
         LIMIT 1"
    );

    $staffStmt->execute([
        ':id' => (int) $assignment['staff_id'],
    ]);

    $assignedStaff = $staffStmt->fetch(PDO::FETCH_ASSOC);
}

$isOverdue = StatusService::isOverdue(
    (string) $issue['status'],
    new DateTimeImmutable((string) $issue['updated_at'])
);

$locationParts = [];

if ($location !== false) {
    $locationParts[] = (string) $location['building_name'];

    if (!empty($location['location_name'])) {
        $locationParts[] = (string) $location['location_name'];
    }

    if (!empty($location['floor'])) {
        $locationParts[] = 'Floor ' . (string) $location['floor'];
    }

    if (!empty($location['room_no'])) {
        $locationParts[] = 'Room ' . (string) $location['room_no'];
    }
}

$locationName = $locationParts !== []
    ? implode(' - ', $locationParts)
    : 'Unknown';

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
        <a href="index.php" class="active">All Issues</a>
        <a href="categories.php">Categories</a>
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
                <h1><?= escapeHtml($issue['title']) ?></h1>
                <p>Issue <?= (int) $issue['id'] ?></p>
            </div>

            <a href="index.php" class="btn">
                Back to All Issues
            </a>
        </div>

        <?php if ($isOverdue): ?>
            <section class="panel">
                <h2>Escalation Required</h2>
                <p>
                    <?= escapeHtml(
                        StatusService::getEscalationReason(
                            (string) $issue['status']
                        )
                    ) ?>
                </p>
            </section>
        <?php endif; ?>

        <section class="panel">

            <h2>Issue Information</h2>

            <p>
                <strong>Status:</strong>
                <?= escapeHtml($issue['status']) ?>
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
                <strong>Category:</strong>
                <?= escapeHtml($categoryName ?: 'Unknown') ?>
            </p>

            <p>
                <strong>Location:</strong>
                <?= escapeHtml($locationName) ?>
            </p>

            <p>
                <strong>Community Confirmations:</strong>
                <?= $confirmationCount ?>
            </p>

            <p>
                <strong>Reported:</strong>
                <?= escapeHtml(
                    date(
                        'M j, Y g:i A',
                        strtotime((string) $issue['created_at'])
                    )
                ) ?>
            </p>

            <p>
                <strong>Last Updated:</strong>
                <?= escapeHtml(
                    date(
                        'M j, Y g:i A',
                        strtotime((string) $issue['updated_at'])
                    )
                ) ?>
            </p>

            <h3>Description</h3>

            <p>
                <?= nl2br(escapeHtml($issue['description'])) ?>
            </p>

            <?php if (!empty($issue['rejection_note'])): ?>

                <h3>Rejection Note</h3>

                <p>
                    <?= nl2br(
                        escapeHtml($issue['rejection_note'])
                    ) ?>
                </p>

            <?php endif; ?>

        </section>

        <section class="panel">

            <h2>Reporter</h2>

            <?php if ($reporter !== false): ?>

                <p>
                    <strong>Name:</strong>
                    <?= escapeHtml(
                        $reporter['f_name'] . ' ' . $reporter['l_name']
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

            <?php if ($assignment === null): ?>

                <p>This issue has not been assigned yet.</p>

                <?php if (
                    (string) $issue['status'] === StatusService::PENDING
                ): ?>

                    <a
                        href="assign.php?id=<?= $issueId ?>"
                        class="btn btn-primary"
                    >
                        Assign Issue
                    </a>

                <?php endif; ?>

            <?php else: ?>

                <?php if ($assignedStaff !== false): ?>

                    <p>
                        <strong>Assigned Staff:</strong>
                        <?= escapeHtml(
                            $assignedStaff['f_name']
                            . ' '
                            . $assignedStaff['l_name']
                        ) ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= escapeHtml($assignedStaff['email']) ?>
                    </p>

                <?php endif; ?>

                <p>
                    <strong>Assigned:</strong>
                    <?= escapeHtml(
                        date(
                            'M j, Y g:i A',
                            strtotime(
                                (string) $assignment['assigned_at']
                            )
                        )
                    ) ?>
                </p>

                <?php if (!empty($assignment['assignment_note'])): ?>

                    <p>
                        <strong>Assignment Note:</strong>
                        <?= nl2br(
                            escapeHtml($assignment['assignment_note'])
                        ) ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($assignment['resolution_note'])): ?>

                    <p>
                        <strong>Resolution Note:</strong>
                        <?= nl2br(
                            escapeHtml($assignment['resolution_note'])
                        ) ?>
                    </p>

                <?php endif; ?>

            <?php endif; ?>

            <?php if (
                (string) $issue['status'] === StatusService::PENDING
            ): ?>

                <a
                    href="reject.php?id=<?= $issueId ?>"
                    class="btn btn-danger"
                >
                    Reject Issue
                </a>

            <?php endif; ?>

        </section>

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

                    <?php foreach ($statusHistory as $history): ?>
                        <tr>
                            <td>
                                <?= escapeHtml(
                                    $history['old_status'] ?? 'None'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($history['new_status']) ?>
                            </td>

                            <td>
                                <?= (int) $history['changed_by'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($history['note'] ?? '') ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    date(
                                        'M j, Y g:i A',
                                        strtotime(
                                            (string) $history['changed_at']
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
                                <?= escapeHtml(
                                    date(
                                        'M j, Y g:i A',
                                        strtotime(
                                            (string) $comment['created_at']
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
