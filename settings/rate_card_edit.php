<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
//include_once(ROOT_PATH.'/rate-cards/ajax_load.php');


$rate_details = get_rate_card_details();


if (isset($_GET['uid'])) {
    $db->where('fee_id', $_GET['uid']);
    $data = $db->objectBuilder()->getOne(TBL_MP_FEES . ' f', NULL, 'f.*');

    $db->startTransaction();
    $productBrands = $db->objectBuilder()->get(TBL_PRODUCTS_BRAND);


    $db->startTransaction();
    $productCategory = $db->objectBuilder()->get(TBL_PRODUCTS_CATEGORY);
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Edit <small>Rate</small>
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet">
                    <div class="portlet-body form">
                        <!-- BEGIN FORM-->
                        <form action="" class="horizontal-form" id="new-payment" method="post">
                            <div class="form-body">
                                <h3 class="form-section">Edit Rate</h3>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Name</label>
                                            <select class="form-control select2me fee_name" name="fee_name" data-placeholder="Payment Mode" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_name'] as $rate_detail)
                                                    if ($rate_detail == $data->fee_name) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!--/span-->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Tier</label>
                                            <select class="form-control select2me payment_mode" name="fee_tier" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_tier'] as $rate_detail)

                                                    if ($rate_detail == $data->fee_tier) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Type</label>
                                            <select class="form-control select2me payment_mode" name="fee_type" data-placeholder="Payment Mode" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_type'] as $rate_detail)
                                                    if ($rate_detail == $data->fee_type) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                ?>
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee FulfilmentType</label>
                                            <select class="form-control select2me payment_mode" name="fee_fulfilmentType" data-placeholder="Payment Mode" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_fulfilmentType'] as $rate_detail) {
                                                    if ($rate_detail == $data->fee_fulfilmentType) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!--/span-->
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Brand</label>
                                            <select class="form-control select2me payment_mode" name="fee_brand" data-placeholder="Payment Mode" required tabindex="5">
                                                <option value="all" <?php if ($data->fee_brand == 'all') { ?> selected="selected" <?php } ?>>All</option>
                                                <?php
                                                foreach ($productBrands as $productBrand)
                                                    if ($productBrand->brandName == $data->fee_brand) {
                                                        echo "<option selected='selected' value='" . $productBrand->brandName . "'>" . $productBrand->brandName . "</option>";
                                                    } else {
                                                        echo '<option value="' . $productBrand->brandName . '">' . $productBrand->brandName . '</option>';
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Category</label>
                                            <select class="form-control select2me payment_mode" name="fee_category" data-placeholder="Payment Mode" required tabindex="5">
                                                <option value="all" <?php if ($data->fee_category == 'all') { ?> selected="selected" <?php } ?>>All</option>
                                                <?php
                                                foreach ($productCategory as $category)

                                                    if ($category->categoryName == $data->fee_category) {
                                                        echo "<option selected='selected' value='" . $category->categoryName . "'>" . $category->categoryName . "</option>";
                                                    } else {
                                                        echo '<option value="' . $category->categoryName . '">' . $category->categoryName . '</option>';
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Column</label>
                                            <select class="form-control select2me payment_mode" name="fee_column" data-placeholder="Payment Mode" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_column'] as $rate_detail)
                                                    if ($rate_detail == $data->fee_column) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Marketplace</label>
                                            <select class="form-control select2me payment_mode" name="fee_marketplace" data-placeholder="Payment Mode" required tabindex="5">
                                                <?php
                                                foreach ($rate_details['fee_marketplace'] as $rate_detail)

                                                    if ($rate_detail == $data->fee_marketplace) {
                                                        echo "<option selected='selected' value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    } else {
                                                        echo "<option value='" . $rate_detail . "'>" . $rate_detail . "</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Attribute Min</label>
                                            <input type="text" class="form-control payment_amount" name="fee_attribute_min" placeholder="Fee Attribute Min" required tabindex="3" value="<?php echo $data->fee_attribute_min ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Attribute Max</label>
                                            <input type="text" class="form-control payment_amount" name="fee_attribute_max" placeholder="Fee Attribute Max" required tabindex="3" value="<?php echo $data->fee_attribute_max ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Value</label>
                                            <input type="text" class="form-control payment_amount" name="fee_value" placeholder="Fee Value" required tabindex="3" value="<?php echo $data->fee_value ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Fee Constant</label>
                                            <input type="text" class="form-control payment_amount" name="fee_constant" placeholder="Fee Constant" required tabindex="3" value="<?php echo $data->fee_constant ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Start Date</label>
                                            <div class="input-group date " data-date="<?php echo date('d-m-Y'); ?>" data-date-format="dd-mm-yyyy" data-date-end-date="+1d">
                                                <input class="form-control " type="date" value="<?php echo $data->start_date ?>" name="start_date" data-date-format="yyyy-mm-dd" data-date-end-date="0d" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">End Date</label>
                                            <div class="input-group date date-picker" data-date="<?php echo date('d-m-Y'); ?>" data-date-format="dd-mm-yyyy" data-date-end-date="+1d">
                                                <input class="form-control " type="date" value="<?php echo $data->end_date ?>" name="end_date" data-date-format="yyyy-mm-dd" data-date-end-date="0d" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-action">
                                            <label class="control-label">&nbsp;</label><br />
                                            <input type="hidden" name="fee_id" class="fee_id" value="<?php echo $data->fee_id  ?>" />
                                            <button type="submit" class="btn btn-success input-xsmall" tabindex="7"><i></i> Save</button>
                                            <button type="reset" class="btn btn-default input-xsmall" tabindex="8">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <!-- END FORM-->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END PAGE CONTENT-->
</div>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script defer src="<?php echo BASE_URL; ?>/assets/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>


<script>
    $("#new-payment").submit(function(e) {
        e.preventDefault();
        $(".form-group").removeClass("has-error");
        var form = $('#new-payment');
        var other_data = form.serializeArray();
        var formData = new FormData();
        formData.append('action', 'save_rate');
        $.each(other_data, function(key, input) {
            formData.append(input.name, input.value);
        });


        // console.log(formData);
        $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime(),
            cache: false,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            async: false,
            success: function(s) {
                s = $.parseJSON(s);
                $('.list-container').show();
                if (s.type == 'success') {
                    window.location.href = base_url + "/rate-cards/rate_card.php";
                } else {
                    $('ul.failed-list').append(s.content);

                }
                // console.log(rtd_trackingIds);
            },
            error: function() {
                console.log('Error Processing your Request!!');
            }
        });
    });
</script>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>