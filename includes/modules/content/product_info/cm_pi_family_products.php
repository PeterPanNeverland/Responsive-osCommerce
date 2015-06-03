<?php
/*
  $Id$

  Family products: display the other products in the same family
	- content module for product_info.php
	
	part of: Family Categories Addon
	loosely derived from an early version of family products addon http://addons.oscommerce.com/info/1429
	
	Author john@sewebsites.net @BrockleyJohn
	
	TODO: finish caching if required

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/
  define('MODULE_CONTENT_PI_FAMILY_PRODUCTS_FILE_VER','00.02');

  class cm_pi_family_products {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;
    var $caching = false;
    var $version = MODULE_CONTENT_PI_FAMILY_PRODUCTS_FILE_VER;

    function cm_pi_family_products() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PI_FAMILY_PRODUCTS_TITLE;
      $this->description = MODULE_CONTENT_PI_FAMILY_PRODUCTS_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PI_FAMILY_PRODUCTS_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PI_FAMILY_PRODUCTS_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PI_FAMILY_PRODUCTS_STATUS == 'True');
//        $this->caching = (MODULE_CONTENT_PI_FAMILY_PRODUCTS_CACHING_INSTALLED == 'True'); // not yet implemented
      }
    }

    function execute() {
      global $oscTemplate, $languages_id, $currencies, $PHP_SELF;
      
      $family_data = NULL;
			$content_width = (int)MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_WIDTH;
	
			if ($this->caching && (USE_CACHE == 'true') && empty($SID)) { // not yet implemented - always false
				$family_data .= tep_cache_family_products(3600); 
			} else {
					ob_start();
					include(DIR_WS_MODULES . 'family_products.php');
					$family_data .= ob_get_clean();
			}
			if (!is_null($family_data) && strlen($family_data) > 0) {
				ob_start();
				include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/family_products.php');
				$template = ob_get_clean();
			
				$oscTemplate->addContent($template, $this->group);
			}
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PI_FAMILY_PRODUCTS_STATUS');
    }

    function install() {
			tep_family_products_db_check();
	
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Family Products Module', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_STATUS', 'False', 'Should the Family Products block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_WIDTH', '8', 'What width container should the content be shown in?', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Align Content', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_ALIGN', 'text-left', 'How should the content be aligned or float?', '6', '3', 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Number of cross sells', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_LIMIT', '10', 'Maximum number of products to display in the Family Products block. NB output may be cached.', '6', '4', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Min Product Width', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_PRODUCT_MIN_WIDTH', '4', 'Minimum width (in page columns) of product grid listing in Family Products Block - used to fill out a single row. NB output may be cached.', '6', '5', 'tep_cfg_select_option(array(\'6\', \'5\', \'4\', \'3\', \'2\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_SORT_ORDER', '700', 'Sort order of display. Lowest is displayed first.', '6', '6', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Model', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MODEL', 'False', 'Should model be shown in the Family Products block?', '6', '7', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Manufacturer', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MANUFACTURER', 'False', 'Should manufacturer be shown in the Family Products block?', '6', '8', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Quantity', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_QUANTITY', 'False', 'Should quantity be shown in the Family Products block?', '6', '8', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Weight', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_WEIGHT', 'False', 'Should weight be shown in the Family Products block?', '6', '9', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	  //validation stuff
    // maybe for later version!  tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CACHING_INSTALLED',  '',  '', '6', '7', 'tep_family_products_caching', 'tep_cfg_do_nothing(', now() ) ");
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK',  '',  '', '6', '8', 'tep_family_products_version_check', 'tep_cfg_do_nothing(', now() ) ");
			tep_register_version_var('MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK');
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_PI_FAMILY_PRODUCTS_STATUS', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_WIDTH', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_ALIGN', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_LIMIT', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_PRODUCT_MIN_WIDTH', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_SORT_ORDER', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MODEL', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MANUFACTURER', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_QUANTITY', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_WEIGHT', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK');
    }
  }
	// **************************************************************************************************************************************
  // class def ends here, what follows are definitions of helper functions used above

	// Check whether database changes applied and if not apply them
	if( !function_exists( 'tep_family_products_db_check' ) ) {
		function tep_family_products_db_check() {
			if (!tep_db_num_rows(tep_db_query("SHOW COLUMNS FROM categories LIKE 'family_category'"))) {
				tep_db_query('ALTER TABLE categories ADD family_category TINYINT( 1 ) NOT NULL default 0');
			}
			if (!tep_db_num_rows(tep_db_query("SHOW COLUMNS FROM products LIKE 'products_family'"))) {
				tep_db_query('ALTER TABLE products ADD products_family VARCHAR( 24 )');
			}
		  if (!tep_db_num_rows(tep_db_query("SHOW INDEX FROM products WHERE column_name = 'products_family'"))) {
				tep_db_query('CREATE INDEX products_family ON products products_family');
			}
    }
	}
	
	// Called on module display. Checks run on new install, version change or when check files is pressed
	if( !function_exists( 'tep_family_products_version_check' ) ) {
		function tep_family_products_version_check() {
		  global $language;
			$file_version = MODULE_CONTENT_PI_FAMILY_PRODUCTS_FILE_VER;
			if (defined('MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK') && MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK <>'') {
				$db_version = MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK;
			} else {
				$db_version = '00.00';
			}
			$fail = false; $reset = true; $detail = '';
			if ($db_version == $file_version) {
				$msg = sprintf(MODULE_ADDON_VERSION_SAME,$file_version);
			} elseif ($db_version > $file_version) {
				$msg = MODULE_ADDON_VERSION_NOK;
				$fail = true;
			} else { // ($db_version < $file_version) { //file version is higher - upgrade or newly installed
				//put any update processing here ... remember to cater for skipped versions
				
				if (!defined('MODULE_CONTENT_PI_FAMILY_PRODUCTS_MODEL')) { // insert additional config vars
					tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Model', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MODEL', 'False', 'Should model be shown in the Family Products block?', '6', '7', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
					tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Manufacturer', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_MANUFACTURER', 'False', 'Should manufacturer be shown in the Family Products block?', '6', '8', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
					tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Quantity', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_QUANTITY', 'False', 'Should quantity be shown in the Family Products block?', '6', '8', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
					tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Weight', 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_WEIGHT', 'False', 'Should weight be shown in the Family Products block?', '6', '9', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
				}

				$newfiles = array( 
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/reset_version.php', // just check current language for admin
					DIR_FS_ADMIN . 'reset_version.php',
					DIR_FS_CATALOG . DIR_WS_CLASSES . 'split_union_results.php',
					DIR_FS_CATALOG_MODULES . 'family_listing.php',
					DIR_FS_CATALOG_MODULES . 'family_products.php',
					DIR_FS_CATALOG_MODULES . 'content/product_info/templates/family_products.php'
				);
				// check there's a module language file for every installed catalog language
        $languages = tep_get_languages();
				for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
				  $newfiles[] = DIR_FS_CATALOG_LANGUAGES . strtolower($languages[$i]['name']) . '/modules/content/product_info/cm_pi_family_products.php';
				}
				
				$log = tep_addon_upload_error($newfiles); //check if addons additional files are present
				if ($log !== false) {
					$fail = true;
				  $msg = MODULE_ADDON_UPLOAD_FAIL;
				} else {
				  $msg = MODULE_ADDON_UPLOAD_OK;
				}
				$msg .= '<br>';

				$editfiles = array(
					DIR_FS_CATALOG . 'index.php' => array('FAM-CAT-INDEX-EDIT',2),
					DIR_FS_CATALOG . DIR_WS_CLASSES . 'category_tree.php' => array('FAM-CAT-TREE-EDIT',1),
					DIR_FS_ADMIN . 'categories.php' => array('PI-GALLERY-CAPTION-ADM-CAT-EDIT',8),
					DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php' => array('PI-GALLERY-CAPTION-EDIT',1),
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/categories.php' => array('PI-GALLERY-CAPTION-EDIT',1),
				);
				$log2 = tep_addon_edit_error($editfiles); //check if addon edits to core files are present
				if ($log2 !== false) {
					$fail = true;
				  $msg .= MODULE_ADDON_EDIT_FAIL;
				} else {
				  $msg .= MODULE_ADDON_EDIT_OK;
				}
				if ($fail) {
				  $detail = tep_log_detail($log . '<br>' . $log2);
					$reset = false; //the checks will rerun next time anyway
				} else {
				//all checks passed - set module version in database (suppress checks next time)
					tep_db_query("update configuration set configuration_value = '".$file_version."' where configuration_key = 'MODULE_CONTENT_PI_FAMILY_PRODUCTS_VERSION_CHECK'");
					$msg = sprintf(MODULE_ADDON_VERSION_OK,$db_version,$file_version);
				}
			}
			//checks finished
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ($fail ? MODULE_ADDON_VALIDATION_FAIL : MODULE_ADDON_VALIDATION_OK) . '<br>' . $msg . '</span>';
			if ($reset) $return .= '<br>' . tep_draw_button(MODULE_ADDON_FILE_BTN, 'wrench', tep_href_link('reset_version.php', 'var=MODULE_ADDON_VERSION_CHECK&page=modules_content.php&module=cm_pi_xsell'));
			if (strlen($detail) > 0) $return .= '<br><span class="log_detail">' . tep_draw_button(MODULE_ADDON_LOG_BTN, 'document-b','','',array('type'=>'reset')).'</span>'.$detail;
			return $return;
		} 
	} 

	// Check whether all new files have been uploaded
	if( !function_exists( 'tep_addon_upload_error' ) ) {
		function tep_addon_upload_error($newfiles) {
			global $language;
			$missing = '';
			foreach ($newfiles as $file) {
				if (!file_exists($file)) $missing .= $file.'<br><br>';
			}
			if (strlen($missing) == 0) {
				//checks passed
				return false;
			} else {
			// failed, so return the errors
				return '<h4>'.MODULE_ADDON_UPLOAD_FAIL.'</h4>'.$missing;
			}
		} 
	} 

	// Check whether all new files have been uploaded
	if( !function_exists( 'tep_addon_edit_error' ) ) {
		function tep_addon_edit_error($editfiles) {
			$missing = '';
			foreach ($editfiles as $file => $edits) {
				if (!file_exists($file)) $missing .= $file.sprintf(MODULE_ADDON_EDIT_NOT_FOUND).'<br><br>';
			  elseif (($found = tep_check_edit_error($file,$edits)) !== false) {
					$missing .= $file.sprintf(MODULE_ADDON_EDIT_FOUND,$found,$edits[1]).'<br><br>';
				}
			}
			if (strlen($missing) == 0) {
				//checks passed
				return false;
			} else {
			// failed, so return the errors
				return '<h4>'.MODULE_ADDON_EDIT_FAIL.'</h4>'.$missing;
			}
		} 
	} 

  // Function to register version vars for validated deletion in reset_version.php
  if( !function_exists( 'tep_register_version_var' ) ) {
    function tep_register_version_var($version_var) {
		  if (!defined('MODULE_VERSION_CHECK_VARS')) {
				tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Version check vars', 'MODULE_VERSION_CHECK_VARS', '".$version_var."', 'checked in reset-var.php before deletion', '6', '1', now())");
			} else {
			  $var_array = explode('|',MODULE_VERSION_CHECK_VARS);
				if (!in_array($version_var,$var_array)) {
				  tep_db_query("UPDATE configuration SET configuration_value = '".MODULE_VERSION_CHECK_VARS.'|'.$version_var."' WHERE configuration_key = 'MODULE_VERSION_CHECK_VARS'");
				}
			}
      return true;
    }
  }

  // Function to deregister version vars from validated deletion in reset_version.php
  if( !function_exists( 'tep_deregister_version_var' ) ) {
    function tep_deregister_version_var($version_var) {
		  if (defined('MODULE_VERSION_CHECK_VARS')) {
			  $var_array = explode('|',MODULE_VERSION_CHECK_VARS);
				if (($key = array_search($version_var, $var_array)) !== false) { //get the index of $version_var in the array
				  unset($var_array[$key]); //delete the value from the array
				}
				if (count($var_array) > 0) { 
				  tep_db_query("UPDATE configuration SET configuration_value = '". implode('|',$var_array) ."' WHERE configuration_key = 'MODULE_VERSION_CHECK_VARS'");
				} else {
				  tep_db_query("DELETE FROM configuration WHERE configuration_key = 'MODULE_VERSION_CHECK_VARS'");
				}
			}
      return true;
    }
  }

	// Function to prevent boxes showing for the output-only test functions
  if( !function_exists( 'tep_cfg_do_nothing' ) ) {
    function tep_cfg_do_nothing() {
      return '';
    }
  }