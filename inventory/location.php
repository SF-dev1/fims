<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <div class="model">
            <div class="modal fade modal_770" id="map-location" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Map Inventory Location</h4>
                        </div>
                        <form method="post" class="form-horizontal" action="" name="map_location" id="map_location">
                            <div class="modal-body form">
                                <div class="form-body">
                                    <div class="form-group">
                                        <label class="col-md-2 control-label">Category: </label>
                                        <div class="col-md-10">
                                            <select class="form-control select2me w-100" id="category1" name="category" tabindex="1">
                                                <option value="" selected disabled>Select Category</option>
                                                <option value="qc_failed">Repairs</option>
                                                <option value="components_requested">Components Requested
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label">SKU: </label>
                                        <div class="col-md-10">
                                            <select class="form-control select2me w-100" id="sku1" name="sku" tabindex="2">
                                                <option value="" selected disabled>Select SKU</option>
                                                <?php
                                                $skus = $db->get(TBL_PRODUCTS_MASTER, null, "sku");
                                                foreach ($skus as $sku) {
                                                    echo "<option value='" . $sku["sku"] . "'>" . $sku["sku"] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label">Location ID: </label>
                                        <div class="col-md-10">
                                            <select class="form-control select2me w-100" id="location" name="location" tabindex="2">
                                                <option value="" selected disabled>Select Location</option>
                                                <?php
                                                $db->where("locationId", "FAILED%", "LIKE");
                                                $db->orWhere("locationId", "CMPREQ%", "LIKE");
                                                $db->orderBy("locationTitle", "ASC");
                                                $locations = $db->get(TBL_INVENTORY_LOCATIONS, null, "locationId, locationTitle");
                                                foreach ($locations as $location) {
                                                    echo "<option value='" . $location["locationId"] . "'>" . $location["locationTitle"] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="update_type" name="type" value="" />
                                <input class="btn btn-success" type="submit" name="submit" value="submit" />
                            </div>
                    </div>
                    </form>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <div class="modal fade modal_770" id="add-location" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Add Inventory Location</h4>
                        </div>
                        <form method="post" class="form-horizontal" action="" name="add_location" id="add_location">
                            <div class="modal-body form">
                                <div class="form-body">
                                    <div class="form-group">
                                        <label class="col-md-2 control-label">Category: </label>
                                        <div class="col-md-10">
                                            <select class="form-control select2me w-100" id="category" name="category" tabindex="1">
                                                <option value="" selected disabled>Select Category</option>
                                                <option value="qc_failed">Repairs</option>
                                                <option value="components_requested">Components Requested
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label">Capacity: </label>
                                        <div class="col-md-10">
                                            <input type="number" class="form-control w-100" id="capacity" placeholder="Add capacity of location">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="update_type" name="type" value="" />
                                <button class="btn btn-success" type="submit" name="submit">Submit</button>
                            </div>
                    </div>
                    </form>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Tasks <small>My Tasks</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
            <div class="page-toolbar">
                <div class="btn-group pull-right">
                    <div>
                        <!-- <button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
                            Actions <i class="fa fa-angle-down"></i>
                        </button> -->
                        <!-- <ul class="dropdown-menu pull-right" role="menu">
                        <li>
                            <a href="#" data-target="#add-location" id="add_location" role="menu"
                                data-toggle="modal">Add Location</a>
                        </li>
                        </ul> -->
                    </div>
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="portlet">
                <div class="portlet-title">
                    <strong>My Tasks</strong>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover dataTable no-footer" id="sample_1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Location ID</th>
                                <th>SKU</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody id="locationBody"></tbody>
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