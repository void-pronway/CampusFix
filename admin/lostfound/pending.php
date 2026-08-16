<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pendingItems = [];
$errorMessage = null;

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
        );
    }

    $repository = new LostFoundRepository($pdo);
    $service = new LostFoundService($repository);

    $pendingItems = $service->getPendingItems();
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pending Lost & Found | CampusFix</title>

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

        <a href="pending.php" class="active">
            Pending Lost & Found
        </a>

        <a href="claims.php">
            Pending Claims
        </a>

        <a href="../analytics.php">
            Analytics
        </a>

        <a href="../../logout.php">
            Logout
        </a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Pending Lost & Found Items</h1>

                <p>
                    Review Lost & Found submissions waiting for approval.
                </p>
            </div>
        </div>

        <?php if ($errorMessage !== null): ?>

            <section>
                <p>
                    <?= escapeHtml($errorMessage) ?>
                </p>
            </section>

        <?php elseif ($pendingItems === []): ?>

            <section>
                <p>
                    There are no Lost & Found items waiting for review.
                </p>
            </section>

        <?php else: ?>

            <section>

                <?php foreach ($pendingItems as $item): ?>

                    <article>

                        <h2>
                            <?= escapeHtml(
                                $item['item_name'] ?? 'Unnamed Item'
                            ) ?>
                        </h2>

                        <p>
                            <strong>Type:</strong>
                            <?= escapeHtml(
                                $item['item_type'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?= escapeHtml(
                                $item['status'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Date:</strong>
                            <?= escapeHtml(
                                $item['item_date'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Category ID:</strong>
                            <?= (int) ($item['item_category_id'] ?? 0) ?>
                        </p>

                        <p>
                            <strong>Location ID:</strong>
                            <?= (int) ($item['location_id'] ?? 0) ?>
                        </p>

                        <p>
                            <strong>Posted By:</strong>
                            User #<?= (int) ($item['posted_by'] ?? 0) ?>
                        </p>

                        <?php if (!empty($item['description'])): ?>

                            <p>
                                <?= nl2br(
                                    escapeHtml(
                                        $item['description']
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>

                        <a
                            href="review.php?type=item&id=<?= (int) ($item['item_id'] ?? 0) ?>"
                        >
                            Review Item
                        </a>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>