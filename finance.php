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

//  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_FINANCE);
  require(DIR_WS_CLASSES . 'finance_options.php');

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_FINANCE));
  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<div class="contentContainer">
  <div class="contentText">
    <p>Spread the cost of your purchase over a year at no additional cost (Interest Free Credit), or opt for low monthly payments and take up to three years to pay it off (Low Rate Credit).</p>
<?php
	$show_egs = true;
	if ($show_egs) {
		$finance = new FinanceOptions();
		$options = $finance->GetInterestFreeOptions(300);
//		$no_options = count($options);
		$no_options = 1;
//		echo var_dump($options);
?>
    <h2>Interest Free Credit at 0&#37;</h2>
    <p>Interest Free Credit is available:
       <ul>
         	<li>for 6 months on purchases over &pound;300 with a deposit from 15&#37 to 50&#37;</li>
         	<li>for 12 months on purchases over &pound;500 with a deposit from 33&#37 to 50&#37;</li>
       </ul>
    </p>
<table border="0" class="infobox" cellspacing="0" cellpadding="3">
          	<tr><td class="infoBoxHeading" colspan="<?php echo ($no_options * 4); ?>" align="center">Representative example of<br> Interest Free Credit
             on &pound;300 <br>monthly payments &pound;<?php echo $options[0]['monthly_repayment']; ?></td></tr>
            <tr>
<?php 	for ($i = 0; $i < no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxHeading" align="center" colspan="2">
                	<?php echo $options[$i]['term'] . ' Months ' . $options[$i]['rate_of_interest']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right"></td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Purchase Price
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['goods_spend']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Deposit
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['deposit']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Loan amount
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['loan_amount']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Monthly payment
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['monthly_repayment']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Agreement length
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['term']; ?> months
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Total repayable
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['total_repayable']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Interest rate (fixed)
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['apr']; ?>&#37;
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" align="left" colspan="2">
                	- <?php echo $options[$i]['apr']; ?>&#37; APR Representative
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
          </table>
<?php
		$options = $finance->GetInterestFreeOptions(500);
		$no_options = count($options);
?>          
          <table border="0" cellspacing="0" cellpadding="3">
          	<tr><td class="infoBoxHeading" colspan="<?php echo ($no_options * 4); ?>" align="center">Representative examples of Interest Free Credit<br>
             on &pound;500, monthly payments from &pound;<?php echo $finance->GetMonthlyFrom(); ?></td></tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxHeading" align="center" colspan="2">
                	<?php echo $options[$i]['term'] . ' Months ' . $options[$i]['rate_of_interest']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right"></td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Purchase Price
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['goods_spend']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Deposit
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['deposit']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Loan amount
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['loan_amount']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Monthly payment
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['monthly_repayment']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Agreement length
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['term']; ?> months
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Total repayable
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['total_repayable']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Interest rate (fixed)
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['apr']; ?>&#37;
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" align="left" colspan="2">
                	- <?php echo $options[$i]['apr']; ?>&#37; APR Representative
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
          </table>
    <h2>Low Rate Credit</h2>
    <p>Low Rate Credit is available:
            <ul>
            	<li>for periods from 12 to 36 months on purchases over &pound;280 with a deposit from 10&#37 to 50&#37;</li>
            </ul></p>
<?php          
		$options = $finance->GetLowRateOptions(500);
		$no_options = count($options);
//		echo var_dump($options);
?>
<table border="0" cellspacing="0" cellpadding="3">
          	<tr><td class="infoBoxHeading" colspan="<?php echo ($no_options * 4); ?>" align="center">Representative examples of Low Rate Credit<br>
             on a purchase of &pound;500, monthly payments from &pound;<?php echo $finance->GetMonthlyFrom(); ?></td></tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxHeading" align="center" colspan="2">
                	<?php echo $options[$i]['term'] . ' Months ' . $options[$i]['rate_of_interest']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right"></td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Purchase Price
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['goods_spend']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Deposit
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['deposit']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Loan amount
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['loan_amount']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Monthly payment
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['monthly_repayment']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Agreement length
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['term']; ?> months
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Total repayable
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	&pound;<?php echo $options[$i]['total_repayable']; ?>
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" width="140" align="left">
                	- Interest rate (fixed)
                </td>
				<td class="infoBoxContents" width="70" align="right">
                	<?php echo $options[$i]['apr']; ?>&#37;
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
            <tr>
<?php 	for ($i = 0; $i < $no_options; $i++) { ?>
				<td class="infoBoxContents" width="10" align="right"></td>
				<td class="infoBoxContents" align="left" colspan="2">
                	- <?php echo $options[$i]['apr']; ?>&#37; APR Representative
                </td>
				<td class="infoBoxContents" width="10" align="right">
                </td>
<?php 	} ?>
			</tr>
          </table>
<?php } // end of if show finance options
            ?>
     <h2>FAQ</h2>
     <div class="FAQ">
       <h3>How can I apply for finance?</h3>
       <p>Paying with finance is now available as one of the payment options in checkout, to pay for the whole of your order. You can consider the options for finance, choose the plan and deposit amount you want, then when you confirm your order we&rsquo;ll pass you straight to Pay4Later to make your application.</p>
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

  <div class="buttonSet">
    <div class="text-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'fa fa-angle-right', tep_href_link(FILENAME_DEFAULT)); ?></div>
  </div>
</div>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>