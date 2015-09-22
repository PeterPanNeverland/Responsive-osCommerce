<?php      if ($products['ukpostarea2']) { 
        echo '<a href="' . tep_href_link('categories.php', 'action=setexclude&flag=0&module=ukpostarea2&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', sprintf('Click to allow %s','Standard (myHermes)'), 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setexclude&flag=1&module=ukpostarea2&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', sprintf('Click to exclude %s','Standard (myHermes)'), 10, 10) . '</a>' ;
      }
?>