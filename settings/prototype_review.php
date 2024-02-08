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
            Reports <small>Prototype Review</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="portlet">
                <div class="portlet-title">
                    <strong>Customer Reviews</strong>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover" id="sample_1">
                        <thead>
                            <th>#</th>
                            <th>Name</th>
                            <th>Order Id</th>
                            <th>sku</th>
                            <th>Design</th>
                            <th>Value For Money</th>
                            <th>Strap</th>
                            <th>Buckle</th>
                            <th>Durability</th>
                            <th>Functionality</th>
                            <th>Customer Support</th>
                            <th>Comfort</th>
                            <th>Average</th>
                            <th>Overall Review</th>
                        </thead>
                        <tbody id="reviewBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>