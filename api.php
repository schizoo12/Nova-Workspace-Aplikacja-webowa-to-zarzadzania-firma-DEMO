<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function validText(mixed $value, int $limit): string
{
    if (!is_string($value)) {
        respond(['error' => 'Nieprawidłowe dane formularza.'], 422);
    }

    $text = trim($value);

    if ($text === '' || mb_strlen($text) > $limit) {
        respond(['error' => 'Wypełnij poprawnie wszystkie pola.'], 422);
    }

    return $text;
}

try {
    require __DIR__ . '/database.php';
    $user = currentUser($pdo);

    if (!$user) {
        respond(['error' => 'Zaloguj się ponownie.'], 401);
    }

    $isAdmin = $user['role'] === 'admin';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $employees = $pdo->query(
            'SELECT id, first_name, last_name, employee_type
             FROM employees
             ORDER BY last_name, first_name'
        )->fetchAll();

        $query = 'SELECT o.*, d.owner_id, d.progress
                  FROM orders o
                  LEFT JOIN nova_order_details d ON d.order_id = o.id';

        if (!$isAdmin) {
            $query .= ' WHERE d.owner_id = :owner_id
                        OR JSON_CONTAINS(o.employees, :employee_id)';
        }

        $query .= ' ORDER BY o.created_at DESC, o.id DESC';
        $statement = $pdo->prepare($query);
        $statement->execute($isAdmin ? [] : [
            'owner_id' => $user['id'],
            'employee_id' => json_encode((int) $user['id']),
        ]);

        respond([
            'employees' => $employees,
            'projects' => $statement->fetchAll(),
            'user' => $user,
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        respond(['error' => 'Niedozwolona metoda.'], 405);
    }

    if (!verifyToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        respond(['error' => 'Sesja formularza wygasła. Odśwież stronę.'], 403);
    }

    if (!$isAdmin) {
        respond(['error' => 'Ta operacja wymaga uprawnień administratora.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        respond(['error' => 'Nieprawidłowe dane.'], 400);
    }

    $action = $input['action'] ?? '';

    if ($action === 'create') {
        $name = validText($input['name'] ?? null, 255);
        $client = validText($input['client'] ?? null, 255);
        $location = validText($input['location'] ?? null, 255);
        $date = validText($input['date'] ?? null, 10);
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            respond(['error' => 'Podaj prawidłowy termin.'], 422);
        }

        $ownerId = filter_var($input['ownerId'] ?? null, FILTER_VALIDATE_INT);
        $owner = $pdo->prepare('SELECT id FROM employees WHERE id = :id');
        $owner->execute(['id' => $ownerId ?: 0]);

        if (!$owner->fetch()) {
            respond(['error' => 'Wybierz istniejącego pracownika.'], 422);
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'INSERT INTO orders
                (order_number, name, client, location, order_date,
                 status, employees_count, employees)
             VALUES
                (:number, :name, :client, :location, :date,
                 :status, :count, :employees)'
        );
        $statement->execute([
            'number' => 'PR-' . date('Y') . '-' . bin2hex(random_bytes(6)),
            'name' => $name,
            'client' => $client,
            'location' => $location,
            'date' => $date,
            'status' => 'nowe',
            'count' => 1,
            'employees' => json_encode([$ownerId]),
        ]);
        $id = (int) $pdo->lastInsertId();
        $details = $pdo->prepare(
            'INSERT INTO nova_order_details (order_id, owner_id, progress)
             VALUES (:id, :owner, 0)'
        );
        $details->execute(['id' => $id, 'owner' => $ownerId]);
        $pdo->commit();

        respond(['message' => 'Projekt został utworzony.', 'id' => $id], 201);
    }

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        respond(['error' => 'Nieprawidłowy projekt.'], 422);
    }

    if ($action === 'advance') {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT o.status, o.employees, d.progress
             FROM orders o
             LEFT JOIN nova_order_details d ON d.order_id = o.id
             WHERE o.id = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $id]);
        $project = $statement->fetch();

        if (!$project) {
            $pdo->rollBack();
            respond(['error' => 'Projekt nie istnieje.'], 404);
        }

        if ($project['status'] === 'anulowane') {
            $pdo->rollBack();
            respond(['error' => 'Anulowany projekt nie może zmieniać postępu.'], 422);
        }

        $currentProgress = $project['progress'] === null
            ? match ($project['status']) {
                'zakończone' => 100,
                'w toku' => 25,
                default => 0,
            }
            : (int) $project['progress'];
        $progress = min(100, $currentProgress + 25);
        $assigned = json_decode($project['employees'] ?? '[]', true) ?: [];
        $ownerId = isset($assigned[0]) ? (int) $assigned[0] : null;
        $owner = $pdo->prepare('SELECT id FROM employees WHERE id = :id');
        $owner->execute(['id' => $ownerId ?: 0]);
        $ownerId = $owner->fetchColumn() ?: null;
        $details = $pdo->prepare(
            'INSERT INTO nova_order_details (order_id, owner_id, progress)
             VALUES (:id, :owner, :progress)
             ON DUPLICATE KEY UPDATE progress = VALUES(progress)'
        );
        $details->execute([
            'id' => $id,
            'owner' => $ownerId,
            'progress' => $progress,
        ]);
        $statement = $pdo->prepare(
            'UPDATE orders SET status = :status WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'status' => $progress === 100 ? 'zakończone' : 'w toku',
        ]);
        $pdo->commit();

        respond(['message' => 'Postęp projektu został zapisany.']);
    }

    if ($action === 'delete') {
        $statement = $pdo->prepare('DELETE FROM orders WHERE id = :id');
        $statement->execute(['id' => $id]);

        if (!$statement->rowCount()) {
            respond(['error' => 'Projekt nie istnieje.'], 404);
        }

        respond(['message' => 'Projekt został usunięty.']);
    }

    respond(['error' => 'Nieznana operacja.'], 400);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    respond(['error' => 'Nie udało się wykonać operacji. Spróbuj ponownie.'], 500);
}
