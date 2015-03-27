DROP TABLE IF EXISTS `bp_rule`;
DROP TABLE IF EXISTS `bp_feed`;
DROP TABLE IF EXISTS `bp_publisher`;

CREATE TABLE IF NOT EXISTS `wsys_publisher` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `api_key` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_3` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wsys_publisher_name_ukey` (`name`),
  UNIQUE KEY `wsys_publisher_url_ukey` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wsys_feed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `publisher_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `processing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_fetch` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wsys_feed_url_ukey` (`url`),
  KEY `wsys_feed_publisher_fk_idx` (`publisher_id`),
  CONSTRAINT `feed_publisher_fk` FOREIGN KEY (`publisher_id`) REFERENCES `wsys_publisher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wsys_feed_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feed_id` int(10) unsigned NOT NULL,
  `fetch_time` timestamp NULL DEFAULT NULL,
  `duration` decimal(8,2) DEFAULT NULL,
  `posts_total` int(11) DEFAULT NULL,
  `posts_no_image` int(11) DEFAULT NULL,
  `posts_inserted` int(11) DEFAULT NULL,
  `posts_updated` int(11) DEFAULT NULL,
  `posts_unchanged` int(11) DEFAULT NULL,
  `sandbox` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `fetch_type` tinyint(1) DEFAULT NULL COMMENT '0 - manual, 1 - cron',
  `messages` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `wsys_feed_log_feed_fk_idx` (`feed_id`),
  CONSTRAINT `wsys_feed_log_feed_fk` FOREIGN KEY (`feed_id`) REFERENCES `wsys_feed` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wsys_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `publisher_id` int(10) unsigned DEFAULT NULL,
  `feed_id` int(10) unsigned DEFAULT NULL,
  `categories` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wsys_rule_publisher_fk_idx` (`publisher_id`),
  KEY `wsys_rule_feed_fk_idx` (`feed_id`),
  CONSTRAINT `wsys_rule_feed_fk` FOREIGN KEY (`feed_id`) REFERENCES `wsys_feed` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `wsys_rule_publisher_fk` FOREIGN KEY (`publisher_id`) REFERENCES `wsys_publisher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
