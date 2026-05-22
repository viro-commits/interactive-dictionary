-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Počítač: 127.0.0.1:3306
-- Vytvořeno: Stř 11. bře 2026, 21:59
-- Verze serveru: 9.1.0
-- Verze PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `slovnicek`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `history`
--

DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `word_id` int NOT NULL,
  `rating` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `history_user_id_users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=294 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `languages`
--

DROP TABLE IF EXISTS `languages`;
CREATE TABLE IF NOT EXISTS `languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lang_name` varchar(50) NOT NULL,
  `lang_code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vypisuji data pro tabulku `languages`
--

INSERT INTO `languages` (`id`, `lang_name`, `lang_code`) VALUES
(1, 'Čeština', 'cs'),
(2, 'Angličtina', 'en'),
(3, 'Němčina', 'de'),
(4, 'Španělština', 'es'),
(5, 'Francouzština', 'fr');

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(22, 'demo', 'demo@gmail.com', '$2y$10$msuGdkSQnnT0kbcNNcxOROCP.GAJ5ZA7YoEDXS3iDp9CicjDHrTlu');

-- --------------------------------------------------------

--
-- Struktura tabulky `words`
--

DROP TABLE IF EXISTS `words`;
CREATE TABLE IF NOT EXISTS `words` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cz` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `translation` varchar(255) NOT NULL,
  `vyznam` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `level` int NOT NULL DEFAULT '1',
  `next_review` datetime NOT NULL,
  `user_id` int NOT NULL,
  `lang_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `words_user_id_users` (`user_id`),
  KEY `words_lang_id_languages` (`lang_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1587 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vypisuji data pro tabulku `words`
--

INSERT INTO `words` (`id`, `cz`, `translation`, `vyznam`, `level`, `next_review`, `user_id`, `lang_id`) VALUES
(1523, 'Jablko', 'Apple', 'A common, round fruit produced by the tree Malus domestica, cultivated in temperate climates.', 1, '0000-00-00 00:00:00', 22, 2),
(1524, 'Pes', 'Dog', 'A domesticated animal that often lives with people.', 1, '2026-03-11 22:50:07', 22, 2),
(1525, 'Obývák', 'Living room', 'A room for relaxing and spending time.', 1, '2026-03-11 22:50:09', 22, 2),
(1526, 'Letadlo', 'Airplane', 'A vehicle that flies in the sky.', 1, '2026-03-11 22:50:12', 22, 2),
(1527, 'Odpoledne', 'Afternoon', 'The time after midday.', 1, '2026-03-11 22:50:15', 22, 2),
(1528, 'Strach', 'Fear', 'An unpleasant emotion caused by danger.', 1, '2026-03-11 22:50:18', 22, 2),
(1529, 'Vést', 'To lead', '', 1, '0000-00-00 00:00:00', 22, 2),
(1530, 'Umřít', 'Die', 'To stop living; to become dead; to undergo death.', 1, '0000-00-00 00:00:00', 22, 2),
(1531, 'Slunce', 'Sun', 'A star, especially when seen as the centre of any single solar system.', 1, '0000-00-00 00:00:00', 22, 2),
(1534, 'Peníze', 'Money', '', 1, '0000-00-00 00:00:00', 22, 2),
(1535, 'Noc', 'Night', '', 1, '0000-00-00 00:00:00', 22, 2),
(1536, 'Přítel', 'Friend', 'A person other than a family member, spouse or lover whose company one enjoys and towards whom one feels affection.', 1, '0000-00-00 00:00:00', 22, 2),
(1537, 'Jídlo', 'Food', 'Any solid substance that can be consumed by living organisms, especially by eating, in order to sustain life.', 1, '0000-00-00 00:00:00', 22, 2),
(1538, 'Město', 'City', 'A large settlement, bigger than a town; sometimes with a specific legal definition, depending on the place.', 1, '0000-00-00 00:00:00', 22, 2),
(1539, 'Předpokládat', 'Assume', 'To accept something as true without proof.', 1, '2026-03-11 22:52:43', 22, 2),
(1540, 'Jablko', 'Apfel', '', 1, '2026-03-11 22:52:46', 22, 3),
(1541, 'Pes', 'Hund', '', 1, '2026-03-11 22:52:48', 22, 3),
(1542, 'Kočka', 'Katze', '', 1, '2026-03-11 22:52:49', 22, 3),
(1543, 'Tužka', 'Bleistift', '', 1, '2026-03-11 22:52:51', 22, 3),
(1544, 'Pokoj', 'Zimmer', '', 1, '2026-03-11 22:52:55', 22, 3),
(1545, 'Banán', 'Banane', '', 1, '2026-03-11 22:52:58', 22, 3),
(1546, 'Guma', 'Radiergummi', '', 1, '2026-03-11 22:53:01', 22, 3),
(1547, 'Letadlo', 'Flugzeug', '', 1, '2026-03-11 22:53:04', 22, 3),
(1549, 'Přítel', 'Freund', '', 1, '0000-00-00 00:00:00', 22, 3),
(1550, 'Peníze', 'Geld', '', 1, '0000-00-00 00:00:00', 22, 3),
(1551, 'čas', 'Zeit', '', 1, '0000-00-00 00:00:00', 22, 3),
(1552, 'Práce', 'Arbeit', '', 1, '0000-00-00 00:00:00', 22, 3),
(1553, 'Slunce', 'Sonnen', '', 1, '0000-00-00 00:00:00', 22, 3),
(1554, 'Loď', 'Schiff', '', 1, '2026-03-11 22:54:42', 22, 3),
(1555, 'Samostatný', 'Selbstständig', '', 1, '2026-03-11 22:54:50', 22, 3),
(1556, 'Jablko', 'Pomme', '', 1, '2026-03-11 22:54:55', 22, 5),
(1557, 'Kuchyně', 'Cuisine', '', 1, '2026-03-11 22:54:56', 22, 5),
(1558, 'Papír', 'Papier', '', 1, '2026-03-11 22:54:58', 22, 5),
(1559, 'Země', 'Terre', '', 1, '2026-03-11 22:55:00', 22, 5),
(1560, 'Kamarád', 'Copain', '', 1, '0000-00-00 00:00:00', 22, 5),
(1561, 'Tráva', 'Herbe', '', 1, '2026-03-11 22:55:31', 22, 5),
(1562, 'Dům', 'Maison', '', 1, '2026-03-11 22:55:36', 22, 5),
(1563, 'Květina', 'Fleur', '', 1, '2026-03-11 22:55:42', 22, 5),
(1564, 'čas', 'Temps', '', 1, '0000-00-00 00:00:00', 22, 5),
(1565, 'Noc', 'Nuit', '', 1, '0000-00-00 00:00:00', 22, 5),
(1566, 'Letadlo', 'Avion', '', 1, '2026-03-11 22:56:10', 22, 5),
(1567, 'Večer', 'Soir', '', 1, '2026-03-11 22:56:15', 22, 5),
(1568, 'Porovnat', 'Comparer', '', 1, '2026-03-11 22:56:19', 22, 5),
(1569, 'Jidlo', 'Nourriture', '', 1, '0000-00-00 00:00:00', 22, 5),
(1570, 'Město', 'Ville', '', 1, '0000-00-00 00:00:00', 22, 5),
(1571, 'Jablko', 'Manzana', '', 1, '2026-03-11 22:56:58', 22, 4),
(1572, 'Pes', 'Perro', '', 1, '2026-03-11 22:56:59', 22, 4),
(1573, 'Kočka', 'Gato', '', 1, '2026-03-11 22:57:01', 22, 4),
(1574, 'Ryba', 'Pez', '', 1, '2026-03-11 22:57:03', 22, 4),
(1575, 'Tužka', 'Lápiz', '', 1, '2026-03-11 22:57:07', 22, 4),
(1576, 'Voda', 'Agua', '', 1, '2026-03-11 22:57:10', 22, 4),
(1577, 'Banán', 'Plátano', '', 1, '2026-03-11 22:57:13', 22, 4),
(1578, 'Pomeranč', 'Naranja', '', 1, '2026-03-11 22:57:15', 22, 4),
(1579, 'Přítel', 'Amigo', '', 1, '0000-00-00 00:00:00', 22, 4),
(1580, 'Noc', 'Noche', '', 1, '0000-00-00 00:00:00', 22, 4),
(1581, 'Peníze', 'Dinero', '', 1, '0000-00-00 00:00:00', 22, 4),
(1582, 'Jídlo', 'Comida', '', 1, '0000-00-00 00:00:00', 22, 4),
(1583, 'Práce', 'Trabajo', '', 1, '0000-00-00 00:00:00', 22, 4),
(1584, 'Čtvrtek', 'Jueves', '', 1, '2026-03-11 22:58:05', 22, 4),
(1585, 'Strach', 'Miedo', '', 1, '2026-03-11 22:58:09', 22, 4),
(1586, 'Štěstí', 'Felicidad', '', 1, '2026-03-11 22:58:14', 22, 4);

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Omezení pro tabulku `words`
--
ALTER TABLE `words`
  ADD CONSTRAINT `words_lang_id_languages` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `words_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
