SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `bonus` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `bonus`;

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `ckey` varchar(100) NOT NULL,
  `cval` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`id`, `ckey`, `cval`) VALUES
(1, 'prim.amount', '50'),
(2, 'prim.limit.monthly', '2'),
(3, 'prim.limit.yearly', '24');

CREATE TABLE `prim` (
  `id` bigint(20) NOT NULL,
  `user` varchar(100) NOT NULL,
  `user_slack_id` varchar(100) NOT NULL,
  `winner` varchar(100) NOT NULL,
  `winner_slack_id` varchar(100) NOT NULL,
  `prim_date` date NOT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `info` longtext,
  `slack_response_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `slack_user_info` (
  `id` int(11) NOT NULL,
  `slack_id` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ckey` (`ckey`);

ALTER TABLE `prim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `winner` (`winner`),
  ADD KEY `prim_date` (`prim_date`),
  ADD KEY `user_slack_id` (`user_slack_id`);

ALTER TABLE `slack_user_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slack_id` (`slack_id`);


ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `prim`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `slack_user_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
