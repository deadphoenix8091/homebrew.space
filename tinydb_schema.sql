-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 13. Dez 2019 um 09:24
-- Server-Version: 10.1.38-MariaDB-0+deb9u1
-- PHP-Version: 7.0.33-0+deb9u6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `homebrewdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `app`
--

CREATE TABLE `app` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `author` varchar(256) NOT NULL,
  `github_url` varchar(256) NOT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_release_scan_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `app_categories`
--

CREATE TABLE `app_categories` (
  `app_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `app_releases`
--

CREATE TABLE `app_releases` (
  `id` int(11) NOT NULL,
  `content_type` int(1) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `download_url` varchar(512) NOT NULL,
  `tag_name` varchar(256) NOT NULL,
  `prerelease` int(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `author` varchar(255) NOT NULL DEFAULT '',
  `titleid` varchar(255) NOT NULL DEFAULT '',
  `size` int(11) NOT NULL DEFAULT '0',
  `app_id` int(11) NOT NULL,
  `qr_code` blob NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `pos` int(11) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `app`
--
ALTER TABLE `app`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indizes für die Tabelle `app_categories`
--
ALTER TABLE `app_categories`
  ADD KEY `category_id` (`category_id`),
  ADD KEY `app_id` (`app_id`);

--
-- Indizes für die Tabelle `app_releases`
--
ALTER TABLE `app_releases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `app_id` (`app_id`);
ALTER TABLE `app_releases` ADD FULLTEXT KEY `name_fulltext` (`name`,`description`,`author`,`titleid`);

--
-- Indizes für die Tabelle `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `app`
--
ALTER TABLE `app`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;
--
-- AUTO_INCREMENT für Tabelle `app_releases`
--
ALTER TABLE `app_releases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22075141;
--
-- AUTO_INCREMENT für Tabelle `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `app_categories`
--
ALTER TABLE `app_categories`
  ADD CONSTRAINT `app_categories_ibfk_1` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`),
  ADD CONSTRAINT `app_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints der Tabelle `app_releases`
--
ALTER TABLE `app_releases`
  ADD CONSTRAINT `app_releases_ibfk_1` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
