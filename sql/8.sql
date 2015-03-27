ALTER TABLE `wsys_publisher`
ADD COLUMN `author_id` BIGINT(20) UNSIGNED NULL AFTER `created_at`,
ADD INDEX `wsys_publisher_author_key` (`author_id` ASC);
update `wsys_publisher` set `author_id`=(select `user_id` from `wp_usermeta` where `meta_key`='publisher_id' and `meta_value`=`wsys_publisher`.`id` limit 1) where (select count(*) from `wp_usermeta` where `meta_key`='publisher_id' and `meta_value`=`wsys_publisher`.`id`)>0;
