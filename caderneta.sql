-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Fev-2025 às 01:20
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `caderneta`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `caderneta`
--

CREATE TABLE `caderneta` (
  `cadernetaid` int(11) NOT NULL,
  `nomecaderneta` varchar(40) NOT NULL,
  `tema` longtext NOT NULL,
  `cadernetapfp` varchar(535) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `card`
--

CREATE TABLE `card` (
  `cardid` int(11) NOT NULL,
  `cardname` varchar(40) NOT NULL,
  `carddescription` text NOT NULL,
  `pubkeyserver` text NOT NULL,
  `cardimage` varchar(535) NOT NULL,
  `aprovado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cardcaderneta`
--

CREATE TABLE `cardcaderneta` (
  `cardid` int(11) NOT NULL,
  `cadernetaid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `collectedcard`
--

CREATE TABLE `collectedcard` (
  `username` varchar(20) NOT NULL,
  `cardid` int(11) NOT NULL,
  `publickeyuser` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `linkusers`
--

CREATE TABLE `linkusers` (
  `link` mediumtext NOT NULL,
  `username` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `sharelinks`
--

CREATE TABLE `sharelinks` (
  `linkid` int(11) NOT NULL,
  `link` mediumtext NOT NULL,
  `key_value` varchar(32) NOT NULL,
  `expiration` datetime DEFAULT NULL,
  `cadernetaid` int(11) DEFAULT NULL,
  `cardid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usercaderneta`
--

CREATE TABLE `usercaderneta` (
  `cadernetaid` int(11) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `own` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `username` varchar(20) NOT NULL,
  `password` varchar(535) NOT NULL,
  `email` varchar(535) NOT NULL,
  `pfp` varchar(535) NOT NULL,
  `admin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `caderneta`
--
ALTER TABLE `caderneta`
  ADD PRIMARY KEY (`cadernetaid`);

--
-- Índices para tabela `card`
--
ALTER TABLE `card`
  ADD PRIMARY KEY (`cardid`);

--
-- Índices para tabela `cardcaderneta`
--
ALTER TABLE `cardcaderneta`
  ADD KEY `cardcaderneta_ibfk_1` (`cadernetaid`),
  ADD KEY `cardrelacao` (`cardid`);

--
-- Índices para tabela `collectedcard`
--
ALTER TABLE `collectedcard`
  ADD KEY `username` (`username`),
  ADD KEY `card` (`cardid`);

--
-- Índices para tabela `linkusers`
--
ALTER TABLE `linkusers`
  ADD KEY `user` (`username`);

--
-- Índices para tabela `sharelinks`
--
ALTER TABLE `sharelinks`
  ADD PRIMARY KEY (`linkid`),
  ADD KEY `idx_cadernetaid` (`cadernetaid`),
  ADD KEY `idx_cardid` (`cardid`);

--
-- Índices para tabela `usercaderneta`
--
ALTER TABLE `usercaderneta`
  ADD KEY `userrelacao` (`username`),
  ADD KEY `cadernetarelacao` (`cadernetaid`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caderneta`
--
ALTER TABLE `caderneta`
  MODIFY `cadernetaid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `card`
--
ALTER TABLE `card`
  MODIFY `cardid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sharelinks`
--
ALTER TABLE `sharelinks`
  MODIFY `linkid` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `cardcaderneta`
--
ALTER TABLE `cardcaderneta`
  ADD CONSTRAINT `cardcaderneta_ibfk_1` FOREIGN KEY (`cadernetaid`) REFERENCES `caderneta` (`cadernetaid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cardrelacao` FOREIGN KEY (`cardid`) REFERENCES `card` (`cardid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `collectedcard`
--
ALTER TABLE `collectedcard`
  ADD CONSTRAINT `card` FOREIGN KEY (`cardid`) REFERENCES `card` (`cardid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `username` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `linkusers`
--
ALTER TABLE `linkusers`
  ADD CONSTRAINT `user` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `sharelinks`
--
ALTER TABLE `sharelinks`
  ADD CONSTRAINT `cadernetalink` FOREIGN KEY (`cadernetaid`) REFERENCES `caderneta` (`cadernetaid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cardlink` FOREIGN KEY (`cardid`) REFERENCES `card` (`cardid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `usercaderneta`
--
ALTER TABLE `usercaderneta`
  ADD CONSTRAINT `cadernetarelacao` FOREIGN KEY (`cadernetaid`) REFERENCES `caderneta` (`cadernetaid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `userrelacao` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `delete_expired_links` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-02-18 20:45:32' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM sharelinks WHERE expiration < NOW()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
