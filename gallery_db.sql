-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2025 at 02:19 AM
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
-- Database: `gallery_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `userid` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`userid`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$t.QI9kpCYV52bZacjsswzOC6wnaCVc5MDrwu4uyq01qXZXUdINOji');

-- --------------------------------------------------------

--
-- Table structure for table `art`
--

CREATE TABLE `art` (
  `art_id` int(255) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Unknown Title',
  `artist_id` int(255) UNSIGNED NOT NULL,
  `date_created` date DEFAULT NULL,
  `genre` varchar(255) NOT NULL DEFAULT 'Undefined',
  `type` varchar(255) NOT NULL DEFAULT 'Undefined'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `art`
--

INSERT INTO `art` (`art_id`, `title`, `artist_id`, `date_created`, `genre`, `type`) VALUES
(1, 'Starry Night', 1, '1889-06-01', 'Post-Impressionism', 'Painting'),
(2, 'Mona Lisa', 2, '1503-01-01', 'Renaissance', 'Painting'),
(3, 'Water Lilies', 3, '1916-01-01', 'Impressionism', 'Painting'),
(4, 'Guernica', 4, '1937-06-01', 'Cubism', 'Painting'),
(5, 'The Potato Eaters', 1, '1885-04-01', 'Realism', 'Painting');

-- --------------------------------------------------------

--
-- Table structure for table `artist`
--

CREATE TABLE `artist` (
  `artist_id` int(255) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Brief description or notes about the artist'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artist`
--

INSERT INTO `artist` (`artist_id`, `first_name`, `last_name`, `dob`, `description`) VALUES
(1, 'Vincent', 'van Gogh', '1853-03-30', NULL),
(2, 'Leonardo', 'da Vinci', '1452-04-15', NULL),
(3, 'Claude', 'Monet', '1840-11-14', NULL),
(4, 'Pablo', 'Picasso', '1881-10-25', NULL),
(6, 'John', 'Smith', '2025-04-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `showcase_config`
--

CREATE TABLE `showcase_config` (
  `slot_id` varchar(50) NOT NULL,
  `art_id` int(11) DEFAULT NULL,
  `custom_description` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `showcase_config`
--

INSERT INTO `showcase_config` (`slot_id`, `art_id`, `custom_description`, `last_updated`) VALUES
('gallery1', NULL, NULL, '2025-04-21 05:53:48'),
('gallery2', NULL, NULL, '2025-04-21 05:53:48'),
('gallery3', NULL, NULL, '2025-04-21 05:53:48'),
('gallery4', NULL, NULL, '2025-04-21 05:53:48'),
('gallery5', NULL, NULL, '2025-04-21 05:53:48'),
('spotlight1', 4, 'Nice painting', '2025-04-21 06:24:20'),
('spotlight2', 1, 'Real neat.', '2025-04-21 06:24:20'),
('spotlight3', 3, 'Water lilies!', '2025-04-21 06:24:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `username_UNIQUE` (`username`);

--
-- Indexes for table `art`
--
ALTER TABLE `art`
  ADD PRIMARY KEY (`art_id`),
  ADD KEY `idx_artist_id` (`artist_id`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_genre` (`genre`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date_created` (`date_created`);

--
-- Indexes for table `artist`
--
ALTER TABLE `artist`
  ADD PRIMARY KEY (`artist_id`),
  ADD KEY `idx_artist_fullname` (`last_name`,`first_name`);

--
-- Indexes for table `showcase_config`
--
ALTER TABLE `showcase_config`
  ADD PRIMARY KEY (`slot_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `art`
--
ALTER TABLE `art`
  MODIFY `art_id` int(255) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `artist`
--
ALTER TABLE `artist`
  MODIFY `artist_id` int(255) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `art`
--
ALTER TABLE `art`
  ADD CONSTRAINT `fk_art_artist` FOREIGN KEY (`artist_id`) REFERENCES `artist` (`artist_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
