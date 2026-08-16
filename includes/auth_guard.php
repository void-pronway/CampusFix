<?php

require_once __DIR__ . '/bootstrap.php';

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(array $allowedRoles): void
{
    require_login();

    $role = $_SESSION['role'] ?? '';

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}