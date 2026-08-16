<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundRepository.php';
require_once __DIR__ . '/../../modules/lostfound/LostFoundService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errorMessage = null;
$successMessage = null;

$categories = [];
$locations = [];

$itemType = trim((string) ($_POST['item_type'] ?? ''));
$itemName = trim((string) ($_POST['item_name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$itemCategoryId = (int) ($_POST['item_category_id'] ?? 0);
$locationId = (int) ($_POST['location_id'] ?? 0);
$itemDate = trim((string) ($_POST['item_date'] ?? ''));

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
        throw new RuntimeException('Database connection is not available.');
    }

    $categoryStatement = $pdo->query(
        'SELECT item_category_id, name
         FROM lost_found_categories
         ORDER BY name ASC'
    );

    $categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

    $locationStatement = $pdo->query(
        'SELECT l_id, floor, floor_type, room_no
         FROM locations
         ORDER BY l_id ASC'
    );

    $locations = $locationStatement->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = currentUserId();

        if ($userId <= 0) {
            throw new RuntimeException(
                'You must be logged in to submit a lost or found item.'
            );
        }

        $repository = new LostFoundRepository($pdo);
        $service = new LostFoundService($repository);

        $itemId = $service->submitItem(
            [
                'item_category_id' => $itemCategoryId,
                'location_id' => $locationId,
                'item_type' => $itemType,
                'item_name' => $itemName,
                'description' => $description,
                'item_date' => $itemDate,
                'image_path' => null,
            ],
            $userId
        );

        $successMessage =
            'Item submitted successfully. It is waiting for admin approval.';

        $itemType = '';
        $itemName = '';
        $description = '';
        $itemCategoryId = 0;
        $locationId = 0;
        $itemDate = '';
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

    <title>Add Lost/Found Item | CampusFix</title>

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
        <a href="create.php" class="active">Add Lost/Found Item</a>
        <a href="my_items.php">My Items</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Add Lost/Found Item</h1>
                <p>
                    Report an item that you lost or found on campus.
                </p>
            </div>
        </div>

        <?php if ($errorMessage !== null): ?>
            <p><?= escapeHtml($errorMessage) ?></p>
        <?php endif; ?>

        <?php if ($successMessage !== null): ?>
            <p><?= escapeHtml($successMessage) ?></p>
        <?php endif; ?>

        <section>

            <form method="post" action="create.php">

                <div>
                    <label for="item_type">Item Type</label>

                    <select
                        name="item_type"
                        id="item_type"
                        required
                    >
                        <option value="">Select type</option>

                        <option
                            value="Lost"
                            <?= $itemType === 'Lost' ? 'selected' : '' ?>
                        >
                            Lost
                        </option>

                        <option
                            value="Found"
                            <?= $itemType === 'Found' ? 'selected' : '' ?>
                        >
                            Found
                        </option>
                    </select>
                </div>

                <div>
                    <label for="item_name">Item Name</label>

                    <input
                        type="text"
                        name="item_name"
                        id="item_name"
                        maxlength="150"
                        required
                        value="<?= escapeHtml($itemName) ?>"
                        placeholder="Example: Black wallet"
                    >
                </div>

                <div>
                    <label for="item_category_id">Category</label>

                    <select
                        name="item_category_id"
                        id="item_category_id"
                        required
                    >
                        <option value="">Select category</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['item_category_id'] ?>"
                                <?= $itemCategoryId ===
                                    (int) $category['item_category_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= escapeHtml($category['name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label for="location_id">Location</label>

                    <select
                        name="location_id"
                        id="location_id"
                        required
                    >
                        <option value="">Select location</option>

                        <?php foreach ($locations as $location): ?>

                            <?php
                            $locationLabel =
                                'Location #' . (int) $location['l_id'];

                            if (!empty($location['floor'])) {
                                $locationLabel .=
                                    ' - Floor ' . $location['floor'];
                            }

                            if (!empty($location['room_no'])) {
                                $locationLabel .=
                                    ' - Room ' . $location['room_no'];
                            }
                            ?>

                            <option
                                value="<?= (int) $location['l_id'] ?>"
                                <?= $locationId === (int) $location['l_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= escapeHtml($locationLabel) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label for="item_date">Date Lost/Found</label>

                    <input
                        type="date"
                        name="item_date"
                        id="item_date"
                        required
                        max="<?= date('Y-m-d') ?>"
                        value="<?= escapeHtml($itemDate) ?>"
                    >
                </div>

                <div>
                    <label for="description">Description</label>

                    <textarea
                        name="description"
                        id="description"
                        rows="6"
                        placeholder="Describe the item and any useful details."
                    ><?= escapeHtml($description) ?></textarea>
                </div>

                <div>
                    <button type="submit">
                        Submit Item
                    </button>

                    <a href="index.php">
                        Cancel
                    </a>
                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>