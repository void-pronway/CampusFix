<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | CampusFix</title>

    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="issues/index.php">All Issues</a>
        <a href="issues/categories.php">Categories</a>
        <a href="lostfound/pending.php">Lost & Found</a>
        <a href="lostfound/claims.php">Claims</a>
        <a href="analytics.php" class="active">Analytics</a>
        <a href="users/manage.php">Users</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Analytics</h1>
                <p>Campus issue statistics and reports.</p>
            </div>
        </div>

        <section class="panel">
            <h2>Issues by Status</h2>
            <canvas id="statusChart"></canvas>
        </section>

        <section class="panel">
            <h2>Issues by Category</h2>
            <canvas id="categoryChart"></canvas>
        </section>

    </main>

</div>

<script>
new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: ['Pending', 'Assigned', 'In Progress', 'Resolved', 'Rejected'],
        datasets: [{
            label: 'Issues',
            data: [0, 0, 0, 0, 0]
        }]
    }
});

new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: ['Electrical', 'Internet', 'Cleaning', 'Water', 'Other'],
        datasets: [{
            label: 'Issues',
            data: [0, 0, 0, 0, 0]
        }]
    }
});
</script>

</body>
</html>