<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!tep_session_is_registered('shipping')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

// Stock Check
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $cart->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (tep_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
        break;
      }
    }
  }

// if no billing destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('billto')) {
    tep_session_register('billto');
    $billto = $customer_default_address_id;
  } else {
// verify the selected billing address
    if ( (is_array($billto) && empty($billto)) || is_numeric($billto) ) {
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$billto . "'");
      $check_address = tep_db_fetch_array($check_address_query);

      if ($check_address['total'] != '1') {
        $billto = $customer_default_address_id;
        if (tep_session_is_registered('payment')) tep_session_unregister('payment');
      }
    }
  }

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  if (!tep_session_is_registered('comments')) tep_session_register('comments');
  if (isset($HTTP_POST_VARS['comments']) && tep_not_null($HTTP_POST_VARS['comments'])) {
    $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
  }

  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();

// load all enabled payment modules
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment;

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PAYMENT);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

  require(DIR_WS_INCLUDES . 'template_top.php');
?>
<style type="text/css">
	div.pay4later_button, div.pay4later_confirm {
		position:relative;
		float:right;
		text-align:center;
		background-color:#6666CC;
		color:#FFFFFF;
		padding:10px;
		margin-right:10px;
		margin-bottom:10px;
		border-radius:10px;
		cursor:pointer;
		box-shadow: 7px 7px 3px #888888;
	}
	#finance_calc td {
		font-family: Verdana,Arial,sans-serif;
		font-size: 11px;
		line-height: 1.5;
	}
	#calc.ui-dialog-content { visibility:hidden; width:0px; font-size:1px; }
	.ui-dialog-title {font-size:small;}
</style>
<?php $finance = new FinanceOptions();
echo '<script type="text/javascript" src="'.$finance->GetJsApiScript().'"></script>';
?><script><!--
function jqid( myid ) {
    return "#" + myid.replace( /(:|\.|\[|\])/g, "\\$1" );
}
function depositSelect(chosenOpt) {
	var deposits = [];
	var minDeposit = $(jqid(chosenOpt + "_min")).val()*1;// frig to make a number
	var maxDeposit = $(jqid(chosenOpt + "_max")).val()*1;// ditto
	for (var i = minDeposit; i <= maxDeposit; i = i+5) {
		deposits.push('<option value="',i,'">',i,'</option>');
//		alert("add deposit " + i);
	}
	return deposits;
}
$(document).ready(function() {

	$( "#finance_calc" ).dialog({
		autoOpen: false,
    open: function() {
        $(this).closest(".ui-dialog")
        .find(".ui-dialog-titlebar-close")
//        .removeClass("ui-dialog-titlebar-close")
 //       .addClass("pull-right")
 //       .html("<span class='ui-button-icon-primary ui-icon ui-icon-close'></span>");
        .html("<span style='color:#000; position:relative; top: -6px;'>x</span>");
    },
		title: "Finance Options Calculator",
		width : 360,
		height : 400,
		position : { my: "center", at: "center" }
	});

	$('div.pay4later_confirm').click(function() {
		var chosenCode = $("#finance_option").val();
		var chosenOpt = $("#finance_option option[value='"+chosenCode+"']").text();
		var depositAmt = $("#depositamt").val();
		var loan = $("#loan").text();
		var monthly = $("#monthly").text();
		var term = $("#term").text();
		var repay = $("#repay").text();
		var apr = $("#apr").text();
		$("#pay4later_option_id").val(chosenCode);
		$("#option").text(chosenOpt);
		$("#pay4later_option_text").val(chosenOpt);
		$("#deposit").text("\u00A3"+depositAmt);
		$("#pay4later_deposit").val(depositAmt);
		$("#loan_amt").text("\u00A3"+loan);
		$("#pay4later_loan").val(loan);
		$("#monthly_amt").text("\u00A3"+monthly);
		$("#pay4later_monthly").val(monthly);
		$("#loan_term").text(term);
		$("#pay4later_term").val(term);
		$("#total").text("\u00A3"+repay);
		$("#pay4later_total").val(repay);
		$("#loan_apr").text(apr+'%');
		$("#pay4later_apr").val(apr);
		$('input[name=payment][value=pay4later]').prop("checked", true);
		$("#finance_calc").dialog('close');
	});

	$('div.pay4later_button').click(function() {
		var chosenOpt = $("#finance_option").val();
		var deposits = depositSelect(chosenOpt);
		var productVal = $("#spend").text();
	    $("#depositpc").html(deposits.join(''));
		var depositpc = $("#depositpc").val()*1;// frig to make a number
//		alert("Finance Calc params: '" + chosenOpt + "','" + productVal + "','" + depositpc + "',0");
		var fd_obj = new FinanceDetails(chosenOpt, productVal, depositpc, 0);
		$("#finance_calc").setfromObj(fd_obj);
		$("#finance_calc").dialog('open');
	});

	$( "#finance_option" ).change(function() {
		var productVal = $("#spend").text();
		var chosenOpt = this.value;
//		alert("Option changed to " + chosenOpt + ", for spend of " + productVal);
		var deposits = depositSelect(chosenOpt);
	    $("#depositpc").html(deposits.join(''));
		var depositpc = $("#depositpc").val()*1;// frig to make a number
//		alert("Finance Calc params: '" + chosenOpt + "','" + productVal + "','" + depositpc + "',0");
		var fd_obj = new FinanceDetails(chosenOpt, productVal, depositpc, 0);
		$("#finance_calc").setfromObj(fd_obj);
	});
	
	$("#depositpc").change(function() {
		var chosenOpt = $("#finance_option").val();
		var productVal = $("#spend").text();
		var depositpc = $("#depositpc").val()*1;// frig to make a number
		var depositamt = productVal * depositpc;
		$("#depositamt").val(depositamt);
//		alert("Finance Calc params: '" + chosenOpt + "','" + productVal + "','" + depositpc + "',0");
		var fd_obj = new FinanceDetails(chosenOpt, productVal, depositpc, 0);
		$("#finance_calc").setfromObj(fd_obj);
	});
	
	$("#depositamt").change(function() {
		var chosenOpt = $("#finance_option").val();
		var productVal = $("#spend").text();
		var depositamt = $("#depositamt").val()*1;// frig to make a number
		var fd_obj = new FinanceDetails(chosenOpt, productVal, 0, depositamt);
		$("#finance_calc").setfromObj(fd_obj);
	});
	
	$.fn.setfromObj = function(fd_obj) { //set the dialog values from p4l calc return object
		$("#depositpc").val(fd_obj.d_pc);
		$("#depositamt").val(fd_obj.d_amount);
		$("#loan").text(fd_obj.l_amount);
		$("#monthly").text(fd_obj.m_inst);
		$("#term").text(fd_obj.term);
		$("#repay").text(fd_obj.total);
		$("#rate").text(fd_obj.apr);
		$("#apr").text(fd_obj.apr);
//		alert("Return from api: '" + fd_obj.d_pc + "','" + fd_obj.d_amount + "','" + fd_obj.l_amount + "','" + fd_obj.m_inst + "','" + fd_obj.term + "','" +fd_obj.total  + "','" + fd_obj.apr + "'"); 
//		$("#finance_calc").dialog('open');
	}

	$.fn.setDeposits = function(chosenOpt) { //clear and then populate deposit select for this option
		this.empty();
	//	this.find('option').remove().end();
		var minDeposit = $(jqid(chosenOpt + "_min")).val()*1;// frig to make a number
		var maxDeposit = $(jqid(chosenOpt + "_max")).val()*1;// ditto
		alert("Deposit from " + minDeposit + " to " + maxDeposit);
		for (var i = minDeposit; i <= maxDeposit; i = i+10) {
			this.append("<option></option>").attr("value", i).text(i);
		    alert("add deposit " + i);
		}
		this.val(minDeposit);
	};
/*	$('#finance_calc').css("visibility","visible"); */
	
}); //end of document ready function
//--></script>

<?php echo $payment_modules->javascript_validation(); ?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<?php echo tep_draw_form('checkout_payment', tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'), 'post', 'class="form-horizontal" onsubmit="return check_form();"', true); ?>

<div class="contentContainer">

<?php
  if (isset($HTTP_GET_VARS['payment_error']) && is_object(${$HTTP_GET_VARS['payment_error']}) && ($error = ${$HTTP_GET_VARS['payment_error']}->get_error())) {
?>

  <div class="contentText">
    <?php echo '<strong>' . tep_output_string_protected($error['title']) . '</strong>'; ?>

    <p class="messageStackError"><?php echo tep_output_string_protected($error['error']); ?></p>
  </div>

<?php
  }
?>

  <h2><?php echo TABLE_HEADING_BILLING_ADDRESS; ?></h2>

  <div class="contentText row">
    <div class="col-sm-8">
      <div class="alert alert-warning">
        <?php echo TEXT_SELECTED_BILLING_DESTINATION; ?>
        <div class="clearfix"></div>
        <div class="pull-right">
          <?php echo tep_draw_button(IMAGE_BUTTON_CHANGE_ADDRESS, 'fa fa-home', tep_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL')); ?>
        </div>
        <div class="clearfix"></div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="panel panel-primary">
        <div class="panel-heading"><?php echo TITLE_BILLING_ADDRESS; ?></div>
        <div class="panel-body">
          <?php echo tep_address_label($customer_id, $billto, true, ' ', '<br />'); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="clearfix"></div>

  <h2><?php echo TABLE_HEADING_PAYMENT_METHOD; ?></h2>

<?php
  $selection = $payment_modules->selection();

  if (sizeof($selection) > 1) {
?>

  <div class="contentText">
    <div class="alert alert-warning">
      <div class="row">
        <div class="col-xs-8">
          <?php echo TEXT_SELECT_PAYMENT_METHOD; ?>
        </div>
        <div class="col-xs-4 text-right">
          <?php echo '<strong>' . TITLE_PLEASE_SELECT . '</strong>'; ?>
        </div>
      </div>
    </div>
  </div>


<?php
    } else {
?>

  <div class="contentText">
    <div class="alert alert-info"><?php echo TEXT_ENTER_PAYMENT_INFORMATION; ?></div>
  </div>

<?php
    }
?>

  <div class="contentText">

    <table class="table table-striped table-condensed table-hover">
      <tbody>
<?php
  $radio_buttons = 0;
  for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
?>
      <tr class="table-selection">
        <td><strong><?php echo $selection[$i]['module']; ?></strong></td>
        <td align="right">

<?php
    if (sizeof($selection) > 1) {
      echo tep_draw_radio_field('payment', $selection[$i]['id'], ($selection[$i]['id'] == $payment), 'required aria-required="true"');
    } else {
      echo tep_draw_hidden_field('payment', $selection[$i]['id']);
    }
?>

        </td>
      </tr>

<?php
    if (isset($selection[$i]['error'])) {
?>

      <tr>
        <td colspan="2"><?php echo $selection[$i]['error']; ?></td>
      </tr>

<?php
    } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
?>

      <tr>
        <td colspan="2"><table border="0" cellspacing="0" cellpadding="2">

<?php
      for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
?>

          <tr>
            <td><?php echo $selection[$i]['fields'][$j]['title']; ?></td>
            <td><?php echo $selection[$i]['fields'][$j]['field']; ?></td>
          </tr>

<?php
      }
?>

        </table></td>
      </tr>

<?php
    }
?>



<?php
    $radio_buttons++;
  }
?>
      </tbody>
    </table>

  </div>

  <hr>

  <div class="contentText">
    <div class="form-group">
      <label for="inputComments" class="control-label col-sm-4"><?php echo TABLE_HEADING_COMMENTS; ?></label>
      <div class="col-sm-8">
        <?php
        echo tep_draw_textarea_field('comments', 'soft', 60, 5, $comments, 'id="inputComments" placeholder="' . TABLE_HEADING_COMMENTS . '"');
        ?>
      </div>
    </div>
  </div>

  <div class="buttonSet">
    <div class="text-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'fa fa-angle-right', null, 'primary', null, 'btn-success'); ?></div>
  </div>

  <div class="clearfix"></div>

  <div class="contentText">
    <div class="stepwizard">
      <div class="stepwizard-row">
        <div class="stepwizard-step">
          <a href="<?php echo tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>"><button type="button" class="btn btn-default btn-circle">1</button></a>
          <p><a href="<?php echo tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>"><?php echo CHECKOUT_BAR_DELIVERY; ?></a></p>
        </div>
        <div class="stepwizard-step">
          <button type="button" class="btn btn-primary btn-circle">2</button>
          <p><?php echo CHECKOUT_BAR_PAYMENT; ?></p>
        </div>
        <div class="stepwizard-step">
          <button type="button" class="btn btn-default btn-circle" disabled="disabled">3</button>
          <p><?php echo CHECKOUT_BAR_CONFIRMATION; ?></p>
        </div>
      </div>
    </div>
  </div>

</div>

</form>
<script src="ext/jquery/ui/jquery-ui-1.10.4.min.js"></script>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
