<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

require_role(['student']);

$userId = (int) $_SESSION['user_id'];
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

$isOwner = (int) $issue['reported_by'] === $userId;
$isPublic = (string) $issue['visibility'] === 'Public';

if (!$isOwner && !$isPublic) {
    http_response_code(403);
    exit('Access denied.');
}

$confirmationCount = $repository->countConfirmations($issueId);
$statusHistory = $repository->getStatusHistory($issueId);
$comments = $repository->getComments($issueId);
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
                <h1><?= escapeHtml($issue['title']) ?></h1>
                <p>
                    Issue <?= (int) $issue['id'] ?>
                </p>
            </div>

            <a href="my_issues.php" class="btn btn-primary">
                Back to My Issues
            </a>
        </div>

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
                <strong>Category ID:</strong>
                <?= (int) $issue['category_id'] ?>
            </p>

            <p>
                <strong>Location ID:</strong>
                <?= (int) $issue['location_id'] ?>
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
                                <?= escapeHtml(
                                    $history['new_status']
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $history['changed_by'] ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $history['note'] ?? ''
                                ) ?>
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

                <p>No comments have been added yet.</p>

            <?php else: ?>

                <?php foreach ($comments as $comment): ?>
                    <div class="panel">
                        <p>
                            <strong>
                                User <?= (int) $comment['user_id'] ?>
                            </strong>
                        </p>

                        <p>
                            <?= nl2br(
                                escapeHtml($comment['comment'])
                            ) ?>
                        </p>

                        <p>
                            <?= escapeHtml(
                                date(
                                    'M j, Y g:i A',
                                    strtotime(
                                        (string) $comment['created_at']
                                    )
                                )
                            ) ?>
                        </p>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

            <form action="comment.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="issue_id"
                    value="<?= $issueId ?>"
                >

                <div class="form-group">
                    <label for="comment">Add Comment</label>

                    <textarea
                        id="comment"
                        name="comment"
                        class="form-control"
                        rows="4"
                        required
                    ></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Add Comment
                </button>
            </form>
        </section>

    </main>

</div>

</body>
</html>