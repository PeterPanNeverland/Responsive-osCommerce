<?php
/* $Id$

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com
Copyright (c) 2015 osCommerce

Released under the GNU General Public License

Author: BrockleyJohn john@sewebsites.net

Admin page for deleting the value of a config variable - done as a page to force reload
Removing the stored version forces version checking for the add-on
To minimise opportunities for abuse, only deletes config variables that are registered
Then reloads passed page with any parameters

*/
  require('includes/application_top.php');

  if (!isset($_GET['var']) || !isset($_GET['page']) )  {
	  $msg = TEXT_WRONG_PARAMS;
	} elseif (!defined($_GET['var'])) {
	  $msg = sprintf(TEXT_VARIABLE_NOT_DEFINED,$_GET['var']);
	} elseif (!defined('MODULE_VERSION_CHECK_VARS')) {
	  $msg = TEXT_CHECK_VAR_NOT_DEFINED;
	} else {
		$var_array = explode('|',MODULE_VERSION_CHECK_VARS);
		if (in_array($_GET['var'], $var_array)) { //get the index of $version_var in the array
			tep_db_query("UPDATE configuration SET configuration_value = '' WHERE configuration_key = '".$_GET['var']."'");
			$link = $_GET['page'].'?';
			foreach ($_GET as $key => $value) {
				if ($key != 'page' && $key != 'var') $link .= $key . '=' . $value . '&';
			}
			$link = substr($link,0,-1); // drop last character
			tep_redirect($link);
		} else {
			$msg = sprintf(TEXT_VARIABLE_NOT_IN_LIST,$_GET['var']);
		}
	}

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
<!-- body //-->
<tr><td>
<?php echo $msg; ?>
    </td>
   </tr>
  </table>

<?php 
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>