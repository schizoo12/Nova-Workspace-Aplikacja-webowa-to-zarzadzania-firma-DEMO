CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    employee_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    client VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('nowe', 'w toku', 'zakończone', 'anulowane') NOT NULL DEFAULT 'nowe',
    employees_count INT UNSIGNED NOT NULL DEFAULT 0,
    employees JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nova_order_details (
    order_id INT UNSIGNED NOT NULL PRIMARY KEY,
    owner_id INT UNSIGNED NULL,
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT nova_order_fk FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT nova_owner_fk FOREIGN KEY (owner_id)
        REFERENCES employees (id) ON DELETE SET NULL,
    CONSTRAINT nova_progress_check CHECK (progress <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
