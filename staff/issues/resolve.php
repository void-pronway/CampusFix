<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['staff']);

$staffId = (int) $_SESSION['user_id'];

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

$assignment = $repository->getLatestAssignment($issueId);

if (
    $assignment === null
    || (int) $assignment['staff_id'] !== $staffId
) {
    http_response_code(403);
    exit('You are not assigned to this issue.');
}

if (
    !StatusService::canTransition(
        (string) $issue['status'],
        StatusService::RESOLVED
    )
) {
    http_response_code(400);
    exit('This issue cannot be resolved in its current status.');
}

$error = '';
$resolutionNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $resolutionNote = trim(
        (string) ($_POST['resolution_note'] ?? '')
    );

    if ($resolutionNote === '') {
        $error = 'Please provide a resolution note.';
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $currentIssue = $repository->findById($issueId);
            $currentAssignment = $repository->getLatestAssignment($issueId);

            if (
                $currentIssue === null
                || $currentAssignment === null
                || (int) $currentAssignment['staff_id'] !== $staffId
                || !empty($currentAssignment['resolved_at'])
                || !StatusService::canTransition(
                    (string) $currentIssue['status'],
                    StatusService::RESOLVED
                )
            ) {
                throw new RuntimeException(
                    'Issue status or assignment changed before resolution.'
                );
            }

            $oldStatus = (string) $currentIssue['status'];

            $repository->markAssignmentResolved(
                $issueId,
                $resolutionNote
            );

            $repository->updateStatus(
                $issueId,
                StatusService::RESOLVED
            );

            $repository->addStatusLog(
                $issueId,
                $staffId,
                $oldStatus,
                StatusService::RESOLVED,
                $resolutionNote
            );

            $pdo->commit();

            header('Location: detail.php?id=' . $issueId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to resolve the issue.';
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

    <title>Resolve Issue | CampusFix</title>

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
                <h1>Resolve Issue</h1>
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
                Resolving this issue will mark the assignment
                as completed and close the issue.
            </p>

            <form action="resolve.php" method="POST">

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
                    <label for="resolution_note">
                        Resolution Note
                    </label>

                    <textarea
                        id="resolution_note"
                        name="resolution_note"
                        class="form-control"
                        rows="6"
                        required
                    ><?= escapeHtml($resolutionNote) ?></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Resolve Issue
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>
