<?php
/*
  $Id$

	Product info content module for shipping methods with excluded products
	Author @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  define('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_FILE_VER','00.01');

  class cm_pi_shipping_exclude {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_pi_shipping_exclude() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PI_SHIPPING_EXCLUDES_TITLE;
      $this->description = MODULE_CONTENT_PI_SHIPPING_EXCLUDES_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PI_SHIPPING_EXCLUDES_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PI_SHIPPING_EXCLUDES_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $product_info, $language;
      
      $content_width  = (int)MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CONTENT_WIDTH;
      $shipping_output = NULL;
       
      if (isset($_GET['products_id'])) {
			
			  $query = tep_db_query("select shipping_code from shipping_exclusions where products_id = '". (int)$_GET['products_id'] . "'");
        
				if (tep_db_num_rows($query) > 0) {
				
					while ($module = tep_db_fetch_array($query)) {
					
						include(DIR_WS_LANGUAGES . $language . '/modules/shipping/' . $module['shipping_code'] . '.php');
						include(DIR_WS_MODULES . 'shipping/' . $module['shipping_code'] . '.php');
						
						if (class_exists($module['shipping_code'])) {
						
							$ship_module = new $module['shipping_code'];
							if ($ship_module->enabled) {
								$shipping_output .= sprintf(MODULE_CONTENT_PI_SHIPPING_EXCLUDES_PRODUCT_METHOD,$ship_module->title) . '<br>';
							}

						}

					}
				
				}
				
				if (strlen($shipping_output) > 0) {
				
				  $shipping_output .= MODULE_CONTENT_PI_SHIPPING_EXCLUDES_ANOTHER_METHOD;
				
					ob_start();
					include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/shipping_exclude.php');
					$template = ob_get_clean();
	
					$oscTemplate->addContent($template, $this->group);
				
				}

			}
      
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_STATUS');
    }

    function install($parameter = null) {
		  tep_shipping_exclude_db_check();

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
			tep_register_version_var('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK');
    }

    function getParams() {

      $params = array('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_STATUS' => array('title' => 'Enable Excluded from Shipping Module',
                                                                     'desc' => 'Should the module be shown on the product info page?',
                                                                     'value' => 'True',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CONTENT_WIDTH' => array('title' => 'Content Width',
                                                                     'desc' => 'What width container should the content be shown in?',
                                                                     'value' => '4',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), '),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CONTENT_ALIGN' => array('title' => 'Content Align-Float',
                                                                     'desc' => 'How should the content be aligned or float?',
                                                                     'value' => 'pull-right',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), '),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CONTENT_VERT_MARGIN' => array('title' => 'Content Vertical Margin',
                                                                     'desc' => 'Top and Bottom Margin added to the module? none, VerticalMargin=10px',
                                                                     'value' => 'VerticalMargin',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'\', \'VerticalMargin\'), '),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CONTENT_HORIZ_MARGIN' => array('title' => 'Content Horizontal Margin',
                                                                     'desc' => 'Left and Right Margin added to the module? none, HorizontalMargin=10px',
                                                                     'value' => 'HorizontalMargin',
                                                                     'set_func' => 'tep_cfg_select_option(array(\'\', \'HorizontalMargin\'), '),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_SORT_ORDER' => array('title' => 'Sort Order.',
                                                                         'desc' => 'Sort order of display. Lowest is displayed first.',
                                                                         'value' => '300'),
 //                     'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CLASH_CHECK' => array('use_func' => 'tep_shipping_exclude_clash_check',
 //                                                                                    'set_func' => 'tep_cfg_do_nothing('),
                      'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK' => array('use_func' => 'tep_shipping_exclude_version_check',
                                                                                     'set_func' => 'tep_cfg_do_nothing(')
																																				 );

      return $params;
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
			tep_deregister_version_var('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK');
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

// helper functions not part of class:

	// Check whether database changes applied and if not apply them
	if( !function_exists( 'tep_shipping_exclude_db_check' ) ) {
  	require_once(DIR_WS_FUNCTIONS . 'shipping_excludes.php');
	}
	
	// Check whether clashing modules are installed and enabled
/*	if( !function_exists( 'tep_shipping_exclude_clash_check' ) ) {
		function tep_shipping_exclude_clash_check() {
			$fail = false; $msg = '';
			if (defined('MODULE_HEADER_TAGS_PRODUCT_COLORBOX_STATUS') && MODULE_HEADER_TAGS_PRODUCT_COLORBOX_STATUS == 'True') {
			  $fail = true; $msg .= sprintf(MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CLASH_ENABLED,'ht_product_colorbox').'<br>';
			}
			if (defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS') && MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS == 'True') {
			  $fail = true; $msg .= sprintf(MODULE_CONTENT_PI_SHIPPING_EXCLUDES_CLASH_ENABLED,'cm_pi_gallery').'<br>';
			}
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ( $fail ? $msg : MODULE_CONTENT_PI_SHIPPING_EXCLUDES_NO_CLASH) . '</span>';
			return $return;
    }
	} */
	
	// when the version changes (or after each install) check add-on files and edits are there
	if( !function_exists( 'tep_shipping_exclude_version_check' ) ) {
		function tep_shipping_exclude_version_check() {
			global $language;
			$file_version = MODULE_CONTENT_PI_SHIPPING_EXCLUDES_FILE_VER;
			if (defined('MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK') && MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK <>'') {
				$db_version = MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK;
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
					DIR_FS_CATALOG_LANGUAGES . $language . '/modules/content/product_info/cm_pi_shipping_exclude.php',
					DIR_FS_CATALOG_MODULES . 'content/product_info/templates/shipping_exclude.php',
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
/*					DIR_FS_ADMIN . 'categories.php' => array('PI-GALLERY-CAPTION-ADM-CAT-EDIT',8),
					DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php' => array('PI-GALLERY-CAPTION-EDIT',1),
					DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language . '/categories.php' => array('PI-GALLERY-CAPTION-EDIT',1), */
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
					tep_db_query("update configuration set configuration_value = '".$file_version."' where configuration_key = 'MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK'");
					$msg = sprintf(MODULE_ADDON_VERSION_OK,$db_version,$file_version);
				}
			}
			//checks finished
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ($fail ? MODULE_ADDON_VALIDATION_FAIL : MODULE_ADDON_VALIDATION_OK) . '<br>' . $msg . '</span>';
			if ($reset) $return .= '<br>' . tep_draw_button(MODULE_ADDON_FILE_BTN, 'wrench', tep_href_link('reset_version.php', 'var=MODULE_CONTENT_PI_SHIPPING_EXCLUDES_VERSION_CHECK&page=modules_content.php&module=cm_pi_gallery_caption'));
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
