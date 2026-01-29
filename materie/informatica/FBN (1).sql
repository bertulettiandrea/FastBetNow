-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 29, 2026 at 02:21 PM
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
-- Table structure for table `CONTO`
--

CREATE TABLE `CONTO` (
  `email_intestatario` varchar(254) NOT NULL,
  `saldo` float NOT NULL,
  `bonus` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CONTO`
--

INSERT INTO `CONTO` (`email_intestatario`, `saldo`, `bonus`) VALUES
('aaaaaa@gmail.com', 0, 0),
('admin@gmail.com', 0, 0),
('bertuletti.andrea.studente54@itispaleocapa.it', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `PARTITA`
--

CREATE TABLE `PARTITA` (
  `squadra_casa` varchar(15) NOT NULL,
  `squadra_trasferta` varchar(15) DEFAULT NULL,
  `risultato` varchar(5) DEFAULT NULL,
  `data` date NOT NULL,
  `campionato` varchar(15) DEFAULT NULL
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
  `id` int(11) NOT NULL,
  `esito` tinyint(1) DEFAULT NULL,
  `puntata` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `UTENTE`
--

CREATE TABLE `UTENTE` (
  `email` varchar(254) NOT NULL,
  `nome` varchar(20) NOT NULL,
  `cognome` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `UTENTE`
--

INSERT INTO `UTENTE` (`email`, `nome`, `cognome`, `password`, `refresh_token`) VALUES
('aaaaaa@gmail.com', 'a', 'a', '$2y$10$dIK.GHGW6Azy8O81PV7hLu3pzvs2F8K1NWthi1KOrBd7VYCHRyX9m', '3ebac36c24fc4bd3df7903c52e4c36794a0930206d13e6fe11465c1ca38ba3d61fe56717e95d4111'),
('admin@gmail.com', 'admin@gmail.com', 'admin@gmail.com', '$2y$10$G/FL7voNhZxe2PMKGOTgGOZvqYv7iX114/n9R5XhNOCQDx9BCAq6C', 'd9ee0887ea0e89511532389aeb129839b994061bda7c2b97ef801bfa37bf6b6c5381a22c11d09744'),
('bertuletti.andrea.studente54@itispaleocapa.it', 'ANDREA', 'BERTULETTI', '$2y$10$tKPie8GgJ1CANzwLmCQmUed62chPfj9eJWRsEYhr1s7/ezdug4sn2', NULL);

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
('aaaaaa@gmail.com', 2),
('admin@gmail.com', 1),
('bertuletti.andrea.studente54@itispaleocapa.it', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `CONTO`
--
ALTER TABLE `CONTO`
  ADD PRIMARY KEY (`email_intestatario`);

--
-- Indexes for table `PARTITA`
--
ALTER TABLE `PARTITA`
  ADD PRIMARY KEY (`squadra_casa`,`data`);

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
-- Indexes for table `SCHEDINA`
--
ALTER TABLE `SCHEDINA`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `UTENTE`
--
ALTER TABLE `UTENTE`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `UTENTE_RUOLO`
--
ALTER TABLE `UTENTE_RUOLO`
  ADD PRIMARY KEY (`email_utente`,`id_ruolo`),
  ADD KEY `id_ruolo` (`id_ruolo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `PERMESSO`
--
ALTER TABLE `PERMESSO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for dumped tables
--

--
-- Constraints for table `CONTO`
--
ALTER TABLE `CONTO`
  ADD CONSTRAINT `CONTO_ibfk_1` FOREIGN KEY (`email_intestatario`) REFERENCES `UTENTE` (`email`);

--
-- Constraints for table `RUOLO_PERMESSO`
--
ALTER TABLE `RUOLO_PERMESSO`
  ADD CONSTRAINT `RUOLO_PERMESSO_ibfk_1` FOREIGN KEY (`id_ruolo`) REFERENCES `RUOLO` (`id`),
  ADD CONSTRAINT `RUOLO_PERMESSO_ibfk_2` FOREIGN KEY (`id_permesso`) REFERENCES `PERMESSO` (`id`);

--
-- Constraints for table `UTENTE_RUOLO`
--
ALTER TABLE `UTENTE_RUOLO`
  ADD CONSTRAINT `UTENTE_RUOLO_ibfk_1` FOREIGN KEY (`email_utente`) REFERENCES `UTENTE` (`email`),
  ADD CONSTRAINT `UTENTE_RUOLO_ibfk_2` FOREIGN KEY (`id_ruolo`) REFERENCES `RUOLO` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
