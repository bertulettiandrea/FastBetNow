-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 06, 2026 at 10:22 AM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `FBN`
--

-- --------------------------------------------------------

--
-- Table structure for table `TENANT`
--

CREATE TABLE `TENANT` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL UNIQUE,
  `descrizione` text,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `TENANT`
--

INSERT INTO `TENANT` (`id`, `nome`, `slug`, `descrizione`, `attivo`) VALUES
(1, 'FastBetNow Principale', 'fbn-principale', 'Tenant principale', 1);

-- --------------------------------------------------------

--
-- Table structure for table `CONTO` --

CREATE TABLE `CONTO` (
  `email_intestatario` varchar(254) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `saldo` float NOT NULL,
  `bonus` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CONTO`
--

INSERT INTO `CONTO` (`email_intestatario`, `saldo`, `bonus`) VALUES
('aa@gmail.com', 0, 0),
('aaaaaa@gmail.com', 0, 0),
('admin@gmail.com', 0, 0),
('bertuletti.andrea.studente54@itispaleocapa.it', 0, 0),
('esca@gmail.com', 0, 0),
('lza@gmail.com', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `PARTITA`
--

CREATE TABLE `PARTITA` (
  `id_partita` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `squadra_casa` varchar(50) NOT NULL,
  `squadra_trasferta` varchar(50) NOT NULL,
  `risultato` varchar(5) DEFAULT NULL,
  `data_inizio` datetime NOT NULL,
  `campionato` varchar(50) NOT NULL,
  `quota_casa` float NOT NULL DEFAULT 1.0,
  `quota_pareggio` float NOT NULL DEFAULT 1.0,
  `quota_trasferta` float NOT NULL DEFAULT 1.0,
  `stato` varchar(20) NOT NULL DEFAULT 'APERTO' COMMENT 'APERTO, CHIUSO, GIOCATO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_partita`),
  UNIQUE KEY `uq_partita` (`tenant_id`, `squadra_casa`, `squadra_trasferta`, `data_inizio`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `PARTITA`
--

INSERT INTO `PARTITA` (`squadra_casa`, `squadra_trasferta`, `data_inizio`, `campionato`, `quota_casa`, `quota_pareggio`, `quota_trasferta`, `stato`, `risultato`) VALUES
('Inter', 'Milan', '2026-02-05 20:45:00', 'Serie A', 2.10, 3.40, 3.50, 'APERTO', NULL),
('Barcelona', 'Real Madrid', '2026-02-08 21:00:00', 'La Liga', 2.65, 3.30, 2.70, 'APERTO', NULL),
('Man City', 'Liverpool', '2026-02-09 17:30:00', 'Premier League', 2.20, 3.50, 3.30, 'APERTO', NULL),
('Bayern', 'Dortmund', '2026-02-09 18:30:00', 'Bundesliga', 1.75, 3.80, 4.50, 'APERTO', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `PUNTATA`
--

CREATE TABLE `PUNTATA` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `id_schedina` int(11) NOT NULL,
  `id_partita` int(11) NOT NULL,
  `email_utente` varchar(254) NOT NULL,
  `squadra_casa` varchar(50) NOT NULL,
  `squadra_trasferta` varchar(50) NOT NULL,
  `segno` char(1) NOT NULL COMMENT '1=casa, X=pareggio, 2=trasferta',
  `quota` float NOT NULL,
  `importo` float NOT NULL,
  `vincita_potenziale` float NOT NULL,
  `stato` varchar(20) NOT NULL DEFAULT 'APERTO' COMMENT 'APERTO, VINTA, PERSA',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_schedina` (`id_schedina`),
  KEY `idx_partita` (`id_partita`),
  KEY `idx_email_utente` (`email_utente`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PERMESSO`
--

CREATE TABLE `PERMESSO` (
  `id` int(11) NOT NULL,
  `codice` varchar(50) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `PERMESSO`
--

INSERT INTO `PERMESSO` (`id`, `codice`, `descrizione`) VALUES
(1, 'CREA_PARTITA', 'Creazione di una partita'),
(2, 'VISUALIZZA_PARTITE', 'Visualizzazione delle partite'),
(3, 'PUNTA_SCHEDINA', 'Puntare una schedina'),
(4, 'GESTISCI_UTENTI', 'Gestione utenti');

-- --------------------------------------------------------

--
-- Table structure for table `RUOLO`
--

CREATE TABLE `RUOLO` (
  `id` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `RUOLO`
--

INSERT INTO `RUOLO` (`id`, `nome`, `descrizione`) VALUES
(1, 'ADMIN', 'Amministratore del sistema'),
(2, 'UTENTE', 'Utente registrato');

-- --------------------------------------------------------

--
-- Table structure for table `RUOLO_PERMESSO`
--

CREATE TABLE `RUOLO_PERMESSO` (
  `id_ruolo` int(11) NOT NULL,
  `id_permesso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `RUOLO_PERMESSO`
--

INSERT INTO `RUOLO_PERMESSO` (`id_ruolo`, `id_permesso`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `SCHEDINA`
--

CREATE TABLE `SCHEDINA` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `email_utente` varchar(254) NOT NULL,
  `importo_totale` float NOT NULL,
  `quota_totale` float NOT NULL DEFAULT 1.0,
  `vincita_potenziale` float NOT NULL DEFAULT 0.0,
  `esito` tinyint(1) DEFAULT NULL COMMENT 'NULL=aperto, 1=vinta, 0=persa',
  `stato` varchar(20) NOT NULL DEFAULT 'APERTO' COMMENT 'APERTO, CHIUSA, VINTA, PERSA',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_utente` (`email_utente`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `UTENTE`
--

CREATE TABLE `UTENTE` (
  `email` varchar(254) NOT NULL,
  `tenant_id` int(11) NOT NULL DEFAULT 1,
  `nome` varchar(20) NOT NULL,
  `cognome` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`email`, `tenant_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `UTENTE`
--

INSERT INTO `UTENTE` (`email`, `nome`, `cognome`, `password`, `refresh_token`) VALUES
('aa@gmail.com', 'aa', 'aa', '$2y$10$fjGSHipCTjv6kO6BY70G4e7MmRNn3/invfDwrBgdK/OTReenZy1i2', '5c7b04e385fb9b294c7ffa0e1558c2f4ca03490d7d7cd1640b56ee575b3966424f6b981b3a8d0965'),
('aaaaaa@gmail.com', 'a', 'a', '$2y$10$dIK.GHGW6Azy8O81PV7hLu3pzvs2F8K1NWthi1KOrBd7VYCHRyX9m', '3ebac36c24fc4bd3df7903c52e4c36794a0930206d13e6fe11465c1ca38ba3d61fe56717e95d4111'),
('admin@gmail.com', 'admin@gmail.com', 'admin@gmail.com', '$2y$10$G/FL7voNhZxe2PMKGOTgGOZvqYv7iX114/n9R5XhNOCQDx9BCAq6C', '83e63bd459bc2d4d2aa45d4dcf96d18eface02696417e04dac2dd8f1c8a37c5e7bdf4f2db41451ef'),
('bertuletti.andrea.studente54@itispaleocapa.it', 'ANDREA', 'BERTULETTI', '$2y$10$tKPie8GgJ1CANzwLmCQmUed62chPfj9eJWRsEYhr1s7/ezdug4sn2', NULL),
('esca@gmail.com', 'esca', 'esca', '$2y$10$O5JvDwd/.RGeaKKGBXA1ouSKugBzV0pnRdbsuSKXndIdsF5m8PV9S', '4544369167c4c0c2ebfa731b5c9e4803302d56066eb679436510c0ba15bf1ef517ddc5e5d08ed1b5'),
('lza@gmail.com', 'lza', 'lza', '$2y$10$xpJlgS2osFcgWkaoAXzmdOE1OiKhNUyrSUD6ifVOHj9AUCUeCWFh.', '58e26d9ac47599873258da9c58a46bf234b0e4600f604a9efb590203e0491f75ba5bea719aa66de0');

-- --------------------------------------------------------

--
-- Table structure for table `UTENTE_RUOLO`
--

CREATE TABLE `UTENTE_RUOLO` (
  `email_utente` varchar(254) NOT NULL,
  `id_ruolo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `UTENTE_RUOLO`
--

INSERT INTO `UTENTE_RUOLO` (`email_utente`, `id_ruolo`) VALUES
('aa@gmail.com', 2),
('aaaaaa@gmail.com', 2),
('admin@gmail.com', 1),
('bertuletti.andrea.studente54@itispaleocapa.it', 2),
('esca@gmail.com', 2),
('lza@gmail.com', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `TENANT`
--
ALTER TABLE `TENANT`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `CONTO`
--
ALTER TABLE `CONTO`
  ADD PRIMARY KEY (`email_intestatario`, `tenant_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Indexes for table `PERMESSO`
--
ALTER TABLE `PERMESSO`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codice` (`codice`);

--
-- Indexes for table `RUOLO`
--
ALTER TABLE `RUOLO`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indexes for table `RUOLO_PERMESSO`
--
ALTER TABLE `RUOLO_PERMESSO`
  ADD PRIMARY KEY (`id_ruolo`,`id_permesso`),
  ADD KEY `id_permesso` (`id_permesso`);

--
-- Indexes for table `UTENTE`
--
ALTER TABLE `UTENTE`
  ADD PRIMARY KEY (`email`, `tenant_id`),
  ADD KEY `idx_tenant` (`tenant_id`);

--
-- Indexes for table `UTENTE_RUOLO`
--
ALTER TABLE `UTENTE_RUOLO`
  ADD PRIMARY KEY (`email_utente`,`id_ruolo`),
  ADD KEY `id_ruolo` (`id_ruolo`);

--
-- AUTO_INCREMENT for table `TENANT`
--
ALTER TABLE `TENANT`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `PARTITA`
--
ALTER TABLE `PARTITA`
  MODIFY `id_partita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `PERMESSO`
--
ALTER TABLE `PERMESSO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `PUNTATA`
--
ALTER TABLE `PUNTATA`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `RUOLO`
--
ALTER TABLE `RUOLO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `SCHEDINA`
--
ALTER TABLE `SCHEDINA`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `CONTO`
--
ALTER TABLE `CONTO`
  ADD CONSTRAINT `fk_conto_utente` FOREIGN KEY (`email_intestatario`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conto_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `TENANT` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `SCHEDINA`
--
ALTER TABLE `SCHEDINA`
  ADD CONSTRAINT `fk_schedina_utente` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedina_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `TENANT` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `PUNTATA`
--
ALTER TABLE `PUNTATA`
  ADD CONSTRAINT `fk_puntata_schedina` FOREIGN KEY (`id_schedina`) REFERENCES `SCHEDINA` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_puntata_partita` FOREIGN KEY (`id_partita`) REFERENCES `PARTITA` (`id_partita`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_puntata_utente` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_puntata_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `TENANT` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `RUOLO_PERMESSO`
--
ALTER TABLE `RUOLO_PERMESSO`
  ADD CONSTRAINT `fk_ruolo_permesso_ruolo` FOREIGN KEY (`id_ruolo`) REFERENCES `RUOLO` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ruolo_permesso_permesso` FOREIGN KEY (`id_permesso`) REFERENCES `PERMESSO` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `UTENTE_RUOLO`
--
ALTER TABLE `UTENTE_RUOLO`
  ADD CONSTRAINT `fk_utente_ruolo_utente` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utente_ruolo_ruolo` FOREIGN KEY (`id_ruolo`) REFERENCES `RUOLO` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `UTENTE`
--
ALTER TABLE `UTENTE`
  ADD CONSTRAINT `fk_utente_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `TENANT` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `PARTITA`
--
ALTER TABLE `PARTITA`
  ADD CONSTRAINT `fk_partita_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `TENANT` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
