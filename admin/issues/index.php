<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';
require_once __DIR__ . '/../../modules/issues/IssueService.php';
require_once __DIR__ . '/../../modules/issues/StatusService.php';

require_role(['admin']);

$status = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$locationId = (int) ($_GET['location_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

if ($status !== '' && !StatusService::isValidStatus($status)) {
    $status = '';
}

if ($priority !== '' && !IssueService::isValidPriority($priority)) {
    $priority = '';
}

$filters = [];

if ($status !== '') {
    $filters['status'] = $status;
}

if ($priority !== '') {
    $filters['priority'] = $priority;
}

if ($categoryId > 0) {
    $filters['category_id'] = $categoryId;
}

if ($locationId > 0) {
    $filters['location_id'] = $locationId;
}

if ($search !== '') {
    $filters['search'] = $search;
}

$repository = new IssueRepository($pdo);
$issues = $repository->findAll($filters);

$categories = $pdo
    ->query(
        "SELECT id, name
         FROM issue_categories
         ORDER BY name ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo
    ->query(
        "SELECT
            l.l_id,
            b.building_name,
            l.location_name,
            l.room_no
         FROM locations AS l
         INNER JOIN building AS b
            ON b.building_id = l.building_id
         ORDER BY
            b.building_name ASC,
            l.location_name ASC,
            l.room_no ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$categoryNames = [];

foreach ($categories as $category) {
    $categoryNames[(int) $category['id']] = (string) $category['name'];
}

$locationNames = [];

foreach ($locations as $location) {
    $parts = [
        (string) $location['building_name'],
    ];

    if (!empty($location['location_name'])) {
        $parts[] = (string) $location['location_name'];
    }

    if (!empty($location['room_no'])) {
        $parts[] = 'Room ' . (string) $location['room_no'];
    }

    $locationNames[(int) $location['l_id']] = implode(' - ', $parts);
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

    <title>All Issues | CampusFix</title>

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
        <a href="index.php" class="active">All Issues</a>
        <a href="categories.php">Categories</a>
        <a href="escalated.php">Escalated Issues</a>
        <a href="../lostfound/pending.php">Lost & Found</a>
        <a href="../lostfound/claims.php">Claims</a>
        <a href="../analytics.php">Analytics</a>
        <a href="../users/manage.php">Users</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>All Issues</h1>
                <p>
                    Review, search and manage reported campus issues.
                </p>
            </div>
        </div>

        <section class="panel">

            <form method="GET" action="index.php">

                <div class="form-group">
                    <label for="search">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="<?= escapeHtml($search) ?>"
                        placeholder="Search title or description"
                    >
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >
                        <option value="">All Statuses</option>

                        <?php foreach (
                            [
                                'Pending',
                                'Assigned',
                                'In Progress',
                                'Resolved',
                                'Rejected',
                            ] as $statusOption
                        ): ?>
                            <option
                                value="<?= escapeHtml($statusOption) ?>"
                                <?= $status === $statusOption ? 'selected' : '' ?>
                            >
                                <?= escapeHtml($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select
                        id="priority"
                        name="priority"
                        class="form-control"
                    >
                        <option value="">All Priorities</option>

                        <?php foreach (
                            [
                                'Low',
                                'Medium',
                                'High',
                                'Emergency',
                            ] as $priorityOption
                        ): ?>
                            <option
                                value="<?= escapeHtml($priorityOption) ?>"
                                <?= $priority === $priorityOption ? 'selected' : '' ?>
                            >
                                <?= escapeHtml($priorityOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select
                        id="category_id"
                        name="category_id"
                        class="form-control"
                    >
                        <option value="0">All Categories</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= $categoryId === (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= escapeHtml($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location_id">Location</label>
                    <select
                        id="location_id"
                        name="location_id"
                        class="form-control"
                    >
                        <option value="0">All Locations</option>

                        <?php foreach ($locations as $location): ?>
                            <option
                                value="<?= (int) $location['l_id'] ?>"
                                <?= $locationId === (int) $location['l_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= escapeHtml(
                                    $locationNames[(int) $location['l_id']]
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>

                <a href="index.php" class="btn">
                    Clear
                </a>
            </form>

        </section>

        <section class="panel">

            <h2>Reported Issues</h2>

            <?php if ($issues === []): ?>

                <p>No issues match the selected filters.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reported</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td>
                                <?= (int) $issue['id'] ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['title']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $categoryNames[
                                        (int) $issue['category_id']
                                    ] ?? 'Unknown'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $locationNames[
                                        (int) $issue['location_id']
                                    ] ?? 'Unknown'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['priority']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($issue['status']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    date(
                                        'M j, Y g:i A',
                                        strtotime(
                                            (string) $issue['created_at']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="detail.php?id=<?= (int) $issue['id'] ?>"
                                    class="btn btn-primary"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
