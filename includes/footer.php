<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

?>

</div>

<footer>
  <div class="footer">
    <div class="<?php echo BOOTSTRAP_CONTAINER; ?>">
      <div class="row">
        <?php echo $oscTemplate->getContent('footer'); ?>
      </div>
    </div>
  </div>
  <div class="footer-extra">
    <div class="<?php echo BOOTSTRAP_CONTAINER; ?>">
      <div class="row">
        <?php echo $oscTemplate->getContent('footer_suffix'); ?>
      </div>
    </div>
 <?php
/*** Begin Header Tags SEO ***/
if ($request_type == 'NONSSL') { 
  if (HEADER_TAGS_DISPLAY_TAG_CLOUD == 'true') {
      echo '<div id="hts_footer">';
      include(DIR_WS_INCLUDES . 'headertags_seo_tagcloud_footer.php');
      echo '</div>';
  }
}
/*** End Header Tags SEO ***/
?>  
  </div>
</footer>

