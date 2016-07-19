<?php
/*

  Pay4Later payment module for 
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 SEwebsites.biz

  Released under the GNU General Public License
*/

  class pay4later {
    var $code, $title, $description, $enabled, $min_goods;
	var $no_options = 6;
// class constructor
    function __construct() {
      global $order;

      $this->signature = 'pay4later|';

      $this->code = 'pay4later';
      $this->title = MODULE_PAYMENT_PAY4LATER_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_PAY4LATER_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_PAY4LATER_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_PAY4LATER_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID;
      }
	  
      $this->email_footer = MODULE_PAYMENT_PAY4LATER_TEXT_EMAIL_FOOTER;

	  if (defined('MODULE_PAYMENT_PAY4LATER_OPTION_1')) {
		  $this->options = array(); $this->min_goods = 0;
		  for ($i = 1; $i <= $this->no_options ; $i++) {
			$work_option = constant('MODULE_PAYMENT_PAY4LATER_OPTION_'.$i);
			$workvalues = explode("|",$work_option);
			if (count($workvalues)>1){ // unpack option if it's set
				$load = array(
					'code' => $workvalues[0],
					'min_goods' => $workvalues[1],
					'max_goods' => $workvalues[2],
					'min_deposit' => $workvalues[3],
					'max_deposit' => $workvalues[4]
				);
				$this->options[] = $load;
				if ($this->min_goods == 0 || $load['min_goods'] < $this->min_goods) $this->min_goods = $load['min_goods'];
	//			if ($load['min_goods'] < $this->min_goods) $this->min_goods = $load['min_goods'];
			}
		  }
	
		  $this->public_title = MODULE_PAYMENT_PAY4LATER_TEXT_PUBLIC_TITLE . ' (Min Order '.$this->min_goods. ')';
	  }

      if (is_object($order)) $this->update_status();
	  
//	  $this->form_action_url = 'output_params.php';

      if (MODULE_PAYMENT_PAY4LATER_GATEWAY_SERVER == 'Live') {
        $this->form_action_url = 'https://secure.pay4later.com/credit_app/';
      } else {
        $this->form_action_url = 'https://test.pay4later.com/credit_app/';
      } 
    }

// class methods
    function update_status() {
      global $order, $finance, $finance_application;

	  require_once(DIR_WS_CLASSES . 'finance_options.php');
	  $finance = new FinanceOptions();
	  if (!tep_session_is_registered('finance_application')) tep_session_register('finance_application');
	  if (tep_not_null($_POST['pay4later_option_id'])) {
		$finance_application['finance_code'] = tep_db_prepare_input($_POST['pay4later_option_id']);
		$finance_application['option_text'] = tep_db_prepare_input($_POST['pay4later_option_text']);
		$finance_application['deposit'] = tep_db_prepare_input($_POST['pay4later_deposit']);
		$finance_application['loan'] = tep_db_prepare_input($_POST['pay4later_loan']);
		$finance_application['monthly'] = tep_db_prepare_input($_POST['pay4later_monthly']);
		$finance_application['term'] = tep_db_prepare_input($_POST['pay4later_term']);
		$finance_application['total'] = tep_db_prepare_input($_POST['pay4later_total']);
		$finance_application['apr'] = tep_db_prepare_input($_POST['pay4later_apr']);
	  }
     /* if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAY4LATER_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAY4LATER_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        } 
      } */
    }

    function javascript_validation() {
      global $order;
	
		if ($order->info['subtotal'] < $this->min_goods) {
			$js = 'if (payment_value == "' . $this->code . '") {' . "\n" .
				  '    error_message = error_message + "' . MODULE_PAYMENT_PAY4LATER_TEXT_SPEND_ERROR . '";' . "\n" .
				  '    error = 1;' . "\n" .
				  '}' . "\n";
		} else {
			$js = 'if (payment_value == "' . $this->code . '") {' . "\n" .
			' var chosenOpt = $("#pay4later_option_id").val();' . "\n" .
			' var depositpc = $("#depositpc").val()*1;' . "\n" .
			'  if (typeof chosenOpt === "undefined" || chosenOpt == "" || isNaN(depositpc)) {' . "\n" .
			'    error_message = error_message + "' . MODULE_PAYMENT_PAY4LATER_TEXT_CHOOSE_ERROR . '";' . "\n" .
			'    error = 1;' . "\n" .
			'  }' . "\n" .
			'}' . "\n";
		}
		return $js;
    }

    function selection() {
      global $cart_PAY4LATER_ID, $order, $finance, $shipping, $finance_application;
	  
	  //turns out that $order->info['total'] doesn't include the tax on the shipping so we need to add that in to get the real order total
	  
	  $order_total = tep_round($order->info['total'] + tep_calculate_tax($shipping['cost'],$shipping['tax']),2);

      if (tep_session_is_registered('cart_PAY4LATER_ID')) {
        $order_id = substr($cart_PAY4LATER_ID, strpos($cart_PAY4LATER_ID, '-')+1);

        $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

        if (tep_db_num_rows($check_query) < 1) {
					tep_db_query('delete from ' . TABLE_QUICKBOOKS_TRANS . ' where qb_osc_id = "' . (int)$order_id . '"'); //QB Interface - delete order transaction for paypal preparing
          tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');

          tep_session_unregister('cart_PAY4LATER_ID');
        }
      }

      if ($order_total < $this->min_goods) {
	  
		  $selection = array('id' => $this->code,
					   'module' => $this->public_title,
					   'fields' => array(array('title' => '',
											   'field' => MODULE_PAYMENT_PAY4LATER_TEXT_SPEND_MORE_1 . round(($this->min_goods - $order_total),2) . MODULE_PAYMENT_PAY4LATER_TEXT_SPEND_MORE_2)
										)
						);
	  } else {
	  $finance_price = $order_total;
	  $finance_options = $finance->GetPriceOptions($finance_price);
$calc_div = '<div id="finance_calc" title="Finance Options Calculator" class="dialog">
    <table width="100%">
	<tr><td colspan="2">Spread the cost - choose the length of loan and how much to put down</td></tr>
	<tr height="10px"><td colspan="2">';
	foreach ($finance_options as $finance_option) {
		$calc_div .= "<input type='hidden' id='".$finance_option['code']."_min' value='".$finance_option['min_deposit']."'>\n";
		$calc_div .= "<input type='hidden' id='".$finance_option['code']."_max' value='".$finance_option['max_deposit']."'>\n";
	}
	$calc_div .= "</td></tr>\n";
    $calc_div .= "<tr><td>Spend:</td><td>&pound;<span id='spend'>$finance_price</span></td>\n";
    $calc_div .= "<tr><td>Option:</td><td><select id='finance_option'>\n";
	foreach ($finance_options as $finance_option) {
		$calc_div .= "<option value='".$finance_option['code']."'>".$finance_option['text']."</option>\n";
	}
$calc_div .= '	</select></td></tr>
	<tr><td>Deposit:</td><td><select id="depositpc"></select>&#37; &pound;<input type="text" size="6" id="depositamt"></td></tr>
	<tr><td>Loan amount:</td><td>&pound;<span id="loan"></span></td></tr>
	<tr><td>Monthly repayment:</td><td>&pound;<span id="monthly"></span></td></tr>
	<tr><td>Term:</td><td><span id="term"></span> months</td></tr>
	<tr><td>Total repayable:</td><td>&pound;<span id="repay"></span></td></tr>
	<tr><td>Rate of interest:</td><td><span id="rate"></span>&#37; Fixed</td></tr>
	<tr><td colspan="2"><span id="apr"></span>&#37; APR Representative</td></tr>
	<tr height="10px"><td colspan="2"></td></tr>
    <tr><td colspan="2"><div class="pay4later_confirm">'.MODULE_PAYMENT_PAY4LATER_TEXT_BUTTON.'</div></td></tr>
    <tr><td colspan="2">Available rates and deposits depend on spend.</td></tr>
    </table>
</div>';
		  $selection = array('id' => $this->code,
					   'module' => $this->public_title,
					   'fields' => array(array('title' => $calc_div,
											   'field' => '<div class="pay4later_button">'.MODULE_PAYMENT_PAY4LATER_TEXT_BUTTON.'</div>'.'<b><i>Please note:</i></b> we must send your order to the address used in your finance application.<br>Review your order on the next page &amp; on confirmation we\'ll transfer you to apply online for credit.'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_OPTION,
											   'field' => '<span id="option">'.(isset($finance_application['option_text'])?$finance_application['option_text']:'').'</span>'. tep_draw_hidden_field('pay4later_option_id', (isset($finance_application['finance_code'])?$finance_application['finance_code']:''),'id="pay4later_option_id"'). tep_draw_hidden_field('pay4later_option_text', (isset($finance_application['option_text'])?$finance_application['option_text']:''),'id="pay4later_option_text"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_DEPOSIT,
											   'field' => '<span id="deposit">'.(isset($finance_application['deposit'])?$finance_application['deposit']:'').'</span>'. tep_draw_hidden_field('pay4later_deposit', (isset($finance_application['deposit'])?$finance_application['deposit']:''),'id="pay4later_deposit"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_LOAN,
											   'field' => '<span id="loan_amt">'.(isset($finance_application['loan'])?$finance_application['loan']:'').'</span>'. tep_draw_hidden_field('pay4later_loan', (isset($finance_application['loan'])?$finance_application['loan']:''),'id="pay4later_loan"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_MONTHLY,
											   'field' => '<span id="monthly_amt">'.(isset($finance_application['monthly'])?$finance_application['monthly']:'').'</span>'. tep_draw_hidden_field('pay4later_monthly', (isset($finance_application['monthly'])?$finance_application['monthly']:''),'id="pay4later_monthly"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_TERM,
											   'field' => '<span id="loan_term">'.(isset($finance_application['term'])?$finance_application['term']:'').'</span>'. tep_draw_hidden_field('pay4later_term', (isset($finance_application['term'])?$finance_application['term']:''),'id="pay4later_term"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_REPAYABLE,
											   'field' => '<span id="total">'.(isset($finance_application['total'])?$finance_application['total']:'').'</span>'. tep_draw_hidden_field('pay4later_total', (isset($finance_application['total'])?$finance_application['total']:''),'id="pay4later_total"')),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_APR,
											   'field' => '<span id="loan_apr">'.(isset($finance_application['apr'])?$finance_application['apr'].'&#37':'').'</span>'. tep_draw_hidden_field('pay4later_apr', (isset($finance_application['apr'])?$finance_application['apr']:''),'id="pay4later_apr"'))
										)
					   );
	  }	   
	  return $selection;
    }

    function pre_confirmation_check() {
      global $cartID, $cart;

      if (empty($cart->cartID)) {
        $cartID = $cart->cartID = $cart->generate_cart_id();
      }

      if (!tep_session_is_registered('cartID')) {
        tep_session_register('cartID');
      }
    }

    function confirmation() {
      global $cartID, $cart_PAY4LATER_ID, $customer_id, $languages_id, $order, $order_total_modules, $finance_application;

      if (tep_session_is_registered('cartID')) {
        $insert_order = false;

        if (tep_session_is_registered('cart_PAY4LATER_ID')) {
          $order_id = substr($cart_PAY4LATER_ID, strpos($cart_PAY4LATER_ID, '-')+1);

          $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
          $curr = tep_db_fetch_array($curr_check);

          if ( ($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_PAY4LATER_ID, 0, strlen($cartID))) ) {
            $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

            if (tep_db_num_rows($check_query) < 1) {
							tep_db_query('delete from ' . TABLE_QUICKBOOKS_TRANS . ' where qb_osc_id = "' . (int)$order_id . '"'); //QB Interface - delete order transaction for paypal preparing
              tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
            }

            $insert_order = true;
          }
        } else {
          $insert_order = true;
        }

        if ($insert_order == true) {
          $order_totals = array();
          if (is_array($order_total_modules->modules)) {
            reset($order_total_modules->modules);
            while (list(, $value) = each($order_total_modules->modules)) {
              $class = substr($value, 0, strrpos($value, '.'));
              if ($GLOBALS[$class]->enabled) {
                for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                  if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                    $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                            'title' => $GLOBALS[$class]->output[$i]['title'],
                                            'text' => $GLOBALS[$class]->output[$i]['text'],
                                            'value' => $GLOBALS[$class]->output[$i]['value'],
                                            'sort_order' => $GLOBALS[$class]->sort_order);
                  }
                }
              }
            }
          }

          $sql_data_array = array('customers_id' => $customer_id,
                                  'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                                  'customers_company' => $order->customer['company'],
                                  'customers_street_address' => $order->customer['street_address'],
                                  'customers_suburb' => $order->customer['suburb'],
                                  'customers_city' => $order->customer['city'],
                                  'customers_postcode' => $order->customer['postcode'],
                                  'customers_state' => $order->customer['state'],
                                  'customers_country' => $order->customer['country']['title'],
                                  'customers_telephone' => $order->customer['telephone'],
                                  'customers_email_address' => $order->customer['email_address'],
                                  'customers_address_format_id' => $order->customer['format_id'],
                                  'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                                  'delivery_company' => $order->delivery['company'],
                                  'delivery_street_address' => $order->delivery['street_address'],
                                  'delivery_suburb' => $order->delivery['suburb'],
                                  'delivery_city' => $order->delivery['city'],
                                  'delivery_postcode' => $order->delivery['postcode'],
                                  'delivery_state' => $order->delivery['state'],
                                  'delivery_country' => $order->delivery['country']['title'],
                                  'delivery_address_format_id' => $order->delivery['format_id'],
                                  'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                                  'billing_company' => $order->billing['company'],
                                  'billing_street_address' => $order->billing['street_address'],
                                  'billing_suburb' => $order->billing['suburb'],
                                  'billing_city' => $order->billing['city'],
                                  'billing_postcode' => $order->billing['postcode'],
                                  'billing_state' => $order->billing['state'],
                                  'billing_country' => $order->billing['country']['title'],
                                  'billing_address_format_id' => $order->billing['format_id'],
                                  'payment_method' => $order->info['payment_method'],
                                  'cc_type' => $order->info['cc_type'],
                                  'cc_owner' => $order->info['cc_owner'],
                                  'cc_number' => $order->info['cc_number'],
                                  'cc_expires' => $order->info['cc_expires'],
                                  'finance_code' => $order->info['finance_code'],
                                  'finance_deposit' => $order->info['finance_deposit'],
                                  'date_purchased' => 'now()',
                                  'orders_status' => (MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
                                  'currency' => $order->info['currency'],
                                  'currency_value' => $order->info['currency_value']);

          tep_db_perform(TABLE_ORDERS, $sql_data_array);

          $insert_id = tep_db_insert_id();

/*		  $sql_data_array = array('orders_id' => $insert_id,
								  'orders_status_id' => MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID,
								  'date_added' => 'now()',
								  'customer_notified' => '0',
								  'comments' => MODULE_PAYMENT_PAY4LATER_TEXT_OPTION.' '.$_POST['pay4later_option_text'].' : '.MODULE_PAYMENT_PAY4LATER_TEXT_DEPOSIT.' '.$_POST['pay4later_deposit']);

		  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array); */
	
					//QB Interface - record create order transaction
					qb_order_transaction($insert_id);

          for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'title' => $order_totals[$i]['title'],
                                    'text' => $order_totals[$i]['text'],
                                    'value' => $order_totals[$i]['value'],
                                    'class' => $order_totals[$i]['code'],
                                    'sort_order' => $order_totals[$i]['sort_order']);

            tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
          }

          for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'products_id' => tep_get_prid($order->products[$i]['id']),
                                    'products_model' => $order->products[$i]['model'],
                                    'products_name' => $order->products[$i]['name'],
                                    'products_price' => $order->products[$i]['price'],
                                    'final_price' => $order->products[$i]['final_price'],
                                    'products_tax' => $order->products[$i]['tax'],
                                    'products_quantity' => $order->products[$i]['qty']);

            tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

            $order_products_id = tep_db_insert_id();

            $attributes_exist = '0';
            if (isset($order->products[$i]['attributes'])) {
              $attributes_exist = '1';
              for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                if (DOWNLOAD_ENABLED == 'true') {
                  $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
                  $attributes = tep_db_query($attributes_query);
                } else {
                  $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                }
                $attributes_values = tep_db_fetch_array($attributes);

                $sql_data_array = array('orders_id' => $insert_id,
                                        'orders_products_id' => $order_products_id,
                                        'products_options' => $attributes_values['products_options_name'],
                                        'products_options_values' => $attributes_values['products_options_values_name'],
                                        'options_values_price' => $attributes_values['options_values_price'],
                                        'price_prefix' => $attributes_values['price_prefix']);

                tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                  $sql_data_array = array('orders_id' => $insert_id,
                                          'orders_products_id' => $order_products_id,
                                          'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                          'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                          'download_count' => $attributes_values['products_attributes_maxcount']);

                  tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                }
              }
            }
          }

          $cart_PAY4LATER_ID = $cartID . '-' . $insert_id;
          tep_session_register('cart_PAY4LATER_ID');
        }
      }

		$return = array('id' => $this->code,
					   'module' => $this->public_title,
					   'fields' => array(
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_OPTION,
											   'field' => '<span id="option">'.(isset($finance_application['option_text'])?$finance_application['option_text']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_DEPOSIT,
											   'field' => '<span id="deposit">'.(isset($finance_application['deposit'])?'&pound;'.$finance_application['deposit']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_LOAN,
											   'field' => '<span id="loan_amt">'.(isset($finance_application['loan'])?'&pound;'.$finance_application['loan']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_MONTHLY,
											   'field' => '<span id="monthly_amt">'.(isset($finance_application['monthly'])?'&pound;'.$finance_application['monthly']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_TERM,
											   'field' => '<span id="loan_term">'.(isset($finance_application['term'])?$finance_application['term']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_REPAYABLE,
											   'field' => '<span id="total">'.(isset($finance_application['total'])?'&pound;'.$finance_application['total']:'').'</span>'),
										 array('title' => MODULE_PAYMENT_PAY4LATER_TEXT_APR,
											   'field' => '<span id="loan_apr">'.(isset($finance_application['apr'])?$finance_application['apr'].'&#37':'').'</span>')
						)
					);
      return $return;
    }

    function xx_process_button() {
      return false;
    }

    function process_button() {
      global $customer_id, $order, $sendto, $currencies, $cart_PAY4LATER_ID, $shipping, $finance, $finance_application;

      $process_button_string = '';
      $parameters = $finance->GetParams();
      $parameters['Identification[RetailerUniqueRef]'] = substr($cart_PAY4LATER_ID, strpos($cart_PAY4LATER_ID, '-')+1);
      $parameters['Finance[Code]'] = $finance_application['finance_code'];
      $parameters['Finance[Deposit]'] = $finance_application['deposit'];
	  
		for ($i = 0; $i < sizeof($order->products); $i++) {
			$parameters['Goods['.$i.'][Description]'] = $order->products[$i]['name'];
			$parameters['Goods['.$i.'][Quantity]'] = $order->products[$i]['qty'];
			$parameters['Goods['.$i.'][Price]'] = number_format($currencies->calculate_price($order->products[$i]['final_price'],$order->products[$i]['tax']),2, '.', ''); //each inc VAT
		}
		if ($order->info['shipping_cost'] > 0) {
			$parameters['Goods['.$i.'][Description]'] = 'Shipping ';
			$parameters['Goods['.$i.'][Quantity]'] = 1;
			$parameters['Goods['.$i.'][Price]'] = $this->format_raw($order->info['shipping_cost']); //each inc VAT
		}

/*		
      if (is_numeric($sendto) && ($sendto > 0)) {
        $parameters['address_override'] = '1';
        $parameters['first_name'] = $order->delivery['firstname'];
        $parameters['last_name'] = $order->delivery['lastname'];
        $parameters['address1'] = $order->delivery['street_address'];
        $parameters['city'] = $order->delivery['city'];
        $parameters['state'] = tep_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']);
        $parameters['zip'] = $order->delivery['postcode'];
        $parameters['country'] = $order->delivery['country']['iso_code_2'];
      } else {
        $parameters['no_shipping'] = '1';
        $parameters['first_name'] = $order->billing['firstname'];
        $parameters['last_name'] = $order->billing['lastname'];
        $parameters['address1'] = $order->billing['street_address'];
        $parameters['city'] = $order->billing['city'];
        $parameters['state'] = tep_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']);
        $parameters['zip'] = $order->billing['postcode'];
        $parameters['country'] = $order->billing['country']['iso_code_2'];
      }
      if (tep_not_null(MODULE_PAYMENT_PAY4LATER_PAGE_STYLE)) {
        $parameters['page_style'] = MODULE_PAYMENT_PAY4LATER_PAGE_STYLE;
      }

*/
	  reset($parameters);
	  while (list($key, $value) = each($parameters)) {
	  	$process_button_string .= tep_draw_hidden_field($key, $value);
	  }

      return $process_button_string;
    }

    function before_process() {
      global $customer_id, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, $cart, $cart_PAY4LATER_ID, $finance_application;
      global $$payment;

      $order_id = substr($cart_PAY4LATER_ID, strpos($cart_PAY4LATER_ID, '-')+1);

      $check_query = tep_db_query("select orders_status from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
      if (tep_db_num_rows($check_query)) {
        $check = tep_db_fetch_array($check_query);

        if ($check['orders_status'] == MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID) {

			// took out a conditional order status history insert

        }
      }

      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', last_modified = now() where orders_id = '" . (int)$order_id . "'");

      $sql_data_array = array('orders_id' => $order_id,
                              'orders_status_id' => MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID,
                              'date_added' => 'now()',
                              'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                              'comments' => MODULE_PAYMENT_PAY4LATER_TEXT_OPTION . ' ' . $finance_application['option_text'] . ' ' . MODULE_PAYMENT_PAY4LATER_TEXT_DEPOSIT . ' ' . $finance_application['deposit'] . ' ' . MODULE_PAYMENT_PAY4LATER_TEXT_DECISION . ': ' . $finance_application['decision']);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

	if ($order->info['comments'] <> '') {
	
      $sql_data_array = array('orders_id' => $order_id,
                              'orders_status_id' => MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID,
                              'date_added' => 'now()',
                              'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                              'comments' => $order->info['comments']);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

	}

// initialized for the email confirmation
      $products_ordered = '';
      $subtotal = 0;
      $total_tax = 0;

// begin product bundles
  function reduce_bundle_stock($bundle_id, $qty_sold) {
    $bundle_query = tep_db_query('select pb.subproduct_id, pb.subproduct_qty, p.products_bundle, p.products_quantity from ' . TABLE_PRODUCTS_BUNDLES . ' pb, ' . TABLE_PRODUCTS . ' p where p.products_id = pb.subproduct_id and bundle_id = ' . (int)tep_get_prid($bundle_id));
    while ($bundle_info = tep_db_fetch_array($bundle_query)) {
      if ($bundle_info['products_bundle'] == 'yes') {
        reduce_bundle_stock($bundle_info['subproduct_id'], ($qty_sold * $bundle_info['subproduct_qty']));
        // update quantity of nested bundle sold
        tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', ($qty_sold * $bundle_info['subproduct_qty'])) . " where products_id = " . (int)$bundle_info['subproduct_id']); 
      } else {
        $bundle_stock_left = $bundle_info['products_quantity'] - ($qty_sold * $bundle_info['subproduct_qty']);
        tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = " . (int)$bundle_stock_left . ", products_ordered = products_ordered + " . (int)($qty_sold * $bundle_info['subproduct_qty']) . " where products_id = " . (int)$bundle_info['subproduct_id']);
        if ( ($bundle_stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = " . (int)$bundle_info['subproduct_id']);
        }
      }
    }
  } 
//JAF bundled prods proper stock
	$bundles = array();	
  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
//JAF bundled prods proper stock
		$bundle_query = tep_db_query("SELECT bundle_id FROM ".TABLE_PRODUCTS_BUNDLES." WHERE subproduct_id = '".tep_get_prid($order->products[$i]['id']) . "'");
		while ($bundle = tep_db_fetch_array($bundle_query)) {
			$bundles[] = $bundle['bundle_id'];
		}
// Stock Update - Joao Correia
    if (STOCK_LIMITED == 'true') {
      if (DOWNLOAD_ENABLED == 'true') {
        $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename, products_bundle
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
        $products_attributes = $order->products[$i]['attributes'];
        if (is_array($products_attributes)) {
          $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
        }
        $stock_query = tep_db_query($stock_query_raw);
      } else {
        $stock_query = tep_db_query("select products_quantity, products_bundle from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
      }
      if (tep_db_num_rows($stock_query) > 0) {
        $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
        if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
          if ($stock_values['products_bundle'] == 'yes') {
            reduce_bundle_stock($order->products[$i]['id'], $order->products[$i]['qty']);
//JAF bundled prods proper stock
/*            $stock_left = 1; // products_quantity has no meaning for bundles but must be at least one for bundle to sell, bundle quantity check is done by other means
          } else {
            $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty']; */
          }
//JAF bundled prods proper stock
          $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty']; 
        } else {
          $stock_left = $stock_values['products_quantity'];
        }
        tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
        if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
        }
      }
    }
//JAF bundled prods proper stock
	if (count($bundles) >0) {
		$bundles = array_unique($bundles);
		foreach ($bundles as $id) {
			set_bundle_quantity($id);
		}
	}	
  // end product bundles

// Update products_ordered (for bestsellers list)
        tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

//------insert customer choosen option to order--------
        $attributes_exist = '0';
        $products_ordered_attributes = '';
        if (isset($order->products[$i]['attributes'])) {
          $attributes_exist = '1';
          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
            if (DOWNLOAD_ENABLED == 'true') {
              $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "'
                                   and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . $languages_id . "'
                                   and poval.language_id = '" . $languages_id . "'";
              $attributes = tep_db_query($attributes_query);
            } else {
              $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
            }
            $attributes_values = tep_db_fetch_array($attributes);

            $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
          }
        }
//------insert customer choosen option eof ----
        $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
        $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
        $total_cost += $total_products_price;

        $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
      }

// lets start with the email confirmation
      $email_order = STORE_NAME . "\n" .
                     EMAIL_SEPARATOR . "\n" .
                     EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
                     EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
                     EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
      if ($order->info['comments']) {
        $email_order .= tep_db_output($order->info['comments']) . "\n\n";
      }
      $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
                      EMAIL_SEPARATOR . "\n" .
                      $products_ordered .
                      EMAIL_SEPARATOR . "\n";

      for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
      }

      if ($order->content_type != 'virtual') {
        $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                        EMAIL_SEPARATOR . "\n" .
                        tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
      }

      $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                      EMAIL_SEPARATOR . "\n" .
                      tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

      if (is_object($$payment)) {
        $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                        EMAIL_SEPARATOR . "\n";
        $payment_class = $$payment;
        $email_order .= $payment_class->title . "\n\n";
        if ($payment_class->email_footer) {
          $email_order .= $payment_class->email_footer . "\n\n";
        }
      }

      tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

// send emails to other people
      if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }

// load the after_process function from the payment modules
      $this->after_process();

      $cart->reset(true);

// unregister session variables used during checkout
      tep_session_unregister('sendto');
      tep_session_unregister('billto');
      tep_session_unregister('shipping');
      tep_session_unregister('payment');
      tep_session_unregister('comments');

      tep_session_unregister('cart_PAY4LATER_ID');
	  tep_session_unregister('finance_application');

      tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
    }

    function after_process() {

      return false;
    }

    function output_error() {
      return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAY4LATER_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      $check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Application [Pay4Later]' limit 1");

      if (tep_db_num_rows($check_query) < 1) {
        $status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
        $status = tep_db_fetch_array($status_query);

        $status_id = $status['status_id']+1;

        $languages = tep_get_languages();

        foreach ($languages as $lang) {
          tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Application [Pay4Later]')");
        }

        $flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
        if (tep_db_num_rows($flags_query) == 1) {
          tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
        }
      } else {
        $check = tep_db_fetch_array($check_query);

        $status_id = $check['orders_status_id'];
      }

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pay4Later Credit Applications', 'MODULE_PAYMENT_PAY4LATER_STATUS', 'False', 'Do you want to enable Pay4Later credit finance applications?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit Processing E-Mail Address', 'MODULE_PAYMENT_PAY4LATER_EMAILS', '".STORE_OWNER_EMAIL_ADDRESS."', 'Send credit application notification emails to this address', '6', '3', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('BackOffice API', 'MODULE_PAYMENT_PAY4LATER_GATEWAY_SERVER', 'Test', 'Use the testing or live BackOffice API for transactions?', '6', '5', 'tep_cfg_select_option(array(\'Live\', \'Test\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Test API Key', 'MODULE_PAYMENT_PAY4LATER_TEST_API_KEY', '', 'Your unique API key from Pay4Later Test BackOffice installations page', '6', '7', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Test Installation Id', 'MODULE_PAYMENT_PAY4LATER_TEST_INSTALLATION_ID', '', 'The Installation Id for this website from Pay4Later Test BackOffice installations page', '6', '9', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Live API Key', 'MODULE_PAYMENT_PAY4LATER_LIVE_API_KEY', '', 'Your unique API key from Pay4Later Live BackOffice installations page', '6', '11', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Live Installation Id', 'MODULE_PAYMENT_PAY4LATER_LIVE_INSTALLATION_ID', '', 'The Installation Id for this website from Pay4Later Live BackOffice installations page', '6', '13', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAY4LATER_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '15', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAY4LATER_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '17', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Preparing Order Status', 'MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID', '" . $status_id . "', 'Set the status of prepared orders made with this payment module to this value', '6', '19', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Acknowledged Order Status', 'MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '21', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Debug E-Mail Address', 'MODULE_PAYMENT_PAY4LATER_DEBUG_EMAIL', '', 'All parameters of an Invalid IPN notification will be sent to this email address if one is entered.', '6', '27', now())");
	  for ($i = 1; $i <= $this->no_options ; $i++) {
		  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Finance Option $i', 'MODULE_PAYMENT_PAY4LATER_OPTION_$i', '', 'List in order short to long terms. Format is productcode|goods min|goods max|deposit min|deposit max|description', '6', '30', now())");
	  }
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      $return = array('MODULE_PAYMENT_PAY4LATER_STATUS',
'MODULE_PAYMENT_PAY4LATER_EMAILS',
'MODULE_PAYMENT_PAY4LATER_GATEWAY_SERVER',
'MODULE_PAYMENT_PAY4LATER_TEST_API_KEY',
'MODULE_PAYMENT_PAY4LATER_TEST_INSTALLATION_ID',
'MODULE_PAYMENT_PAY4LATER_LIVE_API_KEY',
'MODULE_PAYMENT_PAY4LATER_LIVE_INSTALLATION_ID',
'MODULE_PAYMENT_PAY4LATER_SORT_ORDER',
'MODULE_PAYMENT_PAY4LATER_ZONE',
'MODULE_PAYMENT_PAY4LATER_PREPARE_ORDER_STATUS_ID',
'MODULE_PAYMENT_PAY4LATER_ORDER_STATUS_ID',
'MODULE_PAYMENT_PAY4LATER_DEBUG_EMAIL');
	  for ($i = 1; $i <= $this->no_options ; $i++) {
	  	$return[] = 'MODULE_PAYMENT_PAY4LATER_OPTION_'.$i;
	  }
	  return $return;
    }

// format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
      global $currencies, $currency;

      if (empty($currency_code) || !$this->is_set($currency_code)) {
        $currency_code = $currency;
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }
  }
?>
