-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 26, 2026 at 05:43 AM
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
-- Database: `pdl_helpdesk`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('solved','status_update') DEFAULT 'status_update',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `requested_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `user_id`, `username`, `status`, `requested_at`, `processed_at`, `processed_by`) VALUES
(1, 2, 'normal user', 'done', '2026-01-25 13:48:50', '2026-01-25 13:49:16', 1),
(2, 2, 'normal user', 'done', '2026-01-25 14:07:43', '2026-01-25 14:11:45', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_no` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team` enum('it','mis') NOT NULL,
  `problem` text NOT NULL,
  `status` enum('Pending','Solved') DEFAULT 'Pending',
  `solution` text DEFAULT NULL,
  `solved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `solved_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','super_admin','user','it','mis') NOT NULL,
  `user_status` enum('active','inactive') DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `user_status`, `remarks`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'admin', '$2y$10$T9oSIKeI26mhnCQvK7VR/.qBp/tkRA9FrPZXzdxEB3nOahqy1bgHC', 'admin', 'active', NULL, '2026-01-18 17:20:51', NULL, NULL),
(2, 'normal user', '$2y$10$q2Pwz5CNOh/7oODbOvBCROowearFRV8AsXv94ZZL1wTLlHX6HoNAW', 'user', 'active', NULL, '2026-01-18 17:32:34', NULL, NULL),
(3, 'it user', '$2y$10$lHVc4W5g03dacvdQ1MgiFOSGV2OLD0idoB2l9JjYN3LvC/Ubhd1Ve', 'it', 'active', 'Activated by admin on 2026-01-25 09:13:00', '2026-01-18 17:32:46', NULL, NULL),
(4, 'mis user', '$2y$10$YUzFxaoOJJIYz..LvOhMie7By4AVKhosm5VJi65iB3LrJou44klgy', 'mis', 'active', NULL, '2026-01-18 17:32:53', NULL, NULL),
(6, 'PDLC1-40915', '$2y$10$TZWWfcrIsQv0aKwp1GRh4OZ/2Ads0A9D/zmkptK3N9OAQXKbvoBA.', 'admin', 'active', NULL, '2026-01-21 09:56:14', NULL, NULL),
(7, '39505', '$2y$10$2BfvIG0DTM/VNMuJ.k8iC.1iIsmPjpiDeCqBcTZXBENxjcTedvHZO', 'super_admin', 'active', NULL, '2026-01-25 12:23:37', NULL, NULL),
(8, 'PDLC1-7086', '$2y$10$l1mNWLA0s52fuRqghqbrDODO8/fRU8YBrHUxCvMLzNOHWsUlyoZR6', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(9, 'PDLC1-9773', '$2y$10$ZykUcOQ.9XEh224iFAepr.5fTifN0IKmf5UfX3rHAjnaUf9TA0r4u', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(10, 'PDLC1-20918', '$2y$10$WgRDFTU6FS2zwfhW4JU5L.hbpbakxqmmWkBDdLgnbXXsXkrLbe822', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(11, 'PDLC1-21015', '$2y$10$dQrrGVD1HHffdojazaMAeevrx1YPkXAR07LdWXWoWcQTnlA3ATO3i', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(12, 'PDLC1-30705', '$2y$10$UUdl0fMQJ.1kAZWcTddRJ.eBvVXHRdya/faqZLjbZlm2HB19JhG0W', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(13, 'PDLC1-30707', '$2y$10$spXCMe35fgnj1rPkTjiXF.hmziFg/jSXLj8a9.4JhPvwJvXQLG9qa', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(14, 'PDLC1-30709', '$2y$10$HtSslPlTtatu222omB5YTuPbP/M4imFCL/eaoIY4FVBZZdhr4GgjO', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(15, 'PDLC1-30710', '$2y$10$lIshQ6HXz5Au5/YEJujv6eIxAbTsV5bxfu172ZqR2TxglicE3j8EC', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(16, 'PDLC1-30711', '$2y$10$0c8/4NSfR.He62KdDeLak.vqsJQxLyAL8oPn68kKXIsW3lyNXye7W', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(17, 'PDLC1-30712', '$2y$10$wWikLcxOHUE/eWU31ZhuIewGI51eDKCadOQ9F2APzsFOFj5ARKDgy', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(18, 'PDLC1-30713', '$2y$10$f6nplD0/yRBCVjDLchijKeri2WhSTyjDfmk7A0bAVDm.kxq0I5OQS', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(19, 'PDLC1-30714', '$2y$10$cXGs./ir0zZTUnyB39DrauEORFQsMp1NfqfmiKzADXr5KVHT/DHx.', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(20, 'PDLC1-30715', '$2y$10$D7SLswoN3ANArpkt9XGiHOqgSrf5yv9ALbBDFSXrtSuynNOUCt3ee', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(21, 'PDLC1-30716', '$2y$10$DwG3JQqLAP52goAEnxAf2.Alm/dLvX2yw/qTwqxBB8TAS0Cc5yi2K', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(22, 'PDLC1-30717', '$2y$10$HIitfJkO0yYUddjxtXxmKOxy.EvPWiw.G7ztOD5Ozz/ll/ESICbR.', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(23, 'PDLC1-30718', '$2y$10$jlpBP1rWivak8ORKD4v/Wu1rnOuLsrmAkmMVGcbLaGg52k0sZF9bG', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(24, 'PDLC1-30719', '$2y$10$INlQcDVblSUbLl4bFPD9butazOlObwiObxSMFEcLO2MbD6V7gelCq', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(25, 'PDLC1-30720', '$2y$10$sZbhUmUSS8Z3gbW/mjt0zuskkFGGqd66/adsNUUI3D6c.5R5k6sm.', 'user', 'active', NULL, '2026-01-26 10:32:20', NULL, NULL),
(26, '40915', '$2y$10$6a79tlrT1aY3x30w7APhaenwjK9ORQYIVu9Bk.oEv.4Z7iiMjGp9m', 'admin', 'active', NULL, '2026-01-26 10:33:52', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `solved_by` (`solved_by`),
  ADD KEY `idx_team` (`team`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`);

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `password_reset_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`solved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
