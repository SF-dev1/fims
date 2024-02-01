<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <div class="modal fade" id="view-history" tabindex="-1" role="dialog" aria-labelledby="addLotModel"
            aria-hidden="true" data-backdrop="static" data-keyboard="true">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title">Inventory Quality Check</h4>
                </div>
                <div class="modal-body">
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Inventory <small>QC Reject</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet">
                    <div class="portlet-body">
                        <form role="form" action="#" type="post" class="form-horizontal form-row-seperated"
                            name="get-uid" id="get-uid">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-barcode"></i>
                                                    </span>
                                                    <span class="ctn_id"></span>
                                                    <input type="text" id="uid" name="uid"
                                                        class="form-control round-left"
                                                        placeholder="Scan the product barcode with a scanner"
                                                        tabindex="1" autocomplete="off" minlength="12" maxlength="12">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-info" id="sidelineProduct" type="button">
                                                            Sideline Product
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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