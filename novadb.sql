-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Paź 03, 2026 at 10:19 PM
-- Wersja serwera: 26.7.0
-- Wersja PHP: 8.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `webapp`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `employees`
--

CREATE TABLE `employees` (
  `id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `employee_type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Zrzut danych tabeli `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `login`, `password`, `birth_date`, `employee_type`, `created_at`, `role`) VALUES
(5, 'Anna', 'Nowak', 'demo.anna', '$2y$12$x.rMHuArEQeCayuvwQdK5u51OYzHL8pYxftWur7aIUIXHrJJWkO6.', '1994-05-12', 'Marketing', '2026-10-03 22:16:59', 'user'),
(6, 'Michał', 'Wiśniewski', 'demo.michal', '$2y$12$x.rMHuArEQeCayuvwQdK5u51OYzHL8pYxftWur7aIUIXHrJJWkO6.', '1991-09-23', 'Projektant', '2026-10-03 22:16:59', 'user'),
(7, 'Julia', 'Wójcik', 'demo.julia', '$2y$12$x.rMHuArEQeCayuvwQdK5u51OYzHL8pYxftWur7aIUIXHrJJWkO6.', '1996-02-18', 'Obsługa klienta', '2026-10-03 22:16:59', 'user'),
(8, 'Piotr', 'Zieliński', 'demo.piotr', '$2y$12$x.rMHuArEQeCayuvwQdK5u51OYzHL8pYxftWur7aIUIXHrJJWkO6.', '1993-11-07', 'Developer', '2026-10-03 22:16:59', 'user');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nova_order_details`
--

CREATE TABLE `nova_order_details` (
  `order_id` int UNSIGNED NOT NULL,
  `owner_id` int UNSIGNED DEFAULT NULL,
  `progress` tinyint UNSIGNED NOT NULL DEFAULT '0'
) ;

--
-- Zrzut danych tabeli `nova_order_details`
--

INSERT INTO `nova_order_details` (`order_id`, `owner_id`, `progress`) VALUES
(111, 6, 75),
(112, 8, 50),
(113, 5, 90),
(114, 7, 0),
(115, 6, 100),
(116, 5, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `orders`
--

CREATE TABLE `orders` (
  `id` int UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('nowe','w toku','zakończone','anulowane') NOT NULL DEFAULT 'nowe',
  `employees_count` int UNSIGNED NOT NULL DEFAULT '0',
  `employees` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Zrzut danych tabeli `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `name`, `client`, `location`, `order_date`, `status`, `employees_count`, `employees`, `created_at`) VALUES
(111, 'PR-DEMO-001', 'Nowa identyfikacja marki', 'Bloom Studio', 'Warszawa', '2026-10-12', 'w toku', 1, '[6]', '2026-10-03 22:16:59'),
(112, 'PR-DEMO-002', 'Wdrożenie strony internetowej', 'Forma Studio', 'Online', '2026-10-16', 'w toku', 1, '[8]', '2026-10-03 22:16:59'),
(113, 'PR-DEMO-003', 'Kampania promocyjna', 'Horizon', 'Łódź', '2026-10-09', 'w toku', 1, '[5]', '2026-10-03 22:16:59'),
(114, 'PR-DEMO-004', 'Warsztaty z klientem', 'North Partners', 'Kraków', '2026-10-20', 'nowe', 1, '[7]', '2026-10-03 22:16:59'),
(115, 'PR-DEMO-005', 'Projekt katalogu produktów', 'Vertex', 'Online', '2026-10-01', 'zakończone', 1, '[6]', '2026-10-03 22:16:59'),
(116, 'PR-DEMO-006', 'Przygotowanie newslettera', 'Green Office', 'Poznań', '2026-10-07', 'anulowane', 1, '[5]', '2026-10-03 22:16:59');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indeksy dla tabeli `nova_order_details`
--
ALTER TABLE `nova_order_details`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `nova_owner_fk` (`owner_id`);

--
-- Indeksy dla tabeli `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

--
-- AUTO_INCREMENT dla zrzuconych tabel
--

--
-- AUTO_INCREMENT dla tabeli `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT dla tabeli `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `nova_order_details`
--
ALTER TABLE `nova_order_details`
  ADD CONSTRAINT `nova_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nova_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
