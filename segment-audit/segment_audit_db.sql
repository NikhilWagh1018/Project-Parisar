-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 11:57 AM
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
-- Database: `segment_audit_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `intersections`
--

DROP TABLE IF EXISTS `intersections`;
CREATE TABLE `intersections` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `intersection_num` int(11) NOT NULL,
  `gps_coords` varchar(100) DEFAULT NULL,
  `landmark_name` varchar(255) DEFAULT NULL,
  `off_ramp` enum('Comfortable','Uncomfortable','No Ramp') DEFAULT NULL,
  `on_ramp` enum('Comfortable','Uncomfortable','No Ramp') DEFAULT NULL,
  `markings` enum('Present','Absent') DEFAULT NULL,
  `signage` enum('Present','Absent') DEFAULT NULL,
  `segment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `intersections`
--

INSERT INTO `intersections` (`id`, `audit_id`, `intersection_num`, `gps_coords`, `landmark_name`, `off_ramp`, `on_ramp`, `markings`, `signage`, `segment_id`) VALUES
(1, 6, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 7, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 7, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 8, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 8, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 9, 1, '18.5,73.8', 'signal', 'Comfortable', 'Comfortable', 'Present', 'Present', NULL),
(7, 10, 1, '45', 'gfhgdf', 'Comfortable', 'Comfortable', 'Present', 'Present', NULL),
(8, 10, 2, '50', 'eryey', 'Comfortable', 'Comfortable', 'Present', 'Present', NULL),
(9, 11, 1, '11.22,49.876', 'handedwadi', 'Comfortable', 'Comfortable', 'Absent', 'Present', NULL),
(10, 11, 2, '50', 'eryey', 'Uncomfortable', 'Comfortable', 'Absent', 'Present', NULL),
(11, 11, 3, '50.99', '234.2', 'No Ramp', 'No Ramp', 'Absent', 'Absent', NULL),
(12, 11, 4, '34.66', '65.43', 'Uncomfortable', 'Uncomfortable', 'Present', 'Present', NULL),
(13, 12, 1, '18.5,73.8', 'hg', 'Uncomfortable', 'Uncomfortable', 'Absent', 'Present', NULL),
(14, 13, 1, '45', '50', 'Uncomfortable', 'Comfortable', NULL, 'Present', NULL),
(15, 14, 1, '45', '50', 'Uncomfortable', 'Comfortable', NULL, 'Present', NULL),
(16, 15, 1, '45', '50', 'Uncomfortable', 'Comfortable', NULL, 'Present', NULL),
(17, 16, 1, '45', '50', 'Comfortable', 'Comfortable', 'Present', NULL, NULL),
(18, 18, 1, '18.5,73.8', '43534', 'Comfortable', 'Uncomfortable', 'Absent', 'Present', NULL),
(19, 20, 1, '18.5,73.8', '43534', 'No Ramp', 'Comfortable', 'Absent', 'Absent', NULL),
(20, 21, 1, '18.5,73.8', 'gym', 'Uncomfortable', 'Comfortable', 'Absent', 'Present', NULL),
(21, 23, 1, '432', '6543', 'No Ramp', 'Comfortable', 'Absent', 'Present', NULL),
(22, 24, 1, '18.5,73.8', '43534', 'Uncomfortable', 'Comfortable', 'Present', 'Present', NULL),
(23, 24, 2, 'rwety', 'eryey', 'No Ramp', 'No Ramp', 'Present', 'Absent', NULL),
(24, 24, 3, '50.99', '234.2', 'No Ramp', 'Uncomfortable', 'Absent', 'Absent', NULL),
(25, 24, 4, '34.66', '65.43', 'Uncomfortable', 'Comfortable', 'Absent', 'Present', NULL),
(26, 25, 1, NULL, NULL, NULL, NULL, 'Present', 'Absent', NULL),
(27, 25, 2, NULL, NULL, NULL, NULL, 'Absent', 'Present', NULL),
(28, 25, 3, NULL, NULL, NULL, NULL, 'Absent', 'Absent', NULL),
(29, 27, 1, '18.5,73.8', '50', 'Uncomfortable', 'Comfortable', 'Absent', 'Present', NULL),
(30, 27, 2, '4535345', '34556544', 'Comfortable', 'Comfortable', 'Absent', 'Absent', NULL),
(38, 69, 1, NULL, NULL, 'Uncomfortable', 'Uncomfortable', 'Present', 'Present', 4);

-- --------------------------------------------------------

--
-- Table structure for table `obstructions`
--

DROP TABLE IF EXISTS `obstructions`;
CREATE TABLE `obstructions` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `obstruction_category` enum('fixed','movable','parked') NOT NULL,
  `obstruction_type` varchar(100) NOT NULL,
  `cyclist_slowed` int(11) DEFAULT 0,
  `partial_obstructions` int(11) DEFAULT 0,
  `total_obstructions` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `segment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `obstructions`
--

INSERT INTO `obstructions` (`id`, `audit_id`, `obstruction_category`, `obstruction_type`, `cyclist_slowed`, `partial_obstructions`, `total_obstructions`, `created_at`, `segment_id`) VALUES
(1, 4, 'fixed', 'Trees', 1, 1, 0, '2026-04-27 09:46:17', NULL),
(2, 4, 'fixed', 'Poles', 0, 1, 1, '2026-04-27 09:46:17', NULL),
(3, 4, 'fixed', 'CCTV', 1, 1, 0, '2026-04-27 09:46:17', NULL),
(4, 5, 'fixed', 'TrafficSignal', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(5, 5, 'fixed', 'SignBoard', 0, 0, 1, '2026-04-27 09:46:17', NULL),
(6, 12, 'fixed', 'Trees', 1, 0, 1, '2026-04-27 09:46:17', NULL),
(7, 12, 'fixed', 'Poles', 3, 2, 0, '2026-04-27 09:46:17', NULL),
(8, 12, 'fixed', 'CCTV', 3, 2, 2, '2026-04-27 09:46:17', NULL),
(9, 12, 'fixed', 'TrafficSignal', 3, 1, 0, '2026-04-27 09:46:17', NULL),
(10, 12, 'fixed', 'SignBoard', 5, 0, 3, '2026-04-27 09:46:17', NULL),
(11, 12, 'fixed', 'ElectricalPanel', 5, 4, 0, '2026-04-27 09:46:17', NULL),
(12, 12, 'fixed', 'Bollards', 4, 0, 0, '2026-04-27 09:46:17', NULL),
(13, 13, 'fixed', 'Trees', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(14, 13, 'fixed', 'Poles', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(15, 14, 'fixed', 'Trees', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(16, 14, 'fixed', 'Poles', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(17, 15, 'fixed', 'Trees', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(18, 15, 'fixed', 'Poles', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(19, 16, 'fixed', 'Trees', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(20, 16, 'fixed', 'Poles', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(21, 17, 'fixed', 'Poles', 0, 0, 1, '2026-04-27 09:46:17', NULL),
(22, 18, 'fixed', 'Trees', 1, 1, 0, '2026-04-27 09:46:17', NULL),
(23, 18, 'fixed', 'Poles', 3, 2, 0, '2026-04-27 09:46:17', NULL),
(24, 19, 'fixed', 'Trees', 2, 0, 0, '2026-04-27 09:46:17', NULL),
(25, 19, 'fixed', 'BusStand', 1, 2, 0, '2026-04-27 09:46:17', NULL),
(32, 5, 'movable', 'Hawkers', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(33, 5, 'movable', 'GarbageBins', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(34, 5, 'movable', 'ConstructionMaterial', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(35, 12, 'movable', 'Hawkers', 5, 3, 9, '2026-04-27 09:46:17', NULL),
(36, 12, 'movable', 'ConstructionMaterial', 5, 0, 4, '2026-04-27 09:46:17', NULL),
(37, 12, 'movable', 'Hoardings', 3, 6, 0, '2026-04-27 09:46:17', NULL),
(38, 13, 'movable', 'Hawkers', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(39, 13, 'movable', 'GarbageBins', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(40, 14, 'movable', 'Hawkers', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(41, 14, 'movable', 'GarbageBins', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(42, 15, 'movable', 'Hawkers', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(43, 15, 'movable', 'GarbageBins', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(44, 16, 'movable', 'GarbageBins', 2, 0, 0, '2026-04-27 09:46:17', NULL),
(45, 18, 'movable', 'Hawkers', 1, 2, 4, '2026-04-27 09:46:17', NULL),
(46, 19, 'movable', 'Hawkers', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(47, 5, 'parked', 'ReligiousLandmark', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(48, 5, 'parked', 'RestaurantEatery', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(49, 5, 'parked', 'AutoGarage', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(50, 12, 'parked', 'AutoGarage', 1, 4, 2, '2026-04-27 09:46:17', NULL),
(51, 12, 'parked', 'CommercialRetailShops', 4, 0, 9, '2026-04-27 09:46:17', NULL),
(52, 13, 'parked', 'ReligiousLandmark', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(53, 14, 'parked', 'ReligiousLandmark', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(54, 15, 'parked', 'ReligiousLandmark', 1, 0, 0, '2026-04-27 09:46:17', NULL),
(55, 16, 'parked', 'RestaurantEatery', 0, 3, 0, '2026-04-27 09:46:17', NULL),
(56, 17, 'parked', 'RestaurantEatery', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(57, 18, 'parked', 'ReligiousLandmark', 3, 3, 2, '2026-04-27 09:46:17', NULL),
(58, 19, 'parked', 'RestaurantEatery', 0, 1, 0, '2026-04-27 09:46:17', NULL),
(59, 19, 'parked', 'AutoGarage', 0, 2, 0, '2026-04-27 09:46:17', NULL),
(60, 19, 'parked', 'OnStreetVending', 0, 2, 0, '2026-04-27 09:46:17', NULL),
(62, 20, 'fixed', 'CCTV', 2, 4, 6, '2026-04-27 09:57:03', NULL),
(63, 20, 'movable', 'PeopleSitting', 2, 4, 6, '2026-04-27 09:57:03', NULL),
(64, 20, 'parked', 'RestaurantEatery', 4, 2, 6, '2026-04-27 09:57:03', NULL),
(65, 20, 'parked', 'OnStreetVending', 2, 4, 6, '2026-04-27 09:57:03', NULL),
(66, 20, 'parked', 'PublicSpace', 6, 4, 2, '2026-04-27 09:57:03', NULL),
(67, 21, 'fixed', 'UtilityChambers', 1, 10, 0, '2026-04-27 10:54:31', NULL),
(68, 21, 'movable', 'PeopleSitting', 4, 3, 0, '2026-04-27 10:54:31', NULL),
(69, 21, 'movable', 'Hoardings', 1, 0, 1, '2026-04-27 10:54:31', NULL),
(70, 21, 'parked', 'OnStreetVending', 1, 0, 1, '2026-04-27 10:54:31', NULL),
(71, 21, 'parked', 'PublicSpace', 0, 1, 0, '2026-04-27 10:54:31', NULL),
(72, 22, 'fixed', 'ElectricalPanel', 0, 10, 0, '2026-04-27 12:01:04', NULL),
(73, 26, 'fixed', 'CCTV', 0, 1, 1, '2026-04-27 12:32:18', NULL),
(74, 26, 'movable', 'GarbageBins', 0, 1, 2, '2026-04-27 12:32:18', NULL),
(75, 26, 'movable', 'TrafficBarricade', 1, 1, 5, '2026-04-27 12:32:18', NULL),
(76, 27, 'fixed', 'Trees', 1, 2, 0, '2026-04-27 18:21:24', NULL),
(77, 27, 'fixed', 'Poles', 1, 1, 1, '2026-04-27 18:21:24', NULL),
(78, 27, 'fixed', 'SignBoard', 2, 1, 4, '2026-04-27 18:21:24', NULL),
(79, 27, 'movable', 'Hawkers', 1, 1, 3, '2026-04-27 18:21:24', NULL),
(80, 27, 'movable', 'GarbageBins', 3, 0, 1, '2026-04-27 18:21:24', NULL),
(81, 27, 'parked', 'ReligiousLandmark', 1, 2, 5, '2026-04-27 18:21:24', NULL),
(82, 27, 'parked', 'RestaurantEatery', 2, 3, 1, '2026-04-27 18:21:24', NULL),
(83, 28, 'fixed', 'Trees', 1, 1, 1, '2026-04-28 06:10:56', NULL),
(84, 28, 'movable', 'Hoardings', 1, 0, 1, '2026-04-28 06:10:56', NULL),
(85, 28, 'parked', 'PublicSpace', 0, 1, 0, '2026-04-28 06:10:56', NULL),
(115, 49, 'fixed', 'Poles', 3, 0, 0, '2026-04-28 15:20:29', 4),
(116, 49, 'fixed', 'CCTV', 24, 0, 0, '2026-04-28 15:20:29', 4),
(117, 50, 'fixed', 'Poles', 1, 1, 0, '2026-04-28 15:20:49', 5),
(118, 50, 'fixed', 'TrafficSignal', 0, 5, 3, '2026-04-28 15:20:49', 5);

-- --------------------------------------------------------

--
-- Table structure for table `segments`
--

DROP TABLE IF EXISTS `segments`;
CREATE TABLE `segments` (
  `id` int(11) NOT NULL,
  `road_name` varchar(255) DEFAULT NULL,
  `road_start` varchar(255) DEFAULT '',
  `road_end` varchar(255) DEFAULT '',
  `road_length` float DEFAULT 0,
  `road_gps_start` varchar(100) DEFAULT '',
  `road_gps_end` varchar(100) DEFAULT '',
  `road_method` varchar(20) DEFAULT 'auto',
  `road_segment_length` float DEFAULT 0,
  `start_distance` float DEFAULT 0,
  `end_distance` float DEFAULT 0,
  `start_label` varchar(255) DEFAULT NULL,
  `end_label` varchar(255) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `segments`
--

INSERT INTO `segments` (`id`, `road_name`, `road_start`, `road_end`, `road_length`, `road_gps_start`, `road_gps_end`, `road_method`, `road_segment_length`, `start_distance`, `end_distance`, `start_label`, `end_label`, `length`, `status`, `completed_at`) VALUES
(1, 'DP ROAD', 'z1', 'z2', 1500, '', '', 'auto', 500, 0, 500, 'z1', '500m from start', 500, 'pending', NULL),
(2, 'DP ROAD', 'z1', 'z2', 1500, '', '', 'auto', 500, 500, 1000, '500m from start', '1000m from start', 500, 'pending', NULL),
(3, 'DP ROAD', 'z1', 'z2', 1500, '', '', 'auto', 500, 1000, 1500, '1000m from start', 'z2', 500, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `segment_audits`
--

DROP TABLE IF EXISTS `segment_audits`;
CREATE TABLE `segment_audits` (
  `id` int(11) NOT NULL,
  `start_landmark` varchar(255) NOT NULL,
  `end_landmark` varchar(255) NOT NULL,
  `gps_start` varchar(100) NOT NULL,
  `gps_end` varchar(100) NOT NULL,
  `cycle_track_missing` enum('Yes','No') DEFAULT NULL,
  `missing_length` decimal(10,2) DEFAULT 0.00,
  `cyclist_use` enum('Yes','No') DEFAULT NULL,
  `better_surface` enum('Cycle Track','Road') DEFAULT NULL,
  `surface_material` enum('Interlock Blocks','Concrete','Asphalt') DEFAULT NULL,
  `people_walking` enum('Yes','No') DEFAULT NULL,
  `signage_count` int(11) DEFAULT 0,
  `shade` enum('Yes','No','Partial') DEFAULT NULL,
  `light_after_sunset` enum('Yes','No','Partial') DEFAULT NULL,
  `track_geometry` enum('Road Level','Footpath Level','Segregated from FPR','NA') DEFAULT NULL,
  `buffer_zone` enum('Segregated','Buffer Zone','None','NA') DEFAULT NULL,
  `segment_width` decimal(10,2) DEFAULT 0.00,
  `segment_length` decimal(10,2) DEFAULT 0.00,
  `comments` text DEFAULT NULL,
  `surface_issues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`surface_issues`)),
  `overhead_issues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`overhead_issues`)),
  `footpath_rating` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`footpath_rating`)),
  `footpath_score` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `segment_id` int(11) DEFAULT NULL,
  `surveyor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `segment_audits`
--

INSERT INTO `segment_audits` (`id`, `start_landmark`, `end_landmark`, `gps_start`, `gps_end`, `cycle_track_missing`, `missing_length`, `cyclist_use`, `better_surface`, `surface_material`, `people_walking`, `signage_count`, `shade`, `light_after_sunset`, `track_geometry`, `buffer_zone`, `segment_width`, `segment_length`, `comments`, `surface_issues`, `overhead_issues`, `footpath_rating`, `footpath_score`, `created_at`, `segment_id`, `surveyor_id`) VALUES
(1, 'dsgdsdg', 'sedg', 'sdg', '34', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 18:07:41', NULL, NULL),
(2, 'sdsdg', '2', '12', '34', 'Yes', 50.00, 'Yes', 'Cycle Track', 'Concrete', 'Yes', 4, 'No', 'No', 'Footpath Level', 'Segregated', 50.00, 20000.00, 'hi', '[\"broken\",\"water\",\"roots\",\"manholes\"]', '[\"overheadCables\"]', '[\"minWidth\",\"obstructionFree\",\"comfort\"]', 60, '2026-04-01 18:09:30', NULL, NULL),
(3, '1', '2', '12', '34', 'Yes', 70.00, 'Yes', 'Cycle Track', 'Concrete', 'Yes', 4, 'No', 'Partial', 'Footpath Level', 'Segregated', 70.00, 5000.00, 'hello', '[\"broken\",\"water\",\"roots\"]', '[\"branches\"]', '[\"disabledFriendly\",\"comfort\"]', 40, '2026-04-01 18:47:05', NULL, NULL),
(4, 'sdsdg', 'sedg', 'sdg', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 18:52:57', NULL, NULL),
(5, 'dsgdsdg', 'sedg', 'sdg', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 18:55:33', NULL, NULL),
(6, 'dsgdsdg', 'sedg', '12', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 18:56:22', NULL, NULL),
(7, 'sdsdg', 'sd', 'sdg', '34', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 18:58:22', NULL, NULL),
(8, 'sdsdg', 'sedg', 'sdg', '34', NULL, 0.00, NULL, NULL, NULL, NULL, 0, 'Yes', NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 19:08:14', NULL, NULL),
(9, 'dsgdsdg', 'sedg', 'sdg', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 19:19:12', NULL, NULL),
(10, 'sdsdg', '2', '12', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 19:20:07', NULL, NULL),
(11, 'sdsdg', '2', 'sdg', 'fggh', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-01 19:24:32', NULL, NULL),
(12, 'hfjk', '7y2i3u4h1', 'uayqg', 'syqf', 'Yes', 799.00, 'Yes', 'Road', 'Asphalt', 'No', 5, 'No', 'Yes', '', 'Buffer Zone', 648.00, 57.00, 'KWHEGLAHRGTLI', '[\"broken\",\"roots\",\"cables\"]', '[\"branches\"]', '[\"obstructionFree\",\"disabledFriendly\"]', 40, '2026-04-01 19:33:44', NULL, NULL),
(13, 'pune', 'hgfbjfg', '12', '34', 'Yes', 200.00, 'Yes', 'Road', 'Interlock Blocks', 'Yes', 0, 'Yes', 'No', 'Footpath Level', 'Buffer Zone', 30.00, 500.00, '', '[\"loose\",\"broken\",\"water\"]', '[\"overheadCables\"]', '[\"continuous\"]', 20, '2026-04-11 05:35:35', NULL, NULL),
(14, 'pune', 'hgfbjfg', '12', '34', 'Yes', 200.00, 'Yes', 'Road', 'Interlock Blocks', 'Yes', 0, 'Yes', 'No', 'Footpath Level', 'Buffer Zone', 30.00, 500.00, '', '[\"loose\",\"broken\",\"water\"]', '[\"overheadCables\"]', '[\"continuous\"]', 20, '2026-04-11 05:35:56', NULL, NULL),
(15, 'pune', 'hgfbjfg', '12', '34', 'Yes', 200.00, 'Yes', 'Road', 'Interlock Blocks', 'Yes', 0, 'Yes', 'No', 'Footpath Level', 'Buffer Zone', 30.00, 500.00, '', '[\"loose\",\"broken\",\"water\"]', '[\"overheadCables\"]', '[\"continuous\"]', 20, '2026-04-11 05:36:53', NULL, NULL),
(16, 'dsgdsdg', 'sedg', 'sdg', 'fggh', 'Yes', 20.00, 'Yes', 'Road', 'Interlock Blocks', 'Yes', 0, 'No', 'No', 'Footpath Level', 'Segregated', 23.00, 500.00, '', '[\"gravel\",\"loose\"]', '[\"overheadCables\"]', '[\"continuous\"]', 20, '2026-04-11 05:52:04', NULL, NULL),
(17, 'sdsdg', 'sedg', 'sdg', '34', 'Yes', 0.00, 'No', 'Road', 'Concrete', 'Yes', 1, 'Yes', 'No', '', 'Segregated', 6.00, 0.00, '', '[\"loose\"]', '[\"overheadCables\"]', '[\"obstructionFree\"]', 20, '2026-04-11 07:46:53', NULL, NULL),
(18, 'pune', 'sedg', '12', '34', 'Yes', 65.00, 'Yes', 'Road', 'Concrete', 'Yes', 2, 'Yes', 'No', 'Footpath Level', 'Buffer Zone', 43.00, 500.00, '', '[\"loose\",\"water\"]', '[\"overheadCables\"]', '[\"continuous\",\"disabledFriendly\"]', 40, '2026-04-11 08:02:13', NULL, NULL),
(19, 'dsgdsdg', 'sd', 'sdg', '34', 'No', 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 05:12:42', NULL, NULL),
(20, '1', '2', '11111', '22222', 'No', 0.00, NULL, 'Cycle Track', 'Concrete', 'Yes', 7, 'Partial', 'No', 'Road Level', 'Segregated', 20.00, 500.00, 'nikhil', '[\"loose\",\"broken\"]', '[\"overheadCables\"]', '[\"continuous\",\"obstructionFree\",\"disabledFriendly\"]', 60, '2026-04-27 09:57:03', NULL, NULL),
(21, 'handewadi', 'katraj', '12.226', '13.226', 'Yes', 4.00, 'Yes', 'Cycle Track', 'Interlock Blocks', 'Yes', 3, 'Yes', 'Yes', 'Footpath Level', 'Buffer Zone', 55.00, 500.00, 'hello there!', '[\"loose\",\"broken\",\"roots\"]', '[\"overheadCables\",\"branches\"]', '[\"minWidth\",\"obstructionFree\",\"disabledFriendly\",\"comfort\"]', 80, '2026-04-27 10:54:31', NULL, NULL),
(22, 'nikhil', 'wagh', '1234', '4321', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 12:01:04', NULL, NULL),
(23, 'abc', 'def', '12', '6432', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 12:11:05', NULL, NULL),
(24, '12345', '56789', 'ddgd', 'sdbgsthdfb', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 12:15:55', NULL, NULL),
(25, 'markings ', 'signage', '12', '332', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 12:24:33', NULL, NULL),
(26, 'total', 'obstructions', '2234233455335', '343242', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-27 12:32:18', NULL, NULL),
(27, 'nikhil', 'abhijit', '34567', '645352', 'Yes', 50.00, 'Yes', 'Cycle Track', 'Concrete', 'Yes', 10, 'Yes', 'Yes', 'Road Level', 'Segregated', 50.00, 300.00, 'hello world', '[\"loose\",\"roots\",\"manholes\",\"cables\"]', '[\"overheadCables\",\"branches\"]', '[\"minWidth\",\"obstructionFree\",\"comfort\"]', 60, '2026-04-27 18:21:24', NULL, NULL),
(28, 'miyhilesh', 'nikhil', '2143254', '634524', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-28 06:10:56', NULL, NULL),
(49, 'fdghfjg', 'sdhfjg', 'sdhf', 'sdf', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-28 15:20:29', 4, NULL),
(50, 'wrhj', 'yk', 'fg', 'ytur', NULL, 0.00, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-04-28 15:20:49', 5, NULL),
(69, 'nikhil', 'sd', '12', 'syqf', NULL, 0.00, NULL, NULL, 'Asphalt', 'No', 0, 'No', 'No', NULL, 'None', 0.00, 0.00, '', '[]', '[]', '[]', 0, '2026-05-01 18:32:19', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `organisation` varchar(200) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('surveyor','admin') DEFAULT 'surveyor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `organisation`, `password`, `role`, `created_at`, `gender`, `age`) VALUES
(1, 'nikhil', 'nikhil@gmail.com', '1234567890', 'KSE', '$2y$10$BNgJUl019/y6fLNZQ4Pbo.Y3O9brsKQRAfJtExWT3Az78WSK9z4YS', 'surveyor', '2026-05-02 10:16:20', NULL, NULL),
(2, 'Mithileish Waghmare', 'mithileish@gmail.com', '987654321', 'waghs creation', '$2y$10$VjDRTwK4b4Chd7NwUOaNdega37lksr0aRfXclK3yIVo2LKWlj1J4i', 'surveyor', '2026-05-02 10:48:29', NULL, NULL),
(3, 'Test Surveyor', 'test@cycleaudit.in', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surveyor', '2026-05-03 21:12:09', 'Male', 25),
(4, 'Harsh Patil', 'harsh@gmail.com', '6678900998', 'KSE', '$2y$10$N2raQXdKSrlVOe/1q.W.OulrZq9FcFsFFDjqeffIh1UHXvOSrGDxK', 'surveyor', '2026-05-05 09:22:10', 'Male', 21);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `intersections`
--
ALTER TABLE `intersections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit` (`audit_id`);

--
-- Indexes for table `obstructions`
--
ALTER TABLE `obstructions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit` (`audit_id`);

--
-- Indexes for table `segments`
--
ALTER TABLE `segments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_road_name` (`road_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `segment_audits`
--
ALTER TABLE `segment_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gps` (`gps_start`,`gps_end`),
  ADD KEY `idx_segment` (`segment_id`),
  ADD KEY `idx_surveyor` (`surveyor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `intersections`
--
ALTER TABLE `intersections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `obstructions`
--
ALTER TABLE `obstructions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `segment_audits`
--
ALTER TABLE `segment_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `intersections`
--
ALTER TABLE `intersections`
  ADD CONSTRAINT `fk_int` FOREIGN KEY (`audit_id`) REFERENCES `segment_audits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `intersections_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `segment_audits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `obstructions`
--
ALTER TABLE `obstructions`
  ADD CONSTRAINT `fk_obs` FOREIGN KEY (`audit_id`) REFERENCES `segment_audits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `obstructions_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `segment_audits` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
