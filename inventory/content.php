<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Inventory <small>Reconciliation</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="col-md-12 table-responsive">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-hockey-puck"></i>Content
                        </div>
                        <div class="tools">
                            <!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
                        </div>
                    </div>
                    <div class="portlet-body">
                        <form role="form" class="form-horizontal form-row-seperated" id="content">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="control-label col-md-1">Box / CTN Number</label>
                                    <div class="col-md-3 input-group">
                                        <input type="text" class="form-control box_number" tabindex="1" name="box_number" value="<?php echo (isset($_GET['box']) ? $_GET['box'] : "") ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" type="submit" tabindex="2"><i class="fa fa-search"></i></button>
                                        </span>
                                    </div>
                                </div>
                        </form>
                        <div class="form-group content_details hide">
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- <a href="./ajax_load.php?action=export_content_details&sku=##SKU##" class="export_content_details" target="_blank">Export</a> -->
                                </div><br /><br />
                            </div>
                            <div class="content_data"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END EXAMPLE TABLE PORTLET-->
        </div>
    </div>
    <!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>