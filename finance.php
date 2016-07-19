<?php
/*
  $Id: finance.php 
	
	pay4later finance page
	author @BrockleyJohn john@sewebsites.net

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/
  define('FILENAME_FINANCE','finance.php');
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_FINANCE);
  require(DIR_WS_CLASSES . 'finance_options.php');
	$finance = new FinanceOptions();

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_FINANCE));
  require(DIR_WS_INCLUDES . 'template_top.php');
	
	$finance_price = 1000;
?>
<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<div class="contentContainer">
<style type="text/css" scoped>
	div.pay4later_button {
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
		box-shadow: 10px 10px 5px #888888;
	}
	div.pay4later_button span {
		font-size:large;
		font-weight:bolder;
	}
	#finance_calc td {
		font-family: Verdana,Arial,sans-serif;
		font-size: 11px;
		line-height: 1.5;
	}
	#calc.ui-dialog-content { visibility:hidden; width:0px; font-size:1px; }
	.ui-dialog-title {font-size:small;}
</style>
  <div class="contentText">
    <p>Spread the cost of your purchase over a year at no additional cost (Interest Free Credit), or opt for low monthly payments and take up to three years to pay it off (Low Rate Credit).</p>
    <div class="row">
<?php
	$show_egs = true;
	if ($show_egs) {
//		$no_options = count($options);
//		echo var_dump($options);
?>
  <style scoped>
	  .credit-eg { font-size:12px; padding-left:0; padding-right:0; }
	  .eg-header { font-size:14px; }
		.eg-value { text-align:right; }
		.eg-label {padding-left:30px; }
		.eg-body, .eg-value { padding-right:0; }
		em { font-weight: bold; font-size:13px; }
	</style>
  <div class="col-sm-12">
    <h2>Interest Free Credit at 0&#37;</h2>
    <p>Interest Free Credit is available:
       <ul>
         	<li>for 6 months on purchases over &pound;300 with a deposit from 15&#37 to 50&#37;</li>
         	<li>for 12 months on purchases over &pound;500 with a deposit from 33&#37 to 50&#37;</li>
       </ul>
    </p>
    <h3>Representative Examples of Interest Free Credit</h3>
  </div>
  <div class="col-sm-12">
<?php
    function eg_row($label,$value,$span = false) {
		  if (! $span) {
			  return '        <div class="eg-datum row col-xs-12">
          <div class="eg-label col-xs-8">'.$label.'</div>
          <div class="eg-value col-xs-4">'.$value.'</div>
        </div>
';
			} else {
			  return '        <div class="eg-datum row col-xs-12">
          <div class="eg-label col-xs-12">'.$label.'</div>
        </div>
';
			}
		}
		$options = $finance->GetInterestFreeOptions(300);
		$no_options = 1;
	 	for ($i = 0; $i < $no_options; $i++) { 		
?>
    <div class="credit-eg col-sm-4 col-xs-12 panel panel-default">
      <div class="eg-header panel-heading text-center">Credit on <em>&pound;300</em> over <?php echo $options[$i]['term']; ?> months<br class="hidden-xs"/> <?php echo '<em>&pound;'.$options[$i]['monthly_repayment'].' / month</em> : '.$options[$i]['rate_of_interest'] ; ?></div>
      <div class="eg-body row"> 
        <?php echo eg_row('Purchase Price','&pound;'.$options[$i]['goods_spend']); ?>
        <?php echo eg_row('Deposit','&pound;'.$options[$i]['deposit']); ?>
        <?php echo eg_row('Loan amount','&pound;'.$options[$i]['loan_amount']); ?>
        <?php echo eg_row('Monthly payment','&pound;'.$options[$i]['monthly_repayment']); ?>
        <?php echo eg_row('Agreement length',$options[$i]['term'].' month'); ?>
        <?php echo eg_row('Total repayable','&pound;'.$options[$i]['total_repayable']); ?>
        <?php echo eg_row('Interest rate (fixed)',$options[$i]['apr'].'&#37;'); ?>
        <?php echo eg_row($options[$i]['apr'].'&#37; APR Representative','',true); ?>
      </div>
    </div>
<?php } ?>
<?php
		$options = $finance->GetInterestFreeOptions(500);
		$no_options = count($options);
	 	for ($i = 0; $i < $no_options; $i++) { 		
?>
    <div class="credit-eg col-sm-4 col-xs-12 panel panel-default">
      <div class="eg-header panel-heading text-center">Credit on <em>&pound;500</em> over <?php echo $options[$i]['term']; ?> months<br class="hidden-xs"/> <?php echo '<em>&pound;'.$options[$i]['monthly_repayment'].' / month</em> : '.$options[$i]['rate_of_interest'] ; ?></div>
      <div class="eg-body row"> 
        <?php echo eg_row('Purchase Price','&pound;'.$options[$i]['goods_spend']); ?>
        <?php echo eg_row('Deposit','&pound;'.$options[$i]['deposit']); ?>
        <?php echo eg_row('Loan amount','&pound;'.$options[$i]['loan_amount']); ?>
        <?php echo eg_row('Monthly payment','&pound;'.$options[$i]['monthly_repayment']); ?>
        <?php echo eg_row('Agreement length',$options[$i]['term'].' month'); ?>
        <?php echo eg_row('Total repayable','&pound;'.$options[$i]['total_repayable']); ?>
        <?php echo eg_row('Interest rate (fixed)',$options[$i]['apr'].'&#37;'); ?>
        <?php echo eg_row($options[$i]['apr'].'&#37; APR Representative','',true); ?>
      </div>
    </div>
<?php } ?>
  </div>
  <div class="col-sm-12">
    <h2>Low Rate Credit</h2>
    <p>Low Rate Credit is available:
            <ul>
            	<li>for periods from 12 to 36 months on purchases over &pound;280 with a deposit from 10&#37 to 50&#37;</li>
            </ul></p>
    <h3>Representative Examples of Low Rate Credit</h3>
  </div>
  <div class="col-sm-12">
<?php          
		$options = $finance->GetLowRateOptions(500);
		$no_options = count($options);
//		echo var_dump($options);
	 	for ($i = 0; $i < $no_options; $i++) { 		
?>
    <div class="credit-eg col-sm-4 col-xs-12 panel panel-default">
      <div class="eg-header panel-heading text-center">Credit on <em>&pound;500</em> over <?php echo $options[$i]['term']; ?> months<br class="hidden-xs"/> <?php echo '<em>&pound;'.$options[$i]['monthly_repayment'].' / month</em> : '.$options[$i]['rate_of_interest'] ; ?></div>
      <div class="eg-body row"> 
        <?php echo eg_row('Purchase Price','&pound;'.$options[$i]['goods_spend']); ?>
        <?php echo eg_row('Deposit','&pound;'.$options[$i]['deposit']); ?>
        <?php echo eg_row('Loan amount','&pound;'.$options[$i]['loan_amount']); ?>
        <?php echo eg_row('Monthly payment','&pound;'.$options[$i]['monthly_repayment']); ?>
        <?php echo eg_row('Agreement length',$options[$i]['term'].' month'); ?>
        <?php echo eg_row('Total repayable','&pound;'.$options[$i]['total_repayable']); ?>
        <?php echo eg_row('Interest rate (fixed)',$options[$i]['apr'].'&#37;'); ?>
        <?php echo eg_row($options[$i]['apr'].'&#37; APR Representative','',true); ?>
      </div>
    </div>
<?php } ?>

<?php } // end of if show finance options
            ?>
	  <div class="pay4later_button"><span>Finance Calculator</span><br>More Info
    </div>
     <h2>FAQ</h2>
     <div class="FAQ">
       <h3>How can I apply for finance?</h3>
       <p>Paying with finance will soon be available as one of the payment options in checkout, to pay for the whole of your order. In the meantime you can place your order over the phone or in the shop and we'll process the application for you.</p>
<!--       <p>Paying with finance is now available as one of the payment options in checkout, to pay for the whole of your order. You can consider the options for finance, choose the plan and deposit amount you want, then when you confirm your order we&rsquo;ll pass you straight to Pay4Later to make your application.</p> -->
     </div>
     <div class="FAQ">
       <h3>Will I qualify for finance?</h3>
       <p>Before your application is considered, you need to be able to answer 'yes' to these questions:
            <ul>
            <li>Can you afford the monthly repayments?</li>
            <li>Are you over 18?</li>
            <li>Do you work more than 16 hours per week?</li>
            <li>Do you have a good credit history? (No late payments / debt relief orders / CCJs / IVA's / bankruptcies)</li>
            <li>Are you a permanent UK resident?</li>
            </ul>
            Pay4Later will process your application. A credit check will be performed and your application assessed based on the check.
            </p>
            <p>If your application is not successful, you can cancel your order or choose another method of payment.</p>
     </div>
     <div class="FAQ">
       <h3>Who provides the finance?</h3>
       <p>We are authorised by the FCA (Financial Conduct Authority) as a credit broker. Our consumer credit service is provided by Pay4Later in association with Omni Capital Retail Finance. Pay4Later is licensed by the Financial Conduct Authority (Consumer Credit Licence: 0616240). For more information please refer to Pay4Later's <a
href="http://www.pay4later.com/consumerfaq">frequently asked questions</a> or visit <a href="http://www.pay4later.com/">http://www.pay4later.com/</a>.</p>
<p>Omni Capital Retail Finance is authorised and regulated by the Financial Conduct Authority, licensed by the Office of Fair Trading and a member of the Finance &amp;
Leasing Association. For more information please visit <a href="http://www.omnicapitalretailerfinance.co.uk/">http://www.omnicapitalretailerfinance.co.uk/</a></p>
     </div>

   </div>
   </div>

  </div>

  <div class="buttonSet">
    <div class="text-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'fa fa-angle-right', tep_href_link(FILENAME_DEFAULT)); ?></div>
  </div>
</div>

<?php 
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
	
// get rid of the clash between bootstrap and jquery ui
var bootstrapButton = $.fn.button.noConflict() // return $.fn.button to previously assigned value
$.fn.bootstrapBtn = bootstrapButton            // give $().bootstrapBtn the Bootstrap functionality

}); //end of document ready function
//--></script>
<div id="finance_calc" title="Finance Options Calculator" class="dialog">
<style type="text/css" scoped>
  #finance_calc { font-size:12px; }
/* .ui-state-default .ui-icon {
        background-image: url("ext/jquery/ui/redmond/images/ui-icons_2e83ff_256x240.png");
} */
</style>
    <table width="100%">
<?php 
	echo "<tr><td colspan='2'>Spread the cost of your order - choose the length of loan and how much to put down</td></tr>\n";
	$finance_options = $finance->GetPriceOptions($finance_price);
	echo "<tr height='10px'><td colspan='2'>";
	foreach ($finance_options as $finance_option) {
		echo "<input type='hidden' id='".$finance_option['code']."_min' value='".$finance_option['min_deposit']."'>\n";
		echo "<input type='hidden' id='".$finance_option['code']."_max' value='".$finance_option['max_deposit']."'>\n";
	}
	echo "</td></tr>\n";
    echo "<tr><td>Spend:</td><td>&pound;<span id='spend'>$finance_price</span></td>\n";
    echo "<tr><td>Option:</td><td><select id='finance_option'>\n";
	foreach ($finance_options as $finance_option) {
		echo "<option value='".$finance_option['code']."'>".$finance_option['text']."</option>\n";
	}
?>
	</select></td></tr>
	<tr><td>Deposit:</td><td><select id='depositpc'></select>&#37; &pound;<input type='text' size='6' id='depositamt'></td></tr>
	<tr><td>Loan amount:</td><td>&pound;<span id='loan'></span></td></tr>
	<tr><td>Monthly repayment:</td><td>&pound;<span id='monthly'></span></td></tr>
	<tr><td>Term:</td><td><span id='term'></span> months</td></tr>
	<tr><td>Total repayable:</td><td>&pound;<span id='repay'></span></td></tr>
	<tr><td>Rate of interest:</td><td><span id='rate'></span>&#37; Fixed</td></tr>
	<tr><td colspan='2'><span id='apr'></span>&#37; APR Representative</td></tr>
<!-- 	<tr><td colspan='2' align="right"><button id="recalc">Recalculate</button></td></tr> -->
	<tr height='10px'><td colspan='2'></td></tr>
    <tr><td colspan='2'>Available rates and deposits depend on spend. Apply for finance during checkout.</td></tr>
    </table>
<?php
	echo "</div>\n";
?>
<script src="ext/jquery/ui/jquery-ui-1.10.4.min.js"></script>
<!-- <script
			  src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"
			  integrity="sha256-tp8VZ4Y9dg702r7D6ynzSavKSwB9zjariSZ4Snurvmw="
			  crossorigin="anonymous"></script> -->
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>