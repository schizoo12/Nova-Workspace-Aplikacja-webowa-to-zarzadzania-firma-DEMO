<?php

declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = realpath(__DIR__ . $path);
$publicFiles = ['index.php', 'login.php', 'dashboard.php', 'logout.php', 'api.php'];

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

if (!$file || !str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$relative = substr($file, strlen(__DIR__) + 1);

if (in_array($relative, $publicFiles, true)) {
    require $file;
    return true;
}

if (str_starts_with($relative, 'res/') && in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'js'], true)) {
    return false;
}

http_response_code(404);
exit;
