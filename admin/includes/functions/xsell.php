<?php
/*
  $Id$
  
  admin functions for addon Cross Sell

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

function xsell_check_db(){
	$query = tep_db_query('SHOW TABLES LIKE \'products_xsell\'');
	return (tep_db_num_rows($query) > 0);
}

function xsell_setup_db(){
	return tep_db_query('CREATE TABLE products_xsell (
  ID int(10) NOT NULL auto_increment,
  products_id int(10) unsigned NOT NULL,
  xsell_id int(10) unsigned NOT NULL,
  sort_order int(10) unsigned NOT NULL default \'1\',
  PRIMARY KEY  (ID),
  INDEX idx_products_id (products_id),
  INDEX idx_xsell_id (xsell_id)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
}
function xsell_check_data(){
	$query = tep_db_query('SELECT COUNT(ID) AS total FROM products_xsell');
	$result = tep_db_fetch_array($query);
	return ($result['total'] > 0);
}
function clean_db() {
	return tep_db_query('DROP TABLE products_xsell');
}
?>