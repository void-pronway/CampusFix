<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimRepository.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimService.php';

require_role(['student']);

$myItems = [];
$myClaims = [];
$errorMessage = null;

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
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
        );
    }

    $userId = currentUserId();

    if ($userId <= 0) {
        throw new RuntimeException(
            'You must be logged in to view your items.'
        );
    }

    $lostFoundRepository = new LostFoundRepository($pdo);
    $lostFoundService = new LostFoundService(
        $lostFoundRepository
    );

    $claimRepository = new ClaimRepository($pdo);
    $claimService = new ClaimService(
        $claimRepository,
        $lostFoundRepository
    );

    $myItems = $lostFoundService->getUserItems($userId);
    $myClaims = $claimService->getUserClaims($userId);
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Lost & Found | CampusFix</title>

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
        <a href="index.php">Lost & Found</a>
        <a href="create.php">Add Lost/Found Item</a>
        <a href="my_items.php" class="active">My Items</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>My Lost & Found</h1>
                <p>
                    View the items you posted and the claims you submitted.
                </p>
            </div>

            <a href="create.php">
                Add Lost/Found Item
            </a>
        </div>

        <?php if ($errorMessage !== null): ?>

            <section>
                <p><?= escapeHtml($errorMessage) ?></p>
            </section>

        <?php else: ?>

            <section>

                <h2>My Posted Items</h2>

                <?php if ($myItems === []): ?>

                    <p>You have not posted any Lost & Found items yet.</p>

                <?php else: ?>

                    <?php foreach ($myItems as $item): ?>

                        <article>

                            <h3>
                                <?= escapeHtml(
                                    $item['item_name'] ?? 'Unnamed Item'
                                ) ?>
                            </h3>

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
                                href="detail.php?id=<?= (int) ($item['item_id'] ?? 0) ?>"
                            >
                                View Details
                            </a>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>

            <section>

                <h2>My Submitted Claims</h2>

                <?php if ($myClaims === []): ?>

                    <p>You have not submitted any claims yet.</p>

                <?php else: ?>

                    <?php foreach ($myClaims as $claim): ?>

                        <article>

                            <h3>
                                <?= escapeHtml(
                                    $claim['item_name'] ?? 'Unnamed Item'
                                ) ?>
                            </h3>

                            <p>
                                <strong>Item Type:</strong>
                                <?= escapeHtml(
                                    $claim['item_type'] ?? ''
                                ) ?>
                            </p>

                            <p>
                                <strong>Claim Status:</strong>
                                <?= escapeHtml(
                                    $claim['status'] ?? ''
                                ) ?>
                            </p>

                            <p>
                                <strong>Item Status:</strong>
                                <?= escapeHtml(
                                    $claim['item_status'] ?? ''
                                ) ?>
                            </p>

                            <p>
                                <strong>Claim Message:</strong>
                                <?= nl2br(
                                    escapeHtml(
                                        $claim['claim_msg'] ?? ''
                                    )
                                ) ?>
                            </p>

                            <?php if (!empty($claim['admin_note'])): ?>

                                <p>
                                    <strong>Admin Note:</strong>
                                    <?= nl2br(
                                        escapeHtml(
                                            $claim['admin_note']
                                        )
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <a
                                href="detail.php?id=<?= (int) ($claim['item_id'] ?? 0) ?>"
                            >
                                View Item
                            </a>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>