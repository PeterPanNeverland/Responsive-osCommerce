<?php
/*
  $Id$
	Cross Sell Addon
	Content module and bootstrap version by @BrockleyJohn john@sewebsites.net

	TO DO:
	- [done] tidy up install / remove functions... success/fail messaging is wrong
	- [done] add some validation functions for complete install
	- split caching out separately for simpler install
	- remove deprecated constants

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/
  define('MODULE_CONTENT_PRODUCT_INFO_XSELL_FILE_VER','00.04');

  class cm_pi_xsell {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;
    var $version = MODULE_CONTENT_PRODUCT_INFO_XSELL_FILE_VER;

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

    function install($parameter = null) {
			require(DIR_WS_FUNCTIONS . 'xsell.php'); 
			//check if need to install database changes
			if (! xsell_check_db()) {
				xsell_setup_db();
			}

      $params = $this->getParams();

      if (isset($parameter)) {
        if (isset($params[$parameter])) {
          $params = array($parameter => $params[$parameter]);
        } else {
          $params = array();
        }
      }

      foreach ($params as $key => $data) {
        $sql_data_array = array('configuration_title' => (isset($data['title']) ? $data['title'] : ''),
                                'configuration_key' => $key,
                                'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
                                'configuration_description' => (isset($data['desc']) ? $data['desc'] : ''),
                                'configuration_group_id' => '6',
                                'sort_order' => '0',
                                'date_added' => 'now()');

        if (isset($data['set_func'])) {
          $sql_data_array['set_function'] = $data['set_func'];
        }

        if (isset($data['use_func'])) {
          $sql_data_array['use_function'] = $data['use_func'];
        }

        tep_db_perform('configuration', $sql_data_array);
      }
			tep_register_version_var('MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK');
    }

    function getParams() {

      $params = array('MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS' => array('title' => 'Enable Cross Sell Module',
                                                                     'desc' => 'Should the module be shown on the product info page?',
                                                                     'value' => 'True',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH' => array('title' => 'Content Width',
                                                                     'desc' => 'What width container should the content be shown in?',
                                                                     'value' => '12',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), '),
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_ALIGN' => array('title' => 'Content Align-Float',
                                                                     'desc' => 'How should the content be aligned or float?',
                                                                     'value' => 'text-left',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), '),
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT' => array('title' => 'Number of cross sells',
                                                                     'desc' => 'Maximum number of products to display in the Cross Sell block. NB output may be cached.',
                                                                     'value' => '6'),
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH' => array('title' => 'Min Product Width',
                                                                     'desc' => 'Minimum width (in page columns) of product grid listing in Cross Sell Block - used to fill out a single row. NB output may be cached.',
                                                                     'value' => '4',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'6\', \'5\', \'4\', \'3\', \'2\'), '),
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER' => array('title' => 'Sort Order.',
                                                                         'desc' => 'Sort order of display. Lowest is displayed first.',
                                                                         'value' => '700'),
	  //validation stuff
                      'MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK' => array('use_func' => 'tep_xsell_version_check',
                                                                                     'set_func' => 'tep_cfg_do_nothing(')
																																				 );

      return $params;
    }

    function remove() {
			require(DIR_WS_FUNCTIONS . 'xsell.php'); 
			//check if any data before reverting database changes
			if (!xsell_check_data()) {
				xsell_clean_db();
			}
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      $keys = array_keys($this->getParams());

      if ($this->check()) {
        foreach ($keys as $key) {
          if (!defined($key)) {
            $this->install($key);
          }
        }
      }

      return $keys;
    }
  }
  // class def ends here, what follows are definitions of functions used above

	// Check whether there's any updating to do (?and maybe if it's the latest version)
	if( !function_exists( 'tep_xsell_version_check' ) ) {
		function tep_xsell_version_check() {
			global $language;
			$file_version = MODULE_CONTENT_PRODUCT_INFO_XSELL_FILE_VER;
			if (defined('MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK') && MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK <>'') {
				$db_version = MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK;
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

				$newfiles = array(
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/reset_version.php',
					DIR_FS_ADMIN . 'reset_version.php',
					DIR_FS_ADMIN . DIR_WS_BOXES . 'xsell.php',
					DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'xsell.php',
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/modules/boxes/xsell.php',
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/xsell.php',
					DIR_FS_ADMIN . 'xsell.php',
					DIR_FS_CATALOG_LANGUAGES . $language . '/modules/content/product_info/cm_pi_xsell.php',
					DIR_FS_CATALOG_MODULES . 'content/product_info/templates/xsell.php',
					DIR_FS_CATALOG_MODULES . 'xsell_products.php'
				);
				$log = tep_addon_upload_error($newfiles); //check if addons additional files are present
				if ($log !== false) {
					$fail = true;
				  $msg = MODULE_ADDON_UPLOAD_FAIL;
				} else {
				  $msg = MODULE_ADDON_UPLOAD_OK;
				}
				$msg .= '<br>';

				$editfiles = array(
					DIR_FS_ADMIN . 'categories.php' => array('XSELL-ADM-CAT',8),
					DIR_FS_ADMIN . DIR_WS_INCLUDES . 'application_top.php' => array('XSELL-ADM-APP-TOP',1),
					DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php' => array('XSELL-ADM-GENERAL-FUNCTIONS',3),
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '.php' => array('XSELL-ADM-LANGUAGE',1),
					DIR_FS_CATALOG . DIR_WS_INCLUDES . 'application_top.php' => array('XSELL-CAT-APP-TOP',2),
					DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'cache.php' => array('XSELL-CAT-CACHE-FUNCTION',1)
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
					tep_db_query("update configuration set configuration_value = '".$file_version."' where configuration_key = 'MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK'");
					$msg = sprintf(MODULE_ADDON_VERSION_OK,$db_version,$file_version);
				}
			}
			//checks finished
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ($fail ? MODULE_ADDON_VALIDATION_FAIL : MODULE_ADDON_VALIDATION_OK) . '<br>' . $msg . '</span>';
			if ($reset) $return .= '<br>' . tep_draw_button(MODULE_ADDON_FILE_BTN, 'wrench', tep_href_link('reset_version.php', 'var=MODULE_CONTENT_PRODUCT_INFO_XSELL_VERSION_CHECK&page=modules_content.php&module=cm_pi_xsell'));
			if (strlen($detail) > 0) $return .= '<br><span class="log_detail">' . tep_draw_button(MODULE_ADDON_LOG_BTN, 'document-b','','',array('type'=>'reset')).'</span>'.$detail;
			return $return;
		} 
	} 

	// Check whether all new files have been uploaded
	if( !function_exists( 'tep_addon_upload_error' ) ) {
		function tep_addon_upload_error($newfiles) {
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

	// Check whether all core files have been edited
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

  // Function to look for right number of edits in core files
  if( !function_exists( 'tep_check_edit_error' ) ) {
    function tep_check_edit_error($file,$edits) {
			//return exec('grep -cw '.$edits[0].' '.$file);
			//$return = 'grep -c "'.$edits[0].'" '.$file;
			exec('grep -c "'.$edits[0].'" '.$file,$output);
			if ($output[0] == $edits[1]) return false;
			else return $output[0];
    }
  }

  // Function that returns the passed contents in a hidden div displayed on click of id log_detail
	// returns all required html inc script and styles in a chunk
  if( !function_exists( 'tep_log_detail' ) ) {
    function tep_log_detail($contents) { 
		  $styles = "\n";
		  $scripts = '<script><!--
$(document).ready(function() {

	$( "#detail_div" ).dialog({
		autoOpen: false,
		title: "'.MODULE_ADDON_LOG_TITLE.'",
		width : 500,
		height : 400,
		position : { my: "top", at: "top", of: "#contentText" }
	});

	$(".log_detail").click(function() {
		$("#detail_div").dialog("open");
	});

}); //end of document ready function
//--></script>
';
      $detail_div = '    <div id="detail_div" class="dialog">
' . $contents . '
</div>';
      return $styles.$scripts.$detail_div;
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
