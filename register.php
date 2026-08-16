<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/csrf.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $fName = trim($_POST['f_name'] ?? '');
    $lName = trim($_POST['l_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $deptId = (int) ($_POST['dept_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');

    if ($fName === '' || $lName === '' || $email === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!in_array($role, ['student', 'staff'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if ($deptId <= 0) {
        $errors[] = 'Please select a department.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT user_id FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (dept_id, f_name, l_name, role, email, pass)
                VALUES
                (:dept_id, :f_name, :l_name, :role, :email, :pass)'
            );

            $stmt->execute([
                'dept_id' => $deptId,
                'f_name' => $fName,
                'l_name' => $lName,
                'role' => $role,
                'email' => $email,
                'pass' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $pdo->lastInsertId();

            if ($phone !== '') {
                $stmt = $pdo->prepare(
                    'INSERT INTO user_phones (user_id, phone)
                     VALUES (:user_id, :phone)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'phone' => $phone,
                ]);
            }

            $pdo->commit();

            header('Location: login.php?registered=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$departments = $pdo
    ->query('SELECT dept_id, d_name FROM departments ORDER BY d_name')
    ->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CampusFix</title>
</head>
<body>

<h1>Create Account</h1>

<?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" action="register.php">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(csrf_token()) ?>"
    >

    <label>First Name</label>
    <input
        type="text"
        name="f_name"
        value="<?= htmlspecialchars($fName ?? '') ?>"
        required
    >

    <br><br>

    <label>Last Name</label>
    <input
        type="text"
        name="l_name"
        value="<?= htmlspecialchars($lName ?? '') ?>"
        required
    >

    <br><br>

    <label>Email</label>
    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($email ?? '') ?>"
        required
    >

    <br><br>

    <label>Department</label>
    <select name="dept_id" required>
        <option value="">Select Department</option>

        <?php foreach ($departments as $department): ?>
            <option
                value="<?= (int) $department['dept_id'] ?>"
                <?= (($deptId ?? 0) === (int) $department['dept_id']) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($department['d_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <label>Role</label>
    <select name="role" required>
        <option value="student">Student</option>
        <option value="staff">Staff</option>
    </select>

    <br><br>

    <label>Phone</label>
    <input
        type="text"
        name="phone"
        value="<?= htmlspecialchars($phone ?? '') ?>"
    >

    <br><br>

    <label>Password</label>
    <input
        type="password"
        name="password"
        required
    >

    <br><br>

    <label>Confirm Password</label>
    <input
        type="password"
        name="confirm_password"
        required
    >

    <br><br>

    <button type="submit">Register</button>

</form>

<p>
    Already have an account?
    <a href="login.php">Login</a>
</p>

</body>
</html>