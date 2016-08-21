<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License

  Discount Code 3.8 BS
*/

class ht_discount_code {

    var $code = 'ht_discount_code';
    var $group = 'header_tags';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
        $this->title = MODULE_HEADER_TAGS_DISCOUNT_CODE_TITLE;
        $this->description = MODULE_HEADER_TAGS_DISCOUNT_CODE_DESCRIPTION;

        if (defined('MODULE_HEADER_TAGS_DISCOUNT_CODE_STATUS')) {
            $this->sort_order = MODULE_HEADER_TAGS_DISCOUNT_CODE_SORT_ORDER;
            $this->enabled = (MODULE_HEADER_TAGS_DISCOUNT_CODE_STATUS == 'True');
        }
    }

    function execute() {
        global $PHP_SELF, $HTTP_GET_VARS, $oscTemplate, $sess_discount_code;
        if (MODULE_ORDER_TOTAL_DISCOUNT_STATUS == 'true') {
            if (basename($PHP_SELF) == 'checkout_payment.php') {
                ob_start();
                include('ht_discount_code/ht_discount_code.php');
                $template = ob_get_clean();
                $oscTemplate->addBlock($template, $this->group);
            } elseif (basename($PHP_SELF) == 'logoff.php') {
                if (tep_session_is_registered('sess_discount_code')) {
                    tep_session_unregister('sess_discount_code');
                }
            }
        }
    }

    function isEnabled() {
        return $this->enabled;
    }

    function check() {
        return defined('MODULE_HEADER_TAGS_DISCOUNT_CODE_STATUS');
    }

    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Module', 'MODULE_HEADER_TAGS_DISCOUNT_CODE_STATUS', 'True', 'Do you want to enable the module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_DISCOUNT_CODE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_HEADER_TAGS_DISCOUNT_CODE_STATUS', 'MODULE_HEADER_TAGS_DISCOUNT_CODE_SORT_ORDER');
    }

}

?>
