<?php
/*
  $Id$

  Family products: display the other products in the same family
	- module for product_info.php, used in content module cm_pi_family_products.php
	
	part of: Family Categories Addon
	loosely derived from an early version of family products addon http://addons.oscommerce.com/info/1429
	
	Author john@sewebsites.net @BrockleyJohn

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

if (isset($_GET['products_id'])) {

  $content_width = (int)MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_WIDTH;

	$family_query = tep_db_query("select products_family from products where products_id = '" . (int)$_GET['products_id'] . "'");
	$result = tep_db_fetch_array($family_query);
	if ($result['products_family'] != null) {
		
    $define_list = array('FAMILY_LIST_MODEL' => (MODULE_CONTENT_PI_FAMILY_PRODUCTS_MODEL == 'True'),
                         'FAMILY_LIST_NAME' => FAMILY_LIST_NAME,
                         'FAMILY_LIST_MANUFACTURER' => (MODULE_CONTENT_PI_FAMILY_PRODUCTS_MANUFACTURER == 'True'), 
                         'FAMILY_LIST_PRICE' => FAMILY_LIST_PRICE, 
                         'FAMILY_LIST_QUANTITY' => (MODULE_CONTENT_PI_FAMILY_PRODUCTS_QUANTITY == 'True'), 
                         'FAMILY_LIST_WEIGHT' => (MODULE_CONTENT_PI_FAMILY_PRODUCTS_WEIGHT == 'True'), 
                         'FAMILY_LIST_IMAGE' => FAMILY_LIST_IMAGE, 
                         'FAMILY_LIST_BUY_NOW' => FAMILY_LIST_BUY_NOW);
                          
    asort($define_list);

    $column_list = array();
    reset($define_list);
    while (list($key, $value) = each($define_list)) {
      if ($value > 0) $column_list[] = $key;
    }

    $select_column_list = '';

		$lc_show_model = false;
		$lc_show_manu = false;
		$lc_show_qty = false;
		$lc_show_lbs = false;

    for ($i=0, $n=sizeof($column_list); $i<$n; $i++) {
      switch ($column_list[$i]) {
        case 'FAMILY_LIST_MODEL':
          $select_column_list .= 'p.products_model, ';
          $lc_show_model = true;
          break;
        case 'FAMILY_LIST_MANUFACTURER':
          $select_column_list .= 'm.manufacturers_name, ';
          $lc_show_manu = true;
          break;
        case 'FAMILY_LIST_QUANTITY':
          $select_column_list .= 'p.products_quantity, ';
          $lc_show_qty = true;
          break;
        case 'FAMILY_LIST_WEIGHT':
          $select_column_list .= 'p.products_weight, ';
          $lc_show_lbs = true;
          break;
      }
    }
		
    $family_sql = "select p.products_id,  p.manufacturers_id, p.products_image, pd.products_name, SUBSTRING_INDEX(pd.products_description, ' ', 20) as products_description, p.products_tax_class_id, products_price, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price, " . $select_column_list . " p.products_family from products_description pd, products p left join manufacturers m on p.manufacturers_id = m.manufacturers_id left join specials s on p.products_id = s.products_id where p.products_id !=" . (int)$_GET['products_id'] . " and p.products_id = pd.products_id and p.products_family = '" . $result['products_family'] . "' and p.products_status = '1' and pd.language_id = '" . (int)$languages_id . "'";

    if ( (!isset($_GET['sort'])) || (!preg_match('/[1-8][ad]/', $_GET['sort'])) || (substr($_GET['sort'], 0, 1) > sizeof($column_list)) ) {
      for ($i=0, $n=sizeof($column_list); $i<$n; $i++) {
        if ($column_list[$i] == 'FAMILY_LIST_NAME') {
          $_GET['sort'] = $i+1 . 'a';
          $family_sql .= " order by pd.products_name";
          break;
        }
      }
    } else {
      $sort_col = substr($_GET['sort'], 0 , 1);
      $sort_order = substr($_GET['sort'], 1);
      $family_sql .= ' order by ';
      switch ($column_list[$sort_col-1]) {
        case 'FAMILY_LIST_MODEL':
          $family_sql .= "p.products_model " . ($sort_order == 'd' ? 'desc' : '') . ", pd.products_name";
          break;
        case 'FAMILY_LIST_NAME':
          $family_sql .= "pd.products_name " . ($sort_order == 'd' ? 'desc' : '');
          break;
        case 'FAMILY_LIST_MANUFACTURER':
          $family_sql .= "m.manufacturers_name " . ($sort_order == 'd' ? 'desc' : '') . ", pd.products_name";
          break;
        case 'FAMILY_LIST_QUANTITY':
          $family_sql .= "p.products_quantity " . ($sort_order == 'd' ? 'desc' : '') . ", pd.products_name";
          break;
        case 'FAMILY_LIST_IMAGE':
          $family_sql .= "pd.products_name";
          break;
        case 'FAMILY_LIST_WEIGHT':
          $family_sql .= "p.products_weight " . ($sort_order == 'd' ? 'desc' : '') . ", pd.products_name";
          break;
        case 'FAMILY_LIST_PRICE':
          $family_sql .= "products_price " . ($sort_order == 'd' ? 'desc' : '') . ", pd.products_name";
          break;
      }
    }
		
		if ((int)MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_LIMIT > 0) {
		  $family_sql .= " limit " . (int)MODULE_CONTENT_PI_FAMILY_PRODUCTS_CONTENT_LIMIT;
		}
		
		$products_query = tep_db_query($family_sql);

		$family_products_data = NULL;
	
		if (($num_results = tep_db_num_rows($products_query)) > 0) {
			//minimum size at default format is 3 page cols if no specials or 4 cols with specials
			$per_row = floor($content_width / MODULE_CONTENT_PI_FAMILY_PRODUCTS_PRODUCT_MIN_WIDTH);
			if ($num_results <= $per_row) {
				$columns = 12 / $num_results;
			} else {
				$columns = 12 / $per_row;
			}
		
			while ($product = tep_db_fetch_array($products_query)) {
				$family_products_data .= '<div class="item list-group-item col-sm-'.$columns.'">';
				$family_products_data .= '  <div class="productHolder equal-height">';
				$family_products_data .= '    <a href="' . tep_href_link('product_info.php', 'products_id=' . $product['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $product['products_image'], $product['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, NULL, NULL, 'img-responsive thumbnail group list-group-image') . '</a>';
				$family_products_data .= '    <div class="caption">';
				$family_products_data .= '      <h2 class="group inner list-group-item-heading">';
				$family_products_data .= '    <a href="' . tep_href_link('product_info.php', 'products_id=' . $product['products_id']) . '">' . $product['products_name'] . '</a>';
				$family_products_data .= '      </h2>';
				
				$family_products_data .= '      <p class="group inner list-group-item-text">' . strip_tags($product['products_description'], '<br>') . '&hellip;</p><div class="clearfix"></div>';
				$family_products_data .= '      <div class="row">';
				
				// here it goes the extras, yuck
				$extra_list_contents = NULL;
				// manufacturer
				if (($lc_show_manu == true) && ($product['manufacturers_id'] !=  0)) $extra_list_contents .= '<dt>' . TABLE_HEADING_MANUFACTURER . '</dt><dd><a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $product['manufacturers_id']) . '">' . $product['manufacturers_name'] . '</a></dd>';
				// model
				if ( ($lc_show_model == true) && tep_not_null($product['products_model'])) $extra_list_contents .= '<dt>' . TABLE_HEADING_MODEL . '</dt><dd>' . $product['products_model'] . '</dd>';
				// stock
				if (($lc_show_qty == true) && (tep_get_products_stock($product['products_id'])!= 0) ) $extra_list_contents .= '<dt>' . TABLE_HEADING_QUANTITY . '</dt><dd>' . tep_get_products_stock($product['products_id']) . '</dd>';
				// weight
				if (($lc_show_lbs == true) && ($product['products_weight'] != 0)) $extra_list_contents .= '<dt>' . TABLE_HEADING_WEIGHT . '</dt><dd>' . $product['products_weight'] . '</dd>';
		
				if (tep_not_null($extra_list_contents)) {
					 $family_products_data .= '    <dl class="dl-horizontal list-group-item-text">';
					 $family_products_data .=  $extra_list_contents;
					 $family_products_data .= '    </dl>';
				}

				if (tep_not_null($product['specials_new_products_price'])) {
					$family_products_data .= '      <div class="col-xs-6"><div class="btn-group" role="group"><button type="button" class="btn btn-default"><del>' .  $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</del></span>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price($product['specials_new_products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
				} else {
					$family_products_data .= '      <div class="col-xs-6"><div class="btn-group" role="group"><button type="button" class="btn btn-default">' . $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
				}
				$family_products_data .= '       <div class="col-xs-6 text-right">' . tep_draw_button(IMAGE_BUTTON_IN_CART, 'glyphicon glyphicon-shopping-cart', tep_href_link('product_info.php', tep_get_all_get_params(array('action')) . 'action=buy_now&product_to_buy_id=' . $product['products_id']), NULL, NULL, 'btn-success btn-sm') . '</div>';
				$family_products_data .= '      </div>';
				
				$family_products_data .= '    </div>';
				$family_products_data .= '  </div>';
				$family_products_data .= '</div>';
			} // end while ($product = tep_db_fetch_array($products_query))
			echo $family_products_data;
		} // end if (($num_results = tep_db_num_rows($products_query)) > 0)
	} // end if ($result['products_family'] != null)
} // end if (isset($_GET['products_id']))
?>