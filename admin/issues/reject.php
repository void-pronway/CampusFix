<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['admin']);

$adminId = (int) $_SESSION['user_id'];

$issueId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (int) ($_POST['issue_id'] ?? 0)
    : (int) ($_GET['id'] ?? 0);

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

if (
    !StatusService::canTransition(
        (string) $issue['status'],
        StatusService::REJECTED
    )
) {
    http_response_code(400);
    exit('This issue cannot be rejected in its current status.');
}

$error = '';
$rejectionNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $rejectionNote = trim((string) ($_POST['rejection_note'] ?? ''));

    if ($rejectionNote === '') {
        $error = 'Please provide a rejection reason.';
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $currentIssue = $repository->findById($issueId);

            if (
                $currentIssue === null
                || !StatusService::canTransition(
                    (string) $currentIssue['status'],
                    StatusService::REJECTED
                )
            ) {
                throw new RuntimeException(
                    'Issue status changed before rejection.'
                );
            }

            $oldStatus = (string) $currentIssue['status'];

            $repository->updateStatus(
                $issueId,
                StatusService::REJECTED,
                $rejectionNote
            );

            $repository->addStatusLog(
                $issueId,
                $adminId,
                $oldStatus,
                StatusService::REJECTED,
                $rejectionNote
            );

            $pdo->commit();

            header('Location: detail.php?id=' . $issueId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to reject the issue.';
        }
    }
}

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

    <title>Reject Issue | CampusFix</title>

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
                <h1>Reject Issue</h1>
                <p><?= escapeHtml($issue['title']) ?></p>
            </div>

            <a
                href="detail.php?id=<?= $issueId ?>"
                class="btn"
            >
                Back to Issue
            </a>
        </div>

        <section class="panel">

            <?php if ($error !== ''): ?>

                <p><?= escapeHtml($error) ?></p>

            <?php endif; ?>

            <p>
                Rejecting this issue will close it and record
                the reason in the issue history.
            </p>

            <form action="reject.php" method="POST">

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
                    <label for="rejection_note">
                        Rejection Reason
                    </label>

                    <textarea
                        id="rejection_note"
                        name="rejection_note"
                        class="form-control"
                        rows="5"
                        required
                    ><?= escapeHtml($rejectionNote) ?></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-danger"
                >
                    Reject Issue
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>
