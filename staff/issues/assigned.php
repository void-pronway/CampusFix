<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../modules/issues/IssueRepository.php';

require_role(['staff']);

$staffId = (int) $_SESSION['user_id'];

$repository = new IssueRepository($pdo);
$assignments = $repository->findAssignmentsByStaff($staffId);

$categories = $pdo
    ->query(
        "SELECT id, name
         FROM issue_categories
         ORDER BY name ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$categoryNames = [];

foreach ($categories as $category) {
    $categoryNames[(int) $category['id']] = $category['name'];
}

$locations = $pdo
    ->query(
        "SELECT
            l.l_id,
            l.location_name,
            b.building_name
         FROM locations AS l
         INNER JOIN building AS b
            ON b.building_id = l.building_id
         ORDER BY b.building_name ASC, l.location_name ASC"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$locationNames = [];

foreach ($locations as $location) {
    $locationNames[(int) $location['l_id']] =
        $location['building_name']
        . ' - '
        . $location['location_name'];
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

    <title>Assigned Issues | CampusFix</title>

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
        <a href="assigned.php" class="active">Assigned Issues</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Assigned Issues</h1>
                <p>View and manage issues assigned to you.</p>
            </div>
        </div>

        <section class="panel">

            <?php if ($assignments === []): ?>

                <p>No issues have been assigned to you yet.</p>

            <?php else: ?>

                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Issue</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td>
                                <?= escapeHtml($assignment['title']) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $categoryNames[
                                        (int) $assignment['category_id']
                                    ] ?? 'Unknown'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml(
                                    $locationNames[
                                        (int) $assignment['location_id']
                                    ] ?? 'Unknown'
                                ) ?>
                            </td>

                            <td>
                                <?= escapeHtml($assignment['priority']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($assignment['status']) ?>
                            </td>

                            <td>
                                <?= escapeHtml($assignment['assigned_at']) ?>
                            </td>

                            <td>
                                <a
                                    href="detail.php?id=<?= (int) $assignment['issue_id'] ?>"
                                    class="btn"
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
