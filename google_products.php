<?php
/*
  generate feed for google products / adwords - run from cron
	output file(s): catalog /feeds/products-static-english.xml
	
	Author John Ferguson (@BrockleyJohn) john@sewebsites.net
	
	latest code copyright (c) 2015 osCommerce

  Some of called code based on part of Google Checkout v1.5.0
  
  All released under the GNU General Public Licence
*/

	require_once('includes/application_top.php'); 

  require(DIR_WS_LANGUAGES . $language . '/google_products.php');

// need this admin function for multiple languages...
  function tep_get_languages() {
    $languages_query = tep_db_query("select languages_id, name, code, image, directory from " . TABLE_LANGUAGES . " order by sort_order");
    while ($languages = tep_db_fetch_array($languages_query)) {
      $languages_array[] = array('id' => $languages['languages_id'],
                                 'name' => $languages['name'],
                                 'code' => $languages['code'],
                                 'image' => $languages['image'],
                                 'directory' => $languages['directory']);
    }

    return $languages_array;
  }

echo '<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html dir="LTR" lang="en">
<head><title>' . TEXT_TITLE . '</title>
</head>
<body>';

// builder class
  require_once(DIR_WS_CLASSES . 'google_base_feed_builder.php');

// check store languages
  $languages = tep_get_languages();

// for each language
  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
// Get the feed.
		$google_base_feed_builder = new GoogleBaseFeedBuilder($languages[$i]['id']);
		$feed = $google_base_feed_builder->get_xml();

// Update the feed file for this language and output a link to it.
		$filename = 'feeds/products-static-' . $languages[$i]['directory'] . '.xml';
		echo '<p>' . sprintf(TEXT_GENERATING,$filename) . "\n";
		$file = fopen(DIR_FS_CATALOG . $filename, "w");
		if ($file) {
			fwrite($file, $feed);
			fclose($file);
			$full_filename = HTTP_SERVER . DIR_WS_CATALOG . $filename;
			echo('<a href="' . $full_filename .'">' . $full_filename . '</a> </p>'."\n");
		} else {
			echo(TEXT_OPEN_FAILED . '</p>');
		}
  }
?>
</body>
</html>