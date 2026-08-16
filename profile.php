<?php

require_once __DIR__ . '/includes/auth_guard.php';

require_login();

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        u.user_id,
        u.f_name,
        u.l_name,
        u.email,
        u.role,
        u.status,
        d.d_name AS department,
        p.phone
    FROM users u
    LEFT JOIN departments d
        ON u.dept_id = d.dept_id
    LEFT JOIN user_phones p
        ON u.user_id = p.user_id
    WHERE u.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - CampusFix</title>
</head>
<body>

<h1>My Profile</h1>

<p>
    <strong>Name:</strong>
    <?= htmlspecialchars($user['f_name'] . ' ' . $user['l_name']) ?>
</p>

<p>
    <strong>Email:</strong>
    <?= htmlspecialchars($user['email']) ?>
</p>

<p>
    <strong>Role:</strong>
    <?= htmlspecialchars($user['role']) ?>
</p>

<p>
    <strong>Status:</strong>
    <?= htmlspecialchars($user['status']) ?>
</p>

<p>
    <strong>Department:</strong>
    <?= htmlspecialchars($user['department'] ?? 'Not assigned') ?>
</p>

<p>
    <strong>Phone:</strong>
    <?= htmlspecialchars($user['phone'] ?? 'Not provided') ?>
</p>

<p>
    <a href="logout.php">Logout</a>
</p>

</body>
</html>