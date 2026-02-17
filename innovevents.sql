-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : db
-- Généré le : dim. 15 fév. 2026 à 14:02
-- Version du serveur : 8.0.45
-- Version de PHP : 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `innovevents`
--

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `app_settings`
--

INSERT INTO `app_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'quote_success_message', 'Merci pour votre demande. Chloé vous recontactera dans les plus brefs délais pour discuter de votre projet.', '2026-02-14 00:52:48');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `company_name`, `phone`, `address`, `created_at`) VALUES
(13, 22, 'TEST DASHBOARD', '00 00 00 00 00', 'Test22', '2026-02-09 18:03:12'),
(14, 23, 'Test photo prospect', '00 00 00 00 00', 'Paris', '2026-02-10 17:22:54'),
(18, 30, 'Test modif', '00 00 00 00 00', 'Paris', '2026-02-13 23:34:05'),
(19, 31, 'rgegegergerg', '00 00 00 00 00', 'Paris', '2026-02-14 01:34:28'),
(20, 32, 'test modiffff', '00 00 00 00 00', 'Toulouse', '2026-02-14 01:34:52'),
(21, 33, 'rgg(&#039;rtgrt', '00 00 00 00 00', 'Paris', '2026-02-14 01:47:28'),
(23, 35, 'rthh-rh', '00 00 00 00 00', 'Paris', '2026-02-14 13:11:45'),
(24, 36, 'thhrh', '00 00 00 00 00', 'Paris', '2026-02-14 13:51:19');

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `client_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `attendees_count` int DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `theme` varchar(100) DEFAULT NULL,
  `status` enum('draft','client_review','accepted','in_progress','completed','cancelled') DEFAULT 'draft',
  `is_visible` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `events`
--

INSERT INTO `events` (`id`, `client_id`, `name`, `description`, `start_date`, `end_date`, `location`, `attendees_count`, `budget`, `image_path`, `event_type`, `theme`, `status`, `is_visible`, `created_at`, `updated_at`) VALUES
(22, 13, 'Test employé', '', '2029-10-20 10:00:00', '2029-11-10 11:00:00', 'Toulouse', 0, 0.00, NULL, 'Conférence', 'High-Tech', 'completed', 0, '2026-02-10 20:42:32', '2026-02-11 21:57:11'),
(23, 13, 'Test app', '', '2029-02-10 10:00:00', '2029-02-10 11:00:00', 'Toulouse', 0, 0.00, '/uploads/events/event_23_1770847073.jpg', 'Séminaire', 'Tropical', 'accepted', 1, '2026-02-11 21:57:53', '2026-02-14 12:31:15'),
(24, 19, 'conference - rgegegergerg greegerg geergege', NULL, '2029-01-10 09:00:00', '2029-01-10 18:00:00', 'Paris', NULL, NULL, NULL, 'conference', 'Élégant', 'draft', 0, '2026-02-14 01:34:34', '2026-02-14 01:34:34'),
(26, 14, 'conference - Test Entreprise Jean Dupont', '', '2029-12-20 09:00:00', '2029-12-20 18:00:00', '123', 0, 0.00, NULL, 'conference', 'Élégant', 'in_progress', 1, '2026-02-14 01:38:15', '2026-02-14 15:27:37'),
(27, 21, 'seminaire - rgg(&#039;rtgrt hsrthsrh trhrehsrt', NULL, '2029-01-20 09:00:00', '2029-01-20 18:00:00', 'Paris', NULL, NULL, '/uploads/events/event_27_1771033746.png', 'Soirée d\'entreprise', 'Élégant', 'draft', 0, '2026-02-14 01:49:05', '2026-02-14 01:49:06'),
(29, 24, 'soiree - thhrh trhsrth tdhtrshr', NULL, '2028-02-10 09:00:00', '2028-02-10 18:00:00', 'Paris', NULL, NULL, NULL, 'soiree', 'Élégant', 'draft', 0, '2026-02-14 13:52:19', '2026-02-14 13:52:19'),
(30, 13, 'Test mobile', NULL, '2027-02-10 10:00:00', '2027-02-10 11:00:00', 'Test', NULL, NULL, NULL, 'Autre', 'Élégant', 'accepted', 0, '2026-02-14 16:59:35', '2026-02-14 16:59:35');

-- --------------------------------------------------------

--
-- Structure de la table `event_types`
--

CREATE TABLE `event_types` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `event_types`
--

INSERT INTO `event_types` (`id`, `name`, `description`) VALUES
(1, 'Séminaire', 'Réunion de travail ou de formation professionnelle'),
(2, 'Conférence', 'Présentation devant un public'),
(3, 'Soirée d\'entreprise', 'Événement festif pour les collaborateurs'),
(4, 'Team Building', 'Activités de cohésion d\'équipe'),
(5, 'Autre', 'Autre type d\'événement');

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int NOT NULL,
  `event_id` int DEFAULT NULL,
  `author_id` int NOT NULL,
  `content` text NOT NULL,
  `is_global` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO `notes` (`id`, `event_id`, `author_id`, `content`, `is_global`, `created_at`) VALUES
(1, NULL, 1, 'Test', 0, '2026-02-06 16:11:47'),
(2, NULL, 1, 'test', 0, '2026-02-06 16:41:03'),
(4, NULL, 21, 'Test', 0, '2026-02-09 15:14:16'),
(5, NULL, 21, 'Test 2', 0, '2026-02-09 16:57:41'),
(6, 23, 1, 'Note test E2E Cypress', 0, '2026-02-12 15:35:54'),
(7, 23, 1, 'Note test E2E Cypress', 0, '2026-02-12 15:44:40'),
(8, 23, 1, 'Note test E2E Cypress', 0, '2026-02-12 16:17:30'),
(9, 26, 1, 'Test', 0, '2026-02-14 14:58:18'),
(15, 29, 1, 'Test', 0, '2026-02-14 17:05:08');

-- --------------------------------------------------------

--
-- Structure de la table `prospects`
--

CREATE TABLE `prospects` (
  `id` int NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `planned_date` date DEFAULT NULL,
  `estimated_participants` int DEFAULT NULL,
  `needs_description` text,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('to_contact','qualification','failed','converted') DEFAULT 'to_contact',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `prospects`
--

INSERT INTO `prospects` (`id`, `company_name`, `last_name`, `first_name`, `email`, `phone`, `location`, `event_type`, `planned_date`, `estimated_participants`, `needs_description`, `image_path`, `status`, `created_at`) VALUES
(1, 'Test devis', 'Test 8', 'test', 'test@test.fr', '00 00 00 00 00', '20', 'conference', '2029-02-20', 20, 'Nous souhaitons organiser une conférence pour notre équipe de 20 personnes.', NULL, 'converted', '2026-02-06 18:01:09'),
(2, 'Test8', 'Test 8', 'test', 'test@test.fr', '00 00 00 00 00', 'Paris', 'soiree', '2029-09-20', 20, 'Nous souhaitons organiser une conférence pour notre équipe de 20 personnes.', NULL, 'failed', '2026-02-06 18:02:00'),
(3, 'Test Entreprise', 'Dupont', 'Jean', 'test@test.fr', '0123456789', '123', 'conference', '2029-12-20', 20, 'Ceci est une description de test avec plus de 20 caractères', NULL, 'converted', '2026-02-06 18:13:21'),
(4, 'Test8', 'Test 8', 'test', 'test@test.fr', '00 00 00 00 00', '4', 'conference', '2029-05-20', 20, 'fffffffffffffffffffffffffffff', NULL, 'failed', '2026-02-06 18:15:07'),
(5, 'Test photo prospect', 'Test', 'Test', 'test@test.fr', '00 00 00 00 00', 'Paris', 'conference', '2028-10-10', 10, 'dddddfdfdfdffffffffffffffff', '/uploads/prospects/prospect_5_1770420094.jpg', 'converted', '2026-02-06 23:21:34'),
(6, 'Entreprise test 123456', 'test', 'Test', 'testentreprise@test.fr', '00 00 00 00 00', 'Paris', 'conference', '2029-02-10', 50, 'dddddddddddddddddddddddd', '/uploads/prospects/prospect_6_1770642372.png', 'converted', '2026-02-09 13:06:12'),
(7, 'TEST DASHBOARD', 'Test', 'Test client dashboard', 'test1518@test.fr', '00 00 00 00 00', 'Toulouse', 'seminaire', '2026-05-10', 20, 'J&#039;aimerai faire ce séminaire pour mon entreprise', NULL, 'failed', '2026-02-09 19:23:06'),
(8, 'TEST DASHBOARD', 'Test', 'Test', 'test1518@test.fr', '00 00 00 00 00', 'Toulouse', 'conference', '2029-02-20', 50, 'test prospect 12eg1rg15rgergegrgegt', '/uploads/prospects/prospect_8_1770753079.jpg', 'converted', '2026-02-10 19:51:19'),
(9, 'TEST DASHBOARD', 'Test', 'test', 'test1518@test.fr', '00 00 00 00 00', 'Toulouse', 'seminaire', '2029-01-10', 50, 'trghgrthrthrthrthrhrh', '/uploads/prospects/prospect_9_1770754797.jpg', 'converted', '2026-02-10 20:19:57'),
(10, 'TEST DASHBOARD', 'Test', 'test', 'test1518@test.fr', '00 00 00 00 00', 'Paris', 'soiree', '2029-02-10', 50, 'fffffffffffffffffffffffffffffffffffffffffffffffff', '/uploads/prospects/prospect_10_1770755474.jpg', 'converted', '2026-02-10 20:31:14'),
(11, 'TEST DASHBOARD', 'Test', 'Test', 'test1518@test.fr', '01 00 00 00 00', 'Paris', 'soiree', '2029-02-10', 50, 'dddddddddddddddddddddddddddddddddddddddddddddd', NULL, 'converted', '2026-02-10 20:33:17'),
(12, 'TEST DASHBOARD', 'Test', 'test', 'test1518@test.fr', '00 00 00 00 00', 'Paris', 'conference', '2029-09-20', 20, 'ssssssssssssssssssssssssssssssss', NULL, 'converted', '2026-02-10 20:41:12'),
(13, 'Test modif', 'Test', 'Test', 'testmodif@test.fr', '00 00 00 00 00', 'Paris', 'soiree', '2029-02-10', 20, 'refffffffffffffffffffffffff', '/uploads/prospects/prospect_13_1771025602.png', 'converted', '2026-02-13 23:33:22'),
(17, 'rgg(&#039;rtgrt', 'trhrehsrt', 'hsrthsrh', 'srthsrh@tgsgestg.fr', '00 00 00 00 00', 'Paris', 'seminaire', '2029-01-20', 20, 'gbfbnrshthrtehsrhrhsh', '/uploads/prospects/prospect_17_1771033636.png', 'converted', '2026-02-14 01:47:16'),
(18, 'hg,h,v', 'h,cg,g,c', 'gh', 'gfjfj@grgg.fr', '00 00 00 00 00', 'Paris', 'autre', '2029-02-10', 20, 'frfffffffffffffffffffff', NULL, 'converted', '2026-02-14 02:15:05'),
(19, 'rthh-rh', 'reshrshrts', 'rhtsrtr', 'strehrsh@tsthr.fr', '00 00 00 00 00', 'Paris', 'conference', '2028-02-10', 20, 'rgeergrgrgrgrgrgrgrgrgrgrgrgrgrgrgrg', NULL, 'converted', '2026-02-14 13:02:47'),
(20, 'thhrh', 'tdhtrshr', 'trhsrth', 'rtsrth@test.fr', '00 00 00 00 00', 'Paris', 'soiree', '2028-02-10', 20, 'rggggggggggggggggggggggggg', NULL, 'converted', '2026-02-14 13:51:01');

-- --------------------------------------------------------

--
-- Structure de la table `quotes`
--

CREATE TABLE `quotes` (
  `id` int NOT NULL,
  `event_id` int DEFAULT NULL,
  `total_ht` decimal(10,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT '20.00',
  `total_ttc` decimal(10,2) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `status` enum('pending','modification','accepted','refused') DEFAULT 'pending',
  `modification_reason` text,
  `counter_proposal` text,
  `counter_proposed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `quotes`
--

INSERT INTO `quotes` (`id`, `event_id`, `total_ht`, `tax_rate`, `total_ttc`, `issue_date`, `status`, `modification_reason`, `counter_proposal`, `counter_proposed_at`, `created_at`, `updated_at`) VALUES
(15, 23, 20.00, 20.00, 24.00, '2026-02-14', 'pending', 'test', 'Est 2', '2026-02-14 14:32:02', '2026-02-14 14:31:31', '2026-02-14 14:32:32');

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `client_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`id`, `event_id`, `client_id`, `rating`, `comment`, `status`, `reviewed_by`, `created_at`) VALUES
(7, 22, 13, 3, 'Test', 'approved', 1, '2026-02-12 17:18:20');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `quote_id` int DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `unit_price_ht` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `quote_id`, `label`, `description`, `unit_price_ht`, `created_at`) VALUES
(15, 15, 'thrth', 'tehsrth', 20.00, '2026-02-14 14:31:31');

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `status` enum('todo','in_progress','done') DEFAULT 'todo',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tasks`
--

INSERT INTO `tasks` (`id`, `event_id`, `assigned_to`, `title`, `description`, `status`, `due_date`, `created_at`, `updated_at`) VALUES
(5, 22, 21, 'Test tache 1', 'Test', 'done', '2027-02-10', '2026-02-11 20:20:29', '2026-02-11 20:23:11'),
(6, 22, 21, 'Test', 'Test', 'done', '2029-02-10', '2026-02-12 17:21:08', '2026-02-12 17:29:30'),
(7, 26, 21, 'Test', 'Test', 'todo', '2028-02-10', '2026-02-14 15:43:50', '2026-02-14 15:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `themes`
--

CREATE TABLE `themes` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `themes`
--

INSERT INTO `themes` (`id`, `name`) VALUES
(1, 'Élégant'),
(2, 'Tropical'),
(3, 'Rétro'),
(4, 'High-Tech'),
(5, 'Nature'),
(6, 'Industriel');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','client') DEFAULT 'client',
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `last_name`, `first_name`, `username`, `email`, `password`, `role`, `is_active`, `email_verified`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `must_change_password`, `created_at`, `updated_at`) VALUES
(1, 'Dubois', 'Chloé', 'chloe_admin', 'chloe@innovevents.com', '$2y$10$cCr5ripU738CnCeNHnYJx./qH.XN7zj/8Y/iksUvmKOFVPaugoPha', 'admin', 1, 1, NULL, NULL, NULL, 0, '2026-01-23 15:21:37', '2026-02-14 15:17:15'),
(14, 'Test 8', 'test', 'Test register', 'test7@test.fr', '$2y$10$khI.0DEia8TFOsaimedDceyvs.YiT89boppICI2snb0d7XjjHM4jK', 'client', 1, 0, NULL, NULL, NULL, 0, '2026-02-07 20:46:00', '2026-02-07 20:46:00'),
(16, 'Test register', 'Test', 'test4549848', 'testregister@test.fr', '$2y$12$lrDDE2w0BtQ4bVuN/k6XKuBPeZwn7QMvrm1xWeJ9tYnD52ICFlBEm', 'client', 1, 0, '2ccf3f97a250198f58c4f5b345226d2fcfb01304702be7ba0b3170478aa06d2f', NULL, NULL, 0, '2026-02-07 22:39:08', '2026-02-07 22:39:08'),
(17, 'Test register bb', 'Test', 'testrzegreg@test.fr', 'testgregg@test.fr', '$2y$10$D.5xitrlWjJKkQxgs0voTeE3H71RTlydIvm7Z8RM3uLHBFrNRM76u', 'client', 1, 0, NULL, NULL, NULL, 0, '2026-02-07 22:45:19', '2026-02-07 22:45:19'),
(20, 'Test register 123456', 'Test', 'gehgh@test.fr', 'gehgh@test.fr', '$2y$10$BKcn5JBMEjOPxOd3D339gu4A/LvSPSiQrRuHhnAY5VrfeOfO.NDHO', 'client', 1, 0, NULL, NULL, NULL, 0, '2026-02-09 14:24:36', '2026-02-09 14:24:36'),
(21, 'Martin', 'Alexandre', 'alexandremartin', 'alexandre@innovevents.com', '$2y$10$rrxcapooJly4YLgs8cg8iOXQwOQ3Oizoc1X4bVVYUF.0BsBAvvsiO', 'employee', 1, 1, NULL, NULL, NULL, 0, '2026-02-09 15:05:10', '2026-02-13 20:26:13'),
(22, 'Test', 'Test client dashboard', NULL, 'test1518@test.fr', '$2y$10$21huHMQPHCYRXyTd8TEp6OEZDajC8jyy5XE1K2kfo0CZkMXAm3jdq', 'client', 1, 1, NULL, NULL, NULL, 0, '2026-02-09 18:03:12', '2026-02-13 20:25:55'),
(23, 'Test', 'Test', NULL, 'test@test.fr', '$2y$10$AQNzpgegL/2snH.kHZzo6ulJlVFNh.tHDqRyxPWgc/v2s0iyy2Z.y', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-10 17:22:54', '2026-02-13 21:37:29'),
(30, 'Test', 'Test', NULL, 'testmodif@test.fr', '$2y$10$l/HQTjLMW0TnY6LkBrR8qOADtEjJbuSWt9.WCubYU2wejAnfCCKgG', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-13 23:34:05', '2026-02-14 01:39:20'),
(31, 'geergege', 'greegerg', NULL, 'rgegerge@egergerg.fr', '$2y$10$DeNPSreVPsG/5bPrFkS1FevNPUBXHbUY/OMnrfLMqOFNIW.aWbqMG', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-14 01:34:28', '2026-02-14 01:34:28'),
(32, 'Test', 'test', NULL, 'tesmodif2@test.fr', '$2y$10$zbMvgnXJE1yNku8kFiUxou.5hZUv.zLhS5SNdaSDREKN17a8leNY2', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-14 01:34:52', '2026-02-14 01:34:52'),
(33, 'trhrehsrt', 'hsrthsrh', NULL, 'srthsrh@tgsgestg.fr', '$2y$10$utEiHHNW5vh7fDkL5QWH0eYjYvCt0aYIAspPJPh.e9hDFMWVb9.Iu', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-14 01:47:28', '2026-02-14 01:47:28'),
(35, 'reshrshrts', 'rhtsrtr', NULL, 'strehrsh@tsthr.fr', '$2y$10$/d6GkWpEZ2U0bQzA5HjTIe0e.P3DohJYjK7KdQnODKAu3R2JonC82', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-14 13:11:45', '2026-02-14 13:11:45'),
(36, 'tdhtrshr', 'trhsrth', NULL, 'rtsrth@test.fr', '$2y$10$9M1SI6axmEj7YdDyIwH5cezi2NwfRtoUKjXNBGI3x2j0vJvbKPuvu', 'client', 1, 0, NULL, NULL, NULL, 1, '2026-02-14 13:51:19', '2026-02-14 13:51:19');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_event_client` (`client_id`);

--
-- Index pour la table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_note_event` (`event_id`),
  ADD KEY `fk_note_author` (`author_id`);

--
-- Index pour la table `prospects`
--
ALTER TABLE `prospects`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quote_event` (`event_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_event` (`event_id`),
  ADD KEY `fk_review_client` (`client_id`),
  ADD KEY `fk_review_reviewer` (`reviewed_by`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_service_quote` (`quote_id`);

--
-- Index pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_task_event` (`event_id`),
  ADD KEY `fk_task_user` (`assigned_to`);

--
-- Index pour la table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `prospects`
--
ALTER TABLE `prospects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_client_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_event_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_note_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_note_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `fk_quote_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_service_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_task_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
