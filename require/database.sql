CREATE TABLE IF NOT EXISTS `data` (
  `board` VARCHAR(10) NOT NULL,
  `post` INT UNSIGNED NOT NULL,
  `thread` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `color` INT UNSIGNED DEFAULT NULL,
  `hue` INT UNSIGNED DEFAULT NULL,
  `trip` VARCHAR(15) DEFAULT NULL,
  `subject` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `ip` VARCHAR(32) NOT NULL,
  `uid` INT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL,
  INDEX (`board`),
  INDEX (`thread`),
  INDEX (`ip`),
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `board_data` (
  `board` VARCHAR(6) NOT NULL,
  `post` INT UNSIGNED DEFAULT 0,
  `time` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`board`)
) ENGINE=InnoDB;

CREATE EVENT IF NOT EXISTS `Cleanup`
  ON SCHEDULE
    EVERY 1 DAY
  DO
    DELETE FROM `data`
    WHERE time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 WEEK));