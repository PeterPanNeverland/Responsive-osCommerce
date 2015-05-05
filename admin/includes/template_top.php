<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/
?>
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<meta name="robots" content="noindex,nofollow">
<title><?php echo TITLE; ?></title>
<base href="<?php echo ($request_type == 'SSL') ? HTTPS_SERVER . DIR_WS_HTTPS_ADMIN : HTTP_SERVER . DIR_WS_ADMIN; ?>" />
<!--[if IE]><script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/excanvas.min.js', '', 'SSL'); ?>"></script><![endif]-->
<link rel="stylesheet" type="text/css" href="<?php echo tep_catalog_href_link('ext/jquery/ui/redmond/jquery-ui-1.10.4.min.css', '', 'SSL'); ?>">
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/jquery-1.11.1.min.js', '', 'SSL'); ?>"></script>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/ui/jquery-ui-1.10.4.min.js', '', 'SSL'); ?>"></script>

<?php
  if (tep_not_null(JQUERY_DATEPICKER_I18N_CODE)) {
?>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/jquery/ui/i18n/jquery.ui.datepicker-' . JQUERY_DATEPICKER_I18N_CODE . '.js', '', 'SSL'); ?>"></script>
<script type="text/javascript">
$.datepicker.setDefaults($.datepicker.regional['<?php echo JQUERY_DATEPICKER_I18N_CODE; ?>']);
</script>
<?php
  }
?>

<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/jquery.flot.min.js', '', 'SSL'); ?>"></script>
<script type="text/javascript" src="<?php echo tep_catalog_href_link('ext/flot/jquery.flot.time.min.js', '', 'SSL'); ?>"></script>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>
<?php
/*** Begin Header Tags SEO ***/
?>
<link rel="stylesheet" type="text/css" href="includes/headertags_seo_styles.css">
<?php
switch (HEADER_TAGS_ENABLE_HTML_EDITOR) {
   case 'CKEditor':
     echo '<script type="text/javascript" src="./ckeditor/ckeditor.js"></script>';
   break;

   case 'FCKEditor':
   break;
         
   case 'TinyMCE':
     if (HEADER_TAGS_ENABLE_EDITOR_CATEGORIES == 'true'   || 
         HEADER_TAGS_ENABLE_EDITOR_PRODUCTS == 'true'     ||
         HEADER_TAGS_ENABLE_EDITOR_LISTING_TEXT == 'true' ||
         HEADER_TAGS_ENABLE_EDITOR_SUB_TEXT == 'true'      
        )
     {  
       if ($action == 'new_product' || $action == 'new_category' || $action == 'edit_category') { // No need to put JS on all pages.
         $languages = tep_get_languages(); // Get all languages
       // Build list of textareas to convert
         $str = '';
         for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
           if (HEADER_TAGS_ENABLE_EDITOR_CATEGORIES == 'true') $str .= "categories_htc_description[".$languages[$i]['id']."],";
           if (HEADER_TAGS_ENABLE_EDITOR_PRODUCTS == 'true') $str .= "products_description[".$languages[$i]['id']."],";
           if (HEADER_TAGS_ENABLE_EDITOR_LISTING_TEXT == 'true') $str .= "products_head_listing_text[".$languages[$i]['id']."],";
           if (HEADER_TAGS_ENABLE_EDITOR_SUB_TEXT == 'true') $str .= "products_head_sub_text[".$languages[$i]['id']."],";
         }  //end for each language
         $mce_str = rtrim ($str,","); // Removed the last comma from the string.
       // You can add more textareas to convert in the $str, be careful that they are all separated by a comma.
         echo '<script language="javascript" type="text/javascript" src="includes/javascript/tiny_mce/tiny_mce.js"></script>';
         include "includes/javascript/tiny_mce/general.php";
       } 
     }
   break;

   default: break; 
}     
/*** End Header Tags SEO ***/
?>
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<?php
  if (tep_session_is_registered('admin')) {
    include(DIR_WS_INCLUDES . 'column_left.php');
  } else {
?>

<style>
#contentText {
  margin-left: 0;
}
</style>

<?php
  }
?>

<div id="contentText">
