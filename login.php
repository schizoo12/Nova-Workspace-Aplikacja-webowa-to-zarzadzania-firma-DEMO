<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$error = '';
$login = '';

try {
    require __DIR__ . '/database.php';

    if (currentUser($pdo)) {
        header('Location: dashboard.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_filter($attempts, static fn (int $time): bool => $time > time() - 300);

        if (!verifyToken($token)) {
            $error = 'Odśwież stronę i spróbuj ponownie.';
        } elseif (count($attempts) >= 10) {
            $error = 'Zbyt wiele prób. Spróbuj ponownie za kilka minut.';
        } elseif ($login === '' || $password === '') {
            $error = 'Podaj login i hasło.';
        } else {
            $statement = $pdo->prepare(
                'SELECT id, password FROM employees WHERE login = :login LIMIT 1'
            );
            $statement->execute(['login' => $login]);
            $employee = $statement->fetch();

            if ($employee && password_verify($password, $employee['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $employee['id'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                unset($_SESSION['login_attempts']);
                header('Location: dashboard.php', true, 303);
                exit;
            }

            $attempts[] = time();
            $_SESSION['login_attempts'] = $attempts;
            $error = 'Nieprawidłowy login lub hasło.';
        }
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $error = 'Baza danych jest chwilowo niedostępna. Spróbuj ponownie później.';
}

?>
<!doctype html>
<html lang="pl">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>NOVA Workspace — logowanie</title>
        <link rel="stylesheet" href="res/dash.css" />
        <script src="res/login.js" defer></script>
    </head>
    <body class="login-page">
        <main class="login-shell">
            <section class="login-brand">
                <a class="brand" href="index.php">
                    <span class="logo">N</span>
                    NOVA
                    <span class="muted">/ workspace</span>
                </a>
                <span class="eyebrow">MNIEJ CHAOSU. WIĘCEJ MOŻLIWOŚCI.</span>
                <h1>
                    Dobra praca
                    <br />
                    zaczyna się od
                    <br />
                    <em>dobrego planu.</em>
                </h1>
                <p>
                    Projekty, ludzie i codzienne zadania.
                    <br />
                    Cała Twoja firma w jednym miejscu.
                </p>
                <div class="preview">
                    <span>PROJEKT / 01</span>
                    <h3>Nowe możliwości</h3>
                    <div class="progress"><i style="width: 72%"></i></div>
                    <small>72% realizacji · Zespół na dobrej drodze</small>
                </div>
                <footer>NOVA Workspace · Panel zarządzania firmą</footer>
            </section>
            <section class="login-form">
                <span class="pill">PANEL TWOJEJ FIRMY</span>
                <h2>Dobrze Cię widzieć.</h2>
                <p>
                    Zaloguj się na swoje konto, aby zarządzać projektami i codzienną pracą zespołu.
                </p>
                <div class="login-error" role="alert"><?= escape($error) ?></div>
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>" />
                    <label for="login">Login</label>
                    <input
                        id="login"
                        name="login"
                        value="<?= escape($login) ?>"
                        autocomplete="username"
                        required
                        maxlength="50"
                    />
                    <label for="password">Hasło</label>
                    <div class="password-field">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                        />
                        <button type="button" id="togglePassword" aria-label="Pokaż hasło">
                            Pokaż
                        </button>
                    </div>
                    <button class="button primary wide" type="submit">
                        Zaloguj się
                        <span>↗</span>
                    </button>
                </form>
                <div class="demo-note">
                    Dostęp dla pracowników. Użyj loginu i hasła przypisanych do konta w firmie.
                </div>
                <div class="login-features">
                    <span>✓ Projekty i zlecenia</span>
                    <span>✓ Zespół i harmonogram</span>
                    <span>✓ Raporty i statystyki</span>
                </div>
            </section>
        </main>
    </body>
</html>
