<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class cm_pi_extra_fields {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_pi_extra_fields() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_TITLE;
      $this->description = MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_DESCRIPTION;

      if ( defined('MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $languages_id;
      
      $content_width  = (int)MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_WIDTH;
        
      $extra_fields_query = tep_db_query("
SELECT pef.products_extra_fields_order, pef.products_extra_fields_status as status, pef.products_extra_fields_name as name, ptf.products_extra_fields_value as value
FROM products_extra_fields pef
LEFT JOIN products_to_products_extra_fields ptf
ON ptf.products_extra_fields_id=pef.products_extra_fields_id
WHERE ptf.products_id=". (int)$_GET['products_id'] ." and ptf.products_extra_fields_value<>'' and (pef.languages_id='0' or pef.languages_id='".(int)$languages_id."') AND pef.products_extra_fields_status = 1 AND pef.google_only = '0' 
ORDER BY pef.products_extra_fields_order");

	if (tep_db_num_rows($extra_fields_query) > 0) {
	
	  $extra_fields_data = NULL;
	  while ($extra_fields = tep_db_fetch_array($extra_fields_query)) {
		$extra_fields_data .= '<strong>' . $extra_fields['name'] . ':</strong> '.stripslashes($extra_fields['value']).'<br>'; 
	  }
     $extra_fields_data = substr($extra_fields_data,0,-4);

      ob_start();
      include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/extra_fields.php');
      $template = ob_get_clean();

      $oscTemplate->addContent($template, $this->group);
	  
	 }
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Product Extra Fields Module', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_STATUS', 'True', 'Should the product description block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_WIDTH', '8', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Align-Float', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_ALIGN', 'text-left', 'How should the content be aligned or float?', '6', '1', 'tep_cfg_select_option(array(\'text-left\', \'text-center\', \'text-right\', \'pull-left\', \'pull-right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Vertical Margin', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_VERT_MARGIN', '', 'Top and Bottom Margin added to the module? none, VerticalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'VerticalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Horizontal Margin', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_HORIZ_MARGIN', '', 'Left and Right Margin added to the module? none, HorizontalMargin=10px', '6', '1', 'tep_cfg_select_option(array(\'\', \'HorizontalMargin\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_SORT_ORDER', '400', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_STATUS', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_WIDTH', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_ALIGN', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_VERT_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_CONTENT_HORIZ_MARGIN', 'MODULE_CONTENT_PRODUCT_INFO_EXTRA_FIELDS_SORT_ORDER');
    }
  }

