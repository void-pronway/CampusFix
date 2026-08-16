<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../modules/issues/IssueService.php';
require_once __DIR__ . '/../../modules/issues/DuplicateService.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

require_role(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token.');
}

$userId = (int) $_SESSION['user_id'];
$csrfToken = csrf_token();

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = (int) ($_POST['category_id'] ?? 0);
$locationId = (int) ($_POST['location_id'] ?? 0);
$priority = trim((string) ($_POST['priority'] ?? 'Medium'));
$visibility = trim((string) ($_POST['visibility'] ?? 'Public'));

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function removePendingImage(array $pendingIssue): void
{
    $imagePath = (string) ($pendingIssue['image_path'] ?? '');

    if (
        $imagePath === ''
        || !str_starts_with(
            $imagePath,
            'assets/uploads/issues/'
        )
    ) {
        return;
    }

    $absolutePath = __DIR__ . '/../../' . $imagePath;

    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
}

$previousPendingIssue = $_SESSION['pending_issue'] ?? null;

if (is_array($previousPendingIssue)) {
    removePendingImage($previousPendingIssue);
}

unset($_SESSION['pending_issue']);

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

if ($errors === []) {
    $categoryStmt = $pdo->prepare(
        "SELECT id
         FROM issue_categories
         WHERE id = :id
         LIMIT 1"
    );

    $categoryStmt->execute([
        ':id' => $categoryId,
    ]);

    if ($categoryStmt->fetchColumn() === false) {
        $errors[] = 'The selected category does not exist.';
    }

    $locationStmt = $pdo->prepare(
        "SELECT l_id
         FROM locations
         WHERE l_id = :id
         LIMIT 1"
    );

    $locationStmt->execute([
        ':id' => $locationId,
    ]);

    if ($locationStmt->fetchColumn() === false) {
        $errors[] = 'The selected location does not exist.';
    }
}

$imagePath = null;

if ($errors === [] && isset($_FILES['image'])) {
    $image = $_FILES['image'];
    $uploadError = (int) ($image['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_NO_FILE) {
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = 'Unable to upload the selected image.';
        } else {
            $temporaryPath = (string) ($image['tmp_name'] ?? '');
            $imageSize = (int) ($image['size'] ?? 0);

            if ($imageSize <= 0 || $imageSize > 5 * 1024 * 1024) {
                $errors[] = 'Image size must not exceed 5 MB.';
            } elseif (!is_uploaded_file($temporaryPath)) {
                $errors[] = 'Invalid uploaded image.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($temporaryPath);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                ];

                if (
                    !is_string($mimeType)
                    || !array_key_exists($mimeType, $allowedTypes)
                ) {
                    $errors[] = 'Only JPEG and PNG images are allowed.';
                } else {
                    $uploadDirectory =
                        __DIR__ . '/../../assets/uploads/issues';

                    if (
                        !is_dir($uploadDirectory)
                        && !mkdir(
                            $uploadDirectory,
                            0775,
                            true
                        )
                        && !is_dir($uploadDirectory)
                    ) {
                        $errors[] =
                            'Unable to prepare the image upload directory.';
                    } else {
                        try {
                            $fileName =
                                bin2hex(random_bytes(16))
                                . '.'
                                . $allowedTypes[$mimeType];

                            $absolutePath =
                                $uploadDirectory
                                . DIRECTORY_SEPARATOR
                                . $fileName;

                            if (
                                !move_uploaded_file(
                                    $temporaryPath,
                                    $absolutePath
                                )
                            ) {
                                $errors[] =
                                    'Unable to save the uploaded image.';
                            } else {
                                $imagePath =
                                    'assets/uploads/issues/'
                                    . $fileName;
                            }
                        } catch (Throwable $e) {
                            $errors[] =
                                'Unable to save the uploaded image.';
                        }
                    }
                }
            }
        }
    }
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

    $_SESSION['pending_issue'] = [
        'title' => $title,
        'description' => $description,
        'category_id' => $categoryId,
        'location_id' => $locationId,
        'priority' => $priority,
        'visibility' => $visibility,
        'image_path' => $imagePath,
    ];
}
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
                            <?= escapeHtml($error) ?>
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
                        name="csrf_token"
                        value="<?= escapeHtml($csrfToken) ?>"
                    >

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
                                <?= escapeHtml($issue['title']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['priority']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['status']) ?>
                            </td>

                            <td>
                                <form
                                    action="confirm.php"
                                    method="POST"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= escapeHtml($csrfToken) ?>"
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
                        name="csrf_token"
                        value="<?= escapeHtml($csrfToken) ?>"
                    >

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
