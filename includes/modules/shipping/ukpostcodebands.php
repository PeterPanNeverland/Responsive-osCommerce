<?php
/* derived from
  $Id: ukpostarea.php,v 1.27 2003/02/05 22:41:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce - by mark enriquez, modified for UK delivery area use 
  Dec 2006 by Martin Hughes-Jones
  Modified by Luke Higton for TNT 48 Hours delivery

-- 
-- This redesign: BrockleyJohn john@sewebsites.net
-- Feb 2011
-- 
-- UK postcode-based shipping bands
-- A database table maps postcode outcodes (the first bit) to geographical areas (zones)
-- This module sets up price bands that give a shipping cost based on weight or order price
-- for one or more zones, eg. Band 1: England, Wales, Lowland Scotland
--
-- This version supports only one set of zones, common to all shipping methods
-- Included zone separations support TNT Overnight contract price bands
-- eg. there is separation of areas of highlands and islands that affect price
-- and availability of service
-- 
-- You can make multiple versions of this module and set up different pricing bands for
-- different combinations of carrier zones as well as prices & charging methods for each carrier
-- 

  USAGE
  By default, the module comes with support for 11 carrier delivery bands.  
  This can be easily changed by editing the line below in the zones constructor 
  that defines $this->num_bands.

  PLEASE NOTE THAT YOU WILL LOSE YOUR CURRENT SHIPPING RATES AND OTHER 
  SETTINGS IF YOU UNINSTALL THIS SHIPPING METHOD.  Make sure you keep a 
  backup of your shipping settings somewhere at all times.
  Hint: if you edit them into this file (see $default_carriercodes below)
  they'll get installed automatically

  If you want an additional handling charge applied to orders that use this
  method, set the Handling Fee field for the band(s).

  Now you need to set up the shipping/deliver rate tables for each band.  
  There's the option of basing them on price or weight. Some time and effort 
  will go into setting the appropriate rates.  For instance, you might want 
  an order than weighs more than 0   and less than or equal to 3 to cost 5.50 
  to ship to a certain band.  
  This would be defined by this:  3:5.5

  You should combine a bunch of these rates together in a comma delimited
  list and enter them into the "Band X Shipping Table" fields where "X" 
  is the Band number.  For example, this might be used for Band 1:
  - Delivery Zone(s): 1,2 [means England, Wales & Scottish Lowlands
  - Band 1 Shipping/Delivery Fee Table:
    1:3.5,2:3.95,3:5.2,4:6.45,5:7.7,6:10.4,7:11.85, 8:13.3,9:14.75,10:16.2,11:17.65,
    12:19.1,13:20.55,14:22,15:23.45

  The above example includes weights over 0 and up to 15.  Note that
  units are not specified in this explanation since they should be
  specific to your locale.

  CAVEATS
  There's a setting for what should happen if the weight/price is bigger than
  the top of the table - free or error. If you want neither, you could have 
  one last very high range with the maximum price you want to charge, eg 
  instance:  999:100

  If a delivery zone is not listed, then the module will add a $0.00 shipping 
  charge and will indicate that shipping is not available to that destination.  
  PLEASE NOTE THAT THE ORDER CAN STILL BE COMPLETED AND PROCESSED!

  It appears that the osC shipping system automatically rounds the 
  shipping weight up to the nearest whole unit.  This makes it more
  difficult to design precise shipping tables.  If you want to, you 
  can hack the shipping.php file to get rid of the rounding.

  Released under the GNU General Public License
*/

  class ukpostcodebands {
    var $code, $title, $description, $enabled, $num_bands;

// class constructor
    function ukpostcodebands() {

      $this->code = get_class($this);
      $this->title = MODULE_SHIPPING_UKCODEBAND_TEXT_TITLE;
      $this->description = MODULE_SHIPPING_UKCODEBAND_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_SHIPPING_UKCODEBAND_SORT_ORDER;
      $this->icon = DIR_WS_ICONS . 'shipping_nextday.png';
      $this->tax_class = MODULE_SHIPPING_UKCODEBAND_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_UKCODEBAND_STATUS == 'True') ? true : false);
      $this->top_error = ((MODULE_SHIPPING_UKCODEBAND_TOP_ERROR == 'Error') ? true : false);

      // CUSTOMIZE THIS SETTING FOR THE NUMBER OF BANDS NEEDED
      $this->num_bands = 11;
    }
// class methods
    function quote($method = '') { //method allows you to get a shipping quote from places other than checkout_shipping
      global $order, $cart, $shipping_weight, $shipping_num_boxes; // JAF cart for table toggle
	  
      if (MODULE_SHIPPING_UKCODEBAND_MODE == 'price' && $method <> 'admin') {  // JAF insert table toggle
        $order_total = $cart->show_total();
      } else {
        $order_total = $shipping_weight;
      }

//First split the destination postcode and check the db for matching delivery zone
        $postcode = $order->delivery['postcode'];
	list($t_postcode, $local) = preg_split('/[\/ -]/', $postcode);
	
 if ( $t_postcode == '' ){
      // Something is wrong, no matching area code
	 $this->quotes['error'] = MODULE_SHIPPING_UKCODEBAND_NO_POSTCODE;
      	return $this->quotes;
      }


  $sql = "SELECT *
      		FROM toll_zones
		WHERE t_postcode = '$t_postcode'";      

$qResult = tep_db_query($sql); // run the query
      $rec = tep_db_fetch_array($qResult); // get the first row of the result
      $dest_zone = $rec['t_zone'];

//checks to see if there is a blank areazone entry and returns error message if not
      if ( $dest_zone == '' ){
	 $this->quotes['error'] = MODULE_SHIPPING_UKCODEBAND_INVALID_ZONE;
      	return $this->quotes;
      }

      for ($i=1; $i<=$this->num_bands; $i++) {
        $carriercode_table = constant('MODULE_SHIPPING_UKCODEBAND_CODES_' . $i);
        $carriercode_zones = preg_split("/[,]/", $carriercode_table);
        if (in_array($dest_zone, $carriercode_zones)) {
          $dest_band = $i;
          break;
        }
      }

      if ($dest_band == 0) {
        $error = true;
      } else {
        $shipping = -1;
        $carriercode_cost = constant('MODULE_SHIPPING_UKCODEBAND_COST_' . $dest_band);

        $carriercode_table = preg_split("/[:,]/" , $carriercode_cost);
        $size = sizeof($carriercode_table);
        for ($i=0; $i<$size; $i+=2) {
          if ($order_total <= $carriercode_table[$i]) {
            $shipping = $carriercode_table[$i+1];
            $shipping_method = '';
            
            break;
          }
        }

        if ($shipping == -1) { // The weight / price was bigger than the top of the table
		  if ($this->top_error) {
		    $this->quotes['error'] = (MODULE_SHIPPING_UKCODEBAND_MODE == 'price' ? MODULE_SHIPPING_UKCODEBAND_UNDEFINED_PRICE : MODULE_SHIPPING_UKCODEBAND_UNDEFINED_WEIGHT);
			return $this->quotes;
		  } else {
          $shipping_cost = 0;
		  }
        } else {
          $shipping_cost = ($shipping * $shipping_num_boxes) + constant('MODULE_SHIPPING_UKCODEBAND_HANDLING_' . $dest_band);
        }
        $shipping_method = MODULE_SHIPPING_UKCODEBAND_TEXT_WAY; // JAF
      }

      $this->quotes = array('id' => $this->code,
                            'module' => MODULE_SHIPPING_UKCODEBAND_TEXT_TITLE,
                            'methods' => array(array('id' => $this->code,
                                                     'title' => $shipping_method,
                                                     'cost' => $shipping_cost)));

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      if ($error == true) $this->quotes['error'] = MODULE_SHIPPING_UKCODEBAND_INVALID_CODE;

      return $this->quotes;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_UKCODEBAND_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable UK postcode banded delivery', 'MODULE_SHIPPING_UKCODEBAND_STATUS', 'True', 'Offer UK carrier shipping by carrier zone?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_UKCODEBAND_TAX_CLASS', '1', 'Use the following tax class on the shipping/delivery fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Table Method', 'MODULE_SHIPPING_UKCODEBAND_MODE', 'weight', 'The shipping cost is based on the order total or the total weight of the items ordered.', '6', '0', 'tep_cfg_select_option(array(\'weight\', \'price\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Off the top of table: error or free?', 'MODULE_SHIPPING_UKCODEBAND_TOP_ERROR', 'Error', '', '6', '0', 'tep_cfg_select_option(array(\'Error\', \'Free\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_UKCODEBAND_SORT_ORDER', '2', 'Sort order of display.', '6', '0', now())");
      for ($i = 1; $i <= $this->num_bands; $i++) {
        $default_zipcodes = '';
        if ($i == 1) {
          $default_carriercodes = '1,2';
          $default_dlvtable = '20:5.825,100:50';
        } else if ($i == 2) {
          $default_carriercodes = '6';
          $default_dlvtable = '20:15,100:100';
        } else if ($i == 3) {
          $default_carriercodes = '3';
          $default_dlvtable = '20:20,100:100';
        } else if ($i == 4) {
          $default_carriercodes = '4';
          $default_dlvtable = '20:30,100:100';
        } else if ($i == 5) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 6) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 7) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 8) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 9) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 10) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        } else if ($i == 11) {
          $default_carriercodes = '';
          $default_dlvtable = '';
        }
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Band " . $i ." Delivery zones(s)', 'MODULE_SHIPPING_UKCODEBAND_CODES_" . $i ."', '" . $default_carriercodes . "', 'Comma separated list of carrier delivery zones with this tariff " . $i . ".', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Band " . $i ." Shipping/Delivery Fee Table', 'MODULE_SHIPPING_UKCODEBAND_COST_" . $i ."', '" . $default_dlvtable . "', 'Shipping rates to Band " . $i . " destinations based on a group of maximum order weights. Example: 4:5,8:7,... weights less than or equal to 4 would cost $5 for Band " . $i . " destinations.', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Band " . $i ." Handling Fee', 'MODULE_SHIPPING_UKCODEBAND_HANDLING_" . $i."', '0', 'Handling Fee for this delivery band', '6', '0', now())");
      }
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      $keys = array('MODULE_SHIPPING_UKCODEBAND_STATUS', 'MODULE_SHIPPING_UKCODEBAND_TAX_CLASS', 'MODULE_SHIPPING_UKCODEBAND_MODE', 'MODULE_SHIPPING_UKCODEBAND_TOP_ERROR','MODULE_SHIPPING_UKCODEBAND_SORT_ORDER');

      for ($i=1; $i<=$this->num_bands; $i++) {
        $keys[] = 'MODULE_SHIPPING_UKCODEBAND_CODES_' . $i;
        $keys[] = 'MODULE_SHIPPING_UKCODEBAND_COST_' . $i;
        $keys[] = 'MODULE_SHIPPING_UKCODEBAND_HANDLING_' . $i;
      }

      return $keys;
    }
  }
?>