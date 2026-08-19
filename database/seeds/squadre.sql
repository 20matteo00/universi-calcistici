-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versione server:              8.0.30 - MySQL Community Server - GPL
-- S.O. server:                  Win64
-- HeidiSQL Versione:            12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dump dei dati della tabella universi_calcistici.squadre: ~64 rows (circa)
INSERT INTO `squadre` (`ID`, `Nome`, `Colori`, `Valore`, `FattoreCasa`, `Paese`, `Tipo`, `Creato`, `Modificato`) VALUES
	(1, 'Juventus', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#000000"}', 856.00, 622.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:38:17'),
	(2, 'Torino', '{"bordo": "#800000", "testo": "#ffffff", "sfondo": "#804000"}', 642.00, 726.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:38:59'),
	(3, 'Inter', '{"bordo": "#ffffff", "testo": "#0000ff", "sfondo": "#000000"}', 890.00, 920.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:39:23'),
	(4, 'Milan', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#000000"}', 880.00, 935.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:39:51'),
	(5, 'Roma', '{"bordo": "#ffff00", "testo": "#ff8040", "sfondo": "#b30000"}', 754.00, 934.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:40:27'),
	(6, 'Lazio', '{"bordo": "#ffff00", "testo": "#ffffff", "sfondo": "#00ffff"}', 758.00, 838.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:40:53'),
	(7, 'Genoa', '{"bordo": "#ffff00", "testo": "#0000a0", "sfondo": "#ff0000"}', 932.00, 982.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:41:35'),
	(8, 'Sampdoria', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#0000ff"}', 568.00, 822.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:41:56'),
	(9, 'Napoli', '{"bordo": "#0080ff", "testo": "#ffffff", "sfondo": "#0080ff"}', 836.00, 856.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:42:20'),
	(10, 'Fiorentina', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#800080"}', 736.00, 802.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:42:46'),
	(11, 'Bologna', '{"bordo": "#ffffff", "testo": "#0000a0", "sfondo": "#ff0000"}', 789.00, 777.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:49:37'),
	(12, 'Atalanta', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#000080"}', 842.00, 645.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:50:22'),
	(13, 'Udinese', '{"bordo": "#808000", "testo": "#000000", "sfondo": "#808080"}', 648.00, 725.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:50:47'),
	(14, 'Cagliari', '{"bordo": "#ffffff", "testo": "#0000ff", "sfondo": "#ff0000"}', 599.00, 860.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:51:15'),
	(15, 'Hellas Verona', '{"bordo": "#ffffff", "testo": "#ffff00", "sfondo": "#000080"}', 520.00, 602.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:51:54'),
	(16, 'Chievo Verona', '{"bordo": "#ffffff", "testo": "#000080", "sfondo": "#ffff00"}', 320.00, 330.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:52:14'),
	(17, 'Palermo', '{"bordo": "#ffffff", "testo": "#000000", "sfondo": "#ff80c0"}', 502.00, 908.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:52:48'),
	(18, 'Catania', '{"bordo": "#ffffff", "testo": "#b70000", "sfondo": "#0000ff"}', 380.00, 850.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:53:19'),
	(19, 'Bari', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#ff0000"}', 508.00, 892.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:54:19'),
	(20, 'Lecce', '{"bordo": "#408080", "testo": "#ff0000", "sfondo": "#ffff00"}', 625.00, 855.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:54:48'),
	(21, 'Parma', '{"bordo": "#acac00", "testo": "#000000", "sfondo": "#ffffff"}', 680.00, 595.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:55:35'),
	(22, 'Sassuolo', '{"bordo": "#ffffff", "testo": "#000000", "sfondo": "#008000"}', 722.00, 458.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:55:59'),
	(23, 'Vicenza', '{"bordo": "#ff0000", "testo": "#ff0000", "sfondo": "#ffffff"}', 382.00, 659.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:56:35'),
	(24, 'Triestina', '{"bordo": "#ffffff", "testo": "#ffffff", "sfondo": "#ff0000"}', 396.00, 508.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:57:03'),
	(25, 'Brescia', '{"bordo": "#ffff00", "testo": "#ffffff", "sfondo": "#0000a0"}', 306.00, 591.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:57:40'),
	(26, 'SPAL', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#0080ff"}', 398.00, 496.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:58:20'),
	(27, 'Livorno', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#800000"}', 411.00, 602.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:59:14'),
	(28, 'Empoli', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#0000a0"}', 522.00, 563.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 10:59:50'),
	(29, 'Como', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#0000ff"}', 802.00, 560.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 11:00:22'),
	(30, 'Venezia', '{"bordo": "#000000", "testo": "#ff8000", "sfondo": "#004000"}', 452.00, 588.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 11:00:57'),
	(31, 'Ascoli', '{"bordo": "#ffffff", "testo": "#000000", "sfondo": "#ff0000"}', 365.00, 567.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 11:01:28'),
	(32, 'Padova', '{"bordo": "#ffffff", "testo": "#ffffff", "sfondo": "#ff0000"}', 452.00, 555.00, 'it', 'Club', '2026-07-30 11:44:17', '2026-08-05 11:01:53'),
	(33, 'Cesena', '{"bordo": "#000000", "testo": "#000000", "sfondo": "#ffffff"}', 415.00, 502.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:08:16'),
	(34, 'Modena', '{"bordo": "#ffffff", "testo": "#ffff00", "sfondo": "#000080"}', 425.00, 523.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:08:45'),
	(35, 'Perugia', '{"bordo": "#ffffff", "testo": "#ffffff", "sfondo": "#ff0000"}', 422.00, 659.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:09:21'),
	(36, 'Avellino', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#008000"}', 488.00, 671.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:10:52'),
	(37, 'Cremonese', '{"bordo": "#ffffff", "testo": "#c0c0c0", "sfondo": "#ff0000"}', 500.00, 536.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:11:19'),
	(38, 'Reggina', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#800000"}', 388.00, 451.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:11:58'),
	(39, 'Reggiana', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#800000"}', 403.00, 501.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:12:38'),
	(40, 'Salernitana', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#800000"}', 503.00, 714.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:13:03'),
	(41, 'Catanzaro', '{"bordo": "#000000", "testo": "#ff0000", "sfondo": "#ffff00"}', 513.00, 629.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:13:34'),
	(42, 'Pescara', '{"bordo": "#0000ff", "testo": "#ffffff", "sfondo": "#0080ff"}', 401.00, 468.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:14:05'),
	(43, 'Pisa', '{"bordo": "#ffffff", "testo": "#0000ff", "sfondo": "#000000"}', 403.00, 451.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:16:26'),
	(44, 'Frosinone', '{"bordo": "#ffffff", "testo": "#ffff00", "sfondo": "#0000ff"}', 536.00, 569.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:17:00'),
	(45, 'Monza', '{"bordo": "#ffffff", "testo": "#ffffff", "sfondo": "#ff0000"}', 526.00, 431.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:17:20'),
	(46, 'Crotone', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#000080"}', 302.00, 458.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:17:52'),
	(47, 'Spezia', '{"bordo": "#808080", "testo": "#000000", "sfondo": "#ffffff"}', 415.00, 433.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:18:18'),
	(48, 'Benevento', '{"bordo": "#000000", "testo": "#ffff00", "sfondo": "#ff0000"}', 388.00, 504.00, 'it', 'Club', '2026-08-03 11:25:58', '2026-08-05 11:18:55'),
	(101, 'Liverpool', '{"bordo": "#ffffff", "testo": "#0080ff", "sfondo": "#d50000"}', 898.00, 925.00, 'gb-eng', 'Club', '2026-08-06 08:52:36', '2026-08-06 08:52:49'),
	(102, 'Arsenal', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#ff0000"}', 802.00, 901.00, 'gb-eng', 'Club', '2026-08-06 08:53:37', '2026-08-06 08:53:37'),
	(103, 'Manchester City', '{"bordo": "#000000", "testo": "#ffffff", "sfondo": "#0080ff"}', 930.00, 820.00, 'gb-eng', 'Club', '2026-08-06 08:54:05', '2026-08-06 08:54:05'),
	(104, 'Manchester United', '{"bordo": "#ffffff", "testo": "#ffff00", "sfondo": "#ff8000"}', 816.00, 965.00, 'gb-eng', 'Club', '2026-08-06 08:54:43', '2026-08-06 08:54:43'),
	(105, 'Barcellona', '{"bordo": "#0000ff", "testo": "#ffff00", "sfondo": "#ff0000"}', 926.00, 923.00, 'es', 'Club', '2026-08-06 08:56:05', '2026-08-06 08:56:05'),
	(106, 'Real Madrid', '{"bordo": "#8000ff", "testo": "#ffff00", "sfondo": "#ffffff"}', 911.00, 923.00, 'es', 'Club', '2026-08-06 08:56:52', '2026-08-06 08:56:52'),
	(107, 'Atletico Madrid', '{"bordo": "#ffffff", "testo": "#0000ff", "sfondo": "#ff0000"}', 845.00, 902.00, 'es', 'Club', '2026-08-06 08:57:27', '2026-08-06 08:57:27'),
	(108, 'Villarreal', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#ffff00"}', 758.00, 726.00, 'es', 'Club', '2026-08-06 08:57:52', '2026-08-06 08:57:52'),
	(109, 'Bayern Monaco', '{"bordo": "#ffffff", "testo": "#004080", "sfondo": "#ff0000"}', 926.00, 922.00, 'de', 'Club', '2026-08-06 08:58:47', '2026-08-06 08:58:47'),
	(110, 'Borussia Dortmund', '{"bordo": "#ffffff", "testo": "#000000", "sfondo": "#ffff00"}', 826.00, 955.00, 'de', 'Club', '2026-08-06 08:59:11', '2026-08-06 08:59:11'),
	(111, 'Lipsia', '{"bordo": "#0000ff", "testo": "#ffffff", "sfondo": "#ff0000"}', 856.00, 832.00, 'de', 'Club', '2026-08-06 08:59:38', '2026-08-06 08:59:38'),
	(112, 'Bayer Leverkusen', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#000000"}', 803.00, 864.00, 'de', 'Club', '2026-08-06 08:59:58', '2026-08-06 08:59:58'),
	(113, 'Paris Saint-Germain', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#000080"}', 961.00, 805.00, 'fr', 'Club', '2026-08-06 09:00:34', '2026-08-06 09:00:34'),
	(114, 'Olympique Lione', '{"bordo": "#ffffff", "testo": "#ff0000", "sfondo": "#0000ff"}', 788.00, 869.00, 'fr', 'Club', '2026-08-06 09:01:12', '2026-08-06 09:01:46'),
	(115, 'Olympique Marsiglia', '{"bordo": "#00ffff", "testo": "#00ffff", "sfondo": "#ffffff"}', 821.00, 822.00, 'fr', 'Club', '2026-08-06 09:01:35', '2026-08-06 09:01:35'),
	(116, 'Lilla', '{"bordo": "#000000", "testo": "#ff0000", "sfondo": "#ffffff"}', 741.00, 798.00, 'fr', 'Club', '2026-08-06 09:02:12', '2026-08-06 09:02:12');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
