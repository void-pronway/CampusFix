<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/AssignmentService.php';
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

if (!AssignmentService::canAssign((string) $issue['status'])) {
    http_response_code(400);
    exit('This issue cannot be assigned in its current status.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $staffId = (int) ($_POST['staff_id'] ?? 0);
    $assignmentNote = AssignmentService::normalizeNote(
        $_POST['assignment_note'] ?? null
    );

    if (
        !AssignmentService::validateAssignment(
            $issueId,
            $staffId,
            $adminId
        )
    ) {
        $error = 'Please select a valid staff member.';
    } else {
        $staffStmt = $pdo->prepare(
            "SELECT user_id
             FROM users
             WHERE user_id = :user_id
               AND role = 'staff'
               AND status = 'active'
             LIMIT 1"
        );

        $staffStmt->execute([
            ':user_id' => $staffId,
        ]);

        $staffExists = $staffStmt->fetchColumn();

        if ($staffExists === false) {
            $error = 'The selected staff member is not available.';
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $currentIssue = $repository->findById($issueId);

            if (
                $currentIssue === null
                || !AssignmentService::canAssign(
                    (string) $currentIssue['status']
                )
            ) {
                throw new RuntimeException(
                    'Issue status changed before assignment.'
                );
            }

            $oldStatus = (string) $currentIssue['status'];

            $repository->createAssignment(
                $issueId,
                $staffId,
                $adminId,
                $assignmentNote
            );

            $repository->updateStatus(
                $issueId,
                StatusService::ASSIGNED
            );

            $repository->addStatusLog(
                $issueId,
                $adminId,
                $oldStatus,
                StatusService::ASSIGNED,
                $assignmentNote
            );

            $pdo->commit();

            header('Location: detail.php?id=' . $issueId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to assign the issue.';
        }
    }
}

$staffMembers = $pdo
    ->query(
        "SELECT
            user_id,
            f_name,
            l_name,
            email
         FROM users
         WHERE role = 'staff'
           AND status = 'active'
         ORDER BY f_name ASC, l_name ASC"
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

    <title>Assign Issue | CampusFix</title>

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
                <h1>Assign Issue</h1>
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

            <?php if ($staffMembers === []): ?>

                <p>No active staff members are available.</p>

            <?php else: ?>

                <form action="assign.php" method="POST">

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
                        <label for="staff_id">Staff Member</label>

                        <select
                            id="staff_id"
                            name="staff_id"
                            class="form-control"
                            required
                        >
                            <option value="">
                                Select Staff
                            </option>

                            <?php foreach ($staffMembers as $staff): ?>
                                <option
                                    value="<?= (int) $staff['user_id'] ?>"
                                >
                                    <?= escapeHtml(
                                        $staff['f_name']
                                        . ' '
                                        . $staff['l_name']
                                        . ' - '
                                        . $staff['email']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assignment_note">
                            Assignment Note
                        </label>

                        <textarea
                            id="assignment_note"
                            name="assignment_note"
                            class="form-control"
                            rows="4"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Assign Issue
                    </button>

                </form>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
