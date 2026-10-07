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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
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
  `status` enum('active','approved','blocked','pending','inactive') NOT NULL DEFAULT 'pending',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `profile_completed` tinyint(1) NOT NULL DEFAULT 1,
  `privileges` text DEFAULT NULL,
  `block_reason` varchar(255) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`idno`, `firstname`, `middlename`, `lastname`, `suffix`, `birthdate`, `age`, `sex`, `country`, `purokstreet`, `brgy`, `municipal`, `province`, `zipcode`, `username`, `email`, `password`, `security_question1`, `security_answer1`, `security_question2`, `security_answer2`, `security_question3`, `security_answer3`, `role`, `status`) VALUES
('2026-0001', 'Super', '', 'Admin', '', '2000-01-01', 24, 'Male', 'Philippines', 'Admin St', 'Admin Brgy', 'Admin City', 'Admin Prov', 0, 'SuperAdmin123', 'mnmrubikscubeshop00@gmail.com', '$2y$10$VCYNVpaBnlo.zaZQhNKbteHs0ecwwjg3kGQQvEC/xqHwlbYXyCobC', 'What is your mother\'s maiden name?', 'qwert', 'What is the name of your favorite teacher?', 'asdf', 'What is your favorite color?', 'zxcv', 'superadmin', 'active');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
