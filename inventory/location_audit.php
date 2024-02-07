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
            Inventory <small>Audit</small>
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
                            <i class="fa fa-hockey-puck"></i>Location Audit
                        </div>
                        <div class="tools">
                            <!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
                        </div>
                    </div>
                    <div class="portlet-body">
                        <form role="form" class="form-horizontal form-row-seperated" id="locationContent">
                            <div class="form-body">
                                <input type="hidden" name="audit_id" id="audit_id" value="<?php echo get_option('current_audit_tag'); ?>">
                                <div class="form-group">
                                    <label class="control-label col-md-1">Location ID</label>
                                    <div class="col-md-3 input-group">
                                        <input type="text" class="form-control locationId" id="locationId" tabindex="1" name="locationId" minlength="12" maxlength="12">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success" type="submit" tabindex="2"><i class="fa fa-search"></i></button>
                                        </span>
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet hide">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-hockey-puck"></i>Audit UID
                            </div>
                            <div class="tools">
                                <!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
                            </div>
                        </div>
                        <div class="portlet-body">
                            <form class="form-horizontal form-row-seperated" id="locationAudit" role="form">
                                <div class="form-body">
                                    <input type="hidden" name="audit_id" value="<?= get_option("current_audit_tag") ?>" id="audit_id">
                                    <div class="form-group">
                                        <label class="control-label col-md-1">UID</label>
                                        <div class="input-group col-md-3">
                                            <input type="text" name="uid" id="uid" class="form-control" minlength="12" maxlength="12">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="portlet">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="fa fa-hockey-puck"></i>Location Content
                            </div>
                            <div class="tools">
                                <!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
                            </div>
                        </div>
                        <div class="portlet-body">
                            <div class="form-group content_details">
                            </div>
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