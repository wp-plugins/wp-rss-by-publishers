ALTER TABLE `wsys_feed`
CHANGE COLUMN `url` `url` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL ,
ADD COLUMN `plugin` TINYINT(1) NULL DEFAULT 0 AFTER `url`;
ALTER TABLE `wsys_feed`
ADD INDEX `wsys_feed_plugin_idx` (`plugin` ASC);
