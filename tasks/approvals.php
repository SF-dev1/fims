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
            <table class="table table-striped table-bordered table-hover" id="myApprovals">
                <thead>
                    <th>#</th>
                    <th>Task Title</th>
                    <th>Create By</th>
                    <th>Reaction Reason</th>
                    <th>Status</th>
                    <th>action</th>
                </thead>
                <tbody id="approvalsBody"></tbody>
            </table>
        </div>
    </div>
    <!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>