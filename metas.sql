CREATE TABLE IF NOT EXISTS `metas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `foreignKey` int(11) NOT NULL,
  `key` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  KEY `object_id` (`foreignKey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=20847 ;
