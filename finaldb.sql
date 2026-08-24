-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 01:17 AM
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
(1, 25, 'superadmin_user', 'superadmin', 'Clear Logs', 'All audit logs have been cleared.', '2026-02-15 11:58:10'),
(2, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 11:58:21'),
(3, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 11:58:32'),
(4, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 11:59:15'),
(5, 23, 'jm.hinautan', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 11:59:45'),
(6, 23, 'jm.hinautan', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 11:59:53'),
(7, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 12:00:00'),
(8, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 12:03:59'),
(9, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 12:04:20'),
(10, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: admin_user', '2026-02-15 12:04:48'),
(11, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: admin_user', '2026-02-15 12:04:49'),
(12, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 12:05:01'),
(13, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 11:39:53'),
(14, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: admin_user', '2026-02-19 11:40:23'),
(15, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: admin_user', '2026-02-19 11:40:24'),
(16, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 11:41:56'),
(17, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 11:51:48'),
(18, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: user', '2026-02-19 13:28:29'),
(19, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: admin', '2026-02-19 13:28:37'),
(20, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: superadmin', '2026-02-19 13:28:43'),
(21, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: admin', '2026-02-19 13:28:46'),
(22, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 13:29:09'),
(23, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 13:30:00'),
(24, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:03:25'),
(25, 24, 'admin_user', 'admin', 'Block User', 'Blocked user: reniel.bantilan', '2026-02-19 16:03:57'),
(26, 24, 'admin_user', 'admin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-02-19 16:03:59'),
(27, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:04:26'),
(28, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:04:45'),
(29, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: superadmin', '2026-02-19 16:05:29'),
(30, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: admin_user', '2026-02-19 16:05:32'),
(31, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: admin_user', '2026-02-19 16:05:34'),
(32, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed admin_user role to: admin', '2026-02-19 16:05:37'),
(33, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 16:05:42'),
(34, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:18:44'),
(35, 26, 'juan.delacruzz', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:32:50'),
(36, 26, 'juan.delacruzz', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:33:09'),
(37, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:33:53'),
(38, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:45:39'),
(39, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 20:49:32'),
(40, 25, 'superadmin_user', 'superadmin', 'Create User', 'Created new admin: renielbantilan00', '2026-02-19 21:06:00'),
(41, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-02-19 21:07:20'),
(42, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-02-19 21:07:22'),
(43, 25, 'superadmin_user', 'superadmin', 'Block User', 'Blocked user: reniel.bantilan', '2026-02-19 21:07:22'),
(44, 25, 'superadmin_user', 'superadmin', 'Unblock User', 'Unblocked user: reniel.bantilan', '2026-02-19 21:07:23'),
(45, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed juan.delacruzz role to: admin', '2026-02-19 21:07:40'),
(46, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed juan.delacruzz role to: superadmin', '2026-02-19 21:07:53'),
(47, 25, 'superadmin_user', 'superadmin', 'Change Role', 'Changed juan.delacruzz role to: user', '2026-02-19 21:07:59'),
(48, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 21:12:08'),
(49, 25, 'superadmin_user', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 21:16:30'),
(50, 25, 'superadmin_user', 'superadmin', 'Create User', 'Created new user: digal123', '2026-02-19 21:36:30'),
(51, 25, 'superadmin_user', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 21:46:38'),
(52, 24, 'admin_user', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 21:46:53'),
(53, 24, 'admin_user', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 21:55:38'),
(54, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:09:05'),
(55, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:09:08'),
(56, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:10:12'),
(57, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:10:29'),
(58, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:11:05'),
(59, 25, 'SuperAdmin123', 'superadmin', 'Change Role', 'Changed reniel123 role to: admin', '2026-02-19 22:11:20'),
(60, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:11:50'),
(61, 28, 'digal123', 'user', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:15:41'),
(62, 28, 'digal123', 'user', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:15:43'),
(63, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:15:57'),
(64, 25, 'SuperAdmin123', 'superadmin', 'Change Role', 'Changed digal123 role to: admin', '2026-02-19 22:16:07'),
(65, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:16:15'),
(66, 28, 'digal123', 'admin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:16:29'),
(67, 28, 'digal123', 'admin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:16:34'),
(68, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:23:48'),
(69, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:31:28'),
(70, 25, 'SuperAdmin123', 'superadmin', 'Login', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:31:47'),
(71, 25, 'SuperAdmin123', 'superadmin', 'Block User', 'Blocked user: admin_user', '2026-02-19 22:37:18'),
(72, 25, 'SuperAdmin123', 'superadmin', 'Unblock User', 'Unblocked user: admin_user', '2026-02-19 22:37:18'),
(73, 25, 'SuperAdmin123', 'superadmin', 'Block User', 'Blocked user: admin_user', '2026-02-19 22:41:02'),
(74, 25, 'SuperAdmin123', 'superadmin', 'Unblock User', 'Unblocked user: admin_user', '2026-02-19 22:41:03'),
(75, 25, 'SuperAdmin123', 'superadmin', 'Logout', 'IP: ::1 | Host: DESKTOP-OJ68MFQ | Device: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-19 22:55:36');

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
(22, '2021-0912', 'John', 'B', 'Digal', ' ', '2001-01-01', 25, 'Male', 'Philippines', '1', 'Wws', 'Rtr', 'Agusan Del Norte', 8605, 'reniel123', 'renielbantilan69@gmail.com', '$2y$10$vIPOCW5pISxIfsJJFfxrve7ZDVxvsB9Us3s197paBA1wiloutwdL6', 'Favorite Pet?', 'iro', 'Favorite Color?', 'pink', 'Favorite Food?', 'iro', 'admin', 'active'),
(23, '2020-0348', 'Jonah May', 'S', 'Hinautan', ' ', '2001-01-01', 25, 'Female', 'Philippines', 'Purok 2', 'Humilog', 'Remedios T Romualdez', 'Agusan Del Norte', 8611, 'jm.hinautan', 'renielbantilan42@gmail.com', '$2y$10$klfk4DU93NZ1B7PzCC6RJO2f9jHeCnuLYxu6ByC7EjWoVSMyiM95W', 'What was the name of your first pet?', 'cat', 'What is your lucky number?', '7', 'What is the name of your best friend?', 'aldrin', 'user', 'active'),
(24, 'ADMIN-6933', 'System', '', 'Admin', '', '2000-01-01', 24, 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', 0, 'admin_user', 'admin@rubiks.com', '$2y$10$ZW0A1mB5E1w2k4t75i/1O.OQD0kZctintDJhkhpU4PTn.QUHwhBAe', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active'),
(25, 'ADMIN-3498', 'Super', '', 'Admin', '', '2000-01-01', 24, 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', 0, 'SuperAdmin123', 'mnmrubikscubeshop00@gmail.com', '$2y$10$VCYNVpaBnlo.zaZQhNKbteHs0ecwwjg3kGQQvEC/xqHwlbYXyCobC', 'What is your mother\'s maiden name?', 'qwert', 'What is the name of your favorite teacher?', 'asdf', 'What is your favorite color?', 'zxcv', 'superadmin', 'active'),
(26, '2020-0343', 'Juan', 'S', 'Dela Cruz', ' ', '2003-01-01', 23, 'Male', 'Philippines', 'Purok 2', 'Totoy', 'Hahaha', 'Agusan Del Norte', 8611, 'juan.delacruzz', 'aldrin.cabilic@csucc.edu.ph', '$2y$10$Flq8ojYBR72DFTrDJ.L5O.u0l9l70aAbRurISwK1qM8xYRnoNWV2a', 'What is your mother\'s maiden name?', 'asd', 'What is the name of your favorite teacher?', 'asd', 'What is your favorite color?', 'asd', 'user', 'active'),
(27, '', 'Jonah Mays', 'S', 'Hinautans', '', '2001-01-01', 25, 'Male', '', '', '', '', '', 0, 'renielbantilan00', 'jonahmay.hinautan@csucc.edu.ph', '$2y$10$oZCm9x2CI3PWLRfWVumiw.C2eyH0rQ7FKJmozy69ZEGCXJoOhCPcu', 'What is your mother\'s maiden name?', 'asd', 'What is the name of your favorite teacher?', 'asd', 'What is your favorite color?', 'asd', 'admin', 'active'),
(28, '2023-2356', 'John', 'G', 'Digal', '', '2002-02-22', 23, 'Male', 'Philippines', 'Purok 2', 'Buhang', 'Cabadbaran', 'Agusan del Norte', 8611, 'digal123', 'limetbreaker69@gmail.com', '$2y$10$wfrKXZWWhTP5dBx8re5qZuHnqUuJUlQ1BsyGdbshUnX2Jc2ZqLp0G', 'What is your mother\'s maiden name?', 'asd', 'What is the name of your favorite teacher?', 'asd', 'What is your favorite color?', 'asd', 'admin', 'active');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
