-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  sam. 26 jan. 2019 à 22:40
-- Version du serveur :  5.7.19
-- Version de PHP :  7.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `froxynetwork`
--

-- --------------------------------------------------------

--
-- Structure de la table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `uuid` char(36) NOT NULL,
  `pseudo` varchar(20) NOT NULL,
  `display_name` varchar(20) NOT NULL,
  `coins` bigint(20) DEFAULT '0',
  `level` int(11) DEFAULT '0',
  `exp` int(11) DEFAULT '0',
  `first_login` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(20) DEFAULT NULL,
  `lang` char(5) DEFAULT 'fr_FR',
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `player_displayName_uindex` (`display_name`),
  UNIQUE KEY `player_uuid_uindex` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `player`
--

-- --------------------------------------------------------

--
-- Structure de la table `server`
--

DROP TABLE IF EXISTS `server`;
CREATE TABLE IF NOT EXISTS `server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `port` int(11) NOT NULL,
  `status` varchar(16) NOT NULL,
  `creation_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `server`
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
