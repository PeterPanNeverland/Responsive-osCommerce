<?php
/*
  $Id$

  add previous orders to admin / orders.php
	
	author: John Ferguson @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
*/

  class hook_admin_orders_customer_orders {

    function listen_orderTab() {
      if ( !class_exists('hook_admin_orders_customer_tab') ) {
        include(DIR_FS_CATALOG . 'includes/modules/hooks/admin/orders_customer_tab.php');
      }

      $hook = new hook_admin_orders_customer_tab();

      return $hook->execute();
    }
  }
?>
