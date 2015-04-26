<style type='text/css'> div.xsell .item {min-width: 250px} </style>
<div class="col-sm-<?php echo $content_width; ?> xsell">
  <h4 class="page-header"><?php echo TEXT_XSELL_PRODUCTS; ?></h4>
    <div class="well well-sm">

		<?php
        if (MODULE_HEADER_TAGS_GRID_LIST_VIEW_STATUS == 'True') {
          ?>
          <strong><?php echo TEXT_VIEW; ?></strong>
          <div class="btn-group">
            <a href="#" id="list" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-th-list"></span><?php echo TEXT_VIEW_LIST; ?></a>
            <a href="#" id="grid" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-th"></span><?php echo TEXT_VIEW_GRID; ?></a>
          </div>
          <?php
        }
        ?>
        <div class="clearfix"></div>
    </div>
    <div id="products" class="row list-group"><?php echo $xsell_data; ?></div>
</div>
