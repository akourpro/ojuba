-- phpMyAdmin SQL Dump

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ojuba`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'editor',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `totp_backup_codes` text DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `text` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `code` text DEFAULT NULL,
  `position` varchar(50) NOT NULL DEFAULT 'home_top',
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `description_en` longtext DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `tags_en` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address_en` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `map` text DEFAULT NULL,
  `working_hours` varchar(255) DEFAULT NULL,
  `working_hours_en` varchar(255) DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issuer_en` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contact` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `ip` varchar(45) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `list_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template` varchar(255) DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `sent_count` int(11) NOT NULL DEFAULT 0,
  `total_count` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_campaign_lists` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_campaign_logs` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `error` text DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_campaign_recipients` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `error` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_lists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_list_contacts` (
  `id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `data` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `ip` varchar(45) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `question` varchar(255) DEFAULT NULL,
  `question_en` varchar(255) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `answer_en` text DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `feed_imported_items` (
  `id` int(11) NOT NULL,
  `source_id` int(11) NOT NULL,
  `guid` varchar(500) NOT NULL,
  `blog_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `feed_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `feed_url` varchar(500) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `replacements` text DEFAULT NULL,
  `auto_publish` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_fetched_at` datetime DEFAULT NULL,
  `last_status` varchar(255) DEFAULT NULL,
  `imported_count` int(11) NOT NULL DEFAULT 0,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `home_sections` (
  `id` int(11) NOT NULL,
  `section_key` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ordering` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `sys` varchar(255) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `price` varchar(50) DEFAULT NULL,
  `currency` varchar(20) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `period_en` varchar(50) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `features_en` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing_compare_features` (
  `id` int(11) NOT NULL,
  `feature` varchar(255) DEFAULT NULL,
  `feature_en` varchar(255) DEFAULT NULL,
  `val1` varchar(150) DEFAULT NULL,
  `val1_en` varchar(150) DEFAULT NULL,
  `val2` varchar(150) DEFAULT NULL,
  `val2_en` varchar(150) DEFAULT NULL,
  `val3` varchar(150) DEFAULT NULL,
  `val3_en` varchar(150) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `ordering` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing_compare_settings` (
  `id` int(11) NOT NULL,
  `eyebrow` varchar(255) DEFAULT NULL,
  `eyebrow_en` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `col1` varchar(100) DEFAULT NULL,
  `col1_en` varchar(100) DEFAULT NULL,
  `col2` varchar(100) DEFAULT NULL,
  `col2_en` varchar(100) DEFAULT NULL,
  `col3` varchar(100) DEFAULT NULL,
  `col3_en` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing_compare_values` (
  `id` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `value` varchar(150) DEFAULT NULL,
  `value_en` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing_faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(500) DEFAULT NULL,
  `question_en` varchar(500) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `answer_en` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `ordering` int(11) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pricing_page_settings` (
  `id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_description_en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `search_logs` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(45) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `description_en` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sport_matches` (
  `id` int(11) NOT NULL,
  `competition` varchar(190) DEFAULT NULL,
  `team_home` varchar(190) NOT NULL,
  `team_home_logo` varchar(255) DEFAULT NULL,
  `team_away` varchar(190) NOT NULL,
  `team_away_logo` varchar(255) DEFAULT NULL,
  `match_date` datetime NOT NULL,
  `venue` varchar(190) DEFAULT NULL,
  `score_home` int(11) DEFAULT NULL,
  `score_away` int(11) DEFAULT NULL,
  `match_status` varchar(20) NOT NULL DEFAULT 'upcoming',
  `broadcast_channel` varchar(190) DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sport_standings` (
  `id` int(11) NOT NULL,
  `team_name` varchar(190) NOT NULL,
  `team_logo` varchar(255) DEFAULT NULL,
  `played` int(11) NOT NULL DEFAULT 0,
  `won` int(11) NOT NULL DEFAULT 0,
  `drawn` int(11) NOT NULL DEFAULT 0,
  `lost` int(11) NOT NULL DEFAULT 0,
  `goals_for` int(11) NOT NULL DEFAULT 0,
  `goals_against` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 0,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sport_videos` (
  `id` int(11) NOT NULL,
  `title` varchar(190) NOT NULL,
  `youtube_url` varchar(500) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats` (
  `id` int(11) NOT NULL,
  `number` varchar(50) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `label_en` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `team` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `position_en` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `bio_en` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `snapchat` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `position_en` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `content_en` text DEFAULT NULL,
  `rating` int(11) NOT NULL DEFAULT 5,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position_status` (`position`,`status`);

ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `list_id` (`list_id`);

ALTER TABLE `email_campaign_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_campaign_list` (`campaign_id`,`list_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `list_id` (`list_id`);

ALTER TABLE `email_campaign_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`);

ALTER TABLE `email_campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_campaign_email` (`campaign_id`,`email`),
  ADD KEY `campaign_id_status` (`campaign_id`,`status`),
  ADD KEY `list_id` (`list_id`),
  ADD KEY `contact_id` (`contact_id`);

ALTER TABLE `email_lists`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `email_list_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `list_id` (`list_id`),
  ADD KEY `ip` (`ip`);

ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `feed_imported_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source_guid` (`source_id`,`guid`(191)),
  ADD KEY `blog_id` (`blog_id`);

ALTER TABLE `feed_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

ALTER TABLE `home_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_key` (`section_key`);

ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pricing`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pricing_compare_features`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pricing_compare_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pricing_compare_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `feature_plan` (`feature_id`,`plan_id`);

ALTER TABLE `pricing_faqs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pricing_page_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `search_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sport_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `match_status` (`match_status`),
  ADD KEY `match_date` (`match_date`);

ALTER TABLE `sport_standings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sport_videos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `stats`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `team`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_campaign_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_campaign_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_campaign_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_list_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `feed_imported_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `feed_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `home_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing_compare_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing_compare_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing_compare_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pricing_page_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `search_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sport_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sport_standings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sport_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
