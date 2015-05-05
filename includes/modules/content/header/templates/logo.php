<div id="storeLogo" class="col-sm-<?php echo $content_width; ?>">
    <?php /*** Begin Header Tags SEO ***/ ?>
     <div><div id="storeLogo">
      <?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image(DIR_WS_IMAGES . 'store_logo.png', (tep_not_null($header_tags_array['logo_text']) ? $header_tags_array['logo_text'] : STORE_NAME)) . '</a>';
      if (HEADER_TAGS_DISPLAY_PAGE_TOP_TITLE == 'true') { ?>
          <div style="position:absolute; top:0; left:40%; color:#777; font-size:10px;text-align:center"><?php echo $header_tags_array['title']; ?></div>
      <?php } ?>
     </div>
    <?php /*** End Header Tags SEO ***/ ?>

</div>

