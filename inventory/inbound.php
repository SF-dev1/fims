<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="model"></div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Inventory <small>Inbound</small>
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
						<form role="form" class="form-horizontal form-row-sepe">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-1">GRN</label>
									<div class="col-md-2">
										<select class="form-control input-small grn_select" tabindex="1" title="">
										</select>
									</div>
									<label class="control-label col-md-1">Product</label>
									<div class="col-md-4">
										<select class="form-control input-medium grn_item_select" data-allowclear="true" data-placeholder="Select..." tabindex="2" title="">
											<option value=''></option>
										</select>
									</div>
									<div class="col-md-4 form-inline text-right">
										<div class="checkbox">
											<label class="control-label">Print Label: </label>
											<label class="checkbox-inline">
												<?php $print_ctn_label = (get_option('print_ctn_label') == "true" ? " checked='checked' " : ""); ?>
												<input type="checkbox" <?php echo $print_ctn_label; ?> class="print_label" data-label_for="print_ctn_label"> Cartoon </label>
											<label class="checkbox-inline">
												<?php $print_box_label = (get_option('print_box_label') == "true" ? " checked='checked' " : ""); ?>
												<input type="checkbox" <?php echo $print_box_label; ?> class="print_label" data-label_for="print_box_label"> Box </label>
										</div>
									</div>
								</div>
								<div class="form-group inbound-details hide">
									<hr />
									<div class="col-md-2">
										<div class="thumbnail" style="width: 154px; height: 154px;">
											<img src="" onerror="this.onerror=null;this.src='https://via.placeholder.com/150x150/EFEFEF/AAAAAA&amp;text=no+image';" alt="" class="product_image" />
										</div>
										<span><b>SKU </b>
											<p class="product_sku"></p>
										</span>
									</div>
									<div class="col-md-8 machine_off hide">
										<p class="text-center">Inbound Completed for this SKU</p>
									</div>
									<div class="col-md-2 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Carton Number</label>
												<div class="input-group">
													<input type="text" class="form-control ctn_number" value="" maxlength="12" minlength="12" readonly="" tabindex="-1">
													<div class="input-group-btn">
														<button type="button" class="btn btn-default add_ctn" data-type="text" data-pk="1" tabindex="1">
															<i class="fa fa-plus"></i>
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
											<div class="form-group">
												<label class="control-label">Box Number</label>
												<div class="input-group">
													<input type="text" class="form-control box_number" value="" maxlength="12" minlength="12" readonly="" tabindex="-1">
													<div class="input-group-btn">
														<button type="button" class="btn btn-default add_box" data-type="text" data-pk="1" tabindex="2">
															<i class="fa fa-plus"></i>
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
											<div class="form-group weight-group hide">
												<label class="control-label">Product Weight (Grams)</label>
												<input type="number" class="form-control product_weight" value="" min="1" max="25" step="1" tabindex="3" readonly="">
											</div>
											<div class="form-group">
												<label class="control-label">Product Number</label>
												<button class="btn btn-xs btn-default add_product_all"><i class="fa fa-plus"></i> All</button>
												<div class="input-group">
													<input type="text" class="form-control product_number" value="" maxlength="12" minlength="12" readonly="" tabindex="-1">
													<div class="input-group-btn">
														<button type="button" class="btn btn-default add_product" tabindex="4">
															<i class="fa fa-plus"></i>
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
										</div>
									</div>
									<div class="col-md-3 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Items in Current Box</label><span class="box_count"></span>
												<div class="ms-container">
													<div class="ms-selection">
														<ul class="ms-list current_box_items" tabindex="-1"></ul>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-3 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Boxes in Current Carton</label><span class="ctn_count"></span>
												<div class="ms-container">
													<div class="ms-selection">
														<ul class="ms-list current_carton_box" tabindex="-1"></ul>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="col-md-12">
											<div class="form-group count-group">
												<label class="control-label qty_received">
													<b>Quantity Received</b><span class="count"></span>
												</label><br /><br /><br />
												<label class="control-label qty_inward">
													<b>Quantity Inward</b><span class="count"></span>
												</label><br /><br /><br />
												<label class="control-label qty_pending">
													<b>Quantity Pending</b><span class="count"></span>
												</label>
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