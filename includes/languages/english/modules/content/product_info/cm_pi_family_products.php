<?php
/*
  $Id$

  Family products: display the other products in the same family
	- content module for product_info.php
	
	part of: Family Categories Addon
	Author john@sewebsites.net @BrockleyJohn

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

/******** definitions for display in catalog *****************************************************************/
  define('MODULE_CONTENT_PI_FAMILY_PRODUCTS_TEXT', 'Family Products');

/******** definitions for display in admin *******************************************************************/
  define('MODULE_CONTENT_PI_FAMILY_PRODUCTS_TITLE', 'Product Page Family Products');
  define('MODULE_CONTENT_PI_FAMILY_PRODUCTS_DESCRIPTION', 'Show other products from the same family on the product info page.');
/******** common definitions for display in admin ************************************************************/
  if (!defined('MODULE_ADDON_CLASH_ENABLED')) define('MODULE_ADDON_CLASH_ENABLED', 'Module %s is installed and enabled. Please disable it.');
  if (!defined('MODULE_ADDON_NO_CLASH')) define('MODULE_ADDON_NO_CLASH', 'Checked for clashing modules. None found.');
	if (!defined('MODULE_ADDON_VERSION_OK')) define('MODULE_ADDON_VERSION_OK','Module version changed from %s to %s.');
	if (!defined('MODULE_ADDON_VERSION_SAME')) define('MODULE_ADDON_VERSION_SAME','Module version %s');
	if (!defined('MODULE_ADDON_VERSION_NOK')) define('MODULE_ADDON_VERSION_NOK','Module version older than database');
	if (!defined('MODULE_ADDON_VERSION_FAIL')) define('MODULE_ADDON_VERSION_FAIL','Version problems: %s');
	if (!defined('MODULE_ADDON_VALIDATION_FAIL')) define('MODULE_ADDON_VALIDATION_FAIL','Validation Failed');
	if (!defined('MODULE_ADDON_VALIDATION_OK')) define('MODULE_ADDON_VALIDATION_OK','Validation Successful');
	if (!defined('MODULE_ADDON_UPLOAD_OK')) define('MODULE_ADDON_UPLOAD_OK','Additional files all found');
	if (!defined('MODULE_ADDON_UPLOAD_FAIL')) define('MODULE_ADDON_UPLOAD_FAIL','Additional files not found');
	if (!defined('MODULE_ADDON_EDIT_OK')) define('MODULE_ADDON_EDIT_OK','File edits all found');
	if (!defined('MODULE_ADDON_EDIT_FAIL')) define('MODULE_ADDON_EDIT_FAIL','File edit problems');
	if (!defined('MODULE_ADDON_EDIT_FOUND')) define('MODULE_ADDON_EDIT_FOUND',' found: %s, expected %s');
	if (!defined('MODULE_ADDON_EDIT_NOT_FOUND')) define('MODULE_ADDON_EDIT_NOT_FOUND',' File not found!');
	if (!defined('MODULE_ADDON_FILE_BTN')) define('MODULE_ADDON_FILE_BTN','Check Files');
	if (!defined('MODULE_ADDON_LOG_BTN')) define('MODULE_ADDON_LOG_BTN','Error Log');
	if (!defined('MODULE_ADDON_LOG_TITLE')) define('MODULE_ADDON_LOG_TITLE','Error Log');
