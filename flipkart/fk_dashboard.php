<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="generate_report" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Request Report</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="request-report" id="request-report">
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
									<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control accounts_select2me input-medium account_id" id="account_id" name="account_id">
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Report Type<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium report_type" id="report_type" name="report_type"></select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Report Sub Type<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium report_sub_type" id="report_sub_type" name="report_sub_type" data-placeholder="Select Sub Report Type"></select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Date Range<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group col-md-4" id="report_daterange">
											<input type="text" class="form-control input-medium report_daterange" name="report_daterange">
											<span class="input-group-btn">
												<button class="btn btn-success date-set" type="button"><i class="fa fa-calendar"></i></button>
											</span>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8">
										<button type="submit" class="btn btn-success btn-submit"><i class=""></i> Submit</button>
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
		<div class="modal fade" id="confirm_mp_inc_amount" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Confirm MP Incentive Amount</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" class="confirm-imc-amount" id="confirm-imc-amount">
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
									<label class="col-sm-4 control-label">Incentive Percentage<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="number" class="form-control input-medium" name="mp_inc_amount" id="mp_inc_amount" min=1 max=100>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Pre Sale Starts<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group date pre_sale_starts_picker input-medium">
											<input type="text" size="16" readonly class="form-control " name="pre_sale_starts" id="pre_sale_starts">
											<div class="input-group-btn">
												<button class="btn btn-success date-reset" type="button"><i class="fa fa-times"></i></button>
												<button class="btn btn-success date-set" type="button"><i class="fa fa-calendar"></i></button>
											</div>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8 text-right">
										<button type="button" class="btn btn-default close_inc_amount_modal" data-dismiss="modal"> Cancel</button>
										<button type="button" class="btn btn-success btn-submit confirm_inc_amount"><i class=""></i> Confirm</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
		<div class="modal fade" id="manual_optin" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Manual Optin via CSV</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="manual-optin" id="manual-optin">
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
									<label class="control-label col-sm-4">CSV File<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">
													Select file </span>
												<span class="fileinput-exists">
													Change </span>
												<input type="file" name="optin_csv" accept=".csv" id="optin_csv" required>
											</span>
											<span class="fileinput-filename">
											</span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<div id="orders_csv_error"></div>
									</div>
								</div>
								<div class="mp_inc_group hide">
									<div class="form-group">
										<label class="col-sm-4 control-label">Incentive Percentage<span class="required"> * </span></label>
										<div class="col-sm-8">
											<div class="input-group">
												<input type="number" class="form-control input-medium" name="mp_inc_amount" id="manual_mp_inc_amount" min=1 max=100>
											</div>
										</div>
									</div>
									<div class="form-group mp_inc_group hide">
										<label class="col-sm-4 control-label">Pre Sale Starts<span class="required"> * </span></label>
										<div class="col-sm-8">
											<div class="input-group date pre_sale_starts_picker input-medium">
												<input type="text" size="16" readonly class="form-control " name="pre_sale_starts" id="manual_pre_sale_starts">
												<div class="input-group-btn">
													<button class="btn btn-success date-reset" type="button"><i class="fa fa-times"></i></button>
													<button class="btn btn-success date-set" type="button"><i class="fa fa-calendar"></i></button>
												</div>
											</div>
										</div>
									</div>
									<div class="form-actions fluid">
										<div class="col-md-offset-4 col-md-8 text-right">
											<button type="button" class="btn btn-default close_manual_optin_modal" data-dismiss="modal"> Cancel</button>
											<button type="button" class="btn btn-success btn-submit submit_manual_optin"><i class=""></i> Confirm</button>
										</div>
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
			Flipkart <small>Dashboard</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<!-- <div class="page-toolbar">
					<div class="btn-group pull-right">
						<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
						Actions <i class="fa fa-angle-down"></i>
						</button>
						<ul class="dropdown-menu pull-right" role="menu">
							<li>
								<a href="#" data-target="#import_dashboard_sheet" id="import_dashboard_sheet" role="button" class="btn btn-inverse import_dashboard_sheet" data-toggle="modal">Upload Payment Sheet &nbsp;</a>
							</li>
							<li>
								<a href="#" id="export_unsettled_orders" class="btn btn-inverse export_unsettled_orders">Export Unsettled Orders &nbsp;</a> 
							</li>
						</ul>
					</div>
				</div> -->
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="span12">
					<!-- BEGIN TAB PORTLET-->
					<div class="tabbable tabbable-custom boxless">
						<ul class="nav nav-tabs dashboard_type">
							<li class="active"><a href="#portlet_reports" data-toggle="tab">Reports </a></li>
							<li><a href="#portlet_promotions" data-toggle="tab">Promotions </a></li>
							<!-- <li><a href="#portlet_unsettled" data-toggle="tab">Unsettled Orders <span class="portlet_unsettled count"></span></a></li>
								<li><a href="#portlet_disputed" data-toggle="tab">Disputed Orders <span class="portlet_disputed count"></span></a></li> -->

						</ul>
						<div class="tab-content">
							<div class="tab-pane active" id="portlet_reports">
								<table class="table table-striped table-bordered table-hover" id="dashboard_reports">
									<thead>
										<tr>
											<th>Account</th>
											<th>Name</th>
											<th>Type</th>
											<th>Sub Type</th>
											<th>From Date</th>
											<th>To Date</th>
											<th>Status</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_promotions">
								<div id="calendar"></div>
							</div>
							<!-- <div class="tab-pane" id="portlet_unsettled">
									<table class="table table-striped table-bordered table-hover" id="dashboard_unsettled">
										<thead>
											<tr>
												<th>Account Name</th>
												<th>Order Item ID</th>
												<th>Order ID</th>
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
								<div class="tab-pane" id="portlet_disputed">
									<table class="table table-striped table-bordered table-hover" id="dashboard_disputed">
										<thead>
											<tr>
												<th>Account Name</th>
												<th>Order Item ID</th>
												<th>Order ID</th>
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
								</div> -->
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