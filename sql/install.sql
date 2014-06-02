CREATE DATABASE IF NOT EXISTS throa;

SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ANSI';
USE throa ;
DROP PROCEDURE IF EXISTS throa.drop_user_if_exists ;
DELIMITER $$
CREATE PROCEDURE throa.drop_user_if_exists()
  BEGIN
    DECLARE count_exists BIGINT DEFAULT 0 ;
    SELECT COUNT(*)
    INTO count_exists
    FROM mysql.user
    WHERE User = 'throa_dba' AND Host = 'localhost';
    IF count_exists > 0 THEN
      DROP USER 'throa_dba'@'localhost' ;
    END IF;
  END ;$$
DELIMITER ;
CALL throa.drop_user_if_exists() ;
DROP PROCEDURE IF EXISTS throa.drop_users_if_exists ;
SET SQL_MODE=@OLD_SQL_MODE ;


## Might want to do a foreign key here ?
DROP TABLE IF EXISTS `fls_queue`;
CREATE TABLE `fls_queue` (
  `id`      bigint(20)        NOT NULL AUTO_INCREMENT,
  `uid`     varchar(127)      DEFAULT NULL              COMMENT 'User ID from location',
  `aid`     varchar(255)      DEFAULT NULL              COMMENT 'Asset identifier from location',
  `loc`     ENUM('in', 'tw')  DEFAULT 'in'              COMMENT 'Incoming location, in=instagram, tw=twitter',
  `grant`   tinyint(1)        NOT NULL DEFAULT FALSE    COMMENT 'Was perms granted bool 0/1',
  `stamp`   datetime NOT NULL DEFAULT CURRENT_DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8`;

DROP TABLE IF EXISTS `fls_approved`;
CREATE TABLE `fls_approved` (
  `id`      bigint(20)        NOT NULL AUTO_INCREMENT,
  `uid`     varchar(127)      DEFAULT NULL              COMMENT 'User ID from location',
  `aid`     varchar(255)      DEFAULT NULL              COMMENT 'Asset identifier from location',
  `loc`     ENUM('in', 'tw')  DEFAULT 'in'              COMMENT 'Incoming location, in=instagram, tw=twitter',
  `grant`   tinyint(1)        NOT NULL DEFAULT FALSE    COMMENT 'Was perms granted bool 0/1',
  `stamp`   datetime NOT NULL DEFAULT CURRENT_DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8`;


GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
       ON throa.*
       TO 'throa_dba'@'%' IDENTIFIED BY '1"__K,UR=}2&6eD';
