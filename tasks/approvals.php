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
            Tasks <small>Approvals</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="portlet">
                <div class="portlet-title">
                    <strong>Pending Approvals</strong>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover dataTable no-footer" id="sample_1">
                        <thead>
                            <th>#</th>
                            <th>Task Title</th>
                            <th>Cartoon Number</th>
                            <th>Quantity</th>
                            <th>Expected Location</th>
                            <th>Create By</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>action</th>
                        </thead>
                        <tbody id="approvalsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modals">
            <div class="modal right fade reject_task" id="reject_task" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><i class='fa fa-comment'></i> Reason for rejecting the task</h4>
                        </div>
                        <div class="modal-body">
                            <form role="form" class="add-comment" id="rejectTask">
                                <div class="form-body">
                                    <div class="form-group">
                                        <textarea class="form-control" name="reason" id="reason" placeholder="Add reason for rejecting the task" rows="3" spellcheck="false"></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 form-action">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-success btn-block submit"><i class="fa"></i> Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div><!-- modal-content -->
                </div><!-- modal-dialog -->
            </div>
        </div>
    </div>
    <!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>