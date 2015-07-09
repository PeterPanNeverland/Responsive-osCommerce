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
      $group['apps'][] = array('code' => 'shipping_excludes.php',
                               'title' => BOX_CATALOG_SHIPPING_EXCLUDES,
                               'link' => tep_href_link('shipping_excludes.php'));

      break;
    }
  }
?>
