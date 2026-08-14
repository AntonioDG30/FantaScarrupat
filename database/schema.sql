-- FantaScarrupat — schema del database
-- Generato a partire da un export phpMyAdmin del database di produzione,
-- ripulito di tutti i dati (in particolare la tabella `users`, che conteneva
-- hash di password reali): contiene solo struttura, indici e chiavi esterne.
--
-- Uso: importare in un database MySQL vuoto (es. via phpMyAdmin o
-- `mysql -u root -p nome_db < schema.sql`) per ricreare le tabelle richieste
-- dall'applicazione. Nessun dato applicativo è incluso: dopo l'import le
-- tabelle di riferimento (tipologia_competizione, tipologia_partita, ruoli)
-- vanno popolate manualmente con i valori usati dal codice.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Struttura della tabella `competizione`
--

DROP TABLE IF EXISTS `competizione`;
CREATE TABLE `competizione` (
  `nome_competizione` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipologia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `competizione_disputata`
--

DROP TABLE IF EXISTS `competizione_disputata`;
CREATE TABLE `competizione_disputata` (
  `id_competizione_disputata` int UNSIGNED NOT NULL,
  `nome_competizione` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `anno` int NOT NULL,
  `vincitore` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `dettagli_rosa`
--

DROP TABLE IF EXISTS `dettagli_rosa`;
CREATE TABLE `dettagli_rosa` (
  `id_rosa` int UNSIGNED NOT NULL,
  `codice_fantacalcio` int UNSIGNED NOT NULL,
  `crediti_pagati` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `fantasquadra`
--

DROP TABLE IF EXISTS `fantasquadra`;
CREATE TABLE `fantasquadra` (
  `nome_fantasquadra` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scudetto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fantallenatore` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `immagine_fantallenatore` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_attuale` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `formazione`
--

DROP TABLE IF EXISTS `formazione`;
CREATE TABLE `formazione` (
  `id_formazione` int UNSIGNED NOT NULL,
  `id_competizione_disputata` int UNSIGNED NOT NULL,
  `giornata` int NOT NULL,
  `tipologia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasquadra` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modificatore_difesa` float DEFAULT NULL,
  `modificatore_capitano` float DEFAULT NULL,
  `totale` float DEFAULT NULL,
  `inserita_il` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `formazione_giocatore`
--

DROP TABLE IF EXISTS `formazione_giocatore`;
CREATE TABLE `formazione_giocatore` (
  `id` int UNSIGNED NOT NULL,
  `id_formazione` int UNSIGNED NOT NULL,
  `ruolo` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_giocatore` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codice_fantacalcio` int UNSIGNED DEFAULT NULL,
  `ordine` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `titolare` tinyint(1) NOT NULL DEFAULT '0',
  `conta` tinyint(1) NOT NULL DEFAULT '0',
  `voto_base` float DEFAULT NULL,
  `fantavoto` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `giocatore`
--

DROP TABLE IF EXISTS `giocatore`;
CREATE TABLE `giocatore` (
  `codice_fantacalcio` int UNSIGNED NOT NULL,
  `nome_completo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `giocatore_stagione`
--

DROP TABLE IF EXISTS `giocatore_stagione`;
CREATE TABLE `giocatore_stagione` (
  `id` int UNSIGNED NOT NULL,
  `codice_fantacalcio` int UNSIGNED NOT NULL,
  `anno` smallint UNSIGNED NOT NULL,
  `squadra_reale` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `immagine`
--

DROP TABLE IF EXISTS `immagine`;
CREATE TABLE `immagine` (
  `id_immagine` int UNSIGNED NOT NULL,
  `percorso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `didascalia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_visibile` tinyint(1) NOT NULL DEFAULT '1',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `login_attempts`
--
-- Rate limiting persistente dei tentativi di login: l'IP non viene mai
-- salvato in chiaro, solo il suo hash SHA-256 (vedi auth/login_rate_limit.php).
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` bigint UNSIGNED NOT NULL,
  `ip_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attempted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `parametri_rosa`
--

DROP TABLE IF EXISTS `parametri_rosa`;
CREATE TABLE `parametri_rosa` (
  `id_parametro` int UNSIGNED NOT NULL,
  `numero_parametro` int NOT NULL,
  `testo_parametro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_visibile` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `partita_avvessario`
--

DROP TABLE IF EXISTS `partita_avvessario`;
CREATE TABLE `partita_avvessario` (
  `id_partita` int UNSIGNED NOT NULL,
  `id_competizione_disputata` int UNSIGNED NOT NULL,
  `nome_fantasquadra_casa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasquadra_trasferta` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gol_casa` int NOT NULL DEFAULT '0',
  `gol_trasferta` int NOT NULL DEFAULT '0',
  `punteggio_casa` float NOT NULL DEFAULT '0',
  `punteggio_trasferta` float NOT NULL DEFAULT '0',
  `giornata` int NOT NULL,
  `tipologia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `girone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplementari` tinyint(1) NOT NULL DEFAULT '0',
  `rigori` tinyint(1) NOT NULL DEFAULT '0',
  `gol_rigori_casa` int DEFAULT NULL,
  `gol_rigori_trasferta` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `partita_solitaria`
--

DROP TABLE IF EXISTS `partita_solitaria`;
CREATE TABLE `partita_solitaria` (
  `id_partita_solitaria` int UNSIGNED NOT NULL,
  `id_competizione_disputata` int UNSIGNED NOT NULL,
  `nome_fantasquadra` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `punteggio` float NOT NULL,
  `giornata` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `rosa`
--

DROP TABLE IF EXISTS `rosa`;
CREATE TABLE `rosa` (
  `id_rosa` int UNSIGNED NOT NULL,
  `nome_fantasquadra` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `anno` int NOT NULL,
  `modalita` enum('classic','mantra') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'classic'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--
-- Tabella di riferimento: valori fissi, popolarla con i ruoli classic
-- (P, D, C, A) e mantra (Por, Ds, Dc, Dd, B, E, M, T, W, Pc, C, A) usati
-- dall'app — vedi INSERT di esempio in fondo al file.
--

DROP TABLE IF EXISTS `ruoli`;
CREATE TABLE `ruoli` (
  `id_ruolo` tinyint UNSIGNED NOT NULL,
  `nome` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('classic','mantra') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli_giocatori`
--

DROP TABLE IF EXISTS `ruoli_giocatori`;
CREATE TABLE `ruoli_giocatori` (
  `id_giocatore_stagione` int UNSIGNED NOT NULL,
  `id_ruolo` tinyint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `stagione`
--

DROP TABLE IF EXISTS `stagione`;
CREATE TABLE `stagione` (
  `anno` int NOT NULL,
  `creata_il` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `storia_timeline`
--

DROP TABLE IF EXISTS `storia_timeline`;
CREATE TABLE `storia_timeline` (
  `id` smallint UNSIGNED NOT NULL,
  `anno` smallint UNSIGNED NOT NULL,
  `etichetta` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `testo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icona` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'history_edu',
  `ordine` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `visibile` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `creato_il` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `aggiornato_il` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tipologia_competizione`
--
-- Tabella di riferimento: popolarla con i valori usati dall'app.
--

DROP TABLE IF EXISTS `tipologia_competizione`;
CREATE TABLE `tipologia_competizione` (
  `tipologia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `tipologia_partita`
--
-- Tabella di riferimento: popolarla con i valori usati dall'app.
--

DROP TABLE IF EXISTS `tipologia_partita`;
CREATE TABLE `tipologia_partita` (
  `tipologia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--
-- NB: nessun utente è incluso in questo schema. Crea gli account manualmente
-- (username + hash bcrypt di `password_hash()`) dopo l'import.
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` int UNSIGNED NOT NULL,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_admin` tinyint(1) NOT NULL DEFAULT '0',
  `nome_fantasquadra` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_preference` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'auto',
  `email_notifications` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `user_parametro_assegnato`
--

DROP TABLE IF EXISTS `user_parametro_assegnato`;
CREATE TABLE `user_parametro_assegnato` (
  `id` int UNSIGNED NOT NULL,
  `id_user` int UNSIGNED NOT NULL,
  `id_parametro` int UNSIGNED NOT NULL,
  `session_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle scaricate
--

ALTER TABLE `competizione`
  ADD PRIMARY KEY (`nome_competizione`),
  ADD KEY `fk_comp_tipologia` (`tipologia`);

ALTER TABLE `competizione_disputata`
  ADD PRIMARY KEY (`id_competizione_disputata`),
  ADD KEY `fk_comp_disputata_nome` (`nome_competizione`),
  ADD KEY `fk_comp_disputata_vincitore` (`vincitore`);

ALTER TABLE `dettagli_rosa`
  ADD PRIMARY KEY (`id_rosa`,`codice_fantacalcio`),
  ADD KEY `fk_dr_giocatore` (`codice_fantacalcio`);

ALTER TABLE `fantasquadra`
  ADD PRIMARY KEY (`nome_fantasquadra`);

ALTER TABLE `formazione`
  ADD PRIMARY KEY (`id_formazione`),
  ADD KEY `fk_form_comp` (`id_competizione_disputata`),
  ADD KEY `fk_form_fantasquadra` (`nome_fantasquadra`);

ALTER TABLE `formazione_giocatore`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fg_formazione` (`id_formazione`);

ALTER TABLE `giocatore`
  ADD PRIMARY KEY (`codice_fantacalcio`);

ALTER TABLE `giocatore_stagione`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gs_giocatore` (`codice_fantacalcio`);

ALTER TABLE `immagine`
  ADD PRIMARY KEY (`id_immagine`);

ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `parametri_rosa`
  ADD PRIMARY KEY (`id_parametro`);

ALTER TABLE `partita_avvessario`
  ADD PRIMARY KEY (`id_partita`),
  ADD KEY `fk_pa_comp` (`id_competizione_disputata`),
  ADD KEY `fk_pa_casa` (`nome_fantasquadra_casa`),
  ADD KEY `fk_pa_trasf` (`nome_fantasquadra_trasferta`);

ALTER TABLE `partita_solitaria`
  ADD PRIMARY KEY (`id_partita_solitaria`),
  ADD KEY `fk_ps_comp` (`id_competizione_disputata`),
  ADD KEY `fk_ps_fantasquadra` (`nome_fantasquadra`);

ALTER TABLE `rosa`
  ADD PRIMARY KEY (`id_rosa`),
  ADD KEY `fk_rosa_fantasquadra` (`nome_fantasquadra`);

ALTER TABLE `ruoli`
  ADD PRIMARY KEY (`id_ruolo`),
  ADD UNIQUE KEY `ux_nome_tipo` (`nome`,`tipo`);

ALTER TABLE `ruoli_giocatori`
  ADD PRIMARY KEY (`id_giocatore_stagione`,`id_ruolo`),
  ADD KEY `fk_rg_ruolo` (`id_ruolo`);

ALTER TABLE `stagione`
  ADD PRIMARY KEY (`anno`);

ALTER TABLE `storia_timeline`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tipologia_competizione`
  ADD PRIMARY KEY (`tipologia`);

ALTER TABLE `tipologia_partita`
  ADD PRIMARY KEY (`tipologia`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `ux_username` (`username`),
  ADD KEY `fk_user_fantasquadra` (`nome_fantasquadra`);

ALTER TABLE `user_parametro_assegnato`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_upa_user` (`id_user`),
  ADD KEY `fk_upa_parametro` (`id_parametro`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

ALTER TABLE `competizione_disputata`
  MODIFY `id_competizione_disputata` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `formazione`
  MODIFY `id_formazione` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `formazione_giocatore`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `giocatore_stagione`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `immagine`
  MODIFY `id_immagine` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `login_attempts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `parametri_rosa`
  MODIFY `id_parametro` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `partita_avvessario`
  MODIFY `id_partita` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `partita_solitaria`
  MODIFY `id_partita_solitaria` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `rosa`
  MODIFY `id_rosa` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ruoli`
  MODIFY `id_ruolo` tinyint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `storia_timeline`
  MODIFY `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `id_user` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_parametro_assegnato`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate (foreign key)
--

ALTER TABLE `competizione`
  ADD CONSTRAINT `fk_comp_tipologia` FOREIGN KEY (`tipologia`) REFERENCES `tipologia_competizione` (`tipologia`) ON UPDATE CASCADE;

ALTER TABLE `competizione_disputata`
  ADD CONSTRAINT `fk_comp_disputata_nome` FOREIGN KEY (`nome_competizione`) REFERENCES `competizione` (`nome_competizione`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comp_disputata_vincitore` FOREIGN KEY (`vincitore`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `dettagli_rosa`
  ADD CONSTRAINT `fk_dr_giocatore` FOREIGN KEY (`codice_fantacalcio`) REFERENCES `giocatore` (`codice_fantacalcio`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dr_rosa` FOREIGN KEY (`id_rosa`) REFERENCES `rosa` (`id_rosa`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `formazione`
  ADD CONSTRAINT `fk_form_comp` FOREIGN KEY (`id_competizione_disputata`) REFERENCES `competizione_disputata` (`id_competizione_disputata`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_form_fantasquadra` FOREIGN KEY (`nome_fantasquadra`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON UPDATE CASCADE;

ALTER TABLE `formazione_giocatore`
  ADD CONSTRAINT `fk_fg_formazione` FOREIGN KEY (`id_formazione`) REFERENCES `formazione` (`id_formazione`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `giocatore_stagione`
  ADD CONSTRAINT `fk_gs_giocatore` FOREIGN KEY (`codice_fantacalcio`) REFERENCES `giocatore` (`codice_fantacalcio`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `partita_avvessario`
  ADD CONSTRAINT `fk_pa_casa` FOREIGN KEY (`nome_fantasquadra_casa`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_comp` FOREIGN KEY (`id_competizione_disputata`) REFERENCES `competizione_disputata` (`id_competizione_disputata`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_trasf` FOREIGN KEY (`nome_fantasquadra_trasferta`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON UPDATE CASCADE;

ALTER TABLE `partita_solitaria`
  ADD CONSTRAINT `fk_ps_comp` FOREIGN KEY (`id_competizione_disputata`) REFERENCES `competizione_disputata` (`id_competizione_disputata`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_fantasquadra` FOREIGN KEY (`nome_fantasquadra`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON UPDATE CASCADE;

ALTER TABLE `rosa`
  ADD CONSTRAINT `fk_rosa_fantasquadra` FOREIGN KEY (`nome_fantasquadra`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON UPDATE CASCADE;

ALTER TABLE `ruoli_giocatori`
  ADD CONSTRAINT `fk_rg_giocatore` FOREIGN KEY (`id_giocatore_stagione`) REFERENCES `giocatore_stagione` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rg_ruolo` FOREIGN KEY (`id_ruolo`) REFERENCES `ruoli` (`id_ruolo`) ON UPDATE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_fantasquadra` FOREIGN KEY (`nome_fantasquadra`) REFERENCES `fantasquadra` (`nome_fantasquadra`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_parametro_assegnato`
  ADD CONSTRAINT `fk_upa_parametro` FOREIGN KEY (`id_parametro`) REFERENCES `parametri_rosa` (`id_parametro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_upa_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------

--
-- Dati di riferimento minimi (tabelle "enum-like", nessun dato applicativo)
--

INSERT INTO `tipologia_competizione` (`tipologia`) VALUES
('A Calendario'),
('A Gruppi'),
('Battle Royale'),
('Eliminazione Diretta'),
('Formula Uno'),
('Highlander'),
('Mista'),
('Uno vs Tutti');

INSERT INTO `tipologia_partita` (`tipologia`) VALUES
('Calendario'),
('Fase a Gironi'),
('Finale'),
('Ottavi'),
('Quarti'),
('Semifinali');

INSERT INTO `ruoli` (`id_ruolo`, `nome`, `tipo`) VALUES
(1, 'P', 'classic'),
(2, 'D', 'classic'),
(3, 'C', 'classic'),
(4, 'A', 'classic'),
(5, 'Por', 'mantra'),
(6, 'Dc', 'mantra'),
(7, 'Dd', 'mantra'),
(8, 'Ds', 'mantra'),
(9, 'B', 'mantra'),
(10, 'E', 'mantra'),
(11, 'M', 'mantra'),
(12, 'C', 'mantra'),
(13, 'T', 'mantra'),
(14, 'W', 'mantra'),
(15, 'A', 'mantra'),
(16, 'Pc', 'mantra');
