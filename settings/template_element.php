<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->

<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <div class="modal fade" id="add_template_element" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close modal-clear" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">Add New Template element</h4>
                    </div>
                    <div class="modal-body form">
                        <form action="#" type="post" class="form-horizontal form-row-seperated" name="add-template-element" id="add-template-element">
                            <div class="form-body">
                                <div class="alert alert-danger display-hide">
                                    <button class="close" data-close="alert"></button>
                                    You have some form errors. Please check below.
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">
                                        <input type="hidden" data-required="1" id="elementId" name="elementId" class="form-control" />

                                        <div class="row">
                                            <?php
                                            $getClient_menu = get_all_table();
                                            $options = "";
                                            foreach ($getClient_menu as $client) {
                                                $options .= '<option value="' . $client['Tables_in_' . DB_NAME] . '">' . $client['Tables_in_' . DB_NAME] . '</option>';
                                            }
                                            ?>
                                            <label class="col-lg-2 control-label">Table Name<span class="required"> * </span></label>
                                            <div class="col-lg-6">
                                                <select class="form-control select2me" id="tableName" name="tableName">
                                                    <option value=""></option>
                                                    <?php echo $options; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">
                                        <label class="col-lg-2 control-label">Field Name<span class="required"> * </span></label>
                                        <div class="col-lg-6">
                                            <select class="form-control select2me" id="fieldName" name="fieldName">
                                                <option value=""></option>
                                            </select>
                                        </div>

                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-lg-12 set-align">
                                        <label class="col-lg-2 control-label">Status<span class="required"> * </span></label>
                                        <div class="col-lg-6">
                                            <select class="form-control select2me" id="status" name="status">
                                                <option value=""></option>
                                                <option value="Enable">Enable</option>
                                                <option value="Disable">Disable</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions fluid">
                                    <div class="col-md-offset-9 col-lg-12">
                                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit
                                        </button>
                                        <button type="button" class="btn btn-default modal-clear" data-dismiss="modal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->

        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <div class="modal fade" id="edit_template_element" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close modal-clear" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">Edit Template Element</h4>
                    </div>
                    <div class="modal-body form">
                        <form action="#" type="post" class="form-horizontal form-row-seperated" name="add-template-element" id="add-template-element">
                            <div class="form-body">
                                <div class="alert alert-danger display-hide">
                                    <button class="close" data-close="alert"></button>
                                    You have some form errors. Please check below.
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">
                                        <input type="hidden" data-required="1" id="elementId" name="elementId" class="form-control" />
                                        <input type="hidden" data-required="1" id="field" name="field" class="form-control" />

                                        <div class="row">
                                            <?php
                                            $getClient_menu = get_all_table();
                                            $options = "";
                                            foreach ($getClient_menu as $client) {
                                                $options .= '<option value="' . $client['Tables_in_bas'] . '">' . $client['Tables_in_bas'] . '</option>';
                                            }
                                            ?>
                                            <label class="col-lg-2 control-label">Table Name<span class="required"> * </span></label>
                                            <div class="col-lg-6">
                                                <select class="form-control select2me" id="tableName" name="tableName">
                                                    <option value=""></option>
                                                    <?php echo $options; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">

                                        <label class="col-lg-2 control-label">Field Name<span class="required"> * </span></label>
                                        <div class="col-lg-6">
                                            <select class="form-control select2me" id="fieldName" name="fieldName">
                                                <option value=""></option>
                                            </select>
                                        </div>

                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-lg-12 set-align">
                                        <label class="col-lg-2 control-label">Status<span class="required"> * </span></label>
                                        <div class="col-lg-6">
                                            <select class="form-control select2me" id="status" name="status">
                                                <option value=""></option>
                                                <option value="Enable">Enable</option>
                                                <option value="Disable">Disable</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions fluid">
                                    <div class="col-md-offset-9 col-lg-12">
                                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit
                                        </button>
                                        <button type="button" class="btn btn-default modal-clear" data-dismiss="modal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Template Elements
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
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-edit"></i>Template Element
                        </div>
                        <div class="tools">
                            <button data-target="#add_template_element" id="add_template_element" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New Template Element<i class="fa fa-plus"></i></button>
                            <button class="btn btn-xs btn-primary reload" id="reload-template-element">Reload</button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover table-bordered" id="editable_template_element"></table>
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