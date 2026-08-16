<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimRepository.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimService.php';

require_role(['admin']);

$pendingClaims = [];
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

    $lostFoundRepository = new LostFoundRepository($pdo);
    $claimRepository = new ClaimRepository($pdo);

    $claimService = new ClaimService(
        $claimRepository,
        $lostFoundRepository
    );

    $pendingClaims = $claimService->getPendingClaims();
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pending Claims | CampusFix</title>

    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="../dashboard.php">
            Dashboard
        </a>

        <a href="pending.php">
            Pending Lost & Found
        </a>

        <a href="claims.php" class="active">
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
                <h1>Pending Claims</h1>

                <p>
                    Review Lost & Found ownership claims waiting for approval.
                </p>
            </div>
        </div>

        <?php if ($errorMessage !== null): ?>

            <section>
                <p>
                    <?= escapeHtml($errorMessage) ?>
                </p>
            </section>

        <?php elseif ($pendingClaims === []): ?>

            <section>
                <p>
                    There are no claims waiting for review.
                </p>
            </section>

        <?php else: ?>

            <section>

                <?php foreach ($pendingClaims as $claim): ?>

                    <article>

                        <h2>
                            <?= escapeHtml(
                                $claim['item_name'] ?? 'Unnamed Item'
                            ) ?>
                        </h2>

                        <p>
                            <strong>Claim ID:</strong>
                            #<?= (int) ($claim['claim_id'] ?? 0) ?>
                        </p>

                        <p>
                            <strong>Item Type:</strong>
                            <?= escapeHtml(
                                $claim['item_type'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Item Status:</strong>
                            <?= escapeHtml(
                                $claim['item_status'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Claim Status:</strong>
                            <?= escapeHtml(
                                $claim['status'] ?? ''
                            ) ?>
                        </p>

                        <p>
                            <strong>Submitted By:</strong>
                            User #<?= (int) ($claim['submitted_by'] ?? 0) ?>
                        </p>

                        <p>
                            <strong>Claim Message:</strong>
                        </p>

                        <p>
                            <?= nl2br(
                                escapeHtml(
                                    $claim['claim_msg'] ?? ''
                                )
                            ) ?>
                        </p>

                        <p>
                            <strong>Submitted:</strong>
                            <?= escapeHtml(
                                $claim['created_at'] ?? ''
                            ) ?>
                        </p>

                        <a
                            href="review.php?type=claim&id=<?= (int) ($claim['claim_id'] ?? 0) ?>"
                        >
                            Review Claim
                        </a>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>