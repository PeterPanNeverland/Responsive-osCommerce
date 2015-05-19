<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  define('MODULE_CONTENT_PRODUCT_INFO_GALLERY_FILE_VER','00.02');

  class cm_pi_gallery_caption {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_pi_gallery_caption() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_TITLE;
      $this->description = MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $product_info, $languages_id;
      
      $content_width  = (int)MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_WIDTH;
      $gallery_output = NULL;
      
      if (tep_not_null($product_info['products_image'])) {
        
				$oscTemplate->addBlock('<script src="ext/photoset-grid/jquery.photoset-grid-t.min.js"></script>' . "\n", 'footer_scripts');
//				$oscTemplate->addBlock('<link rel="stylesheet" href="ext/colorbox/colorbox.css" />' . "\n", 'header_tags'); // moved to an import in the template
				$oscTemplate->addBlock('<script src="ext/colorbox/jquery.colorbox-min.js"></script>' . "\n", 'footer_scripts');
				$oscTemplate->addBlock('<script>var ImgCount = $(".piGal").data("imgcount"); $(function() {$(\'.piGal\').css({\'visibility\': \'hidden\'});$(\'.piGal\').photosetGrid({layout: ""+ ImgCount +"",width: \'100%\',highresLinks: true,rel: \'pigallery\',onComplete: function() {$(\'.piGal\').css({\'visibility\': \'visible\'});$(\'.piGal a\').colorbox({maxHeight: \'90%\',maxWidth: \'90%\', rel: \'pigallery\'});$(\'.piGal img\').each(function() {var imgid = $(this).attr(\'id\').substring(9);if ( $(\'#piGalDiv_\' + imgid).length ) {$(this).parent().colorbox({ inline: true, href: "#piGalDiv_" + imgid });}});}});});</script>', 'footer_scripts');

        $gallery_output .= tep_image(DIR_WS_IMAGES . $product_info['products_image'], NULL, NULL, NULL, 'itemprop="image" style="display:none;"');

        $photoset_layout = '1';

        $pi_query = tep_db_query("select image, htmlcontent, image_caption from products_images pi left join products_image_captions pic on pic.products_images_id = pi.id and pic.language_id = '" . (int)$languages_id . "' where products_id = '" . (int)$product_info['products_id'] . "' order by sort_order");
        $pi_total = tep_db_num_rows($pi_query);

        if ($pi_total > 0) {
            $pi_sub = $pi_total-1;

            while ($pi_sub > 5) {
                $photoset_layout .= 5;
                $pi_sub = $pi_sub-5;
            }

            if ($pi_sub > 0) {
                $photoset_layout .= ($pi_total > 5) ? 5 : $pi_sub;
            }
            
            $gallery_output .= '<div class="piGal" data-imgcount="' . $photoset_layout . '">';
            
            $pi_counter = 0;
            $pi_html = array();

            while ($pi = tep_db_fetch_array($pi_query)) {
                $pi_counter++;

                if (tep_not_null($pi['htmlcontent'])) {
                    $pi_html[] = '<div id="piGalDiv_' . $pi_counter . '">' . $pi['htmlcontent'] . '</div>';
                }

                $gallery_output .= tep_image(DIR_WS_IMAGES . $pi['image'], '', '', '', 'id="piGalImg_' . $pi_counter . '"' . (tep_not_null($pi['image_caption']) ? ' title="' . $pi['image_caption'] . '"' : '' ) );
            }
            
            $gallery_output .= '</div>';
            
            if ( !empty($pi_html) ) {
               $gallery_output .= '    <div style="display: none;">' . implode('', $pi_html) . '</div>';
            }
            
        } else {
            
            $gallery_output .= '<a class="piGal" title="'.addslashes($product_info['products_name']).'">' .
                                    tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name'])) .
                               '</a>';
        }
        
        ob_start();
        include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/gallery_caption.php');
        $template = ob_get_clean();

        $oscTemplate->addContent($template, $this->group);
      
      }
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_STATUS');
    }

    function install() {
		  tep_captions_db_check();
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Product Image Gallery Module', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_STATUS', 'True', 'Should the product image gallery block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_WIDTH', '4', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Align-Float', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_ALIGN', 'pull-right', 'How should the content be aligned or float?', '6', '1', 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Vertical Margin', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_VERT_MARGIN', 'VerticalMargin', 'Top and Bottom Margin added to the module? none, VerticalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'VerticalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Horizontal Margin', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_HORIZ_MARGIN', '', 'Left and Right Margin added to the module? none, HorizontalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'HorizontalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_SORT_ORDER', '300', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CLASH_CHECK',  '',  '', '6', '9', 'tep_captions_clash_check', 'tep_cfg_do_nothing(', now() ) ");
      tep_db_query( "insert into configuration ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added ) values ( '', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK',  '',  '', '6', '9', 'tep_captions_version_check', 'tep_cfg_do_nothing(', now() ) ");
			tep_register_version_var('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK');
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
			tep_deregister_version_var('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK');
    }

    function keys() {
      return array('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_STATUS', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_WIDTH', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_ALIGN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_VERT_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CONTENT_HORIZ_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_SORT_ORDER','MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_CLASH_CHECK','MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK');
    }
  }

// helper functions not part of class:

	// Check whether database changes applied and if not apply them
	if( !function_exists( 'tep_captions_db_check' ) ) {
		function tep_captions_db_check() {
			$query = tep_db_query('SHOW TABLES LIKE \'products_image_captions\'');
			if (tep_db_num_rows($query) == 0){
				tep_db_query('CREATE TABLE products_image_captions (
				 products_images_id int(11) NOT NULL,
				 language_id int DEFAULT \'1\' NOT NULL,
				 image_caption varchar(64),
				 PRIMARY KEY (products_images_id,language_id)
				)');
			}
    }
	}
	
	// Check whether clashing modules are installed and enabled
	if( !function_exists( 'tep_captions_clash_check' ) ) {
		function tep_captions_clash_check() {
			$fail = false; $msg = '';
			if (defined('MODULE_HEADER_TAGS_PRODUCT_COLORBOX_STATUS') && MODULE_HEADER_TAGS_PRODUCT_COLORBOX_STATUS == 'True') {
			  $fail = true; $msg .= sprintf(MODULE_CONTENT_PI_GALLERY_CAPTION_CLASH_ENABLED,'ht_product_colorbox').'<br>';
			}
			if (defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS') && MODULE_CONTENT_PRODUCT_INFO_GALLERY_STATUS == 'True') {
			  $fail = true; $msg .= sprintf(MODULE_CONTENT_PI_GALLERY_CAPTION_CLASH_ENABLED,'cm_pi_gallery').'<br>';
			}
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ( $fail ? $msg : MODULE_CONTENT_PI_GALLERY_CAPTION_NO_CLASH) . '</span>';
			return $return;
    }
	}
	
	// when the version changes (or after each install) check add-on files and edits are there
	if( !function_exists( 'tep_captions_version_check' ) ) {
		function tep_captions_version_check() {
			global $language;
			$file_version = MODULE_CONTENT_PRODUCT_INFO_GALLERY_FILE_VER;
			if (defined('MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK') && MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK <>'') {
				$db_version = MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK;
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
					DIR_FS_CATALOG . 'ext/photoset-grid/jquery.photoset-grid-t.min.js',
					DIR_FS_CATALOG_LANGUAGES . $language . '/modules/content/product_info/cm_pi_gallery_caption.php',
					DIR_FS_CATALOG_MODULES . 'content/product_info/templates/gallery_caption.php',
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
					tep_db_query("update configuration set configuration_value = '".$file_version."' where configuration_key = 'MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK'");
					$msg = sprintf(MODULE_ADDON_VERSION_OK,$db_version,$file_version);
				}
			}
			//checks finished
			$return = tep_image( DIR_WS_ICONS . ($fail ? 'cross.gif' : 'tick.gif'), '', '16', '16', 'style="vertical-align:middle;"' ) . ' <span style="vertical-align:middle; font-weight:bold;">' . ($fail ? MODULE_ADDON_VALIDATION_FAIL : MODULE_ADDON_VALIDATION_OK) . '<br>' . $msg . '</span>';
			if ($reset) $return .= '<br>' . tep_draw_button(MODULE_ADDON_FILE_BTN, 'wrench', tep_href_link('reset_version.php', 'var=MODULE_CONTENT_PRODUCT_INFO_GALLERY_CAPTION_VERSION_CHECK&page=modules_content.php&module=cm_pi_gallery_caption'));
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
