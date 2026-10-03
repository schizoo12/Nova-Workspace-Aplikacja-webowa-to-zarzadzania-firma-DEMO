<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!verifyToken(is_string($_GET['token'] ?? null) ? $_GET['token'] : '')) {
    http_response_code(403);
    exit('Nieprawidłowa sesja.');
}

$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'secure' => $params['secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_destroy();
header('Location: login.php');
exit;
