<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
include_once(ROOT_PATH . '/shopify/functions.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="import_payment_sheet" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Import Remittance Sheet</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="payment-import" id="payment-import">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="alert alert-success display-hide">
									<button class="close" data-close="alert"></button>
									Your form validation is successful!
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">Payment Sheet<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new"> Select file </span>
												<span class="fileinput-exists"> Change </span>
												<input type="file" name="orders_csv" accept=".xlsx, .xls, .csv" id="orders_csv">
											</span>
											<span class="fileinput-filename">
											</span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<div id="orders_csv_error"></div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Remittance By<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2 input-medium account_id" id="account_id" name="account_id">
												<option value="">Please select remittance by</option>
												<option value="Shiprocket">Shiprocket</option>
												<option value="RazorPay">RazorPay</option>
												<option value="Gokwik">Gokwik</option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Shopify Account<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control accounts_select2me select2 input-medium shopify_account" id="shopify_account" name="shopify_account">
												<option value="">Please select shopify account</option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8">
										<button type="submit" class="btn btn-success btn-submit"><i class=""></i>Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
		<div class="modal fade" id="import_payment_charge_sheet" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Import Logistic Sheet</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="payment-charge-import" id="payment-charge-import">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="alert alert-success display-hide">
									<button class="close" data-close="alert"></button>
									Your form validation is successful!
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">Payment Sheet<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="charge_csv" accept=".xlsx, .xls, .csv" id="charge_csv">
											</span>
											<span class="fileinput-filename"></span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<div id="charge_csv_error"></div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8">
										<button type="submit" class="btn btn-success btn-submit"><i class=""></i>Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Shopify <small>Payments</small>
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
							<a href="#" data-target="#import_payment_sheet" id="import_payment_sheet" role="button" class="btn btn-inverse import_payment_sheet" data-toggle="modal">Upload Remittance Sheet &nbsp;</a>
						</li>
						<li>
							<a href="#" data-target="#import_payment_charge_sheet" id="import_payment_charge_sheet" role="button" class="btn btn-inverse import_payment_charge_sheet" data-toggle="modal">Upload Logistic Sheet &nbsp;</a>
						</li>

					</ul>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="span12">
					<!-- BEGIN TAB PORTLET-->
					<div class="tabbable tabbable-custom boxless">
						<ul class="nav nav-tabs settlement_type">
							<li class="active"><a href="#portlet_search_payment" data-toggle="tab">Search Payment <span class="portlet_search_payment count"></span></a></li>
							<li><a href="#portlet_settlements" data-toggle="tab">All Settlements <span class="portlet_start count"></span></a></li>
							<li><a href="#portlet_unsettled" data-toggle="tab">Unsettled Orders <span class="portlet_unsettled count"></span></a></li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane active" id="portlet_search_payment">
								<form class="form-inline search_payments">
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<input type="text" class="form-control search_value input-medium" name="search_value">
												<select class="form-control input-medium search_by select2me" data-placeholder="Search By" data-allow-clear="true" name="search_by">
													<option></option>
													<option value="o.orderId">Order Id</option>
													<option value="o.orderItemId">Item Id</option>
													<option value="p.paymentId">NEFT Id</option>
													<option value="pro.offerId">Offer Id</option>
												</select>
												<span>
													<input class="btn btn-info" type="submit" value="Search" />
												</span>
											</div>
										</div>
									</div>
								</form><br />
								<table class="table table-striped table-bordered table-hover hide" id="payment_search_payments">
									<thead>
										<tr>
											<th>Account Name</th>
											<th>Order Item ID</th>
											<th>Order ID</th>
											<th>Order Date</th>
											<th>Shipped Date</th>
											<th>Due Date</th>
											<th>Expected Payout</th>
											<th>Settlement</th>
											<th>Difference</th>
											<th class="return_hide_column">Account ID</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_settlements">
								<table class="table table-striped table-bordered table-hover" id="payment_settlements">
									<thead>
										<tr>
											<th>Settlement ID</th>
											<th>Settlement Date</th>
											<th>Settlement Amount</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_unsettled">
								<table class="table table-striped table-bordered table-hover" id="payment_unsettled">
									<thead>
										<tr>
											<th>Account Name</th>
											<th>Order Item ID</th>
											<th>Order ID</th>
											<th>Order Date</th>
											<th>Shipped Date</th>
											<th>Due Date</th>
											<th>Expected Payout</th>
											<th>Settlement</th>
											<th>Difference</th>
											<th class="return_hide_column">Account ID</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<!-- END TAB PORTLET-->
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>