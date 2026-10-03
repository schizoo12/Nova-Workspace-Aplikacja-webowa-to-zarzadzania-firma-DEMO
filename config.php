<?php

declare(strict_types=1);

$configFile = getenv('NOVA_CONFIG_FILE') ?: '/Users/schizo/Documents/Codex/2026-10-03/rop/work/nova-config.php';

if (is_file($configFile)) {
    return require $configFile;
}

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'db' => getenv('DB_NAME') ?: 'webapp',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
];
