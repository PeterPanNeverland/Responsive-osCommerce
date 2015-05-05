<div class="col-sm-<?php echo $content_width . ' ' . MODULE_CONTENT_PRODUCT_INFO_DESCRIPTION_CONTENT_ALIGN . ' ' . MODULE_CONTENT_PRODUCT_INFO_DESCRIPTION_CONTENT_VERT_MARGIN . ' ' . MODULE_CONTENT_PRODUCT_INFO_DESCRIPTION_CONTENT_HORIZ_MARGIN; ?> productsdescription">
    <div itemprop="description">
			<?php /*** Begin Header Tags SEO ***/ ?>
      <?php echo HTS_Highlight(stripslashes($product_info['products_description']), $header_tags_array['keywords']); ?>
      <?php /*** End Header Tags SEO ***/ ?>
    </div>
</div>