<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';

require_role(['student']);

$item = null;
$errorMessage = null;

$itemId = (int) ($_GET['id'] ?? 0);

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function currentUserId(): int
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    if (
        isset($_SESSION['user']) &&
        is_array($_SESSION['user']) &&
        isset($_SESSION['user']['user_id'])
    ) {
        return (int) $_SESSION['user']['user_id'];
    }

    return 0;
}

try {
    if ($itemId <= 0) {
        throw new InvalidArgumentException('Invalid Lost & Found item.');
    }

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('Database connection is not available.');
    }

    $repository = new LostFoundRepository($pdo);
    $service = new LostFoundService($repository);

    $item = $service->getItem($itemId);

    if (($item['status'] ?? '') !== 'Approved') {
        throw new RuntimeException(
            'This Lost & Found item is not available for public viewing.'
        );
    }
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

$userId = currentUserId();

$canClaim = (
    $item !== null &&
    ($item['status'] ?? '') === 'Approved' &&
    (int) ($item['posted_by'] ?? 0) !== $userId
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lost & Found Details | CampusFix</title>

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
        <a href="../issues/report.php">Report Issue</a>
        <a href="../issues/my_issues.php">My Issues</a>
        <a href="index.php" class="active">Lost & Found</a>
        <a href="create.php">Add Lost/Found Item</a>
        <a href="my_items.php">My Items</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Lost & Found Details</h1>
                <p>View information about this reported item.</p>
            </div>

            <a href="index.php">Back to Lost & Found</a>
        </div>

        <?php if ($errorMessage !== null): ?>

            <section>
                <p><?= escapeHtml($errorMessage) ?></p>
                <a href="index.php">Return to Lost & Found</a>
            </section>

        <?php elseif ($item !== null): ?>

            <section>

                <h2>
                    <?= escapeHtml($item['item_name'] ?? 'Unnamed Item') ?>
                </h2>

                <p>
                    <strong>Type:</strong>
                    <?= escapeHtml($item['item_type'] ?? '') ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= escapeHtml($item['status'] ?? '') ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= escapeHtml($item['item_date'] ?? '') ?>
                </p>

                <p>
                    <strong>Category ID:</strong>
                    <?= (int) ($item['item_category_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Location ID:</strong>
                    <?= (int) ($item['location_id'] ?? 0) ?>
                </p>

                <?php if (!empty($item['description'])): ?>

                    <div>
                        <h3>Description</h3>

                        <p>
                            <?= nl2br(
                                escapeHtml($item['description'])
                            ) ?>
                        </p>
                    </div>

                <?php endif; ?>

                <?php if (!empty($item['image_path'])): ?>

                    <div>
                        <h3>Item Image</h3>

                        <img
                            src="../../<?= escapeHtml($item['image_path']) ?>"
                            alt="<?= escapeHtml(
                                $item['item_name'] ?? 'Lost and found item'
                            ) ?>"
                            style="max-width: 400px; height: auto;"
                        >
                    </div>

                <?php endif; ?>

                <div>

                    <?php if ($canClaim): ?>

                        <a
                            href="claim.php?id=<?= (int) $item['item_id'] ?>"
                        >
                            Submit Claim
                        </a>

                    <?php elseif (
                        $userId > 0 &&
                        (int) ($item['posted_by'] ?? 0) === $userId
                    ): ?>

                        <p>You posted this item.</p>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>