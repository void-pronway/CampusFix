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

if ((string) $issue['status'] !== StatusService::IN_PROGRESS) {
    http_response_code(400);
    exit('Progress can only be updated for an issue in progress.');
}

$error = '';
$progressNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $progressNote = trim((string) ($_POST['progress_note'] ?? ''));

    if ($progressNote === '') {
        $error = 'Please enter a progress update.';
    } elseif (strlen($progressNote) > 1000) {
        $error = 'Progress update must not exceed 1000 characters.';
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
                || (string) $currentIssue['status']
                    !== StatusService::IN_PROGRESS
            ) {
                throw new RuntimeException(
                    'Issue status or assignment changed before update.'
                );
            }

            $repository->addStatusLog(
                $issueId,
                $staffId,
                StatusService::IN_PROGRESS,
                StatusService::IN_PROGRESS,
                $progressNote
            );

            $updateStmt = $pdo->prepare(
                "UPDATE issues
                 SET updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );

            $updateStmt->execute([
                ':id' => $issueId,
            ]);

            $pdo->commit();

            header('Location: detail.php?id=' . $issueId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to save the progress update.';
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

    <title>Update Progress | CampusFix</title>

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
                <h1>Update Progress</h1>
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

            <form action="progress.php" method="POST">

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
                    <label for="progress_note">
                        Progress Update
                    </label>

                    <textarea
                        id="progress_note"
                        name="progress_note"
                        class="form-control"
                        rows="6"
                        maxlength="1000"
                        required
                    ><?= escapeHtml($progressNote) ?></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Progress
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>
