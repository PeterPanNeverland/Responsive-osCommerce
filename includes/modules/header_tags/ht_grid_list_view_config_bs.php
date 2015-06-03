<?php
/*
  A no-choice version of ht_grid_list_view - requires bootstrap
  @BrockleyJohn

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

 class ht_grid_list_view_config_bs {
    var $code = 'ht_grid_list_view_config_bs';
    var $group = 'footer_scripts';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function ht_grid_list_view_config_bs() {
      $this->title = MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_TITLE;
      $this->description = MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_DESCRIPTION;

      if ( defined('MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_STATUS') ) {
        $this->sort_order = MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_STATUS == 'True');
      }
    }

    function execute() {
      global $PHP_SELF, $oscTemplate;

      if (tep_not_null(MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_PAGES)) {

        $pages_array = array();
	  	$page_settings_array = ht_grid_list_view_cfg_unpack(MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_PAGES);
		$set_to_list = '$(function(){$(\'#products .item\').removeClass(\'grid-group-item\').addClass(\'list-group-item\');});';
		$set_to_grid = '$(function(){$(\'#products .item\').removeClass(\'list-group-item\').addClass(\'grid-group-item\');});';
		$page = basename($PHP_SELF);

		if (array_key_exists($page, $page_settings_array)) {

		  switch ($page_settings_array[$page]) {
		    case  '0000' : // set to list all sizes
			  $script = '<script>'.$set_to_list.'</script>';
			  break;
		    case  '1111' : // set to grid all sizes
			  $script = '<script>'.$set_to_grid.'</script>';
			  break;
			default : // need viewport-dependent settings
			  $script = 
			 '<script src="ext/bootstrap-toolkit/bootstrap-toolkit.min.js"></script>
			 <script>(function($,viewport){
			 	function setGridList(){
				  if (viewport.is(\'xs\')){
				  '.($page_settings_array[$page][0]=='1' ? $set_to_grid : $set_to_list).'
				  } else if (viewport.is(\'sm\')){
				  '.($page_settings_array[$page][1]=='1' ? $set_to_grid : $set_to_list).'
				  } else if (viewport.is(\'md\')){
				  '.($page_settings_array[$page][2]=='1' ? $set_to_grid : $set_to_list).'
				  } else {
				  '.($page_settings_array[$page][3]=='1' ? $set_to_grid : $set_to_list).'
				  }
				}
				$(document).ready(function(){
				  setGridList();
				});
				$(window).bind(\'resize\',function() {
				  viewport.changed(function(){
				    setGridList();
				  });
				});
			  })(jQuery,ResponsiveBootstrapToolkit);
			  </script>';
		  }
          $oscTemplate->addBlock($script . "\n", $this->group);
        }
      }
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Grid List javascript', 'MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_STATUS', 'False', 'Do you want to enable the Grid/List Javascript module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Pages', 'MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_PAGES', '" . implode(';',$this->get_default_page_settings()) . "', 'The page settings for the Grid List JS Scripts.', '6', '4', 'ht_grid_list_view_cfg_show_pages', 'ht_grid_list_view_cfg_edit_pages(', now())");
	    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '5', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_STATUS', 'MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_PAGES', 'MODULE_HEADER_TAGS_GRID_LIST_VIEW_CFG_SORT_ORDER');
    }

    function get_default_page_settings() {
		$return = array();
		foreach ($this->get_default_pages() as $page) {
			$return[] = $page.'|0000';
		}
		return $return;
	}
	
    function get_default_pages() {
      return array('advanced_search_result.php',
                   'index.php',
                   'products_new.php',
                   'specials.php');
    }
  }
  
  function ht_grid_list_view_cfg_unpack($text) {
    $values_array = explode(';', $text);
	$settings_array = array();
	foreach ($values_array as $value) {
		$page = trim(strtok($value,'|'));
		$setting = trim(strtok('|'));
		$settings_array[$page] = $setting;
	}
	return $settings_array;
  }

  function ht_grid_list_view_cfg_show_pages($text) {
  	$page_settings_array = ht_grid_list_view_cfg_unpack($text);
	$pages = array();
	foreach ($page_settings_array as $page => $setting) { $pages[] = $page; }
    return nl2br(implode("\n", $pages));
  }

  function ht_grid_list_view_cfg_edit_pages($values, $key) {
    global $PHP_SELF;

    $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
    $files_array = array();
	  if ($dir = @dir(DIR_FS_CATALOG)) {
	    while ($file = $dir->read()) {
	      if (!is_dir(DIR_FS_CATALOG . $file)) {
	        if (substr($file, strrpos($file, '.')) == $file_extension) {
            $files_array[] = $file;
          }
        }
      }
      sort($files_array);
      $dir->close();
    }

	$settings_array = ht_grid_list_view_cfg_unpack($values);

    $output = ''; 
	$sizes = array('XS','SM','MD','LG');
    foreach ($files_array as $file) {
	  $enabled = array_key_exists($file,$settings_array);
      $output .= tep_draw_checkbox_field('ht_grid_list_view_file[]', $file, $enabled) . '&nbsp;' . tep_output_string($file);
	  for ($i = 0 ; $i < 4; $i++) {
	    $output .= '&nbsp;' . tep_draw_checkbox_field('ht_grid_list_view_'.$file.'[]',1,($enabled ? $settings_array[$file][$i] : false));
//	    $output .= '&nbsp;' . tep_output_string($sizes[$i]) . '&nbsp;' . tep_draw_checkbox_field('ht_grid_list_view_'.$file.'[]',1,($enabled ? $settings_array[$file][$i] : false));
	  }
	  $output .= '<br />';
    }

    if (!empty($output)) {
      $output = '<br />' . substr($output, 0, -6);
    }

    $output .= tep_draw_hidden_field('configuration[' . $key . ']', '', 'id="htrn_file_settings"');

    $output .= '<script>
                function htrn_update_cfg_value() {
                  var htrn_selected_files = \'\';
				  var current_file = \'\';
				  var settings = \'\';

                  if ($(\'input[name="ht_grid_list_view_file[]"]\').length > 0) {
                    $(\'input[name="ht_grid_list_view_file[]"]:checked\').each(function() {
					  current_file = $(this).attr(\'value\');
				  	  settings = \'\';
					  $(\'input[name="ht_grid_list_view_\' + current_file + \'[]"]\').each(function() {
					  	settings += $(this).prop(\'checked\') ? 1 : 0;
					  });
                      htrn_selected_files += current_file + \'|\' + settings + \';\';
                    });

                    if (htrn_selected_files.length > 0) {
                      htrn_selected_files = htrn_selected_files.substring(0, htrn_selected_files.length - 1);
                    }
                  }

                  $(\'#htrn_file_settings\').val(htrn_selected_files);
				  //alert(htrn_selected_files);
                }

                $(function() {
                  htrn_update_cfg_value();

                  if ($(\'input[name="ht_grid_list_view_file[]"]\').length > 0) {
                    $(\'input[name="ht_grid_list_view_file[]"]\').change(function() {
                      htrn_update_cfg_value();
                    });
                    $(\'input:checkbox[name!="ht_grid_list_view_file[]"]\').change(function() {
					  if ($(this).prop(\'checked\')) {
					  	var filename = $(this).attr(\'name\').substring(18,$(this).attr(\'name\').length - 2);
					    $(\'input[name="ht_grid_list_view_file[]"][value="\' + filename + \'"]\').prop("checked",true);
					  }
                      htrn_update_cfg_value();
                    });
                  }
                });
                </script>';

    return $output;
  }
?>