<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
  
  Discount Code 3.8 BS
*/


    foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array('code' => 'discount_codes.php',
                               'title' => BOX_CATALOG_DISCOUNT_CODES,
                               'link' => tep_href_link('discount_codes.php'));

      break;
    }
  }
?>