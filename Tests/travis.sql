SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `oauth_access_tokens` (
    `access_token` varchar(40) NOT NULL,
    `client_id` varchar(80) NOT NULL,
    `user_id` varchar(80) DEFAULT NULL,
    `expires` timestamp NOT NULL,
    `scope` varchar(4000) DEFAULT NULL,
    PRIMARY KEY (`access_token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `oauth_authorization_codes` (
    `authorization_code` varchar(40) NOT NULL,
    `client_id` varchar(80) NOT NULL,
    `user_id` varchar(80) DEFAULT NULL,
    `redirect_uri` varchar(2000) DEFAULT NULL,
    `expires` timestamp NOT NULL,
    `scope` varchar(4000) DEFAULT NULL,
    `id_token` varchar(1000) DEFAULT NULL,
    PRIMARY KEY (`authorization_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `oauth_clients` (
    `client_id` varchar(80) NOT NULL,
    `client_secret` varchar(80) DEFAULT NULL,
    `redirect_uri` varchar(2000) DEFAULT NULL,
    `grant_types` varchar(80) DEFAULT NULL,
    `scope` varchar(4000) DEFAULT NULL,
    `user_id` varchar(80) DEFAULT NULL,
    PRIMARY KEY (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `oauth_jwt` (
    `client_id` varchar(80) NOT NULL,
    `subject` varchar(80) DEFAULT NULL,
    `public_key` varchar(2000) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `oauth_refresh_tokens` (
    `refresh_token` varchar(40) NOT NULL,
    `client_id` varchar(80) NOT NULL,
    `user_id` varchar(80) DEFAULT NULL,
    `expires` timestamp NOT NULL,
    `scope` varchar(4000) DEFAULT NULL,
    PRIMARY KEY (`refresh_token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `oauth_scopes` (
    `scope` varchar(80) NOT NULL,
    `is_default` tinyint(1) DEFAULT NULL,
    PRIMARY KEY (`scope`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `player` (
    `uuid` char(36) NOT NULL,
    `nickname` varchar(20) NOT NULL,
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

-- --------------------------------------------------------

CREATE TABLE `server` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(16) NOT NULL,
    `port` int(11) NOT NULL,
    `status` varchar(16) NOT NULL,
    `creation_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
