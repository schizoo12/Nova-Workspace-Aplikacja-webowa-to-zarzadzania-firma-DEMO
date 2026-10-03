<?php

declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function verifyToken(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(PDO $pdo): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, first_name, last_name, login, employee_type, role
         FROM employees
         WHERE id = :id'
    );
    $statement->execute(['id' => $_SESSION['user_id']]);

    return $statement->fetch() ?: null;
}
