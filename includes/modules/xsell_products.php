<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

if (isset($_GET['products_id'])) {

  $content_width = (int)MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_WIDTH;

  $xsell_query = tep_db_query("select distinct p.products_id, p.products_image, pd.products_name, SUBSTRING_INDEX(pd.products_description, ' ', 20) as products_description, p.products_tax_class_id, products_price, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price
from products_xsell xp left join " . TABLE_PRODUCTS . " p on xp.xsell_id = p.products_id
left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'
left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id 
where xp.products_id = '" . $_GET['products_id'] . "'
and p.products_status = '1'
order by sort_order asc limit " . (int)MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_LIMIT);

  $xsell_products_data = NULL;

  if (($num_results = tep_db_num_rows($xsell_query)) > 0) {
  //minimum size at default format is 3 page cols if no specials or 4 cols with specials
	$per_row = floor($content_width / MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCT_MIN_WIDTH);
	if ($num_results <= $per_row) {
		$columns = 12 / $num_results;
	} else {
		$columns = 12 / $per_row;
	}

	while ($product = tep_db_fetch_array($xsell_query)) {
		$xsell_products_data .= '<div class="item list-group-item col-sm-'.$columns.'">';
		$xsell_products_data .= '  <div class="productHolder equal-height">';
		$xsell_products_data .= '    <a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $product['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $product['products_image'], $product['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, NULL, NULL, 'img-responsive thumbnail group list-group-image') . '</a>';
		$xsell_products_data .= '    <div class="caption">';
		$xsell_products_data .= '      <h2 class="group inner list-group-item-heading">';
		$xsell_products_data .= '    <a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $product['products_id']) . '">' . $product['products_name'] . '</a>';
		$xsell_products_data .= '      </h2>';
		
		$xsell_products_data .= '      <p class="group inner list-group-item-text">' . strip_tags($product['products_description'], '<br>') . '&hellip;</p><div class="clearfix"></div>';
		$xsell_products_data .= '      <div class="row">';
		if (tep_not_null($product['specials_new_products_price'])) {
		$xsell_products_data .= '      <div class="col-xs-6"><div class="btn-group" role="group"><button type="button" class="btn btn-default"><del>' .  $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</del></span>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price($product['specials_new_products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
		} else {
		$xsell_products_data .= '      <div class="col-xs-5"><div class="btn-group" role="group"><button type="button" class="btn btn-default">' . $currencies->display_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])) . '</button></div></div>';
		}
		$xsell_products_data .= '       <div class="col-xs-7 text-right buynow">' . tep_draw_button(IMAGE_BUTTON_IN_CART, 'glyphicon glyphicon-shopping-cart', tep_href_link('product_info.php', tep_get_all_get_params(array('action')) . 'action=buy_now&product_to_buy_id=' . $product['products_id']), NULL, NULL, 'btn-success btn-sm') . '</div>';
		$xsell_products_data .= '      </div>';
		
		$xsell_products_data .= '    </div>';
		$xsell_products_data .= '  </div>';
		$xsell_products_data .= '</div>';
		
	}
	echo $xsell_products_data;
  }
		
}
?>