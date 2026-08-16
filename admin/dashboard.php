<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../modules/dashboard/DashboardService.php';

require_role(['admin']);

$dashboardService = new DashboardService($pdo);
$stats = $dashboardService->getAdminDashboardStats();
$recentIssues = $dashboardService->getRecentIssues();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CampusFix</title>

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
        <a href="issues/index.php">All Issues</a>
        <a href="issues/categories.php">Categories</a>
        <a href="lostfound/pending.php">Lost & Found</a>
        <a href="lostfound/claims.php">Claims</a>
        <a href="analytics.php">Analytics</a>
        <a href="users/manage.php">Users</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>CampusFix system overview</p>
            </div>
        </div>

        <section class="stats-grid">

            <div class="stat-card">
                <h3>Total Issues</h3>
                <div class="number"><?= $stats['total_issues'] ?></div>
            </div>

            <div class="stat-card">
                <h3>Pending</h3>
                <div class="number"><?= $stats['pending_issues'] ?></div>
            </div>

            <div class="stat-card">
                <h3>Assigned</h3>
                <div class="number"><?= $stats['assigned_issues'] ?></div>
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
                <h3>Pending Claims</h3>
                <div class="number"><?= $stats['pending_claims'] ?></div>
            </div>

        </section>

        <section class="panel">

            <h2>Recent Issues</h2>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($recentIssues): ?>
                         <?php foreach ($recentIssues as $issue): ?>
                            <tr>
                               <td><?= (int) $issue['issue_id'] ?></td>
                               <td><?= htmlspecialchars($issue['title']) ?></td>
                               <td><?= htmlspecialchars($issue['priority']) ?></td>
                               <td><?= htmlspecialchars($issue['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                 <tr>
                      <td colspan="4">No issues available yet.</td>
                 </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </section>

    </main>

</div>

</body>
</html>