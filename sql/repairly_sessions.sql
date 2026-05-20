CREATE TABLE IF NOT EXISTS `repairly_sessions` (
  `session_id` VARCHAR(128) NOT NULL PRIMARY KEY,
  `session_data` MEDIUMBLOB NOT NULL,
  `session_expiry` INT UNSIGNED NOT NULL,
  INDEX `idx_expiry` (`session_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

