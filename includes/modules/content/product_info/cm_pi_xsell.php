<?php
/*
  $Id$

	TO DO:
	- tidy up install / remove functions... success/fail messaging is wrong
	- add some validation functions for complete install
	- remove deprecated constants

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  class cm_pi_xsell {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;
    var $version = '0.1';

    function cm_pi_xsell() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PRODUCT_INFO_XSELL_TITLE;
      $this->description = MODULE_CONTENT_PRODUCT_INFO_XSELL_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $languages_id, $currencies, $PHP_SELF;
      
      $xsell_data = NULL;
	  $content_width = (int)MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH;

	  if ((USE_CACHE == 'true') && empty($SID)) {
		  $xsell_data .= tep_cache_xsell_products(3600); 
	  } else {
        ob_start();
        include(DIR_WS_MODULES . 'xsell_products.php');
        $xsell_data .= ob_get_clean();
	  }
	  if (!is_null($xsell_data) && strlen($xsell_data) > 0) {
		  ob_start();
		  include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/xsell.php');
		  $template = ob_get_clean();
		
		  $oscTemplate->addContent($template, $this->group);
	  }
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS');
    }

    function install() {
	  global $messageStack;
	  require(DIR_WS_FUNCTIONS . 'xsell.php'); 
	  //check if need to install database changes
	  if (xsell_check_db()) {
		$messageStack->add(DB_DROP_DATA, 'warning');
	  } else {
		if (xsell_setup_db() === TRUE){
			$messageStack->add(DB_SUCCESS, 'success');
		} else {
			$messageStack->add(DB_FAILURE, 'error');
		}
	  }
	
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Cross Sell Module', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS', 'True', 'Should the Cross Sell block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH', '8', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Align Content', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_ALIGN', 'text-left', 'How should the content be aligned or float?', '6', '1', 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Number of cross sells', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT', '6', 'Maximum number of products to display in the Cross Sell block. NB output may be cached.', '6', '1', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Min Product Width', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH', '4', 'Minimum width (in page columns) of product grid listing in Cross Sell Block - used to fill out a single row. NB output may be cached.', '6', '1', 'tep_cfg_select_option(array(\'6\', \'5\', \'4\', \'3\', \'2\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER', '700', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
	  //validation stuff
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK',  '',  '', '6', '9', 'tep_xsell_version_check', 'tep_cfg_do_nothing(', now() ) ");
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_UPLOAD_CHECK',  '',  '', '6', '9', 'tep_xsell_upload_check', 'tep_cfg_do_nothing(', now() ) ");
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_EDIT_CHECK',  '',  '', '6', '9', 'tep_xsell_edit_check', 'tep_cfg_do_nothing(', now() ) ");
    }

    function remove() {
		global $messageStack;
	  require(DIR_WS_FUNCTIONS . 'xsell.php'); 
	  //check if any data before reverting database changes
	  if (!xsell_check_data()) {
		if (clean_db() === TRUE){
			$messageStack->add(DB_DROP_SUCCESS, 'success');
		} else {
			$messageStack->add(DB_DROP_FAILURE, 'error');
		}
	  } else {
		$messageStack->add(DB_DROP_DATA, 'warning');
	  }
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_ALIGN', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH',  'MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_UPLOAD_CHECK', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_EDIT_CHECK');
    }
  }
  // class def ends here, what follows are definitions of functions used above

define('MODULE_CONTENT_PRODUCT_INFO_XSELL_NO_TEST','This test doesn\'t check anything yet so don\'t read too much into it!');
	// Check whether there's any updating to do (?and maybe if it's the latest version)
	if( !function_exists( 'tep_xsell_version_check' ) ) {
		function tep_xsell_version_check() {
			if (true) {
				//checks passed
				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_NO_TEST . '</span>';
//				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_OK . '</span>';
			} else {
			// The theme was not found, so return an error message
				return tep_image( DIR_WS_ICONS . 'cross.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold; color:red;">' . sprintf(MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_FAIL, $files) . '</span>';
			}
		} 
	} 
	// Check whether all new files have been uploaded
	if( !function_exists( 'tep_xsell_upload_check' ) ) {
		function tep_xsell_upload_check() {
			if (true) {
				//checks passed
				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_NO_TEST . '</span>';
//				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_UPLOAD_OK . '</span>';
			} else {
			// The theme was not found, so return an error message
				return tep_image( DIR_WS_ICONS . 'cross.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold; color:red;">' . sprintf(MODULE_CONTENT_PRODUCT_INFO_XSELL_UPLOAD_FAIL, $missing) . '</span>';
			}
		} 
	} 
	// Check whether all new files have been uploaded
	if( !function_exists( 'tep_xsell_edit_check' ) ) {
		function tep_xsell_edit_check() {
			if (true) {
				//checks passed
				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_NO_TEST . '</span>';
//				return tep_image( DIR_WS_ICONS . 'tick.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . MODULE_CONTENT_PRODUCT_INFO_XSELL_EDIT_OK . '</span>';
			} else {
			// The theme was not found, so return an error message
				return tep_image( DIR_WS_ICONS . 'cross.gif', '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold; color:red;">' . sprintf(MODULE_CONTENT_PRODUCT_INFO_XSELL_EDIT_FAIL, $missing_edits) . '</span>';
			}
		} 
	} 

  // Function to prevent boxes showing for the output-only test functions
  if( !function_exists( 'tep_cfg_do_nothing' ) ) {
    function tep_cfg_do_nothing() {
      return '';
    }
  }
