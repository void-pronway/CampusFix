<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimRepository.php';
require_once __DIR__ . '/../../modules/lostfound/ClaimService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$type = strtolower(trim((string) ($_GET['type'] ?? $_POST['type'] ?? '')));
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$record = null;
$errorMessage = null;

function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function currentAdminId(): int
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
    if (!in_array($type, ['item', 'claim'], true)) {
        throw new InvalidArgumentException(
            'Invalid review type.'
        );
    }

    if ($id <= 0) {
        throw new InvalidArgumentException(
            'Invalid review ID.'
        );
    }

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not available.'
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = strtolower(
            trim((string) ($_POST['action'] ?? ''))
        );

        if (!in_array($action, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException(
                'Invalid review action.'
            );
        }

        if ($type === 'item') {
            if ($action === 'approve') {
                $lostFoundService->approveItem($id);
            } else {
                $lostFoundService->rejectItem($id);
            }

            header('Location: pending.php');
            exit;
        }

        $adminId = currentAdminId();

        if ($adminId <= 0) {
            throw new RuntimeException(
                'You must be logged in as an admin to review claims.'
            );
        }

        $adminNote = trim(
            (string) ($_POST['admin_note'] ?? '')
        );

        if ($action === 'approve') {
            $claimService->approveClaim(
                $id,
                $adminId,
                $adminNote === '' ? null : $adminNote
            );
        } else {
            $claimService->rejectClaim(
                $id,
                $adminId,
                $adminNote === '' ? null : $adminNote
            );
        }

        header('Location: claims.php');
        exit;
    }

    if ($type === 'item') {
        $record = $lostFoundService->getItem($id);
    } else {
        $record = $claimService->getClaim($id);
    }
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review Lost & Found | CampusFix</title>

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
                <h1>
                    <?= $type === 'claim'
                        ? 'Review Claim'
                        : 'Review Lost & Found Item' ?>
                </h1>
            </div>

            <?php if ($type === 'claim'): ?>

                <a href="claims.php">
                    Back to Claims
                </a>

            <?php else: ?>

                <a href="pending.php">
                    Back to Pending Items
                </a>

            <?php endif; ?>

        </div>

        <?php if ($errorMessage !== null): ?>

            <section>

                <p>
                    <?= escapeHtml($errorMessage) ?>
                </p>

            </section>

        <?php elseif ($record !== null && $type === 'item'): ?>

            <section>

                <h2>
                    <?= escapeHtml(
                        $record['item_name'] ?? 'Unnamed Item'
                    ) ?>
                </h2>

                <p>
                    <strong>Item ID:</strong>
                    #<?= (int) ($record['item_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Type:</strong>
                    <?= escapeHtml(
                        $record['item_type'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= escapeHtml(
                        $record['status'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= escapeHtml(
                        $record['item_date'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Category ID:</strong>
                    <?= (int) ($record['item_category_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Location ID:</strong>
                    <?= (int) ($record['location_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Posted By:</strong>
                    User #<?= (int) ($record['posted_by'] ?? 0) ?>
                </p>

                <?php if (!empty($record['description'])): ?>

                    <p>
                        <strong>Description:</strong>
                    </p>

                    <p>
                        <?= nl2br(
                            escapeHtml(
                                $record['description']
                            )
                        ) ?>
                    </p>

                <?php endif; ?>

            </section>

            <?php if (($record['status'] ?? '') === 'Pending'): ?>

                <section>

                    <form method="post" action="review.php">

                        <input
                            type="hidden"
                            name="type"
                            value="item"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) ($record['item_id'] ?? 0) ?>"
                        >

                        <button
                            type="submit"
                            name="action"
                            value="approve"
                        >
                            Approve Item
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="reject"
                        >
                            Reject Item
                        </button>

                    </form>

                </section>

            <?php endif; ?>

        <?php elseif ($record !== null && $type === 'claim'): ?>

            <section>

                <h2>
                    <?= escapeHtml(
                        $record['item_name'] ?? 'Unnamed Item'
                    ) ?>
                </h2>

                <p>
                    <strong>Claim ID:</strong>
                    #<?= (int) ($record['claim_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Item ID:</strong>
                    #<?= (int) ($record['item_id'] ?? 0) ?>
                </p>

                <p>
                    <strong>Item Type:</strong>
                    <?= escapeHtml(
                        $record['item_type'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Item Status:</strong>
                    <?= escapeHtml(
                        $record['item_status'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Claim Status:</strong>
                    <?= escapeHtml(
                        $record['status'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>Submitted By:</strong>
                    User #<?= (int) ($record['submitted_by'] ?? 0) ?>
                </p>

                <p>
                    <strong>Claim Message:</strong>
                </p>

                <p>
                    <?= nl2br(
                        escapeHtml(
                            $record['claim_msg'] ?? ''
                        )
                    ) ?>
                </p>

            </section>

            <?php if (($record['status'] ?? '') === 'Pending'): ?>

                <section>

                    <form method="post" action="review.php">

                        <input
                            type="hidden"
                            name="type"
                            value="claim"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) ($record['claim_id'] ?? 0) ?>"
                        >

                        <div>
                            <label for="admin_note">
                                Admin Note
                            </label>

                            <textarea
                                name="admin_note"
                                id="admin_note"
                                rows="5"
                                maxlength="2000"
                                placeholder="Optional note about this claim decision."
                            ></textarea>
                        </div>

                        <div>

                            <button
                                type="submit"
                                name="action"
                                value="approve"
                            >
                                Approve Claim
                            </button>

                            <button
                                type="submit"
                                name="action"
                                value="reject"
                            >
                                Reject Claim
                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>

        <?php endif; ?>

    </main>

</div>

</body>
</html>