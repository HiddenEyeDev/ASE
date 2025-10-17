-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2025 at 05:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ase`
--

-- --------------------------------------------------------

--
-- Table structure for table `et_player_events`
--

CREATE TABLE `et_player_events` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `player_name` varchar(64) NOT NULL,
  `plain_name` varchar(64) NOT NULL,
  `event_type` enum('connect','disconnect') NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_player_history`
--

CREATE TABLE `et_player_history` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `player_name` varchar(64) NOT NULL,
  `plain_name` varchar(64) NOT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  `total_time_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_player_score_history`
--

CREATE TABLE `et_player_score_history` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `player_name` varchar(64) NOT NULL,
  `plain_name` varchar(64) NOT NULL,
  `score` int(11) DEFAULT 0,
  `recorded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_server_list`
--

CREATE TABLE `et_server_list` (
  `id` int(11) NOT NULL,
  `host` varchar(64) NOT NULL,
  `port` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_query_success` datetime DEFAULT NULL,
  `country` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `et_server_list`
--

INSERT INTO `et_server_list` (`id`, `host`, `port`, `active`, `last_query_success`, `country`) VALUES
(1, '135.148.137.185', 27960, 1, '2025-10-13 12:12:28', 'US'),
(2, '185.150.191.44', 27960, 1, '2025-10-13 12:12:27', 'US'),
(3, '45.45.238.219', 27960, 1, '2025-10-13 12:12:27', NULL),
(4, '204.13.232.202', 27960, 1, '2025-10-13 12:12:27', 'US'),
(5, '67.228.108.137', 27960, 1, '2025-10-13 12:12:27', 'US'),
(6, '153.156.26.48', 27960, 1, '2025-10-13 12:12:27', 'JP'),
(7, '45.83.106.97', 27970, 1, '2025-10-13 12:12:27', 'DE'),
(8, '65.108.82.168', 27960, 1, '2025-10-13 12:12:27', 'FI'),
(9, '103.152.197.99', 27961, 1, '2025-10-13 12:12:27', 'AU'),
(10, '37.187.79.49', 27999, 1, '2025-10-13 12:12:27', 'FR'),
(11, '45.83.106.97', 27960, 1, '2025-10-13 12:12:27', 'DE'),
(12, '164.132.203.173', 27960, 1, '2025-10-13 12:12:27', 'FR'),
(13, '74.50.89.106', 27960, 1, '2025-10-13 12:12:27', 'US'),
(14, '89.144.32.15', 27960, 1, '2025-10-13 12:12:27', 'DE'),
(15, '84.200.135.3', 27960, 1, '2025-10-13 12:12:27', 'DE'),
(16, '108.93.119.194', 27850, 1, '2025-10-13 12:12:27', 'US'),
(17, '107.191.51.159', 27960, 1, '2025-10-13 12:12:27', 'US'),
(18, '178.63.72.133', 27960, 1, '2025-10-13 12:12:27', 'DE'),
(19, '178.63.72.170', 27960, 1, '2025-10-13 12:12:27', 'DE'),
(20, '46.105.209.160', 27960, 1, '2025-10-13 12:12:27', 'FR'),
(21, '45.9.60.102', 27963, 1, '2025-10-13 12:12:27', 'DE'),
(22, '173.230.141.36', 27960, 1, '2025-10-13 12:12:27', 'US');

-- --------------------------------------------------------

--
-- Table structure for table `et_server_map_history`
--

CREATE TABLE `et_server_map_history` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `map_name` varchar(64) NOT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  `play_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_server_map_sessions`
--

CREATE TABLE `et_server_map_sessions` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `map_name` varchar(64) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_server_players`
--

CREATE TABLE `et_server_players` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `plain_name` varchar(64) NOT NULL,
  `score` int(11) DEFAULT 0,
  `ping` int(11) DEFAULT 0,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `et_server_status`
--

CREATE TABLE `et_server_status` (
  `id` int(11) NOT NULL,
  `host` varchar(64) NOT NULL,
  `port` int(11) NOT NULL,
  `hostname` varchar(128) DEFAULT NULL,
  `sv_sac` varchar(64) DEFAULT NULL,
  `mod_version` varchar(64) DEFAULT NULL,
  `g_balancedteams` varchar(64) DEFAULT NULL,
  `g_bluelimbotime` varchar(64) DEFAULT NULL,
  `g_redlimbotime` varchar(64) DEFAULT NULL,
  `gamename` varchar(64) DEFAULT NULL,
  `g_needpass` varchar(64) DEFAULT NULL,
  `sv_privateClients` varchar(64) DEFAULT NULL,
  `mapname` varchar(64) DEFAULT NULL,
  `protocol` varchar(64) DEFAULT NULL,
  `g_gametype` varchar(64) DEFAULT NULL,
  `timelimit` varchar(64) DEFAULT NULL,
  `g_friendlyFire` varchar(64) DEFAULT NULL,
  `g_antilag` varchar(64) DEFAULT NULL,
  `omnibot_enable` varchar(64) DEFAULT NULL,
  `sv_maxclients` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `player_count` int(11) DEFAULT 0,
  `last_query_success` datetime DEFAULT NULL,
  `country` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `et_player_events`
--
ALTER TABLE `et_player_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_plain_name` (`plain_name`);

--
-- Indexes for table `et_player_history`
--
ALTER TABLE `et_player_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_hist_plain_name` (`plain_name`);

--
-- Indexes for table `et_player_score_history`
--
ALTER TABLE `et_player_score_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_score_plain_name` (`plain_name`);

--
-- Indexes for table `et_server_list`
--
ALTER TABLE `et_server_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `host_port` (`host`,`port`);

--
-- Indexes for table `et_server_map_history`
--
ALTER TABLE `et_server_map_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `server_map_unique` (`server_id`,`map_name`);

--
-- Indexes for table `et_server_map_sessions`
--
ALTER TABLE `et_server_map_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`);

--
-- Indexes for table `et_server_players`
--
ALTER TABLE `et_server_players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_player_plain_name` (`plain_name`);

--
-- Indexes for table `et_server_status`
--
ALTER TABLE `et_server_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `host_port` (`host`,`port`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `et_player_events`
--
ALTER TABLE `et_player_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_player_history`
--
ALTER TABLE `et_player_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_player_score_history`
--
ALTER TABLE `et_player_score_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_server_list`
--
ALTER TABLE `et_server_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `et_server_map_history`
--
ALTER TABLE `et_server_map_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_server_map_sessions`
--
ALTER TABLE `et_server_map_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_server_players`
--
ALTER TABLE `et_server_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_server_status`
--
ALTER TABLE `et_server_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `et_player_events`
--
ALTER TABLE `et_player_events`
  ADD CONSTRAINT `et_player_events_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);

--
-- Constraints for table `et_player_history`
--
ALTER TABLE `et_player_history`
  ADD CONSTRAINT `et_player_history_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);

--
-- Constraints for table `et_player_score_history`
--
ALTER TABLE `et_player_score_history`
  ADD CONSTRAINT `et_player_score_history_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);

--
-- Constraints for table `et_server_map_history`
--
ALTER TABLE `et_server_map_history`
  ADD CONSTRAINT `et_server_map_history_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);

--
-- Constraints for table `et_server_map_sessions`
--
ALTER TABLE `et_server_map_sessions`
  ADD CONSTRAINT `et_server_map_sessions_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);

--
-- Constraints for table `et_server_players`
--
ALTER TABLE `et_server_players`
  ADD CONSTRAINT `et_server_players_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `et_server_status` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
