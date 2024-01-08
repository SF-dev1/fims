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
			Inventory <small>Transfer</small>
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
					<div class="portlet-title"><i class="fa fa-warehouse"></i> Stock Transfer</div>
					<div class="portlet-body">
						<form class="horizontal-form" id="stock_transfer">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12 ">
										<div class="form-group">
											<label class="control-label">Product ID <span class="required" aria-required="true">*</span></label>
											<div class="pull-right">
												<label class="control-label">Is return? </label>
												<input type="checkbox" name="is_return" class="form-control is_return">
											</div>
											<textarea class="form-control" rows="5" name="product_ids" spellcheck="false" placeholder="One Product UID per line" required></textarea>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Marketplace <span class="required" aria-required="true">*</span></label>
											<select id="marketplace" name="marketplace" class="form-control select2me" data-placeholder="Select Marketplace" required>
												<option value=""></option>
												<option value="amazon">Amazon</option>
												<option value="flipkart">Flipkart</option>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Account <span class="required" aria-required="true">*</span></label>
											<select id="account" name="account" class="form-control select2me" data-placeholder="Select Account" required></select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Shipment/Consignment ID <span class="required" aria-required="true">*</span></label>
											<input type="text" class="form-control" name="shipment_id" required>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label ">Warehouse <span class="required" aria-required="true">*</span></label>
											<select id="shipped_to" name="shipped_to" class="form-control select2me" data-placeholder="Select Warehouse" required></select>
										</div>
									</div>
									<div class="col-md-12 error_data hide">
									</div>
								</div>
							</div>
							<div class="form-actions">
								<div class="row">
									<div class="col-md-12">
										<input type="hidden" name="action" value="transfer_stock">
										<button type="submit" class="btn btn-success"><i></i> Submit</button>
										<button type="reset" class="btn btn-default">Reset</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="portlet">
					<div class="portlet-title"><i class="fa fa-undo-alt"></i> Vendor Return</div>
					<div class="portlet-body">
						<form class="horizontal-form" id="vendor_return">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12 ">
										<div class="form-group">
											<label class="control-label">Product ID <span class="required" aria-required="true">*</span></label>
											<textarea class="form-control" rows="5" name="product_ids" spellcheck="false" placeholder="One Product UID per line" required></textarea>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Vendor <span class="required" aria-required="true">*</span></label>
											<select id="vendor" name="vendor" class="form-control select2me" data-placeholder="Select Vendor" required>
												<option value=""></option>
												<?php
												$vendors = get_all_vendors();
												$suppliers = get_all_suppliers();
												$parties = array_merge($vendors, $suppliers);
												foreach ($parties as $vendor) {
													echo '<option value="' . $vendor->party_id . '">' . $vendor->party_name . '</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Return Note ID <span class="required" aria-required="true">*</span></label>
											<input type="text" class="form-control" name="return_note_id" required>
										</div>
									</div>
									<div class="col-md-12 error_data hide">
									</div>
								</div>
							</div>
							<div class="form-actions">
								<div class="row">
									<div class="col-md-12">
										<input type="hidden" name="action" value="vendor_return">
										<button type="submit" class="btn btn-success"><i></i> Submit</button>
										<button type="reset" class="btn btn-default">Reset</button>
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