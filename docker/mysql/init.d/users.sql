# dev env user
CREATE DATABASE IF NOT EXISTS `asgoodasnew` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS asgoodasnew@'$s' IDENTIFIED BY '123456';
GRANT ALL ON asgoodasnew.* TO 'asgoodasnew'@'%';
