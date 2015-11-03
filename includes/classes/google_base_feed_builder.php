<?php
/*
	version 4.0 June 2015 @BrockleyJohn john@sewebsites.net
	changes for osc 2.3.4
	- product images instead of addtional images
	- start deprecating file & table name constants
	- performance improvements; remove n+1 (actually worse than 3n!) on parent ctegories, product images and adwords groups

	version 3.0 March 2015 JAF @BrockleyJohn john@sewebsites.net
	- changes for Google Shopping instead of product listings
	- extended/amended for easify interface (extra product fields still there commented out)

	version 2.0 2012 JAF (@BrockleyJohn) john@sewebsites.net
	rewrite to work properly with real osC stores
	- rewrite queries
	- use config vars and standard classes and functions
	- languages
	
	Revisions copyright (c) osCommerce 2012 - 2015

  Original Copyright (C) 2008 Google Inc.

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * Google Checkout v1.5.0
 * $Id: google_base_feed_builder.php 153 2009-01-30 00:16:37Z ed.davisson $
 * 
 * Generates a feed (RSS 2.0) compatible with Google Base for products.
 *
 * See http://base.google.com/support/bin/answer.py?answer=73932
 *
 * osCommerce MySQL tables of interest:
 *   categories
 *   categories_description
 *   products
 *   products_descriptions
 *   products_to_categories
 *   manufacturers
 *
 * Mapping of XML elements to table->columns:
 *
 * Required:
 *   description: products_description->products_description
 *   id: products->products_id
 *   link: products_description->products_url  JAF: NO!!!!
 *   price: products->products_price
 *   title: products_description->products_name
 *
 *   JAF - fields moved from recommended to required
 *   brand: manufacturers->manufacturers_name
 *   condition: (now required - not supported by standard osc) use extra field 'condition'
 *   mpn: (now required - not supported by standard osc) use extra field 'MPN'
 *   gtin(upc/ean/isbn): (now required - not supported by standard osc) use extra field 'barcode - EAN'
 *
 *	 easify interface - replace extra field for barcode with EAN from easify
 *
 * new fields also required - JAF
 *   availability: in stock / available for order / out of stock / preorder - derived from products->products_quantity in quantity function
 *
 * Recommended:
 *   image_link: products->products_image
 *   isbn: (not supported)
 *   weight: products->products_weight
 *   sale_price: specials->specials_new_products_price
 *   sale_price_effective_date: specials->specials_date_added, specials->expires_date
 *
 * Optional:
 *   color:
 *   expiration_date
 *   height: (not supported)
 *   length: (not supported)
 *   model_number: products->products_model
 *   payment_accepted: TODO(eddavisson)
 *   payment_notes: TODO(eddavisson)
 *   price_type: (not supported)
 *   product_type: categories_description->categories_name (calculated recursively)
 *   quantity: products->products_quantity
 *   shipping: TODO(eddavisson)
 *   size: (not supported)
 *   tax: TODO(eddavisson)
 *   width: (not supported)
 *   year: (not supported)
 *
 * TODO(eddavisson): How many more of these fields can we include?
 *
 * @author Ed Davisson (ed.davisson@gmail.com)
 */

require_once(DIR_WS_CLASSES . 'google_xml_builder.php');

class GoogleBaseFeedBuilder {

  var $xml;
  var $languages_id;
  var $categories_tree;
  var $currencies;         // JAF
  var $product_categories; // JAF
  var $product_images; // JAF

  /**
   * Constructor.
   */
  function GoogleBaseFeedBuilder($languages_id) {
	  global $currencies; // JAF
    $this->xml = new GoogleXmlBuilder();
    $this->languages_id = $languages_id;
    $this->categories_tree = $this->build_categories_tree();
		$this->product_categories = $this->build_product_categories(); // JAF
		$this->product_types = $this->build_product_types(); // JAF
		$this->product_images = $this->build_product_images(); // JAF
		$this->product_ad_groups = $this->build_product_ad_groups(); // JAF
		$this->currencies = $currencies;   // JAF
		$this->pub_date = date(DATE_ISO8601); // JAF
  }

  /**
   * Adds all information needed to create a Google Base feed (RSS 2.0).
   */
  function get_xml() {
    $this->xml->Push("rss", array("version" => "2.0",
                                  "xmlns:g" => "http://base.google.com/ns/1.0"));
    $this->xml->Push("channel");

    $this->add_feed_info();
    $this->add_items();

    $this->xml->Pop("channel");
    $this->xml->Pop("rss");

    return $this->xml->GetXml();
  }

  /**
   * Adds feed info (title, link, description) to the XML.
   */
  function add_feed_info() {
    $title = STORE_NAME; // JAF
    /*$title_query = tep_db_query(
        "select configuration_value "
        . "from configuration "
        . "where configuration_key = \"STORE_NAME\"");
    $row = tep_db_fetch_array($title_query);
    $title = $row['configuration_value']; */

    $this->xml->Element('title', $title);
    $this->xml->Element('link', HTTP_SERVER . DIR_WS_CATALOG);
    // osCommerce doesn't store a description of the store.
    $this->xml->Element('description', $title);
  }

  /**
   * Adds items (products) to the XML.
   */
  function add_items() {
    $products_query = $this->get_products_query();
    while ($product = tep_db_fetch_array($products_query)) {
      $this->add_item($product);
    }
  }

  /**
   * Adds a single item (product) to the XML.
   */
  function add_item($product) {
    $this->xml->Push('item');

    // Required, global namespace.
    $this->add_title($product);
    $this->add_link($product);
    $this->add_description($product);

    // Required, Google namespace.
    $this->add_brand($product);
    $this->add_id($product);
    $this->add_price($product);

    $this->add_condition($product);  // JAF
    $this->add_mpn($product);  // JAF
    $this->add_gtin($product);  // JAF

    // Optional.
	$this->add_pubdate();  // JAF (global namespace)
    $this->add_sale_price($product); // JAF - also adds effective date
 //   $this->add_image_link($product);
    $this->add_additional_image_link($product);  // JAF - v4 function now feeds all images from array
    $this->add_weight($product);
    $this->add_model_number($product);
    $this->add_payment_notes($product);
    $this->add_product_type($product);
    $this->add_quantity($product);  // JAF - now adds availability as well as quantity
	$this->ad_groups($product);  // JAF add support for Google Adwords product Ads - 2015 rewritten for custom labels

    $this->xml->Pop('item');
  }

  /**
   * Builds the categories tree.
   */
  function build_categories_tree() {
    $categories_tree = array();
    $categories_query = $this->get_categories_query();
    while ($category = tep_db_fetch_array($categories_query)) {
      $categories_tree[$category['categories_id']] = array(
          'name' => $category['categories_name'],
          'parent_id' => $category['parent_id']);
    }
    return $categories_tree;
  }

  /**
   * Returns a query containing the information necessary
   * to build the categories tree.
   */
  function get_categories_query() {
    return tep_db_query(
      "select c.categories_id, "
      . "c.parent_id, "
      . "cd.categories_name "
      . "from " . TABLE_CATEGORIES . " c, "
      . TABLE_CATEGORIES_DESCRIPTION . " cd "
      . "where c.categories_id = cd.categories_id "
      . "and cd.language_id = " . (int) $this->languages_id . " "
    );
  }

  /**
   * Traverses the categories tree to construct an array of the
   * categories containing the provided category_id.
   */
  function create_category_array($category_id, &$array) {
    $name = $this->categories_tree[$category_id]['name'];
    array_push($array, $name);
    $parent_id = $this->categories_tree[$category_id]['parent_id'];
    if ($parent_id == 0) {
      $array = array_reverse($array);
      return;
    } else {
      $this->create_category_array($parent_id, $array);
    }
  }
  
  function get_filled_categories_query()
  // JAF we only need a list of the categories with active products 
  {
    return tep_db_query("select c.categories_id from categories c, products_to_categories p2c, products p where p2c.categories_id = c.categories_id and p.products_id = p2c.products_id and p.products_status = 1 group by categories_id");
//    return tep_db_query("select c.categories_id, cd.categories_name from categories c, categories_description cd, products_to_categories p2c, products p where c.categories_id = cd.categories_id and p2c.categories_id = c.categories_id and p.products_id = p2c.products_id and p.products_status = 1 group by categories_id");
  /*  return tep_db_query(
      "select c.categories_id, "
      . "from " . TABLE_CATEGORIES . " c, "
      . TABLE_CATEGORIES_DESCRIPTION . " cd "
      . "where c.categories_id = cd.categories_id "
      . "and cd.language_id = " . (int) $this->languages_id . " "
	  . "and EXISTS "  // JAF so add this subquery
	  . "( select p2c.products_id from "  
	  . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, "
	  . TABLE_PRODUCTS . " p "
	  . "where p2c.categories_id = c.categories_id "
	  . "and p2c.products_id = p.products_id "
	  . "and p.products_status = '1' "
	  . ")"  
	  ); */
  }
  
  /** JAF
   * Get a set of the category ids containing each product in a single query
   */
  function build_product_categories() {
    $product_categories = array();
//    $categories_query = $this->get_categories_query();
    $product_categories_query = tep_db_query("select categories_id, p.products_id from products_to_categories p2c, products p where p.products_id = p2c.products_id and p.products_status = 1 order by products_id");
		$products_id = 0;
    while ($product_category = tep_db_fetch_array($product_categories_query)) {
			if ($product_category['products_id'] <> $products_id) $product_categories[$product_category['products_id']] = array();
			$product_categories[$product_category['products_id']][] = $product_category['categories_id'];
			$products_id = $product_category['products_id'];
    }
    return $product_categories;
  }

  /** JAF
   * For each category containing active products, construct a product type based on
   * walking up the categories 
   */
  function build_product_types() {
    $product_types = array();
//    $categories_query = $this->get_categories_query();
    $categories_query = $this->get_filled_categories_query();
    while ($category = tep_db_fetch_array($categories_query)) {
      $product_types[$category['categories_id']] = $this->create_product_type($category['categories_id']);
    }
    return $product_types;
  }

  /** JAF
   * concatenate the category names into the product type 
   */
  function create_product_type($category_id) {
    $category_array = array();
    $this->create_category_array($category_id, $category_array);

    $product_type = "";
    $length = count($category_array);
    for ($i = 0; $i < $length; $i++) {
      $product_type .= $category_array[$i];
      if ($i != $length - 1) {
        $product_type .= " > ";
      }
    }
	return $product_type;
  }

  /** JAF
   * returns categories containing product
   */  
  function get_product_categories($product) {
    return $this->product_categories[$product['products_id']];
  }

  /** JAF
   * returns categories containing product
   */  
  function get_product_categories_query($product) {
    return tep_db_query(  
		"select p2c.categories_id from "  
			. TABLE_PRODUCTS_TO_CATEGORIES . " p2c, "
			. TABLE_PRODUCTS . " p "
			. "where p2c.products_id = p.products_id "
			. "and p.products_id = '" . $product['products_id'] . "' "
		);
  }

  /**
   * Returns a query over all products containing the columns
   * needed to generate the field.
   */
  function get_products_query() {
    return tep_db_query(  // JAF query omits products with no manufacturer specified & includes inactive ones!
/*      "select p.products_id, "
      . "p.products_price, "
      . "p.products_image, "
      . "p.products_weight, "
      . "p.products_model, "
      . "p.products_quantity, "
      . "pd.products_id, "
      . "pd.products_description, "
      . "pd.products_url, "
      . "pd.products_name, "
      . "m.manufacturers_name, "
      . "ptc.categories_id "
      . "from " . TABLE_PRODUCTS . " p, "
      . TABLE_PRODUCTS_DESCRIPTION . " pd, "
      . TABLE_MANUFACTURERS . " m, "
      . TABLE_PRODUCTS_TO_CATEGORIES . " ptc "
      . "where pd.products_id = p.products_id "
      . "and m.manufacturers_id = p.manufacturers_id "
      . "and ptc.products_id = p.products_id "
      . "and pd.language_id = " . (int) $this->languages_id . " ");  */
	  "select p.products_id, "
	. "p.products_price, "
	. "p.products_image, "
	. "p.products_image_med, "
	. "p.products_image_pop, "
	. "p.products_weight, "
	. "p.products_model as products_manual_mpn, "
	. "p.easify_sku as easify_sku, "
	. "p.easify_sku as products_model, "
	. "p.products_quantity, "
	. "p.products_tax_class_id, " 
	. "p.adwords_exclude, " 
	. "pd.products_description, "
	. "pd.products_url, "
	. "pd.products_name, "
	. "m.manufacturers_name, "
	. "cond.products_extra_fields_value as products_condition, "
//	. "mpn.products_extra_fields_value as products_manual_mpn, "
	. "ep.easify_manufacturer_stock_code as products_mpn, "
	. "ep.easify_supplier_stock_code as products_ssc, "
//	. "bar.products_extra_fields_value as products_barcode, "
	. "ep.easify_ean_code as products_barcode, "
	. "IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, "
	. "IF(s.status, s.specials_date_added, NULL) as specials_date_added, "
	. "IF(s.status, s.expires_date, NULL) as specials_expires_date, "
	. "IF(s.status, s.specials_new_products_price, p.products_price) as final_price "
	. "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " 
	. TABLE_PRODUCTS . " p "
	. "left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id "
	. "left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id  "
	. "left join (SELECT ppef.products_id, products_extra_fields_value FROM products_to_products_extra_fields ppef, "
	. products_extra_fields . " pef WHERE ppef.products_extra_fields_id = pef.products_extra_fields_id "
	. "and pef.products_extra_fields_name = 'condition') cond on p.products_id = cond.products_id "
	. "left join easify_products ep on p.easify_sku = ep.easify_sku  "
//	. "left join (SELECT ppef.products_id, products_extra_fields_value FROM products_to_products_extra_fields ppef, "
//	. products_extra_fields . " pef WHERE ppef.products_extra_fields_id = pef.products_extra_fields_id "
//	. "and pef.products_extra_fields_name = 'MPN') mpn on p.products_id = mpn.products_id "
//	. "left join (SELECT ppef.products_id, products_extra_fields_value FROM products_to_products_extra_fields ppef, "
//	. products_extra_fields . " pef WHERE ppef.products_extra_fields_id = pef.products_extra_fields_id "
//	. "and pef.products_extra_fields_name = 'barcode - EAN') bar on p.products_id = bar.products_id "
	. "where p.products_status = '1' and p.skip = 0 and pd.products_id = p.products_id and pd.language_id = '" . (int)$this->languages_id . "' "
	. "and not exists (SELECT ppef.products_id, products_extra_fields_value FROM products_to_products_extra_fields ppef, "
	. products_extra_fields . " pef WHERE ppef.products_extra_fields_id = pef.products_extra_fields_id "
	. "and pef.products_extra_fields_name = 'google feed exclude (set \"yes\")' and products_extra_fields_value = 'yes' and ppef.products_id = p.products_id )"
	);
  }

  /** JAF
   * returns additional images
	 * v4.0 from products_images table
   */  
  function get_images_query($product) {
    return tep_db_query(  
		"select image from products_images WHERE products_id = '" . $product['products_id'] . "' "
		);
/*    return tep_db_query(  
		"select popup_images from "  
			. TABLE_ADDITIONAL_IMAGES
			. " WHERE products_id = '" . $product['products_id'] . "' "
		); */
  }
 
  /** JAF
   * Get a set of the links to additional images of each product in a single query
	 * v4.0 from products_images table
   */
  function build_product_images() {
    $product_images = array();
//    $product_images_query = tep_db_query("select popup_images, p.products_id from additional_images ai, products p where p.products_id = ai.products_id and p.products_status = 1 order by products_id");
    $product_images_query = tep_db_query("select image, p.products_id from products_images ai, products p where p.products_id = ai.products_id and p.products_status = 1 order by products_id");
		$products_id = 0;
    while ($product_image = tep_db_fetch_array($product_images_query)) {
			if ($product_image['products_id'] <> $products_id) $product_images[$product_image['products_id']] = array();
			$product_images[$product_image['products_id']][] = $product_image['image'];
			$products_id = $product_image['products_id'];
    }
    return $product_images;
  }

  /**
   * JAF get and add the 'additional_image_link' elements if present
   */
  function add_additional_image_link($product) {
//    $image_query = $this->get_images_query($product);
//    while ($image = tep_db_fetch_array($image_query)) {
		$done = 0;
    if (array_key_exists($product['products_id'],$this->product_images)) {
			foreach ($this->product_images[$product['products_id']] as $image) {
				$image_link = HTTP_SERVER . DIR_WS_HTTP_CATALOG
						. DIR_WS_IMAGES . $image;
				if ($done == 0) $this->add_if_not_empty('g:image_link', $image_link);
				else $this->add_if_not_empty('g:additional_image_link', $image_link);
				$done++;
				if ($done > 10) break; // JAF new limit on additional image links
			}
		}
		if ($done == 0) {
			$image_link = HTTP_SERVER . DIR_WS_CATALOG
					. DIR_WS_IMAGES . $product['products_image'];
			$this->add_if_not_empty('g:image_link', $image_link);
		}
  }

  /**
   * Adds an element to the XML if content is non-empty.
   */
  function add_if_not_empty($element, $content) {
	// JAF still add if value is 0
    if (!(empty($content) && $content <> '0')) {
  // JAF strip duplicate spaces from content 
  		$content = preg_replace('/\s+/',' ',$content);
      $this->xml->Element($element, $content);
    }
  }

  /**
   * Adds the 'title' element.
   */
  function add_title($product) {
    $title = $product['products_name'];
    $this->add_if_not_empty('title', $title);
  }

  /**
   * Adds the 'link' element.
   */
  function add_link($product) {
    $link = tep_href_link(
        'product_info.php',
        'products_id=' . $product['products_id'],TRUE,FALSE); // JAF suppress session ids
    $this->add_if_not_empty('link', $link);
  }

  /**
   * Adds the 'brand' element.
   */
  function add_brand($product) {
    $brand = (strlen($product['manufacturers_name'])>0 ? $product['manufacturers_name'] : 'Generic'); //JAF default them to Generic if unpopulated
    $this->add_if_not_empty('g:brand', $brand);
  }

  /**
   * Adds the 'description' element.
   *
   * As of 1/13/09, HTML is only supported in individually
   * posted items.
   *
   * See http://base.google.com/support/bin/answer.py?answer=46116.
   */
  function add_description($product) {
  // JAF prevent consecutive paragraphs from running together & get rid of entities that break google shopping
/*  	$search = array('#</p><#i','#</li><#i','#</ul><#i','/<br>/i','#</td><#i','#<br />#i','#<br/>#i','#&sup2;#i','#&trade;#i','#&reg;#i','#&bull;#i','#&nbsp;#i','#&ndash;#i','#&times;#i','#&phi;#i','#&uuml;#i','#&deg;#i','#&hellip;#i','#&rsquo;#i','#&frac14;#i','#&rdquo;#i','#&plusmn;#i','#&oslash;#i');
	  $replace = array('</p> <','</li> <','</ul> <','<br> ','</td> <','<br /> ','<br/> ','&#178;','&#8482;','&#174;','&#8226;',' ','&#8211;','&#215;','&#966;','&#252;','&#176;','&#8230;','&#8217;','&#188;','&#8211;','&#177;','&#248;'); */
  	$search = array('#</p><#i','#</li><#i','#</ul><#i','/<br>/i','#</td><#i','#<br />#i','#<br/>#i');
	$replace = array('</p> <','</li> <','</ul> <','<br> ','</td> <','<br /> ','<br/> ');
    $description = preg_replace($search,$replace,$product['products_description']);
    $description = strip_tags($description);
  // JAF convert any html entities into characters so they don't break google
 $HTML401NamedToNumeric = array(
    '&nbsp;'     => '&#160;',  # no-break space = non-breaking space, U+00A0 ISOnum
    '&iexcl;'    => '&#161;',  # inverted exclamation mark, U+00A1 ISOnum
    '&cent;'     => '&#162;',  # cent sign, U+00A2 ISOnum
    '&pound;'    => '&#163;',  # pound sign, U+00A3 ISOnum
    '&curren;'   => '&#164;',  # currency sign, U+00A4 ISOnum
    '&yen;'      => '&#165;',  # yen sign = yuan sign, U+00A5 ISOnum
    '&brvbar;'   => '&#166;',  # broken bar = broken vertical bar, U+00A6 ISOnum
    '&sect;'     => '&#167;',  # section sign, U+00A7 ISOnum
    '&uml;'      => '&#168;',  # diaeresis = spacing diaeresis, U+00A8 ISOdia
    '&copy;'     => '&#169;',  # copyright sign, U+00A9 ISOnum
    '&ordf;'     => '&#170;',  # feminine ordinal indicator, U+00AA ISOnum
    '&laquo;'    => '&#171;',  # left-pointing double angle quotation mark = left pointing guillemet, U+00AB ISOnum
    '&not;'      => '&#172;',  # not sign, U+00AC ISOnum
    '&shy;'      => '&#173;',  # soft hyphen = discretionary hyphen, U+00AD ISOnum
    '&reg;'      => '&#174;',  # registered sign = registered trade mark sign, U+00AE ISOnum
    '&macr;'     => '&#175;',  # macron = spacing macron = overline = APL overbar, U+00AF ISOdia
    '&deg;'      => '&#176;',  # degree sign, U+00B0 ISOnum
    '&plusmn;'   => '&#177;',  # plus-minus sign = plus-or-minus sign, U+00B1 ISOnum
    '&sup2;'     => '&#178;',  # superscript two = superscript digit two = squared, U+00B2 ISOnum
    '&sup3;'     => '&#179;',  # superscript three = superscript digit three = cubed, U+00B3 ISOnum
    '&acute;'    => '&#180;',  # acute accent = spacing acute, U+00B4 ISOdia
    '&micro;'    => '&#181;',  # micro sign, U+00B5 ISOnum
    '&para;'     => '&#182;',  # pilcrow sign = paragraph sign, U+00B6 ISOnum
    '&middot;'   => '&#183;',  # middle dot = Georgian comma = Greek middle dot, U+00B7 ISOnum
    '&cedil;'    => '&#184;',  # cedilla = spacing cedilla, U+00B8 ISOdia
    '&sup1;'     => '&#185;',  # superscript one = superscript digit one, U+00B9 ISOnum
    '&ordm;'     => '&#186;',  # masculine ordinal indicator, U+00BA ISOnum
    '&raquo;'    => '&#187;',  # right-pointing double angle quotation mark = right pointing guillemet, U+00BB ISOnum
    '&frac14;'   => '&#188;',  # vulgar fraction one quarter = fraction one quarter, U+00BC ISOnum
    '&frac12;'   => '&#189;',  # vulgar fraction one half = fraction one half, U+00BD ISOnum
    '&frac34;'   => '&#190;',  # vulgar fraction three quarters = fraction three quarters, U+00BE ISOnum
    '&iquest;'   => '&#191;',  # inverted question mark = turned question mark, U+00BF ISOnum
    '&Agrave;'   => '&#192;',  # latin capital letter A with grave = latin capital letter A grave, U+00C0 ISOlat1
    '&Aacute;'   => '&#193;',  # latin capital letter A with acute, U+00C1 ISOlat1
    '&Acirc;'    => '&#194;',  # latin capital letter A with circumflex, U+00C2 ISOlat1
    '&Atilde;'   => '&#195;',  # latin capital letter A with tilde, U+00C3 ISOlat1
    '&Auml;'     => '&#196;',  # latin capital letter A with diaeresis, U+00C4 ISOlat1
    '&Aring;'    => '&#197;',  # latin capital letter A with ring above = latin capital letter A ring, U+00C5 ISOlat1
    '&AElig;'    => '&#198;',  # latin capital letter AE = latin capital ligature AE, U+00C6 ISOlat1
    '&Ccedil;'   => '&#199;',  # latin capital letter C with cedilla, U+00C7 ISOlat1
    '&Egrave;'   => '&#200;',  # latin capital letter E with grave, U+00C8 ISOlat1
    '&Eacute;'   => '&#201;',  # latin capital letter E with acute, U+00C9 ISOlat1
    '&Ecirc;'    => '&#202;',  # latin capital letter E with circumflex, U+00CA ISOlat1
    '&Euml;'     => '&#203;',  # latin capital letter E with diaeresis, U+00CB ISOlat1
    '&Igrave;'   => '&#204;',  # latin capital letter I with grave, U+00CC ISOlat1
    '&Iacute;'   => '&#205;',  # latin capital letter I with acute, U+00CD ISOlat1
    '&Icirc;'    => '&#206;',  # latin capital letter I with circumflex, U+00CE ISOlat1
    '&Iuml;'     => '&#207;',  # latin capital letter I with diaeresis, U+00CF ISOlat1
    '&ETH;'      => '&#208;',  # latin capital letter ETH, U+00D0 ISOlat1
    '&Ntilde;'   => '&#209;',  # latin capital letter N with tilde, U+00D1 ISOlat1
    '&Ograve;'   => '&#210;',  # latin capital letter O with grave, U+00D2 ISOlat1
    '&Oacute;'   => '&#211;',  # latin capital letter O with acute, U+00D3 ISOlat1
    '&Ocirc;'    => '&#212;',  # latin capital letter O with circumflex, U+00D4 ISOlat1
    '&Otilde;'   => '&#213;',  # latin capital letter O with tilde, U+00D5 ISOlat1
    '&Ouml;'     => '&#214;',  # latin capital letter O with diaeresis, U+00D6 ISOlat1
    '&times;'    => '&#215;',  # multiplication sign, U+00D7 ISOnum
    '&Oslash;'   => '&#216;',  # latin capital letter O with stroke = latin capital letter O slash, U+00D8 ISOlat1
    '&Ugrave;'   => '&#217;',  # latin capital letter U with grave, U+00D9 ISOlat1
    '&Uacute;'   => '&#218;',  # latin capital letter U with acute, U+00DA ISOlat1
    '&Ucirc;'    => '&#219;',  # latin capital letter U with circumflex, U+00DB ISOlat1
    '&Uuml;'     => '&#220;',  # latin capital letter U with diaeresis, U+00DC ISOlat1
    '&Yacute;'   => '&#221;',  # latin capital letter Y with acute, U+00DD ISOlat1
    '&THORN;'    => '&#222;',  # latin capital letter THORN, U+00DE ISOlat1
    '&szlig;'    => '&#223;',  # latin small letter sharp s = ess-zed, U+00DF ISOlat1
    '&agrave;'   => '&#224;',  # latin small letter a with grave = latin small letter a grave, U+00E0 ISOlat1
    '&aacute;'   => '&#225;',  # latin small letter a with acute, U+00E1 ISOlat1
    '&acirc;'    => '&#226;',  # latin small letter a with circumflex, U+00E2 ISOlat1
    '&atilde;'   => '&#227;',  # latin small letter a with tilde, U+00E3 ISOlat1
    '&auml;'     => '&#228;',  # latin small letter a with diaeresis, U+00E4 ISOlat1
    '&aring;'    => '&#229;',  # latin small letter a with ring above = latin small letter a ring, U+00E5 ISOlat1
    '&aelig;'    => '&#230;',  # latin small letter ae = latin small ligature ae, U+00E6 ISOlat1
    '&ccedil;'   => '&#231;',  # latin small letter c with cedilla, U+00E7 ISOlat1
    '&egrave;'   => '&#232;',  # latin small letter e with grave, U+00E8 ISOlat1
    '&eacute;'   => '&#233;',  # latin small letter e with acute, U+00E9 ISOlat1
    '&ecirc;'    => '&#234;',  # latin small letter e with circumflex, U+00EA ISOlat1
    '&euml;'     => '&#235;',  # latin small letter e with diaeresis, U+00EB ISOlat1
    '&igrave;'   => '&#236;',  # latin small letter i with grave, U+00EC ISOlat1
    '&iacute;'   => '&#237;',  # latin small letter i with acute, U+00ED ISOlat1
    '&icirc;'    => '&#238;',  # latin small letter i with circumflex, U+00EE ISOlat1
    '&iuml;'     => '&#239;',  # latin small letter i with diaeresis, U+00EF ISOlat1
    '&eth;'      => '&#240;',  # latin small letter eth, U+00F0 ISOlat1
    '&ntilde;'   => '&#241;',  # latin small letter n with tilde, U+00F1 ISOlat1
    '&ograve;'   => '&#242;',  # latin small letter o with grave, U+00F2 ISOlat1
    '&oacute;'   => '&#243;',  # latin small letter o with acute, U+00F3 ISOlat1
    '&ocirc;'    => '&#244;',  # latin small letter o with circumflex, U+00F4 ISOlat1
    '&otilde;'   => '&#245;',  # latin small letter o with tilde, U+00F5 ISOlat1
    '&ouml;'     => '&#246;',  # latin small letter o with diaeresis, U+00F6 ISOlat1
    '&divide;'   => '&#247;',  # division sign, U+00F7 ISOnum
    '&oslash;'   => '&#248;',  # latin small letter o with stroke, = latin small letter o slash, U+00F8 ISOlat1
    '&ugrave;'   => '&#249;',  # latin small letter u with grave, U+00F9 ISOlat1
    '&uacute;'   => '&#250;',  # latin small letter u with acute, U+00FA ISOlat1
    '&ucirc;'    => '&#251;',  # latin small letter u with circumflex, U+00FB ISOlat1
    '&uuml;'     => '&#252;',  # latin small letter u with diaeresis, U+00FC ISOlat1
    '&yacute;'   => '&#253;',  # latin small letter y with acute, U+00FD ISOlat1
    '&thorn;'    => '&#254;',  # latin small letter thorn, U+00FE ISOlat1
    '&yuml;'     => '&#255;',  # latin small letter y with diaeresis, U+00FF ISOlat1
    '&fnof;'     => '&#402;',  # latin small f with hook = function = florin, U+0192 ISOtech
    '&Alpha;'    => '&#913;',  # greek capital letter alpha, U+0391
    '&Beta;'     => '&#914;',  # greek capital letter beta, U+0392
    '&Gamma;'    => '&#915;',  # greek capital letter gamma, U+0393 ISOgrk3
    '&Delta;'    => '&#916;',  # greek capital letter delta, U+0394 ISOgrk3
    '&Epsilon;'  => '&#917;',  # greek capital letter epsilon, U+0395
    '&Zeta;'     => '&#918;',  # greek capital letter zeta, U+0396
    '&Eta;'      => '&#919;',  # greek capital letter eta, U+0397
    '&Theta;'    => '&#920;',  # greek capital letter theta, U+0398 ISOgrk3
    '&Iota;'     => '&#921;',  # greek capital letter iota, U+0399
    '&Kappa;'    => '&#922;',  # greek capital letter kappa, U+039A
    '&Lambda;'   => '&#923;',  # greek capital letter lambda, U+039B ISOgrk3
    '&Mu;'       => '&#924;',  # greek capital letter mu, U+039C
    '&Nu;'       => '&#925;',  # greek capital letter nu, U+039D
    '&Xi;'       => '&#926;',  # greek capital letter xi, U+039E ISOgrk3
    '&Omicron;'  => '&#927;',  # greek capital letter omicron, U+039F
    '&Pi;'       => '&#928;',  # greek capital letter pi, U+03A0 ISOgrk3
    '&Rho;'      => '&#929;',  # greek capital letter rho, U+03A1
    '&Sigma;'    => '&#931;',  # greek capital letter sigma, U+03A3 ISOgrk3
    '&Tau;'      => '&#932;',  # greek capital letter tau, U+03A4
    '&Upsilon;'  => '&#933;',  # greek capital letter upsilon, U+03A5 ISOgrk3
    '&Phi;'      => '&#934;',  # greek capital letter phi, U+03A6 ISOgrk3
    '&Chi;'      => '&#935;',  # greek capital letter chi, U+03A7
    '&Psi;'      => '&#936;',  # greek capital letter psi, U+03A8 ISOgrk3
    '&Omega;'    => '&#937;',  # greek capital letter omega, U+03A9 ISOgrk3
    '&alpha;'    => '&#945;',  # greek small letter alpha, U+03B1 ISOgrk3
    '&beta;'     => '&#946;',  # greek small letter beta, U+03B2 ISOgrk3
    '&gamma;'    => '&#947;',  # greek small letter gamma, U+03B3 ISOgrk3
    '&delta;'    => '&#948;',  # greek small letter delta, U+03B4 ISOgrk3
    '&epsilon;'  => '&#949;',  # greek small letter epsilon, U+03B5 ISOgrk3
    '&zeta;'     => '&#950;',  # greek small letter zeta, U+03B6 ISOgrk3
    '&eta;'      => '&#951;',  # greek small letter eta, U+03B7 ISOgrk3
    '&theta;'    => '&#952;',  # greek small letter theta, U+03B8 ISOgrk3
    '&iota;'     => '&#953;',  # greek small letter iota, U+03B9 ISOgrk3
    '&kappa;'    => '&#954;',  # greek small letter kappa, U+03BA ISOgrk3
    '&lambda;'   => '&#955;',  # greek small letter lambda, U+03BB ISOgrk3
    '&mu;'       => '&#956;',  # greek small letter mu, U+03BC ISOgrk3
    '&nu;'       => '&#957;',  # greek small letter nu, U+03BD ISOgrk3
    '&xi;'       => '&#958;',  # greek small letter xi, U+03BE ISOgrk3
    '&omicron;'  => '&#959;',  # greek small letter omicron, U+03BF NEW
    '&pi;'       => '&#960;',  # greek small letter pi, U+03C0 ISOgrk3
    '&rho;'      => '&#961;',  # greek small letter rho, U+03C1 ISOgrk3
    '&sigmaf;'   => '&#962;',  # greek small letter final sigma, U+03C2 ISOgrk3
    '&sigma;'    => '&#963;',  # greek small letter sigma, U+03C3 ISOgrk3
    '&tau;'      => '&#964;',  # greek small letter tau, U+03C4 ISOgrk3
    '&upsilon;'  => '&#965;',  # greek small letter upsilon, U+03C5 ISOgrk3
    '&phi;'      => '&#966;',  # greek small letter phi, U+03C6 ISOgrk3
    '&chi;'      => '&#967;',  # greek small letter chi, U+03C7 ISOgrk3
    '&psi;'      => '&#968;',  # greek small letter psi, U+03C8 ISOgrk3
    '&omega;'    => '&#969;',  # greek small letter omega, U+03C9 ISOgrk3
    '&thetasym;' => '&#977;',  # greek small letter theta symbol, U+03D1 NEW
    '&upsih;'    => '&#978;',  # greek upsilon with hook symbol, U+03D2 NEW
    '&piv;'      => '&#982;',  # greek pi symbol, U+03D6 ISOgrk3
    '&bull;'     => '&#8226;', # bullet = black small circle, U+2022 ISOpub
    '&hellip;'   => '&#8230;', # horizontal ellipsis = three dot leader, U+2026 ISOpub
    '&prime;'    => '&#8242;', # prime = minutes = feet, U+2032 ISOtech
    '&Prime;'    => '&#8243;', # double prime = seconds = inches, U+2033 ISOtech
    '&oline;'    => '&#8254;', # overline = spacing overscore, U+203E NEW
    '&frasl;'    => '&#8260;', # fraction slash, U+2044 NEW
    '&weierp;'   => '&#8472;', # script capital P = power set = Weierstrass p, U+2118 ISOamso
    '&image;'    => '&#8465;', # blackletter capital I = imaginary part, U+2111 ISOamso
    '&real;'     => '&#8476;', # blackletter capital R = real part symbol, U+211C ISOamso
    '&trade;'    => '&#8482;', # trade mark sign, U+2122 ISOnum
    '&alefsym;'  => '&#8501;', # alef symbol = first transfinite cardinal, U+2135 NEW
    '&larr;'     => '&#8592;', # leftwards arrow, U+2190 ISOnum
    '&uarr;'     => '&#8593;', # upwards arrow, U+2191 ISOnum
    '&rarr;'     => '&#8594;', # rightwards arrow, U+2192 ISOnum
    '&darr;'     => '&#8595;', # downwards arrow, U+2193 ISOnum
    '&harr;'     => '&#8596;', # left right arrow, U+2194 ISOamsa
    '&crarr;'    => '&#8629;', # downwards arrow with corner leftwards = carriage return, U+21B5 NEW
    '&lArr;'     => '&#8656;', # leftwards double arrow, U+21D0 ISOtech
    '&uArr;'     => '&#8657;', # upwards double arrow, U+21D1 ISOamsa
    '&rArr;'     => '&#8658;', # rightwards double arrow, U+21D2 ISOtech
    '&dArr;'     => '&#8659;', # downwards double arrow, U+21D3 ISOamsa
    '&hArr;'     => '&#8660;', # left right double arrow, U+21D4 ISOamsa
    '&forall;'   => '&#8704;', # for all, U+2200 ISOtech
    '&part;'     => '&#8706;', # partial differential, U+2202 ISOtech
    '&exist;'    => '&#8707;', # there exists, U+2203 ISOtech
    '&empty;'    => '&#8709;', # empty set = null set = diameter, U+2205 ISOamso
    '&nabla;'    => '&#8711;', # nabla = backward difference, U+2207 ISOtech
    '&isin;'     => '&#8712;', # element of, U+2208 ISOtech
    '&notin;'    => '&#8713;', # not an element of, U+2209 ISOtech
    '&ni;'       => '&#8715;', # contains as member, U+220B ISOtech
    '&prod;'     => '&#8719;', # n-ary product = product sign, U+220F ISOamsb
    '&sum;'      => '&#8721;', # n-ary sumation, U+2211 ISOamsb
    '&minus;'    => '&#8722;', # minus sign, U+2212 ISOtech
    '&lowast;'   => '&#8727;', # asterisk operator, U+2217 ISOtech
    '&radic;'    => '&#8730;', # square root = radical sign, U+221A ISOtech
    '&prop;'     => '&#8733;', # proportional to, U+221D ISOtech
    '&infin;'    => '&#8734;', # infinity, U+221E ISOtech
    '&ang;'      => '&#8736;', # angle, U+2220 ISOamso
    '&and;'      => '&#8743;', # logical and = wedge, U+2227 ISOtech
    '&or;'       => '&#8744;', # logical or = vee, U+2228 ISOtech
    '&cap;'      => '&#8745;', # intersection = cap, U+2229 ISOtech
    '&cup;'      => '&#8746;', # union = cup, U+222A ISOtech
    '&int;'      => '&#8747;', # integral, U+222B ISOtech
    '&there4;'   => '&#8756;', # therefore, U+2234 ISOtech
    '&sim;'      => '&#8764;', # tilde operator = varies with = similar to, U+223C ISOtech
    '&cong;'     => '&#8773;', # approximately equal to, U+2245 ISOtech
    '&asymp;'    => '&#8776;', # almost equal to = asymptotic to, U+2248 ISOamsr
    '&ne;'       => '&#8800;', # not equal to, U+2260 ISOtech
    '&equiv;'    => '&#8801;', # identical to, U+2261 ISOtech
    '&le;'       => '&#8804;', # less-than or equal to, U+2264 ISOtech
    '&ge;'       => '&#8805;', # greater-than or equal to, U+2265 ISOtech
    '&sub;'      => '&#8834;', # subset of, U+2282 ISOtech
    '&sup;'      => '&#8835;', # superset of, U+2283 ISOtech
    '&nsub;'     => '&#8836;', # not a subset of, U+2284 ISOamsn
    '&sube;'     => '&#8838;', # subset of or equal to, U+2286 ISOtech
    '&supe;'     => '&#8839;', # superset of or equal to, U+2287 ISOtech
    '&oplus;'    => '&#8853;', # circled plus = direct sum, U+2295 ISOamsb
    '&otimes;'   => '&#8855;', # circled times = vector product, U+2297 ISOamsb
    '&perp;'     => '&#8869;', # up tack = orthogonal to = perpendicular, U+22A5 ISOtech
    '&sdot;'     => '&#8901;', # dot operator, U+22C5 ISOamsb
    '&lceil;'    => '&#8968;', # left ceiling = apl upstile, U+2308 ISOamsc
    '&rceil;'    => '&#8969;', # right ceiling, U+2309 ISOamsc
    '&lfloor;'   => '&#8970;', # left floor = apl downstile, U+230A ISOamsc
    '&rfloor;'   => '&#8971;', # right floor, U+230B ISOamsc
    '&lang;'     => '&#9001;', # left-pointing angle bracket = bra, U+2329 ISOtech
    '&rang;'     => '&#9002;', # right-pointing angle bracket = ket, U+232A ISOtech
    '&loz;'      => '&#9674;', # lozenge, U+25CA ISOpub
    '&spades;'   => '&#9824;', # black spade suit, U+2660 ISOpub
    '&clubs;'    => '&#9827;', # black club suit = shamrock, U+2663 ISOpub
    '&hearts;'   => '&#9829;', # black heart suit = valentine, U+2665 ISOpub
    '&diams;'    => '&#9830;', # black diamond suit, U+2666 ISOpub
    '&quot;'     => '&#34;',   # quotation mark = APL quote, U+0022 ISOnum
    '&amp;'      => '&#38;',   # ampersand, U+0026 ISOnum
    '&lt;'       => '&#60;',   # less-than sign, U+003C ISOnum
    '&gt;'       => '&#62;',   # greater-than sign, U+003E ISOnum
    '&OElig;'    => '&#338;',  # latin capital ligature OE, U+0152 ISOlat2
    '&oelig;'    => '&#339;',  # latin small ligature oe, U+0153 ISOlat2
    '&Scaron;'   => '&#352;',  # latin capital letter S with caron, U+0160 ISOlat2
    '&scaron;'   => '&#353;',  # latin small letter s with caron, U+0161 ISOlat2
    '&Yuml;'     => '&#376;',  # latin capital letter Y with diaeresis, U+0178 ISOlat2
    '&circ;'     => '&#710;',  # modifier letter circumflex accent, U+02C6 ISOpub
    '&tilde;'    => '&#732;',  # small tilde, U+02DC ISOdia
    '&ensp;'     => '&#8194;', # en space, U+2002 ISOpub
    '&emsp;'     => '&#8195;', # em space, U+2003 ISOpub
    '&thinsp;'   => '&#8201;', # thin space, U+2009 ISOpub
    '&zwnj;'     => '&#8204;', # zero width non-joiner, U+200C NEW RFC 2070
    '&zwj;'      => '&#8205;', # zero width joiner, U+200D NEW RFC 2070
    '&lrm;'      => '&#8206;', # left-to-right mark, U+200E NEW RFC 2070
    '&rlm;'      => '&#8207;', # right-to-left mark, U+200F NEW RFC 2070
    '&ndash;'    => '&#8211;', # en dash, U+2013 ISOpub
    '&mdash;'    => '&#8212;', # em dash, U+2014 ISOpub
    '&lsquo;'    => '&#8216;', # left single quotation mark, U+2018 ISOnum
    '&rsquo;'    => '&#8217;', # right single quotation mark, U+2019 ISOnum
    '&sbquo;'    => '&#8218;', # single low-9 quotation mark, U+201A NEW
    '&ldquo;'    => '&#8220;', # left double quotation mark, U+201C ISOnum
    '&rdquo;'    => '&#8221;', # right double quotation mark, U+201D ISOnum
    '&bdquo;'    => '&#8222;', # double low-9 quotation mark, U+201E NEW
    '&dagger;'   => '&#8224;', # dagger, U+2020 ISOpub
    '&Dagger;'   => '&#8225;', # double dagger, U+2021 ISOpub
    '&permil;'   => '&#8240;', # per mille sign, U+2030 ISOtech
    '&lsaquo;'   => '&#8249;', # single left-pointing angle quotation mark, U+2039 ISO proposed
    '&rsaquo;'   => '&#8250;', # single right-pointing angle quotation mark, U+203A ISO proposed
    '&euro;'     => '&#8364;', # euro sign, U+20AC NEW
    '&apos;'     => '&#39;',   # apostrophe = APL quote, U+0027 ISOnum
);
$description = strtr($description, $HTML401NamedToNumeric); 
	// $description = html_entity_decode($description,ENT_COMPAT | ENT_HTML401,'UTF-8'); no no set the double encode flag instead (in xml_builder)
  // JAF strip invalid characters from content 
	$description = iconv("UTF-8","UTF-8//IGNORE",$description);
  // JAF enforce 5000 character limit
  	if (strlen($description) > 4750) $description = substr($description,0,4750).'...';
    $this->add_if_not_empty('description', $description);
  }

  /**
   * Adds the 'id' element.
   */
  function add_id($product) {
    $id = $product['products_id'];
    $this->add_if_not_empty('g:id', $id);
  }

  /**
   * JAF Adds the 'condition' element.
   */
  function add_condition($product) {
    $thing = $product['products_condition'];
	if (strlen($thing)==0 || $thing == 'ex-Display') { $thing = 'new'; } // default to new
	else { $thing = 'used'; } // otherwise used
    $this->add_if_not_empty('g:condition', $thing);
  }

  /**
   * JAF Adds the 'mpn' element.
   */
  function add_mpn($product) {
//    $thing = (!empty($product['products_mpn']) && $product['products_mpn'] <> '666' ? $product['products_mpn'] : (!empty($product['products_ssc'])? $product['products_ssc'] : $product['easify_sku']));
	if (!empty($product['easify_sku'])) {
		$mpn = trim($product['products_mpn']); $ssc = trim($product['products_ssc']);
		$thing = (!empty($mpn) && $mpn <> '666' ? $mpn : (!empty($ssc)? $ssc : $product['easify_sku']));
		if (!empty($thing)) {
			$this->add_if_not_empty('g:mpn', $thing);
			} else {
				$this->add_if_not_empty('g:mpn', $product['products_model']);
			}
		} else {
			$this->add_if_not_empty('g:mpn', $product['products_manual_mpn']);
		}		
  }

  /**
   * JAF Adds the 'gtin' element.
   */
  function add_gtin($product) {
  	if ($product['manufacturers_name'] == 'Clubtek' && empty($product['easify_sku'])) {
    	$this->add_if_not_empty('g:identifier_exists', 'FALSE');
	} else {
		$thing = $product['products_barcode'];
		/* if (strlen($thing) <> 13 && strlen($thing) <> 6) { // six digit barcode is our sku
			
		} */
		if (strlen($thing) == 13) // don't submit if it's not the right length
			$this->add_if_not_empty('g:gtin', $thing);
	}
  }

  /**
   * Adds the 'price' element.
   */
  function add_price($product) {
  	// JAF use vanilla osc2.2 price display
//    $price = round($product['products_price'], 2);
// sale prices dont seem to work in google products so use final price instead
//	$price = round($this->currencies->calculate_price($product['products_price'], tep_get_tax_rate($product['products_tax_class_id'])),2);
	$price = round($this->currencies->calculate_price($product['final_price'], tep_get_tax_rate($product['products_tax_class_id'])),2);
    $this->add_if_not_empty('g:price', $price . ' GBP');
  }
  
  /**
   * JAF Adds the 'sale price' element and 'effective date'.
   */
  function add_sale_price($product) {
		if ($product['final_price'] <> $product['products_price']) {
			// GOT HALF WAY THROUGH THIS......................
			$price = round($this->currencies->calculate_price($product['final_price'], tep_get_tax_rate($product['products_tax_class_id'])),2);
			$this->add_if_not_empty('g:sale_price', $price . ' GBP');
			$sale_start = date(DATE_ISO8601,strtotime($product['specials_date_added']));
			if (strtotime($product['specials_expires_date']) <> strtotime('0000-00-00 00:00:00')) {
//				$sale_end = $product['specials_expires_date'];
				$sale_end = date(DATE_ISO8601,strtotime($product['specials_expires_date']));
			} else {
				$sale_end = date(DATE_ISO8601,strtotime("+30 day"));
			}
			$this->add_if_not_empty('g:sale_price_effective_date', $sale_start.'/'.$sale_end);
		}
  }
  
  /** 
   * JAF adds pubdate which you can use to check that the feed got regenned
   */
   function add_pubdate() {
     $this->add_if_not_empty('pubDate', $this->pub_date);
   }
  

  /**
   * Adds the 'image_link' element.
   */
  function add_image_link($product) {
    $image_link = HTTP_SERVER . DIR_WS_CATALOG
        . DIR_WS_IMAGES . $product['products_image'];
    $this->add_if_not_empty('g:image_link', $image_link);
  }

  /**
   * JAF get and add the 'additional_image_link' elements if present
   */
/* performance - n+1 - don't do it like this!
  function add_additional_image_link($product) {
    $image_query = $this->get_images_query($product);
    while ($image = tep_db_fetch_array($image_query)) {
			$image_link = HTTP_SERVER . DIR_WS_CATALOG
					. DIR_WS_IMAGES . $image['image']; // JAF v4.0
			$this->add_if_not_empty('g:additional_image_link', $image_link);
    }
  } */

  /**
   * Adds the 'weight' element.
   */
  function add_weight($product) {
    $weight = $product['products_weight'];
    $this->add_if_not_empty('g:weight', $weight);
  }

  /**
   * Adds the 'model_number' element.
   */
  function add_model_number($product) {
    $model_number = $product['products_model'];
    $this->add_if_not_empty('g:model_number', $model_number);
  }

  /**
   * Adds the 'payment_notes' element.
   */
  function add_payment_notes($product) {
    // TODO(eddavisson): What should we actually say here?
/*    $payment_notes = "Google Checkout";
    $this->add_if_not_empty('g:payment_notes', $payment_notes); */
  }

  function add_product_type($product) {
/*    $category_id = $product['categories_id'];
    $category_array = array();
    $this->create_category_array($category_id, $category_array);

    $product_type = "";
    $length = count($category_array);
    for ($i = 0; $i < $length; $i++) {
      $product_type .= $category_array[$i];
      if ($i != $length - 1) {
        $product_type .= " > ";
      }
    }
*/  
/*    $categories_query = $this->get_product_categories_query($product);
    while ($category = tep_db_fetch_array($categories_query)) {
      $this->add_if_not_empty('g:product_type', $this->product_types[$category['categories_id']]);
		} */
    $categories = $this->get_product_categories($product);
		foreach($categories as $cat_id) {
      $this->add_if_not_empty('g:product_type', $this->product_types[$cat_id]);
		}
  }

  /**
   * Adds the 'quantity' element.
   * JAF also adds the 'availability' element.
   */
  function add_quantity($product) {
    $quantity = $product['products_quantity'];
    $this->add_if_not_empty('g:quantity', $quantity);  
//    $this->xml->Element('g:quantity', $quantity); // JAF temp bypass prob can remove
		// JAF we don't list things you can't order and don't have pre-order items so
		$availability = ( $quantity > 0 ? 'in stock' : 'out of stock' );
    $this->add_if_not_empty('g:availability', $availability);
  }
  /** JAF
   * Get a set of the ad groups of each product in a single query
   */
  function build_product_ad_groups() {
    $return_array = array();
    $query = tep_db_query("select ad_name, label_value, p.products_id from adwords_groups a, adwords_groups_products ap, products p where a.ad_id = ap.ad_id and p.products_id = ap.products_id and p.products_status = 1 order by products_id, a.ad_priority DESC");
		$products_id = 0;
    while ($row = tep_db_fetch_array($query)) {
			if ($row['products_id'] <> $products_id) $return_array[$row['products_id']] = array();
			$return_array[$row['products_id']][] = array('ad_name' => $row['ad_name'], 'label_value' => $row['label_value']);
			$products_id = $row['products_id'];
    }
    return $return_array;
  }

  /**
   * JAF check if there are Goodle Adwords groupings or labels to add
   */
  function ad_groups($product) {
/*		if ($product['adwords_exclude']) {
    	$this->add_if_not_empty('g:adwords_publish', 'false'); 
		} else {
    	$this->add_if_not_empty('g:adwords_publish', 'true'); 
		} */
//		$ad_query = tep_db_query('SELECT * FROM '.TABLE_PRODUCTS_ADWORDS_GROUPS.' ap,'.TABLE_ADWORD_GROUPS.' a WHERE ap.products_id = '.$product['products_id'].' AND a.ad_id = ap.ad_id ORDER BY a.ad_priority DESC');
//		while ($ad_label = tep_db_fetch_array($ad_query)) {
    if (array_key_exists($product['products_id'],$this->product_ad_groups)) {
		  foreach($this->product_ad_groups[$product['products_id']] as $ad_label) {
		    $this->add_if_not_empty('g:'.$ad_label['ad_name'], $ad_label['label_value']); 
			}
		}
  }
  function old_ad_groups($product) {
/*		if ($product['adwords_exclude']) {
    	$this->add_if_not_empty('g:adwords_publish', 'false'); 
		} else {
    	$this->add_if_not_empty('g:adwords_publish', 'true'); 
		} */
		$ad_query = tep_db_query('SELECT * FROM adwords_groups_products ap, adwords_groups a WHERE ap.products_id = '.$product['products_id'].' AND a.ad_id = ap.ad_id ORDER BY a.ad_priority DESC');
		$ad_grouping = '';
		$ad_labels = array();
		while ($ad_group = tep_db_fetch_array($ad_query)) {
			if ($ad_group['ad_type']=='group') {
				$ad_grouping = $ad_group['ad_name'];
			} else { // it's a label
				$ad_labels[] = $ad_group['ad_name'];
			}
		}
    $this->add_if_not_empty('g:adwords_grouping', $ad_grouping); 
		if (count($ad_labels)>0) {
			foreach($ad_labels as $label) {
				$this->add_if_not_empty('g:adwords_labels', $label);
			}
		}
  }
}

?>