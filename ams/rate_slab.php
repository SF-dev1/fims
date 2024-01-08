<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal modal_770 fade" id="add-rateSlab" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form action="#" id="add_rateSlab" class="horizontal-form">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">AMS Slab</h4>
						</div>
						<div class="modal-body">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Slab Name:</label>
											<input type="text" name="slab_name" class="form-control" placeholder="Slab Name" required>
										</div>
									</div>
								</div>
								<!-- <div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label">Start Date:</label>
												<div class="input-group date start_date">
													<input type="text" class="form-control" name="start_date" readonly placeholder="DD-MMM-YYYY" required>
													<span class="input-group-btn">
														<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
													</span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label">End Date:</label>
												<div class="input-group date end_date">
													<input type="text" class="form-control" name="end_date" readonly placeholder="DD-MMM-YYYY" required>
													<span class="input-group-btn">
														<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
													</span>
												</div>
											</div>
										</div>
									</div> -->
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Commission Fees (%):</label>
											<div class="input-group">
												<input type="number" name="commission_fees" class="form-control col-md-6" placeholder="Commission Rate" step="0.01" min="0" max="100" required>
												<span class="input-group-addon">
													With GST?<input type="checkbox" name=commission_fees_gst>
												</span>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Fixed Fees Type:</label>
											<div class="radio-list">
												<label class="radio-inline">
													<input type="radio" name="fixed_fees_type" value="flat" checked> Flat </label>
												<label class="radio-inline">
													<input type="radio" name="fixed_fees_type" value="variable"> Variable </label>
											</div>
										</div>
									</div>
								</div>
								<div class="row fixed_fee_flat">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Fixed Fees:</label>
											<div class="input-group">
												<input type="number" name="fixed_fees" class="form-control" placeholder="40" step="0.01" min="0" max="100" required>
												<span class="input-group-addon">
													With GST?<input type="checkbox" name="fixed_fees_gst">
												</span>
											</div>
										</div>
									</div>
								</div>
								<div class="fixed_fee_variable hide">
									<div class="row">
										<label class="control-label col-md-10">Fixed Fees:</label>
										<input type="checkbox" class="variable_fixed_fees hide" name="fixed_fees_gst">
										<div class="col-md-2">
											<button type="button" class="btn btn-xs pull-right add_variable_slot">Add Variable Slab</button>
										</div>
									</div>
									<div class="variable_slots">
										<div class="row fixed_fee_variable" id="variable_0">
											<label class="control-label col-md-12"><u>Fixed Fees Slot:</u></label>
											<div class="col-md-3">
												<div class="form-group">
													<label class="control-label">Orders:</label>
													<input type="number" name="fixed_fees[0][order][min]" class="form-control col-md-6" placeholder="Min Orders" step="1" min="0" max="1001" disabled>
													<input type="number" name="fixed_fees[0][order][max]" class="form-control col-md-6" placeholder="Max Orders" step="1" min="100" max="10000" disabled>
												</div>
											</div>
											<div class="col-md-4">
												<div class="form-group">
													<label class="control-label">Selling Price:</label>
													<input type="number" name="fixed_fees[0][order][selling_price][min]" class="form-control col-md-6" placeholder="Min Selling Price" step="1" min="100" max="1001" disabled>
													<input type="number" name="fixed_fees[0][order][selling_price][max]" class="form-control col-md-6" placeholder="Max Selling Price" step="1" min="100" max="10000" disabled>
												</div>
											</div>
											<div class="col-md-5">
												<div class="form-group">
													<label class="control-label">Fees:</label>
													<div class="input-group">
														<input type="number" name="fixed_fees[0][order][selling_price][rate]" class="form-control col-md-6" placeholder="Slab Rate" step="1" min="1" max="100" disabled>
														<span class="input-group-addon">
															With GST?<input type="checkbox" class="checkbox" disabled>
														</span>
													</div>
												</div>
												<button type="button" class="btn btn-xs pull-right delete_variable_slab" data-variableslab="variable_0" disabled><i class="fa fa-trash"></i></button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<input type="submit" class="btn btn-success" value="Add Slab" />
							<input type="reset" class="btn btn-default hide reset_form" value="Reset" />
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</form>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<div class="modal fade" id="apply-rateSlab" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form action="#" id="apply_rateSlab" class="horizontal-form">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Apply Slab</h4>
						</div>
						<div class="modal-body">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Slab Name:</label>
											<input type="text" class="form-control slab_name" placeholder="Slab Name" disabled>
											<input type="hidden" name="slab_id" class="slab_id" required>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label">Vendor:</label>
											<select class="form-control select2me vendor" name="vendor" required>
												<option value=""></option>
												<?php
												$vendors = get_all_vendors();
												foreach ($vendors as $vendor) {
													echo '<option value="' . $vendor->party_id . '">' . $vendor->party_name . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Start Date:</label>
											<div class="input-group date start_date">
												<input type="text" class="form-control" name="start_date" readonly placeholder="DD-MMM-YYYY" required>
												<span class="input-group-btn">
													<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
												</span>
											</div>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">End Date:</label>
											<div class="input-group date end_date">
												<input type="text" class="form-control" name="end_date" readonly placeholder="DD-MMM-YYYY">
												<span class="input-group-btn">
													<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<input type="submit" class="btn btn-success" value="Apply Slab" />
							<input type="reset" class="btn btn-default hide reset_apply_form" value="Reset" />
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</form>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			AMS <small>Rate Slab</small>
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
						<div class="tools">
							<button class="btn btn-xs btn-primary reload" id="reload-dataTable">Reload</button>
							<button data-target="#add-rateSlab" role="button" class="btn btn-xs btn-primary add_rateSlab" data-toggle="modal">New Slab <i class="fa fa-plus"></i></button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="ams_rate_slabs"></table>
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