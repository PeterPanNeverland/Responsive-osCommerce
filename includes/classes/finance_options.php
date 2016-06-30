<?php
/*
	Credit Applications via Pay4Later
	
	Copyright 2014 sewebsites.biz JohnAtYM
	
	Released under the GNU General Public License
*/

class FinanceOptions {

	private $min_goods, $min_monthly;
	private $options;
	private $product_options = array();
	private $test_product_options = array('ONIF12','ONIB36-19.5');
	private $live_product_options = array('ONIF12','ONIB36-19.9');
	private $interest_free = array('ONIF6','ONIF12');
	private $low_rate = array();
	private $test_low_rate = array('ONIB12-19.5','ONIB18-19.5','ONIB24-19.5','ONIB36-19.5');
	private $live_low_rate = array('ONIB12-19.9','ONIB18-19.9','ONIB24-19.9','ONIB36-19.9');
	private $ApiKey;
	private $InstallationId;
	private $base_url;
	private $form_action_url;
	private $calc_url;
	private $script_url;

	function __construct() {
		//use settings from payment module...
//      $this->finance_enabled = ((MODULE_PAYMENT_PAY4LATER_STATUS == 'True') ? true : false);
	  $this->options = array(); $this->min_goods = 0;
	  $i = 1;
	  while (defined('MODULE_PAYMENT_PAY4LATER_OPTION_'.$i)) {
	  	$work_option = constant('MODULE_PAYMENT_PAY4LATER_OPTION_'.$i);
		$workvalues = explode("|",$work_option);
		if (count($workvalues)>1){ // unpack option if it's set
			$load = array(
				'code' => $workvalues[0],
				'min_goods' => $workvalues[1],
				'max_goods' => $workvalues[2],
				'min_deposit' => $workvalues[3],
				'max_deposit' => $workvalues[4],
				'text' => $workvalues[5]
			);
			$this->options[] = $load;
			if ($this->min_goods == 0 || $load['min_goods'] < $this->min_goods) $this->min_goods = $load['min_goods'];
		}
		$i++;
	  }
      if (MODULE_PAYMENT_PAY4LATER_GATEWAY_SERVER == 'Live') { 
        $this->base_url = 'https://secure.pay4later.com/';
		$this->ApiKey = MODULE_PAYMENT_PAY4LATER_LIVE_API_KEY;
		$this->InstallationId = MODULE_PAYMENT_PAY4LATER_LIVE_INSTALLATION_ID;
		$this->product_options = $this->live_product_options;
		$this->low_rate = $this->live_low_rate;
      } else {
        $this->base_url = 'https://test.pay4later.com/';
		$this->ApiKey = MODULE_PAYMENT_PAY4LATER_TEST_API_KEY;
		$this->InstallationId = MODULE_PAYMENT_PAY4LATER_TEST_INSTALLATION_ID;
		$this->product_options = $this->test_product_options;
		$this->low_rate = $this->test_low_rate;
      }
	  $this->form_action_url = $this->base_url.'credit_app/';
	  $this->calc_url = $this->base_url.'js_api/borrow';
	  $this->script_url = $this->base_url.'js_api/FinanceDetails.js.php?api_key='.$this->ApiKey;
	}
	
	public function GetMinOrder() {
	  return $this->min_goods;
	}
	
	public function GetMonthlyFrom() {
	  return $this->min_monthly;
	}
	
	public function GetParams() {
	  return array('Identification[api_key]' => $this->ApiKey,
                          'Identification[InstallationID]' => $this->InstallationId
					);
	}
	
	public function GetProductPayMonthly($price) { // return the cheapest monthly payment for this spend
		// get the last (longest) option on assumption it's lowest montly outlay
		$use = end($this->options);
		if ($price >= $use['min_goods']) {
			// and use the largest deposit, to get the smallest per month
			$response = $this->GetOption($price,$use['code'],round(($price*$use['max_deposit'])));
			return $response['monthly_repayment'];
		} else return false;
	}
	
	public function GetPriceOptions($price) { // return the valid finance options for this spend
		$return = array();
		reset($this->options);
	  	foreach ($this->options as $option) {
			if ($price >= $option['min_goods'] && $price <= $option['max_goods']) {
				$return[] = $option;
			}
		}
		return $return;
	}
	
	public function GetAllOptions() {
		return $this->options;
	}
	
	public function GetProductOptions($price) {
	  $return = array();
	  if ($price >= $this->min_goods) {
	    reset($this->options); $this->min_monthly = 0;
	  	foreach ($this->options as $option) {
		  if (in_array($option['code'],$this->product_options)) {
		  	$info = $this->StandardInformation($price,$option['code'],round(($price*$option['min_deposit']/100)));
			if ($this->min_monthly == 0 || $info['monthly_repayment'] < $this->min_monthly) $this->min_monthly = $info['monthly_repayment'];
			$return[] = $info;
		  } 
		}
	  }
	  return $return;
	}
	
	public function GetInterestFreeOptions($price) {
		return $this->GetRateOptions($price,$this->interest_free);
	}
	
	public function GetLowRateOptions($price) {
		return $this->GetRateOptions($price,$this->low_rate);
	}
	
	public function GetJsApiScript() {
		return $this->script_url;
	}

	private function GetRateOptions($price,$options_array) {
	  $return = array();
	  if ($price >= $this->min_goods) {
	    reset($this->options); $this->min_monthly = 0;
	  	foreach ($this->options as $option) {
		  if (in_array($option['code'],$options_array)) {
		  	$info = $this->StandardInformation($price,$option['code'],round(($price*$option['min_deposit']/100)));
			if ($this->min_monthly == 0 || $info['monthly_repayment'] < $this->min_monthly) $this->min_monthly = $info['monthly_repayment'];
			$return[] = $info;
		  } 
		}
	  }
	  return $return;
	}
	
	private function StandardInformation($price,$code,$deposit) {
		$return = array();
	  	$response = $this->GetOption($price,$code,$deposit);
		$return['goods_spend'] = $response['goods_spend'];
		$return['loan_amount'] = $response['loan_amount'];
		$return['term'] = $response['term'];
		$return['deposit'] = $response['deposit'];
		$return['monthly_repayment'] = $response['monthly_repayment'];
		$return['total_repayable'] = $response['total_cost']+$response['deposit'];
		$return['rate_of_interest'] = $response['rate_of_interest'];
		$return['apr'] = $response['apr'];
		return $return;
	}

	private function GetOption($price,$code,$deposit) {
		$call = $this->calc_url.'?api_key='.$this->ApiKey.'&goods_spend='.$price.'&deposit='.$deposit.'&finance_product='.$code;
		$response = array();
		$curlSession = curl_init();
		curl_setopt($curlSession, CURLOPT_URL, $call);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_USERAGENT, "Pay4Later HTTP Post");
//		curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, 1);
		$curl_response = curl_exec($curlSession);
		$response = json_decode($curl_response,true);
		return $response;
	}
}

?>