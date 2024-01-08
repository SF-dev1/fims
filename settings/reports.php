<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="portlet-config" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
			Report <small>Generator</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="form">
					<form class="horizintal-form" type="post" id="report_generator" action="about:blank">
						<div class="form-body">
							<div class="alert alert-danger display-hide">
								<button class="close" data-close="alert"></button>
								You have some form errors. Please check below.
							</div>
							<div class="alert alert-success display-hide">
								<button class="close" data-close="alert"></button>
								Your form validation is successful!
							</div>
							<div class="form-group col-md-2">
								<label for="report_marketplace">Marketplace</label>
								<select id="report_marketplace" name="report_marketplace" class="form-control select2me" data-placeholder="Select...">
									<option value=""></option>
									<option value="amazon">Amazon</option>
									<option value="flipkart">Flipkart</option>
									<option value="paytm">Paytm</option>
									<option value="shopclues">ShopClues</option>
									<option value="snapdeal">Snapdeal</option>
								</select>
								<span class="help-block">Select Marketplace </span>
							</div>
							<div class="form-group col-md-2">
								<label for="report_account">Account</label>
								<select id="report_account" name="report_account" class="form-control select2me" data-placeholder="Select...">
								</select>
								<span class="help-block">Select Account </span>
							</div>
							<div class="form-group col-md-2">
								<label for="report_type">Report Type</label>
								<select id="report_type" name="report_type" class="form-control select2me" data-placeholder="Select...">
									<option value=""></option>
									<option value="all">All</option>
									<option value="orders">Orders</option>
									<option value="returns">Returns</option>
									<option value="claims">Claims</option>
									<option value="payments">Payments</option>
								</select>
								<span class="help-block">Select Report Type </span>
							</div>
							<div class="form-group col-md-4">
								<label for="report_daterange">Date Range</label>
								<div class="input-group" id="report_daterange">
									<input type="text" class="form-control" name="report_daterange" autocomplete="off">
									<span class="input-group-btn">
										<button class="btn btn-default date-range-toggle" type="button"><i class="fa fa-calendar"></i></button>
									</span>
								</div>
								<span class="help-block">Select Date Range </span>
							</div>
							<div class="form-group right col-md-2">
								<label for="exampleInputEmail1">&nbsp;</label><br />
								<input class="btn btn-success" type="submit" value="Generate"></input>
								<!-- <input class="btn btn-warning" type="reset" value="Reset"></input> -->
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>