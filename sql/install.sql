CREATE DATABASE IF NOT EXISTS throa;

DROP TABLE IF EXISTS `fls_queue`;
CREATE TABLE `fls_queue` (
  `id`      bigint(20)        NOT NULL AUTO_INCREMENT,
  `uid`     varchar(127)      DEFAULT NULL              COMMENT 'User ID from location',
  `aid`     varchar(255)      DEFAULT NULL              COMMENT 'Asset identifier from location',
  `loc`     ENUM('in', 'tw')  DEFAULT 'in'              COMMENT 'Incoming location, in=instagram, tw=twitter',
  `grant`   tinyint(1)        NOT NULL DEFAULT FALSE    COMMENT 'Was perms granted bool 0/1',
  PRIMARY KEY (`id`),
  KEY uid (uid),
  CONSTRAINT fk_queue FOREIGN KEY (uid) REFERENCES approved (uid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8`;


CREATE TABLE `fls_approved` (
  `id`      bigint(20)        NOT NULL AUTO_INCREMENT,
  `uid`     varchar(127)      DEFAULT NULL              COMMENT 'User ID from location',
  `aid`     varchar(255)      DEFAULT NULL              COMMENT 'Asset identifier from location',
  `loc`     ENUM('in', 'tw')  DEFAULT 'in'              COMMENT 'Incoming location, in=instagram, tw=twitter',
  `grant`   tinyint(1)        NOT NULL DEFAULT FALSE    COMMENT 'Was perms granted bool 0/1',
  PRIMARY KEY (`id`)
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8`;


GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
       ON throa.*
       TO 'throa_dba'@'%' IDENTIFIED BY '1"__K,UR=}2&6eD';
