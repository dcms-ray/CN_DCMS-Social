CREATE TABLE IF NOT EXISTS `forum_p` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`id_forum` int(11) NOT NULL KEY,
	`id_razdel` int(11) NOT NULL KEY,
	`id_them` int(11) NOT NULL KEY,
	`id_user` int(11) NOT NULL KEY,
	`time` int(11) DEFAULT NULL KEY,
	`msg` varchar(1024) CHARSET utf8mb4 COLLATE  utf8mb4_unicode_ci NOT NULL FULLTEXT KEY,
	`cit` int(11) default NULL
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
