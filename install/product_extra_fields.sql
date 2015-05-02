CREATE TABLE IF NOT EXISTS `products_extra_fields` (
  `products_extra_fields_id` int(11) NOT NULL auto_increment,
  `products_extra_fields_name` varchar(64) collate utf8_unicode_ci NOT NULL default '',
  `products_extra_fields_order` int(3) NOT NULL default '0',
  `products_extra_fields_status` tinyint(1) NOT NULL default '1',
  `languages_id` int(11) NOT NULL default '0',
  `category_id` text collate utf8_unicode_ci NOT NULL,
  `google_only` char(1) collate utf8_unicode_ci NOT NULL default '0',
  `searchable` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`products_extra_fields_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `products_to_products_extra_fields` (
  `products_id` int(11) NOT NULL default '0',
  `products_extra_fields_id` int(11) NOT NULL default '0',
  `products_extra_fields_value` text,
  PRIMARY KEY  (`products_id`,`products_extra_fields_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

