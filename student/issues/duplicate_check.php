<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../modules/issues/IssueService.php';
require_once __DIR__ . '/../../modules/issues/DuplicateService.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report.php');
    exit;
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(503);

    exit(
        'Database connection is not available yet. '
        . 'The shared CampusFix bootstrap still needs to provide $pdo.'
    );
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : 0;

if ($userId <= 0) {
    http_response_code(401);

    exit(
        'Login session is not available yet. '
        . 'Authentication integration is still pending.'
    );
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$locationId = (int) ($_POST['location_id'] ?? 0);
$priority = trim($_POST['priority'] ?? 'Medium');
$visibility = trim($_POST['visibility'] ?? 'Public');

$errors = [];

if (!IssueService::validateTitle($title)) {
    $errors[] = 'Please provide a valid issue title.';
}

if (!IssueService::validateDescription($description)) {
    $errors[] = 'Please provide an issue description.';
}

if ($categoryId <= 0) {
    $errors[] = 'Please select a valid category.';
}

if ($locationId <= 0) {
    $errors[] = 'Please select a valid location.';
}

if (!IssueService::isValidPriority($priority)) {
    $errors[] = 'Please select a valid priority.';
}

if (!IssueService::isValidVisibility($visibility)) {
    $errors[] = 'Please select a valid visibility option.';
}

$repository = new IssueRepository($pdo);

$duplicates = [];

if ($errors === []) {
    $candidates = $repository->findOpenDuplicateCandidates(
        $categoryId,
        $locationId
    );

    foreach ($candidates as $candidate) {
        if (
    DuplicateService::isPotentialDuplicate(
        (string) $candidate['title'],
        $title,
        (int) $candidate['category_id'],
        $categoryId,
        (int) $candidate['location_id'],
        $locationId
    )
) {
    $duplicates[] = $candidate;
}
    }
}

$pendingIssue = [
    'title' => $title,
    'description' => $description,
    'category_id' => $categoryId,
    'location_id' => $locationId,
    'priority' => $priority,
    'visibility' => $visibility,
];

$_SESSION['pending_issue'] = $pendingIssue;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Duplicate Check | CampusFix</title>

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
        <a href="report.php" class="active">Report Issue</a>
        <a href="my_issues.php">My Issues</a>
        <a href="../lostfound/index.php">Lost & Found</a>
        <a href="../lostfound/create.php">Add Lost/Found Item</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Duplicate Issue Check</h1>
                <p>
                    CampusFix checks for similar unresolved reports
                    before creating another issue.
                </p>
            </div>
        </div>

        <?php if ($errors !== []): ?>

            <section class="panel">
                <h2>Please correct the following</h2>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p>
                    <a href="report.php" class="btn btn-primary">
                        Back to Report Form
                    </a>
                </p>
            </section>

        <?php elseif ($duplicates === []): ?>

            <section class="panel">
                <h2>No Similar Open Issue Found</h2>

                <p>
                    Your report does not appear to duplicate an
                    existing unresolved issue.
                </p>

                <form action="confirm.php" method="POST">
                    <input
                        type="hidden"
                        name="action"
                        value="create"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit New Issue
                    </button>
                </form>
            </section>

        <?php else: ?>

            <section class="panel">
                <h2>Similar Issues Found</h2>

                <p>
                    If one of these reports describes the same
                    problem, confirm it instead of creating a
                    duplicate report.
                </p>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Issue</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($duplicates as $issue): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    (string) $issue['title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $issue['priority'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $issue['status'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <form
                                    action="confirm.php"
                                    method="POST"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="confirm"
                                    >

                                    <input
                                        type="hidden"
                                        name="issue_id"
                                        value="<?= (int) $issue['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-success"
                                    >
                                        Confirm Existing
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

                <form action="confirm.php" method="POST">
                    <input
                        type="hidden"
                        name="action"
                        value="create"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit as New Issue Anyway
                    </button>
                </form>
            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>
