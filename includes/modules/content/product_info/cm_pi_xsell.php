<?php
/*
  $Id$

	TO DO:
	- tidy up install / remove functions
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
      global $oscTemplate, $_GET, $languages_id, $currencies, $PHP_SELF;
      
      $content_width = (int)MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH;

	  $xsell_query = tep_db_query("select distinct p.products_id, p.products_image, pd.products_name, SUBSTRING_INDEX(pd.products_description, ' ', 20) as products_description, p.products_tax_class_id, products_price, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price
	from products_xsell xp left join " . TABLE_PRODUCTS . " p on xp.xsell_id = p.products_id
	left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'
	left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id 
	where xp.products_id = '" . $_GET['products_id'] . "'
	and p.products_status = '1'
	order by sort_order asc limit " . (int)MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT);

      $xsell_data = NULL;
	  
      if (($num_results = tep_db_num_rows($xsell_query)) > 0) {
	  //minimum size is 3 page cols if no specials or 4 cols with specials
	    $per_row = floor($content_width / MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH);
		if ($num_results <= $per_row) {
			$columns = 12 / $num_results;
		} else {
			$columns = 12 / $per_row;
		}

        while ($product = tep_db_fetch_array($xsell_query)) {
			$xsell_data .= '<div class="item list-group-item col-sm-'.$columns.'">';
			$xsell_data .= '  <div class="productHolder equal-height">';
			$xsell_data .= '    <a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $product['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $product['products_image'], $product['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, NULL, NULL, 'img-responsive thumbnail group list-group-image') . '</a>';
			$xsell_data .= '    <div class="caption">';
			$xsell_data .= '      <h2 class="group inner list-group-item-heading">';
			$xsell_data .= '    <a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $product['products_id']) . '">' . $product['products_name'] . '</a>';
			$xsell_data .= '      </h2>';
			
			$xsell_data .= '      <p class="group inner list-group-item-text">' . strip_tags($product['products_description'], '<br>') . '&hellip;</p><div class="clearfix"></div>';
			$xsell_data .= '      <div class="row">';
			if (tep_not_null($product['specials_new_products_price'])) {
			$xsell_data .= '      <div class="col-xs-6"><div class="btn-group" role="group"><button type="button" class="btn btn-default"><del>' .  $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</del></span>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price($product['specials_new_products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
			} else {
			$xsell_data .= '      <div class="col-xs-6"><div class="btn-group" role="group"><button type="button" class="btn btn-default">' . $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
			}
			$xsell_data .= '       <div class="col-xs-6 text-right">' . tep_draw_button(IMAGE_BUTTON_BUY_NOW, 'cart', tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action', 'sort', 'cPath')) . 'action=buy_now&products_id=' . $product['products_id']), NULL, NULL, 'btn-success btn-sm') . '</div>';
			$xsell_data .= '      </div>';
			
			$xsell_data .= '    </div>';
			$xsell_data .= '  </div>';
			$xsell_data .= '</div>';
			
        }
        
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
	  if (!xsell_check_db()) {
		if (xsell_setup_db() === TRUE){
			$messageStack->add(DB_SUCCESS, 'success');
		} else {
			$messageStack->add(DB_FAILURE, 'error');
		}
	  }
	
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Reviews Module', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS', 'True', 'Should the Cross Sell block be shown on the product info page?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH', '8', 'What width container should the content be shown in?', '6', '1', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Number of cross sells', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT', '6', 'Maximum number of products to display in the Cross Sell block', '6', '1', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Min Product Width', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH', '4', 'Minimum width (in page columns) of product grid listing in Cross Sell Block - used to fill out a single row', '6', '1', 'tep_cfg_select_option(array(\'6\', \'5\', \'4\', \'3\', \'2\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER', '700', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
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
      return array('MODULE_CONTENT_PRODUCT_INFO_XSELL_STATUS', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH',  'MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT', 'MODULE_CONTENT_PRODUCT_INFO_XSELL_SORT_ORDER');
    }
  }

