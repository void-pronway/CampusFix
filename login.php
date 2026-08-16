<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "SELECT user_id, f_name, l_name, email, role, status, pass
             FROM users
             WHERE email = :email
             LIMIT 1"
        );

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (
            !$user ||
            $user['status'] !== 'active' ||
            !password_verify($password, $user['pass'])
        ) {
            $errors[] = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['f_name'] . ' ' . $user['l_name'];
            $_SESSION['email'] = $user['email'];

            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;

                case 'staff':
                    header('Location: staff/dashboard.php');
                    break;

                default:
                    header('Location: student/dashboard.php');
                    break;
            }

            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusFix</title>
</head>
<body>

<h1>Login</h1>

<?php if (isset($_GET['registered'])): ?>
    <p>Registration successful. You can now log in.</p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" action="login.php">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(csrf_token()) ?>"
    >

    <label>Email</label>
    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($email ?? '') ?>"
        required
    >

    <br><br>

    <label>Password</label>
    <input
        type="password"
        name="password"
        required
    >

    <br><br>

    <button type="submit">Login</button>

</form>

<p>
    Don't have an account?
    <a href="register.php">Register</a>
</p>

</body>
</html>