CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `data` MEDIUMBLOB NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB;
