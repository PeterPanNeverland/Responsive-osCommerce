<div class="col-sm-<?php echo $content_width . ' ' . MODULE_CONTENT_PRODUCT_INFO_XSELL_CONTENT_ALIGN; ?> xsell">
  <style type='text/css' scoped> div.xsell .item {min-width: 260px}
	/*div.xsell .buynow {margin-right: 10px;*/} </style>
  <h4 class="page-header"><?php echo MODULE_CONTENT_PRODUCT_INFO_XSELL_PRODUCTS_TEXT; ?></h4>
<?php
if (MODULE_HEADER_TAGS_GRID_LIST_VIEW_STATUS == 'True') {
  ?>
    <div class="well well-sm">

          <strong><?php echo TEXT_VIEW; ?></strong>
          <div class="btn-group">
            <a href="#" id="list" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-th-list"></span><?php echo TEXT_VIEW_LIST; ?></a>
            <a href="#" id="grid" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-th"></span><?php echo TEXT_VIEW_GRID; ?></a>
          </div>
        <div class="clearfix"></div>
    </div>
<?php
}
?>
    <div id="products" class="row list-group"><?php echo $xsell_data; ?></div>
</div>
