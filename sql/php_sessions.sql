CREATE TABLE IF NOT EXISTS `php_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `data` LONGBLOB NOT NULL,
  `timestamp` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

