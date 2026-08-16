<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimRepository.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimService.php';

require_role(['student']);

$item = null;
$errorMessage = null;
$successMessage = null;

$itemId = (int) ($_GET['id'] ?? $_POST['item_id'] ?? 0);
$claimMessage = trim((string) ($_POST['claim_msg'] ?? ''));

function escapeHtml(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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
        throw new InvalidArgumentException(
            'Invalid Lost & Found item.'
        );
    }

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
        );
    }

    $userId = currentUserId();

    if ($userId <= 0) {
        throw new RuntimeException(
            'You must be logged in to submit a claim.'
        );
    }

    $lostFoundRepository = new LostFoundRepository($pdo);

    $lostFoundService = new LostFoundService(
        $lostFoundRepository
    );

    $item = $lostFoundService->getItem($itemId);

    if (($item['status'] ?? '') !== 'Approved') {
        throw new RuntimeException(
            'This item is not available for claiming.'
        );
    }

    if ((int) ($item['posted_by'] ?? 0) === $userId) {
        throw new RuntimeException(
            'You cannot submit a claim for your own item.'
        );
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException(
                'Invalid CSRF token.'
            );
        }

        $claimRepository = new ClaimRepository($pdo);

        $claimService = new ClaimService(
            $claimRepository,
            $lostFoundRepository
        );

        $claimId = $claimService->submitClaim(
            $itemId,
            $userId,
            $claimMessage
        );

        $successMessage =
            'Your claim was submitted successfully and is waiting for admin review.';

        $claimMessage = '';
    }
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Submit Claim | CampusFix</title>

    <link
        rel="stylesheet"
        href="../../assets/css/app.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/forms.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/responsive.css"
    >
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="../dashboard.php">
            Dashboard
        </a>

        <a href="../issues/report.php">
            Report Issue
        </a>

        <a href="../issues/my_issues.php">
            My Issues
        </a>

        <a
            href="index.php"
            class="active"
        >
            Lost & Found
        </a>

        <a href="create.php">
            Add Lost/Found Item
        </a>

        <a href="my_items.php">
            My Items
        </a>

        <a href="../../logout.php">
            Logout
        </a>
    </aside>

    <main class="main-content">

        <div class="page-header">

            <div>
                <h1>Submit Claim</h1>

                <p>
                    Explain why you believe this item belongs to you.
                </p>
            </div>

            <?php if ($itemId > 0): ?>

                <a href="detail.php?id=<?= $itemId ?>">
                    Back to Item
                </a>

            <?php else: ?>

                <a href="index.php">
                    Back to Lost & Found
                </a>

            <?php endif; ?>

        </div>

        <?php if ($errorMessage !== null): ?>

            <section>

                <p>
                    <?= escapeHtml($errorMessage) ?>
                </p>

                <a href="index.php">
                    Return to Lost & Found
                </a>

            </section>

        <?php elseif ($successMessage !== null): ?>

            <section>

                <p>
                    <?= escapeHtml($successMessage) ?>
                </p>

                <a href="index.php">
                    Return to Lost & Found
                </a>

                <a href="my_items.php">
                    View My Items
                </a>

            </section>

        <?php elseif ($item !== null): ?>

            <section>

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

            </section>

            <section>

                <form
                    method="post"
                    action="claim.php"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapeHtml(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="item_id"
                        value="<?= (int) $item['item_id'] ?>"
                    >

                    <div>

                        <label for="claim_msg">
                            Claim Explanation
                        </label>

                        <textarea
                            name="claim_msg"
                            id="claim_msg"
                            rows="7"
                            maxlength="2000"
                            required
                            placeholder="Describe identifying details or other information that proves this item belongs to you."
                        ><?= escapeHtml($claimMessage) ?></textarea>

                    </div>

                    <div>

                        <button type="submit">
                            Submit Claim
                        </button>

                        <a
                            href="detail.php?id=<?= (int) $item['item_id'] ?>"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>

</html>