<?php
/*
	version 4.0 June 2015 @BrockleyJohn john@sewebsites.net
	changes for osc 2.3.4
	- product images instead of addtional images

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

  /**
   * Constructor.
   */
  function GoogleBaseFeedBuilder($languages_id) {
	  global $currencies; // JAF
    $this->xml = new GoogleXmlBuilder();
    $this->languages_id = $languages_id;
    $this->categories_tree = $this->build_categories_tree();
		$this->product_types = $this->build_product_types(); // JAF
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
    $this->add_image_link($product);
    $this->add_additional_image_link($product);  // JAF - feed additional image elements from Additional Images addon
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
    return tep_db_query(
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
	  );
  }
  
  /** JAF
   * For each category containing active products, construct a product type based on
   * walking up the categories 
   */
  function build_product_types() {
    $product_types = array();
    $categories_query = $this->get_categories_query();
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
    return tep_db_query(  // JAF original query omitted products with no manufacturer specified & included inactive ones!
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
	. "p.products_weight, "
//	. "p.products_model, "
	. "p.easify_sku as products_model, "
	. "p.products_quantity, "
	. "p.products_tax_class_id, " 
	. "p.adwords_exclude, " 
	. "pd.products_description, "
	. "pd.products_url, "
	. "pd.products_name, "
	. "m.manufacturers_name, "
	. "cond.products_extra_fields_value as products_condition, "
//	. "mpn.products_extra_fields_value as products_mpn, "
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
	. "where p.products_status = '1' and pd.products_id = p.products_id and pd.language_id = '" . (int)$this->languages_id . "' "
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
  // JAF prevent consecutive paragraphs from running together
  	$search = array('#</p><#i','#</li><#i','#</ul><#i','/<br>/i','#</td><#i','#<br />#i','#<br/>#i');
	$replace = array('</p> <','</li> <','</ul> <','<br> ','</td> <','<br /> ','<br/> ');
    $description = preg_replace($search,$replace,$product['products_description']);
    $description = strip_tags($description);
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
		$this->add_if_not_empty('g:mpn', $product['products_model']);
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
  function add_additional_image_link($product) {
    $image_query = $this->get_images_query($product);
    while ($image = tep_db_fetch_array($image_query)) {
			$image_link = HTTP_SERVER . DIR_WS_CATALOG
					. DIR_WS_IMAGES . $image['image']; // JAF v4.0
			$this->add_if_not_empty('g:additional_image_link', $image_link);
    }
  }

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
    $categories_query = $this->get_product_categories_query($product);
    while ($category = tep_db_fetch_array($categories_query)) {
      $this->add_if_not_empty('g:product_type', $this->product_types[$category['categories_id']]);
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
  /**
   * JAF check if there are Goodle Adwords groupings or labels to add
   */
  function ad_groups($product) {
/*		if ($product['adwords_exclude']) {
    	$this->add_if_not_empty('g:adwords_publish', 'false'); 
		} else {
    	$this->add_if_not_empty('g:adwords_publish', 'true'); 
		} */
		$ad_query = tep_db_query('SELECT * FROM adwords_groups_products ap,adwords_groups a WHERE ap.products_id = '.$product['products_id'].' AND a.ad_id = ap.ad_id ORDER BY a.ad_priority DESC');
		while ($ad_label = tep_db_fetch_array($ad_query)) {
		    $this->add_if_not_empty('g:'.$ad_label['ad_name'], $ad_label['label_value']); 
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