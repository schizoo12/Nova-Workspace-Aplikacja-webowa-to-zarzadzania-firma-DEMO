<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

try {
    require __DIR__ . '/database.php';
    $user = currentUser($pdo);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(503);
    exit('Baza danych jest chwilowo niedostępna.');
}

if (!$user) {
    header('Location: login.php');
    exit;
}

$displayName = trim($user['first_name'] . ' ' . $user['last_name']);
$initials = mb_strtoupper(
    mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1)
);
$roleLabel = $user['role'] === 'admin' ? 'Administrator' : 'Pracownik';

?>
<!doctype html>
<html lang="pl">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>NOVA Workspace — panel firmy</title>
        <link rel="stylesheet" href="res/dash.css" />
        <meta name="csrf-token" content="<?= escape($_SESSION['csrf_token']) ?>" />
        <script src="res/app.js" defer></script>
    </head>
    <body>
        <div class="shade" id="shade"></div>
        <aside id="sidebar">
            <a class="brand" href="dashboard.php">
                <span class="logo">N</span>
                NOVA
                <span class="workspace">WORKSPACE</span>
            </a>
            <div class="company">
                <span class="company-icon">N</span>
                <div>
                    <b>NOVA Workspace</b>
                    <small>Przestrzeń firmowa</small>
                </div>
                <span>⌄</span>
            </div>
            <small class="nav-caption">TWOJA PRZESTRZEŃ</small>
            <nav>
                <button data-view="overview" class="active">
                    <span>◫</span>
                    Przegląd
                </button>
                <button data-view="projects">
                    <span>▤</span>
                    Projekty
                    <i id="projectCount"></i>
                </button>
                <button data-view="team">
                    <span>♧</span>
                    Zespół
                </button>
                <button data-view="schedule">
                    <span>▦</span>
                    Harmonogram
                </button>
                <button data-view="reports">
                    <span>↗</span>
                    Raporty
                </button>
                <button data-view="settings">
                    <span>⚙</span>
                    Ustawienia
                </button>
            </nav>
            <div class="sidebar-bottom">
                <div class="demo-card">
                    <span class="pill">TWÓJ WORKSPACE</span>
                    <p>
                        Twoja firma.
                        <br />
                        Twój sposób pracy.
                    </p>
                    <small>Projekty i zespół w jednym miejscu.</small>
                </div>
                <a class="profile" href="logout.php?token=<?= escape($_SESSION['csrf_token']) ?>">
                    <span class="avatar"><?= escape($initials) ?></span>
                    <div>
                        <b><?= escape($displayName) ?></b>
                        <small><?= escape($roleLabel) ?> · Wyloguj ↗</small>
                    </div>
                </a>
            </div>
        </aside>
        <main class="main">
            <header>
                <div class="header-left">
                    <button id="menu" class="icon-button" aria-label="Otwórz menu">☰</button>
                    <span>
                        Workspace
                        <span class="muted">/</span>
                        <b id="breadcrumb">Przegląd</b>
                    </span>
                </div>
                <div class="header-right">
                    <span class="live">● Połączono z bazą</span>
                    <span class="avatar"><?= escape($initials) ?></span>
                </div>
            </header>
            <div class="content">
                <div class="page-heading">
                    <div>
                        <span class="eyebrow" id="date"></span>
                        <h1 id="heading">Wszystko pod kontrolą.</h1>
                        <p id="subtitle">
                            Dobry dzień na kolejny krok. Oto, co dzieje się w Twojej firmie.
                        </p>
                    </div>
                    <button class="button primary" id="newProject" hidden>＋ Nowy projekt</button>
                </div>
                <div id="view" aria-live="polite"><div class="panel">Ładowanie danych…</div></div>
                <footer class="page-footer">
                    NOVA Workspace
                    <span>Panel zarządzania firmą</span>
                </footer>
            </div>
        </main>
        <dialog id="projectDialog">
            <form id="projectForm">
                <div class="dialog-head">
                    <h2>Nowy projekt</h2>
                    <button type="button" class="icon-button" id="closeDialog" aria-label="Zamknij">
                        ×
                    </button>
                </div>
                <p>Zaplanuj kolejny krok dla swojego zespołu.</p>
                <label>
                    Nazwa projektu
                    <input
                        name="name"
                        required
                        maxlength="100"
                        placeholder="np. Wdrożenie nowej usługi"
                    />
                </label>
                <label>
                    Klient
                    <input name="client" required maxlength="80" placeholder="Nazwa firmy" />
                </label>
                <label>
                    Lokalizacja
                    <input
                        name="location"
                        required
                        maxlength="255"
                        placeholder="np. Warszawa lub online"
                    />
                </label>
                <div class="form-row">
                    <label>
                        Termin
                        <input name="date" type="date" required />
                    </label>
                    <label>
                        Osoba prowadząca
                        <select name="ownerId" id="ownerSelect" required></select>
                    </label>
                </div>
                <button class="button primary wide" type="submit">Utwórz projekt ↗</button>
            </form>
        </dialog>
        <div class="toast" id="toast" role="status"></div>
    </body>
</html>
