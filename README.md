# NOVA Workspace

Prosty panel do zarządzania firmą, napisany w PHP i JavaScript z bazą MySQL. Projekt powstał jako demo do portfolio i może służyć jako baza do dalszej rozbudowy.

Pozwala przeglądać zespół, prowadzić projekty, sprawdzać terminy i śledzić postęp pracy. Administrator może dodawać i usuwać projekty oraz aktualizować ich postęp. Pracownik widzi przypisane mu projekty.

NOVA Workspace to przykładowa nazwa użyta na potrzeby projektu. Nie oznacza powiązania z żadną firmą ani promowania konkretnej marki. Dane w wersji demo są fikcyjne.

## Uruchomienie

Potrzebujesz PHP 8.1+, rozszerzeń PDO MySQL i mbstring oraz MySQL 8.

1. Utwórz pustą bazę `webapp`.
2. Zaimportuj jeden z plików:
   - `schema.sql` — same tabele, bez danych i kont.
   - `novadb.sql` — gotowa baza z przykładowymi pracownikami i projektami.
3. Ustaw dane połączenia z MySQL w `config.php` lub w pliku wskazanym przez `NOVA_CONFIG_FILE`.
4. W katalogu projektu uruchom:

```bash
php -S 127.0.0.1:8082 router.php
```

Otwórz [localhost:8082](http://127.0.0.1:8082). Możesz też uruchomić projekt na serwerze obsługującym PHP.

## Konta demo

Po imporcie `novadb.sql` dostępne są konta pracowników:

| Login | Hasło |
| --- | --- |
| `demo.anna` | `Example2026!` |
| `demo.michal` | `Example2026!` |
| `demo.julia` | `Example2026!` |
| `demo.piotr` | `Example2026!` |

Hasło jest takie samo dla wszystkich kont demo. Konta te mają rolę `user`; do zarządzania projektami potrzebne jest konto z rolą `admin`.
