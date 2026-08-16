<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../modules/dashboard/DashboardService.php';

require_role(['staff']);

$dashboardService = new DashboardService($pdo);
$stats = $dashboardService->getStaffDashboardStats((int) $_SESSION['user_id']);
$recentIssues = $dashboardService->getRecentStaffIssues(
    (int) $_SESSION['user_id']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | CampusFix</title>

    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/forms.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="issues/assigned.php">Assigned Issues</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Staff Dashboard</h1>
                <p>Manage your assigned campus issues.</p>
            </div>
        </div>

        <section class="stats-grid">

            <div class="stat-card">
                <h3>Assigned Issues</h3>
                <div class="number"><?= $stats['total_assigned'] ?></div>
            </div>

            <div class="stat-card">
                <h3>In Progress</h3>
                <div class="number"><?= $stats['in_progress_issues'] ?></div>
            </div>

            <div class="stat-card">
                <h3>Resolved</h3>
                <div class="number"><?= $stats['resolved_issues'] ?></div>
            </div>

            <div class="stat-card">
                <h3>Pending Tasks</h3>
                <div class="number"><?= $stats['assigned_issues'] ?></div>
            </div>

        </section>

        <section class="panel">

            <h2>Assigned Issues</h2>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Issue</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>

              <tbody>
              <?php if ($recentIssues): ?>
                  <?php foreach ($recentIssues as $issue): ?>
                     <tr>
                         <td><?= htmlspecialchars($issue['title']) ?></td>
                         <td>-</td>
                         <td><?= htmlspecialchars($issue['priority']) ?></td>
                         <td><?= htmlspecialchars($issue['status']) ?></td>
                     </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                 <tr>
                     <td colspan="4">No assigned issues yet.</td>
                 </tr>
<?php endif; ?>
</tbody>
            </table>

        </section>

    </main>

</div>

</body>
</html>