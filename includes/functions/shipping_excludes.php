<?php
/*
  $Id$
  
  admin functions for addon Shipping Excludes

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

function tep_shipping_exclude_db_check() {
	$query = tep_db_query('SHOW TABLES LIKE \'shipping_exclusions\'');
	if (tep_db_num_rows($query) == 0){
		tep_db_query('CREATE TABLE shipping_exclusions (
		 shipping_code varchar(64) NOT NULL,
		 products_id int(11) NOT NULL,
		 PRIMARY KEY (shipping_code,products_id)
		)');
	}
}

?>