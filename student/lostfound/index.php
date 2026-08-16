<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';

require_role(['student']);

$items = [];
$errorMessage = null;

$type = trim((string) ($_GET['type'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));

$filters = [];

if (in_array($type, ['Lost', 'Found'], true)) {
    $filters['type'] = $type;
}

if ($search !== '') {
    $filters['search'] = $search;
}

if ($date !== '') {
    $filters['date'] = $date;
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('Database connection is not available.');
    }

    $repository = new LostFoundRepository($pdo);
    $service = new LostFoundService($repository);

    $items = $service->browseApproved($filters);
} catch (Throwable $exception) {
    $errorMessage = 'Lost & Found data is currently unavailable.';
}

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

    <title>Lost & Found | CampusFix</title>

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
                <h1>Lost & Found</h1>
                <p>Browse approved lost and found items reported on campus.</p>
            </div>

            <a href="create.php">Add Item</a>
        </div>

        <section>
            <form method="get" action="index.php">

                <div>
                    <label for="type">Item Type</label>

                    <select name="type" id="type">
                        <option value="">All</option>

                        <option
                            value="Lost"
                            <?= $type === 'Lost' ? 'selected' : '' ?>
                        >
                            Lost
                        </option>

                        <option
                            value="Found"
                            <?= $type === 'Found' ? 'selected' : '' ?>
                        >
                            Found
                        </option>
                    </select>
                </div>

                <div>
                    <label for="search">Search</label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="<?= escapeHtml($search) ?>"
                        placeholder="Search item name or description"
                    >
                </div>

                <div>
                    <label for="date">Date</label>

                    <input
                        type="date"
                        name="date"
                        id="date"
                        value="<?= escapeHtml($date) ?>"
                    >
                </div>

                <div>
                    <button type="submit">Filter</button>
                    <a href="index.php">Clear</a>
                </div>

            </form>
        </section>

        <section>

            <?php if ($errorMessage !== null): ?>

                <p><?= escapeHtml($errorMessage) ?></p>

            <?php elseif ($items === []): ?>

                <p>No approved lost or found items were found.</p>

            <?php else: ?>

                <?php foreach ($items as $item): ?>

                    <article>

                        <h3>
                            <?= escapeHtml($item['item_name'] ?? 'Unnamed Item') ?>
                        </h3>

                        <p>
                            <strong>Type:</strong>
                            <?= escapeHtml($item['item_type'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Date:</strong>
                            <?= escapeHtml($item['item_date'] ?? '') ?>
                        </p>

                        <?php if (!empty($item['description'])): ?>
                            <p>
                                <?= escapeHtml($item['description']) ?>
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

    </main>

</div>

</body>
</html>