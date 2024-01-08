<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="portlet-config" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Modal title</h4>
					</div>
					<div class="modal-body">
						Widget settings form goes here
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-success">Save changes</button>
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Sales <small>Payment</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
						Actions <i class="fa fa-angle-down"></i>
					</button>
					<ul class="dropdown-menu pull-right" role="menu">
						<li>
							<a href="#">Action</a>
						</li>
						<li>
							<a href="#">Another action</a>
						</li>
						<li>
							<a href="#">Something else here</a>
						</li>
						<li class="divider">
						</li>
						<li>
							<a href="#">Separated link</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet">
					<div class="portlet-body form">
						<!-- BEGIN FORM-->
						<form action="#" class="horizontal-form" id="new-payment">
							<div class="form-body">
								<h3 class="form-section">Payment Details</h3>
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Customer</label>
											<select class="form-control customers_list" name="party_id" data-placeholder="Choose a Customer" required tabindex="1">
											</select>
										</div>
									</div>
									<!--/span-->
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Amount</label>
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-rupee-sign"></i>
												</span>
												<input type="text" class="form-control payment_amount" name="payment_amount" placeholder="Amount" required tabindex="2">
											</div>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Date</label>
											<div class="input-group date date-picker" data-date="<?php echo date('d-m-Y'); ?>" data-date-format="dd-mm-yyyy" data-date-end-date="+1d">
												<span class="input-group-btn">
													<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
												</span>
												<input type="text" class="form-control payment_date" value="<?php echo date('d-m-Y'); ?>" name="payment_date" id="dtp_input2" tabindex="3" required readonly>
											</div>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Total Due</label>
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-rupee-sign"></i>
												</span>
												<input type="text" readonly="true" class="form-control payment_due" placeholder="0.00">
											</div>

										</div>
									</div>
									<!--/span-->
								</div>
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Mode</label>
											<select class="form-control select2me payment_mode" name="payment_mode" data-placeholder="Payment Mode" required tabindex="4">
												<option></option>
												<option value="cash">Cash</option>
												<option value="bank">Bank</option>
												<option value="wallet">Wallet</option>
												<option value="discount">Discount</option>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Reference</label>
											<input type="text" class="form-control payment_reference" name="payment_reference" placeholder="Reference Details" required tabindex="5">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="control-label">Remarks</label>
											<input type="text" class="form-control payment_remarks" name="payment_remarks" placeholder="Remarks" tabindex="6">
											<select class="form-control payment_remarks_select hide" name="payment_remarks" required tabindex="5" disabled></select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-action">
											<label class="control-label">&nbsp;</label><br />
											<input type="hidden" name="payment_id" class="payment_id" value="<?php echo isset($_GET['paymentId']) ? $_GET['paymentId'] : '' ?>" />
											<input type="hidden" name="client_id" class="client_id" value="<?php echo $client_id ?>" />
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
		<div class="row recent-payment-details hide">
			<div class="col-md-12">
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">
							Last 10 transactions
						</div>
					</div>
					<div class="portlet-body">
						<div class="table-responsive">
							<table class="table table-striped table-bordered table-advance table-hover table-recent-payment-details">
								<thead>
									<tr>
										<th>
											Date
										</th>
										<th class="hidden-xs">
											Particular
										</th>
										<th>
											Amount
										</th>
										<th>
										</th>
									</tr>
								</thead>
								<tbody>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<!-- END SAMPLE TABLE PORTLET-->
			</div>
		</div>
	</div>
	<!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>