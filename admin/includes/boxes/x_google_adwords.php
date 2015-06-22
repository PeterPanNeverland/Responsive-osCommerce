<?php
/*
  $Id$

	Author John Ferguson (@BrockleyJohn) john@sewebsites.net

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_CATALOG ) {
      $group['apps'][] = array('code' => 'adwords_feed.php',
                               'title' => BOX_CATALOG_ADWORDS,
                               'link' => tep_href_link('adwords_feed.php'));
      break;
    }
  }
?>
