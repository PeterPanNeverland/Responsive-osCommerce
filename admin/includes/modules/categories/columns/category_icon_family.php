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
  class category_icon_family extends admin_categories_column { // definition is in c_modules.php
    var $type = 'category';
    var $title = 'icon (family categories)';
		var $key_prefix = 'MODULE_ADM_CAT_COLS_CAT_ICON_FAMILY_';
		var $default_column = '10';
		var $default_sort = '1';
		var $add_to_select = array('c.family_category');
		var $add_to_from = '';		
  }
?>