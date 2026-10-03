<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/database.php';

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS nova_order_details (
        order_id INT UNSIGNED NOT NULL PRIMARY KEY,
        owner_id INT UNSIGNED NULL,
        progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
        CONSTRAINT nova_order_fk FOREIGN KEY (order_id)
            REFERENCES orders (id) ON DELETE CASCADE,
        CONSTRAINT nova_owner_fk FOREIGN KEY (owner_id)
            REFERENCES employees (id) ON DELETE SET NULL,
        CONSTRAINT nova_progress_check CHECK (progress <= 100)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

echo "Baza danych jest gotowa.\n";
