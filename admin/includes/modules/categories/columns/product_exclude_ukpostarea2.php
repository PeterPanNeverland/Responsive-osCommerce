<?php
/*
  Admin Productivity Enhancements for osCommerce
	Author: @BrockleyJohn john@sewebsites.net
	2015
	
	Productivity improvements in admin/categories: this module manages
	- configurable columns 

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/
  class product_exclude_ukpostarea2 extends shipping_exclude { // definition is in c_modules.php
		var $shipping_module = 'ukpostarea2';
    var $title = 'exclude myhermes';
		var $default_column = '22';
		var $default_sort = '1';
  }
?>