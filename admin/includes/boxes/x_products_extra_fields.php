<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array(
							'code' => 'product_extra_fields.php',
							'title' => BOX_CATALOG_PEF_PRODUCTS,
							'link' => tep_href_link('product_extra_fields.php')
						  );  
      break;
    }
  }
?>