-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 30, 2026 at 01:22 PM
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
-- Database: `finaldb`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `username`, `role`, `action`, `details`, `timestamp`) VALUES
(1, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1', '2026-01-24 07:41:28'),
(2, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1', '2026-01-24 07:44:00'),
(3, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-24 08:22:58'),
(4, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-24 08:23:29'),
(5, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-24 08:23:31'),
(6, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-24 08:23:45'),
(7, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-24 09:53:13'),
(8, 7, 'renielbantilan00', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 10:20:38'),
(9, 7, 'renielbantilan00', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-25 19:29:40'),
(10, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-25 19:32:32'),
(11, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-25 19:33:22'),
(12, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-25 19:33:50'),
(13, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-25 19:34:00'),
(14, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:27:47'),
(15, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:27:54'),
(16, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:29:12'),
(17, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:30:07'),
(18, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 19:30:26'),
(19, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:01:01'),
(20, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:01:13'),
(21, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:02:26'),
(22, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:02:41'),
(23, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-27 20:05:00'),
(24, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-27 20:05:02'),
(25, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-27 20:05:02'),
(26, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-27 20:05:03'),
(27, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-27 20:05:04'),
(28, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-27 20:05:05'),
(29, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:05:07'),
(30, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:06:09'),
(31, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:06:16'),
(32, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:06:52'),
(33, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:07:12'),
(34, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:07:25'),
(35, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:08:20'),
(36, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:08:37'),
(37, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:08:40'),
(38, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:10:06'),
(39, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 20:10:11'),
(40, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-28 17:01:50'),
(41, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-28 17:01:55'),
(42, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-28 17:01:56'),
(43, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-28 17:01:56'),
(44, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-28 17:01:56'),
(45, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-28 17:01:56'),
(46, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-28 17:01:57'),
(47, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-28 17:03:39'),
(48, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:32:27'),
(49, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:32:31'),
(50, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:33:45'),
(51, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:33:48'),
(52, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:34:00'),
(53, 24, 'admin_user', 'admin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-29 12:34:17'),
(54, 24, 'admin_user', 'admin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-29 12:34:17'),
(55, 24, 'admin_user', 'admin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-29 12:34:22'),
(56, 24, 'admin_user', 'admin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-29 12:34:24'),
(57, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:34:26'),
(58, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:34:39'),
(59, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-01-29 12:34:48'),
(60, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-01-29 12:34:48'),
(61, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-29 12:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `idno` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `suffix` varchar(10) NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `country` varchar(100) NOT NULL,
  `purokstreet` varchar(100) NOT NULL,
  `brgy` varchar(100) NOT NULL,
  `municipal` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zipcode` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `security_question1` varchar(255) DEFAULT NULL,
  `security_answer1` varchar(255) DEFAULT NULL,
  `security_question2` varchar(255) DEFAULT NULL,
  `security_answer2` varchar(255) DEFAULT NULL,
  `security_question3` varchar(255) DEFAULT NULL,
  `security_answer3` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `status` enum('active','blocked') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `idno`, `firstname`, `middlename`, `lastname`, `suffix`, `birthdate`, `age`, `sex`, `country`, `purokstreet`, `brgy`, `municipal`, `province`, `zipcode`, `username`, `email`, `password`, `security_question1`, `security_answer1`, `security_question2`, `security_answer2`, `security_question3`, `security_answer3`, `role`, `status`) VALUES
(21, '2021-1170', 'Reniel', 'P', 'Bantilan', ' ', '2001-08-07', 23, 'Male', 'Philippines', 'Purok 1', 'Tagbongabong', 'Cabadbaran', 'Agusan Del Norte', 8611, 'reniel.bantilan', 'reniel.bantilan@csucc.edu.ph', '$2y$10$kpi4zIic.veTV5Tzeq8xI.fa1X5FqCxFB2K7eZQaicw.6VIQfn2VO', 'Favorite Pet?', 'Cat', 'Favorite Color?', 'Blue', 'Favorite Food?', 'Adobo', 'user', 'active'),
(22, '2021-0912', 'John', 'B', 'Digal', ' ', '2001-01-01', 25, 'Male', 'Philippines', '1', 'Wws', 'Rtr', 'Agusan Del Norte', 8605, 'reniel123', 'renielbantilan69@gmail.com', '$2y$10$vIPOCW5pISxIfsJJFfxrve7ZDVxvsB9Us3s197paBA1wiloutwdL6', 'Favorite Pet?', 'iro', 'Favorite Color?', 'pink', 'Favorite Food?', 'iro', 'user', 'active'),
(23, '2020-0348', 'Jonah May', 'S', 'Hinautan', ' ', '2001-01-01', 25, 'Female', 'Philippines', 'Purok 2', 'Humilog', 'Remedios T Romualdez', 'Agusan Del Norte', 8611, 'jm.hinautan', 'renielbantilan42@gmail.com', '$2y$10$klfk4DU93NZ1B7PzCC6RJO2f9jHeCnuLYxu6ByC7EjWoVSMyiM95W', 'What was the name of your first pet?', 'cat', 'What is your lucky number?', '7', 'What is the name of your best friend?', 'aldrin', 'user', 'active'),
(24, 'ADMIN-6933', 'System', '', 'Admin', '', '2000-01-01', 24, 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', 0, 'admin_user', 'admin@rubiks.com', '$2y$10$ZW0A1mB5E1w2k4t75i/1O.OQD0kZctintDJhkhpU4PTn.QUHwhBAe', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active'),
(25, 'ADMIN-3498', 'Super', '', 'Admin', '', '2000-01-01', 24, 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', 0, 'superadmin_user', 'superadmin@rubiks.com', '$2y$10$npGgNdxAJ26VFIVFlq00Zuz3v4q46p78sK78guLFbbFVCucZGLkdO', NULL, NULL, NULL, NULL, NULL, NULL, 'superadmin', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
