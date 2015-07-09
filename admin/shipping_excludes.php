<?php
/*
  $Id: shipping_excludes.php,
	v 0.1 2015/07/01 outline

  admin panel for exclude products from shipping methods 
	
	Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright 2015 osCommerce

  Released under the GNU General Public License
	
	******************************************************************
	
  Functionality to exclude particular products from any specified shipping method
	(with excludes built in)

  Used for:
	this product is too big/heavy for this shipping method
	explosives may not be sent in the mail
	
	*******************************************************************
	
	Implementation
	
	Single table for excludes - maps products_id to shipping class 
	Column(s) in admin/categories (status-style) to add/remove exclusions one product at a time
	Column(s) controlled by config var set in this panel
	Batches of exclusions manipulated in this panel
	Piece of logic to insert into shipping module(s) quote method
	content module to display warning message on product info page
	
	*******************************************************************
	
*/
 
  $ws_msg = '';
  require('includes/application_top.php');

	require_once(DIR_WS_FUNCTIONS . 'shipping_excludes.php');
	if (tep_shipping_exclude_db_check()) $messageStack->add(MSG_DB_DONE, 'success');

	require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
	
  /********************** BEGIN VERSION CHECKER *********************/
  if (file_exists(DIR_WS_FUNCTIONS . 'version_checker.php'))
  {
  require(DIR_WS_LANGUAGES . $language . '/version_checker.php');
  require(DIR_WS_FUNCTIONS . 'version_checker.php');
  $contribPath = 'http://addons.oscommerce.com/info/....';
  $currentVersion = 'osC Adwords V 0.1';
  $contribName = 'osC Adwords V'; 
  $versionStatus = '';
  }
  /********************** END VERSION CHECKER *********************/
  

  /********************** PROCESS OPTIONS *********************/
	
	if (isset($_POST['mode_change']) && $_POST['mode_change']=='Y') $mode_changed = true; else $mode_changed = false;
	$mode_change = 'Y';
	if (isset($_GET['mode'])) {
		$mode = $_GET['mode'];
	} elseif (isset($_POST['mode'])) {
		$mode = $_POST['mode'];
	} else {
		$mode = 'ad_groups';
	}
	
	if (isset($_POST['prodmode'])) {
		$prodmode = $_POST['prodmode'];
	} elseif (isset($_GET['prodmode'])) {
		$prodmode = $_GET['prodmode'];
	} else {
		$prodmode = 'catalog';
	}
	
  $ad_id = ( !empty( $_POST['ad_id'] ) ? tep_db_input( $_POST['ad_id'] ) : ( !empty( $_GET['aID'] ) ? tep_db_input( $_GET['aID'] ) : '' ) );

  $action = ( !empty( $_POST['action'] ) ? tep_db_input( $_POST['action'] ) : ( !empty( $_GET['action'] ) ? tep_db_input( $_GET['action'] ) : '' ) );

	if (isset($_POST['cPath']))	{
	  $cPath = $_POST['cPath'];
		$current_category_id = $cPath;
	} elseif (isset($_GET['cPath']))	$cPath = $_GET['cPath'];
	if (isset($_GET['sort'])) {
		$sort = $_GET['sort'];
	} elseif ($mode == 'unmatched' || $mode == 'deleted') {
		$sort = 'name';
	} elseif ($mode == 'recent') {
		$sort = 'recent';
	} else {
		$sort = 'sort';
	}

	if (isset($_GET['active'])) {
		$active = $_GET['active'];
	} else {
		$active = 'only';
	}
	if (isset($_GET['unmatched']) && !$mode_changed) {
		$unmatched = $_GET['unmatched'];
	} elseif ($mode == 'unmatched') {
		$unmatched = 'only';
	} else {
		$unmatched = 'all';
	}
	
	if (isset($_GET['search'])) $search = $_GET['search'];
	elseif (isset($_POST['search'])) $search = $_POST['search'];
	else $search = false;
	
  if (isset($_POST['update_button']) ) // modules form confirmed
  {
		$sql_data_array = array ( 
	//		'ad_name' => $_POST['ad_name'], // fixed by google
			'ad_description' => $_POST['ad_description'],
			'ad_active' => $_POST['ad_active']
		);
		
        tep_db_perform('adwords_groups', $sql_data_array, 'update', "ad_id = '" . (int)$ad_id . "'");
		
		foreach ($_POST['label_value'] as $prod_id => $label_value) {
			if (!empty($prod_id)) {
				$sql_data_array = array ( 
					'label_value' => $label_value
				);
				tep_db_perform('adwords_groups_products', $sql_data_array, 'update', "ad_id = '" . (int)$ad_id . "' AND products_id ='".(int)$prod_id."'");
//				$ws_msg .= 'Label value updated for product with id '.$prod_id.'<br>';
			}
		}	

		$ws_msg .= TEXT_MSG_UPDATED . '<br>';
		
	/*	if ($_POST['action'] == 'insert') {
      tep_db_perform('adwords_groups', $sql_data_array);
		} else {
      tep_db_perform('adwords_groups', $sql_data_array, 'update', "ad_id = '" . (int)$ad_id . "'");
		} */
  }  
  elseif (isset($_POST['action']) && $_POST['action'] == 'delprod') // remove clicked product from ad group
  {
		tep_db_query ('DELETE FROM adwords_groups_products WHERE products_id ='.(int)$_POST['prod_id'].' AND ad_id='.(int)$ad_id);
		$ws_msg .= sprintf(TEXT_MSG_VALUE_DELETED,$_POST['prod_id']).'<br>';
		$action = 'edit';
	}
  elseif (isset($_POST['products_button'])) // products table confirmed
//  elseif (isset($_POST['action']) && $_POST['action'] == 'edit') // products table confirmed
  {
		if (is_array($_POST['toggles']) && count($_POST['toggles'])>0) {
			$ws_tmp = '';
			foreach ($_POST['toggles'] as $prod_id) {
				$ws_tmp .= $prod_id . ' ';
	
				$query = tep_db_query ('SELECT COUNT(*) AS total FROM adwords_groups_products WHERE products_id ='.(int)$prod_id.' AND ad_id='.(int)$ad_id);
				$row = tep_db_fetch_array($query);
				$in_group = ($row['total'] == 0 ? false : true);
				if($in_group) {
					tep_db_query ('DELETE FROM adwords_groups_products WHERE products_id ='.(int)$prod_id.' AND ad_id='.(int)$ad_id);
				} else {
					$sql_data_array = array ( 
						'ad_id' => (int)$ad_id,
						'products_id' => (int)$prod_id
					);
					tep_db_perform('adwords_groups_products', $sql_data_array);
				}
			}
			$ws_msg .= sprintf(TEXT_MSG_TOGGLE,$ws_tmp).'<br>';
		} else {
			$ws_msg .= TEXT_MSG_NO_TOGGLE.'<br>';
		}
		$action = 'edit';
  }  
/*  elseif (isset($_POST['action']) && $_POST['action'] == 'toggleskip')
  {
		if ($qb_id = qb_toggleskip_product($_POST['prod_id'])) {
			$ws_msg .= 'Toggled rules flag for product with id ' . $_POST['prod_id'] . ' and QB id ' . $qb_id . '<br>';
		} else {
			$ws_msg .= 'Problem unmatching product with id ' . $_POST['prod_id'] . '<br>';
		}
  }  
  elseif (isset($_POST['action']) && $_POST['action'] == 'review_stock')
  {
		if (qb_toggle_stock_review($_POST['prod_id'])) {
			$ws_msg .= 'Toggled stock review for product with id ' . $_POST['prod_id'] . '<br>';
		} else {
			$ws_msg .= 'Problem setting review for product with id ' . $_POST['prod_id'] . '<br>';
		}
  }  
  elseif (isset($_POST['action']) && $_POST['action'] == 'review_price')
  {
		if (qb_toggle_price_review($_POST['prod_id'])) {
			$ws_msg .= 'Toggled price review for product with id ' . $_POST['prod_id'] . '<br>';
		} else {
			$ws_msg .= 'Problem setting review for product with id ' . $_POST['prod_id'] . '<br>';
		}
  }  
  elseif (isset($_POST['action']) && $_POST['action'] == 'toggleupdate')
  {
		if (qb_toggle_update($_POST['prod_id'])) {
			$ws_msg .= 'Toggled update flag for product with id ' . $_POST['prod_id'] . '<br>';
		} else {
			$ws_msg .= 'Problem setting flag for product with id ' . $_POST['prod_id'] . '<br>';
		}
  }  
  elseif (isset($_POST['action']) && $_POST['action'] == 'quantity')
  {
			$sql_data_array = array('products_quantity' => tep_db_prepare_input($_POST['quantity']));
			tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . $_POST['prod_id'] . "'");
			$ws_msg .= 'Set qty to '.$_POST['quantity'].' for product with id ' . $_POST['prod_id'] . '<br>';
  }  */
  /********************** CHECK THE VERSION ***********************/
  elseif (isset($_POST['action']) && $_POST['action'] == 'getversion')
  {
    if (isset($_POST['version_check']) && $_POST['version_check'] == 'on')
      $versionStatus = AnnounceVersion($contribPath, $currentVersion, $contribName);
  } elseif (isset($_POST['action']) && $_POST['action'] <> 'edit')
  {
    $ws_msg .= sprintf(TEXT_DODGY_ACTION,$_POST['action']) . '<br>';
  }  
  else  // only bother with install checks if there's no action set
  {
//    uka_check_install();
  }
  
//  $ws_msg .= 'ad id: "'.$ad_id.'" action: "'.$action.'"<br>';
  require(DIR_WS_INCLUDES . 'template_top.php');
?>
<style type="text/css">
td.HTC_Head {font-family: Verdana, Arial, sans-serif; color: sienna; font-size: 18px; font-weight: bold; } 
td.HTC_subHead {font-family: Verdana, Arial, sans-serif; color: sienna; font-size: 12px; } 
.HTC_title {background: #f0f1f1; text-align: center;} 
/*.uk_postal_county, .uk_inst_town {color:#006600;}
.uk_postal_town {color:#006600; font-style:italic; }
.uk_inst_spec_town {color:#006600; font-style:italic; font-weight:bold }
.uk_add_trad_county, .uk_inst_trad_not {color:#0033FF; }
.uk_additional_county, .uk_inst_not_match { color:#990066}
.uk_unclass_county {color: #FF0000; font-weight:bold}
.uk_inst_not_list {color: #990066; font-weight:bold; font-style:italic}
.uk_list_not_inst {color: #FF0000; font-weight:bold} */
.popup
{
  color: yellow;
  cursor: pointer;
  text-decoration: none
}
.clicker {cursor:pointer;}
</style>
<script language="javascript">
$(document).ready(function() {

	$('#adprods td.clicker').click(function() {
			var productId = $(this).closest('tr').children('td::nth-child(1)').text();
			$("#prodId").val(productId);
			$("#action_field").val('delprod');
//			alert("Product id : " + productId);
			$("#group_form").submit();
  });

});

function confirmdelete(form, stuff)
{
 if (confirm(stuff + '?\r\n\r\n'))
  form.submit();
  
 return false;
}
</script>
<style type="text/css">
/*	.dialog td, .dialog th { font-size:x-small; }
	#QBdialog.ui-dialog-content .ID, .QBID { visibility:hidden; width:0px; font-size:1px; }
	.ui-dialog-title {font-size:small;} */
</style>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
     <tr>
      <td><table border="0" width="95%" cellspacing="0" cellpadding="0">
       <tr>
        <td><table border="0" width="95%" cellspacing="0" cellpadding="0">
         <tr>
          <td class="HTC_Head" valign="top"><?php echo HEADING_TITLE; ?></td>
         </tr>
         <tr>
          <td class="smallText" valign="top"><?php echo HEADING_TITLE_SUPPORT_THREAD; ?></td>
         </tr>
        </table></td>    
        <td><table border="0" width="100%">
         <tr>       
          <td class="smallText" align="right"><?php echo HEADING_TITLE_AUTHOR; ?></td>
         </tr>
</table></td>
       </tr>  
      </table></td>  
     </tr>
     <tr>
      <td colspan="3"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
     </tr>
     <tr>
      <td colspan="3" class="smallText" style="font-weight: bold; color: red;"><?php echo (strlen($ws_msg) > 0 ? $ws_msg : '<br>'); ?></td>
     </tr>
     <tr>
      <td colspan="3"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
     </tr>
     <tr>
      <td colspan="3"><?php echo tep_black_line(); ?></td>
     </tr>     
     <tr>
      <td class="HTC_subHead" colspan="3"><?php 
    echo tep_draw_form('mode', 'adwords_feed.php', '', 'post');
    echo TEXT_MODE . ' <input type="radio" name="mode" value="ad_groups" ' . ($mode=="ad_groups" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_GROUPS_MODE . ' '  . ' <input type="radio" name="mode" value="products" ' . ($mode=="products" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_PRODUCTS_MODE . ' ';
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('mode_change',$mode_change) . '</form>';
			echo (!in_array($mode,array('ad_groups','products')) ? TEXT_MODE_ERROR : '' ); 
			?></td>
     </tr>
 
     <!-- Beginning of item tables -->   
     <tr>
      <td align="right"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      	<tr>
        <!-- begin forms at top of table -->
<?php     
 switch ($mode) {
	case 'ad_groups' : 
?>        <td align="right" width="100%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr><td colspan="3" class="smallText"><b><?php echo HEADING_GROUPS_MODE.'</b><br><br>'.TEXT_DESC_GROUPS_MODE; ?></td>
<?php
				break;
/*	case 'catalog' : 
	case 'order' : 
?>        <td align="right" width="100%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td class="main" valign="top" width="30%"><?php
    echo tep_draw_form('goto', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
    echo tep_hide_session_id() . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('unmatched',$unmatched) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="15%"><?php
    echo tep_draw_form('unmatched', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_UNMATCHED . ' <input type="radio" name="unmatched" value="only" ' . ($unmatched=="only" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ONLY . ' ' . ' <input type="radio" name="unmatched" value="all" ' . ($unmatched=="all" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ALL;
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
<?php 	
	case 'unmatched' : 
	case 'recent' : 
	case 'deleted' : 
?>
          <td class="main" valign="top" width="15%"><?php
    echo tep_draw_form('active', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_ACTIVE . ' <input type="radio" name="active" value="only" ' . ($active=="only" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ONLY . ' ' . ' <input type="radio" name="active" value="all" ' . ($active=="all" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ALL;
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('unmatched',$unmatched) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="40%"><?php
    echo tep_draw_form('filter', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_FILTER . ' ' . tep_draw_input_field('qbFilter', $qbFilter, 'onChange="this.form.submit();"');
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('unmatched',$unmatched). tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
         </tr>
                  </table>
         </td><?php
		break; */
/*		case 'quickbooks' :
?>        <td align="right" width="100%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td class="main" valign="top" width="20%"><?php
    echo tep_draw_form('filter', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_FILTER . ' ' . tep_draw_input_field('qbFilter', $qbFilter, 'onChange="this.form.submit();"');
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('unmatched',$unmatched). tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="15%"><?php
    echo tep_draw_form('active', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_ACTIVE . ' <input type="radio" name="active" value="only" ' . ($active=="only" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ONLY . ' ' . ' <input type="radio" name="active" value="all" ' . ($active=="all" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ALL;
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('unmatched',$unmatched) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="15%"><?php
    echo tep_draw_form('unmatched', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_UNMATCHED . ' <input type="radio" name="unmatched" value="only" ' . ($unmatched=="only" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ONLY . ' ' . ' <input type="radio" name="unmatched" value="all" ' . ($unmatched=="all" ? 'checked ' : '') . 'onChange="this.form.submit();"' . '>' . TEXT_ALL;
    echo tep_hide_session_id() . tep_draw_hidden_field('cPath',$cPath) . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="30%"><?php
    echo tep_draw_form('goto', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
    echo tep_hide_session_id() . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('unmatched',$unmatched) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
          <td class="main" valign="top" width="20%"><?php
    echo tep_draw_form('search', 'adwords_feed.php', '', 'get');
    echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search', $search, 'onChange="this.form.submit();"');
    echo tep_hide_session_id() . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('unmatched',$unmatched) . tep_draw_hidden_field('order_id',$order_id) . '</form>';
?>
</td>
         </tr>
                  </table>
         </td><?php
		break; */
		default : ?>
        <td align="right" width="100%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td class="main" valign="top" width="30%"><?php echo TEXT_MODE_ERROR . " '".$mode."'"; ?></td>
         </tr>
        </table></td>
<?php		break;
} // end of switch mode for header forms
?></tr>
         <tr><td><?php
	if ($mode == 'catalog' || $mode == 'order' || $mode == 'unmatched' || $mode == 'recent' || $mode == 'deleted') { /*
?>          <table>
           <tr><td></td>
             <td valign="top"><?php echo tep_draw_form('match','adwords_feed.php','cPath='.$cPath.'&qbFilter='.$filter.'&mode='.$mode.'&sort='.$sort.'&active='.$active.'&unmatched='.$unmatched,'post','id="match_form"').tep_draw_hidden_field('action','','id="action_field"').tep_draw_hidden_field('prod_id','','id="prodId"').tep_draw_hidden_field('order_id',$order_id) . tep_draw_hidden_field('qb_id','','id="qbId"') . tep_draw_hidden_field('quantity','','id="qty"'); ?><table border="0" width="100%" cellspacing="0" cellpadding="2" id="prodscats">
              <tr class="dataTableHeadingRow"><?php $params = 'cPath='.$cPath.'&qbFilter='.$filter.'&mode='.$mode.'&active='.$active.'&unmatched='.$unmatched.'&sort='; ?>
				<td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'id' ? 'iddesc' : 'id'));?>">ID</a></td>
                <td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'name' ? 'namedesc' : 'name')).'">'.TABLE_HEADING_CATEGORIES_PRODUCTS; ?></a></td>
<td class="dataTableHeadingContent" align="center"></td>
<td class="dataTableHeadingContent" align="center"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'model' ? 'modeldesc' : 'model'));?>">Model</a></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_QUANTITY; ?></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRICE; ?></td>
<td class="dataTableHeadingContent" align="center">inc VAT</td>
<td class="dataTableHeadingContent" align="center">Shop</td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
<td class="dataTableHeadingContent" align="center">Special<br>rules</td>
<td class="dataTableHeadingContent" align="center">Review<br>qty</td>
<td class="dataTableHeadingContent" align="center">Review<br>price</td>
<td class="dataTableHeadingContent" align="center">QB</td>
<td class="dataTableHeadingContent" align="center">QB desc</td>
<td class="dataTableHeadingContent" align="center">QB price</td>
<td class="dataTableHeadingContent" align="center">QB qty</td>
<td class="dataTableHeadingContent" align="center">Upd flag</td>
<td class="dataTableHeadingContent" align="center">QB act</td>
              </tr>
<?php	
if ($mode == 'catalog') {
    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
} else {
	$categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' AND cd.categories_name='Bruce'"); // get an empty query to return
}

	$categories_count = 0;
	$rows = 0;
	
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;

      if ((!isset($_GET['cID']) && !isset($_GET['pID']) || (isset($_GET['cID']) && ($_GET['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }

      if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', tep_get_path($categories['categories_id'])) . '\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
      }
?>
				<td class="dataTableContent" align="left" width="20"><?php echo sprintf ($categories['categories_id']); ?></td>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('adwords_feed.php', tep_get_path($categories['categories_id']) . '&qbFilter='.$filter.'&mode='.$mode).'">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>&nbsp;<b>' . $categories['categories_name'] . '</b>'; ?></td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
              </tr>
<?php
    }

    $products_count = 0;
		
		switch ($sort) {
			case 'sort' : 
				if ($mode <> 'order') $sort_order = 'p2c.products_sort_order, pd.products_name';
				else $sort_order = 'pd.products_name';
				break;
			case 'iddesc' : 
				$desc = ' DESC';
			case 'id' : 
				$sort_order = 'p.products_id' . $desc;
				break;
			case 'namedesc' : 
				$desc = ' DESC';
			case 'name' : 
				$sort_order = 'pd.products_name' . $desc;
				break;
			case 'recent' : 
				$sort_order = 'q.qb_matched DESC';
				break;
			case 'modeldesc' : 
				$desc = ' DESC';
			case 'model' : 
				$sort_order = 'p.products_model' . $desc;
				break;
		}
		$prod_status = ($active == 'only' ? ' and p.products_status = 1' : '');
		$match_status = ($unmatched == 'only' ? ' and p.qb_list_id IS NULL' : '');

	if ($mode == 'catalog') { 
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id) left join " . TABLE_QUICKBOOKS_ITEMS . " q on p.qb_list_id = q.qb_list_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "'" . $prod_status . $match_status . " order by " . $sort_order);
	} elseif ($mode == 'unmatched') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id) left join " . TABLE_QUICKBOOKS_ITEMS . " q on p.qb_list_id = q.qb_list_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id ."'". $prod_status . $match_status . " order by " . $sort_order);
	} elseif ($mode == 'recent') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id), " . TABLE_QUICKBOOKS_ITEMS . " q, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.qb_list_id = q.qb_list_id and pd.language_id = '" . (int)$languages_id ."'". $prod_status . $match_status . " order by " . $sort_order);
	} elseif ($mode == 'deleted') {
		$query = "select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id), " . TABLE_QUICKBOOKS_ITEMS . " q, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.qb_list_id = q.qb_list_id and q.qb_deleted = 1 and pd.language_id = '" . (int)$languages_id ."'". " order by " . $sort_order;
        $products_query = tep_db_query($query);
	} elseif ($mode == 'order') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id) left join " . TABLE_QUICKBOOKS_ITEMS . " q on p.qb_list_id = q.qb_list_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_ORDERS_PRODUCTS . " op where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = op.products_id and op.orders_id = '" . (int)$order_id . "'" . $prod_status . $match_status . " order by " . $sort_order);
	}

    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;
			if ($products['qb_list_id'] <> NULL) { 
				if ($products['qb_quantity']<>$products['products_quantity'] && $products['qb_stock_special']<>1) $match = 'Unequal'; else $match = 'Matched'; 
			} else $match = 'Unmatched';

        echo '              <tr class="dataTableRow'.$match.'" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="return getQBid(this.form, ' . $products['products_id'] . ')">' . "\n";
?>
				<td class="dataTableContent" align="left" width="20"><?php echo sprintf ($products['products_id']); ?></td>
                <td class="dataTableContent"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent" align="left">
<?php echo '<a href="' . tep_href_link('product_info.php', 'products_id=' . $products['products_id']) . '" target="_blank">' . tep_image('../favicon.ico', ''); ?></a></td>
                <td class="dataTableContent" align="left">
<?php echo $products['products_model']; ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $products['products_quantity'].($match == 'Unequal'? ' <a>'.tep_image(DIR_WS_IMAGES.'icon_left.gif','','','', 'class="quantity" id="'.$products['products_id'].'"').'</a>' : ''); ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->format ($products['products_price']); ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id']));?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->format (jaf_get_shop_price($products['products_id'])); ?> </td>
                <td class="dataTableContent" align="center">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
<?php if ($products['qb_list_id'] <> NULL) { ?>
                <td class="dataTableContent" align="left"><input type="checkbox" 
<?php echo ($products['qb_stock_special']==1 ? 'checked' : ''); ?> class="special" /></td>
                <td class="dataTableContent" align="left"><input type="checkbox" 
<?php echo ($products['qb_review_stock']==1 ? 'checked' : ''); ?> class="stock" /></td>
                <td class="dataTableContent" align="left"><input type="checkbox" 
<?php echo ($products['qb_review_price']==1 ? 'checked' : ''); ?> class="price" /></td>
                <td class="dataTableContent" align="left">
<?php echo $products['qb_name']; ?> </td>
                <td class="dataTableContent" align="left">
<?php echo $products['qb_sales_description']; ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->format($products['qb_sales_price']);?> </td>
                <td class="dataTableContent" align="center">
<?php echo $products['qb_quantity']; ?> </td>
                <td class="dataTableContent" align="left"><input type="checkbox" 
<?php echo ($products['qb_updated']==1 ? 'checked' : ''); ?> class="updateflag" /></td>
                <td class="dataTableContent" align="center">
<?php
      if ($products['qb_active'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
<?php } else { ?>
	<td></td><td></td><td></td><td></td><td></td>
<?php } ?>
             </tr>
<?php      } ?>
		</table>

</form></td>
            </tr> 
           </table><?php */
} elseif ($mode == 'ad_groups') {		

// do page: either a list of ad groups or if one is selected, its details, a list of the products in it and underneath a catalog for adding more products
	
  if ( ($action == 'new') || ($action == 'edit') ) {
    $form_action = 'insert';
    if ( ($action == 'edit') && isset($_GET['aID']) ) {
      $form_action = 'update';

      $query = tep_db_query("select * from adwords_groups where ad_id = '" . (int)$ad_id . "'");
      $group = tep_db_fetch_array($query);

      $aInfo = new objectInfo($group);
    } else {
      $aInfo = new objectInfo(array());
    }
  }
?>
<?php echo tep_draw_form('ad_groups','adwords_feed.php',
			 (!empty($action)&&!empty($ad_id)?
			 	'action='.$action.'&aID='.$ad_id
			 :
			 	(!empty($action)?
					'action='.$action
				:
					(!empty($ad_id)?
						'aID='.$ad_id
					:''
					)
				)
			),'post','id="group_form"');
//						 echo tep_draw_hidden_field('action',$form_action,'id="action_field"') . tep_draw_hidden_field('search',$search) . tep_draw_hidden_field('ad_id',$ad_id,'id="adId"'); ?>
          <table border="0" width="100%" cellspacing="0" cellpadding="0">
           <tr>
             <td valign="top">

             <table border="0" width="100%" cellspacing="0" cellpadding="2" id="adgroups">
<?php
  if (empty($action)||($action <> 'new' && $action <> 'edit')) {
?>
              <tr class="dataTableHeadingRow">
							<?php $params = 'cPath='.$cPath.'&mode='.$mode.'&active='.$active.'&search='.$search.'&sort='; ?>
				<td class="dataTableHeadingContent ADID"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'adid' ? 'adiddesc' : 'adid'));?>">ID</a></td>
<td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'name' ? 'namedesc' : 'name')) . '>' . TABLE_HEADING_NAME; ?>"</a></td>
<td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'desc' ? 'descdesc' : 'desc')) . '>' . TABLE_HEADING_DESC; ?>"></a></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_ACTIVE; ?></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
              </tr><?php              
		if ($sort == 'name') $sortsql = 'ad_name'; 
		elseif ($sort == 'namedesc') $sortsql = 'ad_name DESC'; 
		elseif ($sort == 'descdesc') $sortsql = 'ad_description DESC'; 
		elseif ($sort == 'desc') $sortsql = 'ad_description'; 
		else $sortsql = 'ad_id';

$query = tep_db_query('SELECT * FROM adwords_groups ORDER BY '.$sortsql);
while ($items = tep_db_fetch_array($query)) { 

	if( ( !isset( $_GET['aID'] ) || ( isset( $_GET['aID'] ) && ( $_GET['aID'] == $items['ad_id'] ) ) ) && !isset( $aInfo ) && ( substr( $action, 0, 3 ) != 'new' ) ) {
		$aInfo = new objectInfo($items);
	}

	if (isset($aInfo) && is_object($aInfo) && ($items['ad_id'] == $aInfo->ad_id) ) {
		echo '                  <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', 'aID=' . $aInfo->ad_id . '&action=edit') . '\'">' . "\n";
	} else {
		echo '                  <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', 'aID=' . $items['ad_id']) . '\'">' . "\n";
	}
?>
<td class="dataTableContent ADID"><?php echo $items['ad_id'];?></td>
<td class="dataTableContent"><?php echo $items['ad_name'];?></td>
<td class="dataTableContent"><?php echo $items['ad_description'];?></td>
<td class="dataTableContent" align="center"><?php
      if ($items['ad_active'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
<?php $subquery = tep_db_query('SELECT COUNT(products_id) as total FROM adwords_groups_products WHERE ad_id ='.(int)$items['ad_id']);
	$count = tep_db_fetch_array($subquery);
	echo '<td class="dataTableContent" align="center">'. $count['total'];?></td>
</tr>
<?php } //end while
?>
<?php
/*  if (empty($action)) {
?>
            <tr>
              <td colspan="7" align="right"><?php echo '<a href="' . tep_href_link('adwords_feed.php', 'action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
            </tr>
<?php
  } */
?>
             </table></td>
                <td></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_ADWORDS_GROUPS . '</b>');

      $contents = array('form' => tep_draw_form('adwords_feed', 'adwords_feed.php', 'aID=' . $aInfo->ad_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
        $contents[] = array('text' => '<br>' . TEXT_ADWORDS_DESCRIPTION . ' ' . $aInfo->ad_description );
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . '&nbsp;' . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('adwords_feed.php', 'aID=' . $aInfo->ad_id)));
      break;
    default:
      if (is_object($aInfo)) {
        $heading[] = array('text' => '<b>' . $aInfo->ad_id . ' : ' . $aInfo->ad_name . '</b>');

        $contents[] = array('align' => 'center',
        										'text' => tep_draw_button(IMAGE_EDIT, 'document', tep_href_link('adwords_feed.php', 'aID=' . $aInfo->ad_id . '&action=edit'))
  //      															.'<a href="' . tep_href_link('adwords_feed.php', 'aID=' . $aInfo->ad_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> '
        									 );
        $contents[] = array('text' => '<br>' . TEXT_ADWORDS_DESCRIPTION . ' ' . $aInfo->ad_description );
      }
      break;
  }
  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
} else { // action is new or edit
		if ($action == 'edit') {
			echo '<tr><td><table width="100%">';
		}
    if (!isset($aInfo->ad_active)) $aInfo->ad_active = '1';
//    if (!isset($aInfo->ad_type)) $aInfo->ad_type = 'label';
    switch ($aInfo->adwords_active) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }
?>          <tr>
            <td class="main" align="right" valign="middle"><?php echo TEXT_ADWORDS_NAME; ?>&nbsp;</td>
            <td class="main" width="65%"><?php echo tep_draw_input_field('ad_name', ( !empty($aInfo->ad_name) ? $aInfo->ad_name : '' ), 'size="20" disabled') ; ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="middle"><?php echo TEXT_ADWORDS_DESCRIPTION; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('ad_description', ( isset($_POST['ad_description']) ? $_POST['ad_description'] : $aInfo->ad_description ), 'size="30"') ; ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="middle"><?php echo TEXT_ADWORDS_ACTIVE; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_radio_field('ad_active', '1', $in_status) . '&nbsp;' . TEXT_ACTIVE . '&nbsp;' . tep_draw_radio_field('ad_active', '0', $out_status) . '&nbsp;' . TEXT_INACTIVE; ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="bottom"><?php // echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_submit(IMAGE_UPDATE, 'name="update_button"')). '&nbsp;&nbsp;&nbsp;<a href="' . tep_href_link('adwords_feed.php', (isset($_GET['aID']) ? 'aID=' . $_GET['aID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
						echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_draw_button(IMAGE_EDIT, 'document',null,null,  array('params' => 'name="update_button"'))). '&nbsp;&nbsp;&nbsp;'.tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('adwords_feed.php', (isset($_GET['aID']) ? 'aID=' . $_GET['aID'] : ''))); ?>&nbsp;</td>
              </tr>
           </table>
<?php } //end if new / insert
?>
              </tr>
           </table> <!-- </form> -->
<?php 
	if ($action == 'edit') { // products in group plus form to add more : products table to link to group / label

		echo '</td><td width="25%"></td></tr></table><br>';
//		echo '</td><td width="25%"></td></tr></table><br>'.tep_draw_form('ad_products','adwords_feed.php','cPath='.$_GET['cPath'] . '&aID='.$ad_id. '&action=edit','post','id="delprods_form"');
 echo tep_hide_session_id() . tep_draw_hidden_field('action',$action,'id="action_field"') . tep_draw_hidden_field('search',$search) . tep_draw_hidden_field('prod_id','0','id="prodId"').tep_draw_hidden_field('ad_id',$ad_id,'id="adId"'); 
 echo '<table width="100%" id="adprods">';
		
		$products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, ad.label_value from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, adwords_groups_products ad WHERE ad.ad_id =".(int)$ad_id. " and p.products_id = ad.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id ."'");

?>         <tr class="dataTableHeadingRow">
				<td class="dataTableHeadingContent" colspan="4">Products with label (click X to remove)</td>
        <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_LABEL; ?></td>
        <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_QUANTITY; ?></td>
        <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_VAT_PRICE; ?></td>
        <td class="dataTableHeadingContent" align="center"></td>
        </tr>
<?php    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;

			$match = 'Unmatched';
        echo '              <tr class="dataTableRow'.$match.'" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
//        echo '              <tr class="dataTableRow'.$match.'" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="return delProd(this.form, ' . $products['products_id'] . ')">' . "\n";
?>
				<td class="dataTableContent" align="left" width="35"><?php echo sprintf ($products['products_id']); ?></td>
                <td class="dataTableContent clicker" width="35" align="center"><b>X</b></td>
                <td class="dataTableContent"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent" align="center" width="35">
<?php echo '<a href="' . tep_href_link('product_info.php', 'products_id=' . $products['products_id']) . '" target="_blank">' . tep_image(DIR_WS_CATALOG_IMAGES.$products['products_image'], 'page', 32, 32,'style="vertical-align:middle"'); ?></a></td>
                <td class="dataTableContent" align="center">
<?php echo tep_draw_input_field('label_value['.$products['products_id'].']', ( isset($_POST['label_value'][$products['products_id']]) ? $_POST['label_value'][$products['products_id']] : $products['label_value'] ), 'size="30"'); ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $products['products_quantity']; ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id']));?> </td>
                <td class="dataTableContent" align="center">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td></tr>
<?php	   }
	echo '</table>';
//	echo '</table></form>';

//    echo tep_draw_form('goto', 'adwords_feed.php', '', 'get');
    echo '<br><br>'.HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
//    echo tep_hide_session_id() . tep_draw_hidden_field('qbFilter',$qbFilter) . tep_draw_hidden_field('mode',$mode) . tep_draw_hidden_field('sort',$sort) . tep_draw_hidden_field('active',$active) . tep_draw_hidden_field('action',$action,'id="action_field"') . tep_draw_hidden_field('search',$search) . tep_draw_hidden_field('aID',$ad_id,'id="adId"') . '</form>';

// echo '     <br>'.tep_draw_form('ad_products','adwords_feed.php','cPath='.$_GET['cPath'] . '&aID='.$ad_id. '&action=edit','post','id="products_form"');
// echo tep_hide_session_id() . tep_draw_hidden_field('action',$action,'id="action_field"') . tep_draw_hidden_field('search',$search) . tep_draw_hidden_field('ad_id',$ad_id,'id="adId"'); 
 $btn_params = array('params' => 'name="products_button"');
 echo ' ' . TEXT_PRODUCTS.' '.tep_draw_button(IMAGE_TOGGLE,'carat-1-s',null,null,$btn_params);
// echo " Products: ".tep_submit('toggle selected products', 'name="products_button"');
?>     <table border="0" width="100%" cellspacing="0" cellpadding="2" id="groupproducts">
         <tr class="dataTableHeadingRow"><?php $params = 'cPath='.$cPath.'&action=edit&aID='.$ad_id .'&sort='; ?>
				<td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'id' ? 'iddesc' : 'id'));?>">ID</a></td>
				<td class="dataTableHeadingContent"></td>
                <td class="dataTableHeadingContent"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'name' ? 'namedesc' : 'name')).'">'.TABLE_HEADING_CATEGORIES_PRODUCTS; ?></a></td>
<td class="dataTableHeadingContent" align="center"></td>
<td class="dataTableHeadingContent" align="center"></td>
<td class="dataTableHeadingContent" align="center"><a href="<?php echo tep_href_link('adwords_feed.php', $params.($sort == 'model' ? 'modeldesc' : 'model')).'">'.TABLE_HEADING_MODEL;?></a></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_QUANTITY; ?></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRICE; ?></td>
<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_VAT_PRICE; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
        </tr>
<?php
if ($prodmode == 'catalog') {
    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
} else {
	$categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' AND cd.categories_name='Bruce'"); // get an empty query to return
}

	$categories_count = 0;
	$rows = 0;
	
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;

      if ((!isset($_GET['cID']) && !isset($_GET['pID']) || (isset($_GET['cID']) && ($_GET['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }

 /*     if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', tep_get_path($categories['categories_id']).'&action=edit&aID='.$ad_id) . '\'">' . "\n";
      } else { */
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('adwords_feed.php', 'cPath=' . $cPath . '&cID=' . $categories['categories_id']).'&action=edit&aID='.$ad_id . '\'">' . "\n";
 //     }
?>
				<td class="dataTableContent" align="left" width="20"><?php echo $categories['categories_id']; ?></td>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('adwords_feed.php', tep_get_path($categories['categories_id']) . '&action=edit&aID='.$ad_id).'">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>&nbsp;<b>' . $categories['categories_name'] . '</b>'; ?></td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
<td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="center">&nbsp;</td>
				<td class="dataTableContent" align="center">&nbsp;</td>
              </tr>
<?php
    }

    $products_count = 0;
		
		switch ($sort) {
			case 'sort' : 
				if ($mode <> 'order') $sort_order = 'p2c.products_sort_order, pd.products_name';
				else $sort_order = 'pd.products_name';
				break;
			case 'iddesc' : 
				$desc = ' DESC';
			case 'id' : 
				$sort_order = 'p.products_id' . $desc;
				break;
			case 'namedesc' : 
				$desc = ' DESC';
			case 'name' : 
				$sort_order = 'pd.products_name' . $desc;
				break;
/*			case 'recent' : 
				$sort_order = 'q.qb_matched DESC';
				break; */
			case 'modeldesc' : 
				$desc = ' DESC';
			case 'model' : 
				$sort_order = 'p.products_model' . $desc;
				break;
		}
		$prod_status = ($active == 'only' ? ' and p.products_status = 1' : '');
//		$match_status = ($unmatched == 'only' ? ' and p.qb_list_id IS NULL' : '');

	if ($prodmode == 'catalog') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "'" . $prod_status . $match_status . " order by " . $sort_order);
/*	} elseif ($prodmode == 'grouped') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id) left join " . TABLE_QUICKBOOKS_ITEMS . " q on p.qb_list_id = q.qb_list_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, ".'adwords_groups_products'." ag where p.products_id = pd.products_id and p.products_id = ag.products_id and ad_id=".(int)$ad_id." and pd.language_id = '" . (int)$languages_id ."'". $prod_status . $match_status . " order by " . $sort_order);
	} elseif ($prodmode == 'recent') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id), " . TABLE_QUICKBOOKS_ITEMS . " q, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.qb_list_id = q.qb_list_id and pd.language_id = '" . (int)$languages_id ."'". $prod_status . $match_status . " order by " . $sort_order);
	} elseif ($prodmode == 'deleted') {
		$query = "select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id), " . TABLE_QUICKBOOKS_ITEMS . " q, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.qb_list_id = q.qb_list_id and q.qb_deleted = 1 and pd.language_id = '" . (int)$languages_id ."'". " order by " . $sort_order;
        $products_query = tep_db_query($query);
	} elseif ($prodmode == 'order') {
        $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_sort_order, p.products_tax_class_id, p.qb_list_id, q.qb_name, q.qb_active, q.qb_sales_description, q.qb_sales_price, q.qb_quantity, q.qb_stock_special, q.qb_review_price, q.qb_review_stock, q.qb_updated, ptdc.discount_categories_id, dc.discount_categories_name from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_TO_DISCOUNT_CATEGORIES . " ptdc on p.products_id = ptdc.products_id left join " . TABLE_DISCOUNT_CATEGORIES . " dc using(discount_categories_id) left join " . TABLE_QUICKBOOKS_ITEMS . " q on p.qb_list_id = q.qb_list_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_ORDERS_PRODUCTS . " op where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = op.products_id and op.orders_id = '" . (int)$order_id . "'" . $prod_status . $match_status . " order by " . $sort_order); */
	}

    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;
			
				$query = tep_db_query ('SELECT COUNT(*) AS total FROM adwords_groups_products WHERE products_id ='.(int)$products['products_id'].' AND ad_id='.(int)$ad_id);
				$row = tep_db_fetch_array($query);
				$in_group = ($row['total'] == 0 ? false : true);
				if ($in_group) $match = 'Selected'; else $match = ''; 

        echo '              <tr class="dataTableRow'.$match.'" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
?>
				<td class="dataTableContent" align="left" width="20"><?php echo sprintf ($products['products_id']); ?></td>
                <td class="dataTableContent"><?php echo tep_draw_checkbox_field('toggles[]', $products['products_id']); ?></td>
                <td class="dataTableContent"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent"><?php echo tep_image(DIR_WS_CATALOG_IMAGES.$products['products_image'], 'edit', 32, 32,'style="vertical-align:middle"'); ?></td>
                <td class="dataTableContent" align="left"> 
<?php echo '<a href="' . tep_href_link('product_info.php', 'products_id=' . $products['products_id']) . '" target="_blank">' . tep_image('../favicon.ico', ''); ?></a></td>
                <td class="dataTableContent" align="left">
<?php echo $products['products_model']; ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $products['products_quantity'].($match == 'Unequal'? ' <a>'.tep_image(DIR_WS_IMAGES.'icon_left.gif','','','', 'class="quantity" id="'.$products['products_id'].'"').'</a>' : ''); ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->format ($products['products_price']); ?> </td>
                <td class="dataTableContent" align="center">
<?php echo $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id']));?> </td>
                <td class="dataTableContent" align="center">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
      } else {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td></tr>
	
<?php	}
	
 $btn_params = array('params' => 'name="products_button"');
		echo "</table>\n".TEXT_PRODUCTS.' '.tep_draw_button(IMAGE_TOGGLE,'',null,null,$btn_params)."\n";
//		echo "</table>\nProducts: ".tep_submit('toggle selected products', 'name="products_button"')."</form>\n";
//		form for category tree
//    echo tep_draw_form('goto', 'adwords_feed.php', '', 'get');
	
	} //end if action edit

 } //end if mode adwords ?>
							</form></td>
            </tr> 
           </table></td>
          </tr>
        </table></td>      
       </tr>
     <tr>
      <td colspan="3"><?php echo tep_black_line(); ?></td>
     </tr>     
      </table></td> 
     </tr>
     <!-- end of Header Tags -->
 	 
    </table>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>