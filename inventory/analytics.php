<?php
include(dirname(dirname(__FILE__)) . '/config.php');
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
			Inventory <small>Analytics</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="page-toolbar">
					<form class="horizontal-form" id="analytics_details">
						<div class="form-body">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label for="report_daterange">Date Range</label>
										<div class="input-group" id="report_daterange">
											<input type="text" class="form-control" name="report_daterange" autocomplete="off" value="" required data-msg="Date Range is required">
											<span class="input-group-btn">
												<button class="btn btn-default date-range-toggle" type="button"><i class="fa fa-calendar"></i></button>
											</span>
										</div>
										<span class="help-block"></span>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label for="report_dayscover">Days of Cover</label>
										<input type="number" class="form-control" name="report_dayscover" autocomplete="off" min="1" value="30" required data-msg="Days Cover is required">
										<span class="help-block"></span>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label for="report_growthspike">Growth Spike</label>
										<input type="number" class="form-control" name="report_growthspike" autocomplete="off" min="0.0" max="10.0" step="0.1" value="1" required data-msg="Growth Spike is required">
										<span class="help-block"></span>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>&nbsp;</label><br />
										<button type="submit" class="btn btn-success">Generate</button>
										<button type="reset" class="btn btn-default">Reset</button>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="col-md-12 table-responsive inventory_analytics hide">
				<div class="portlet">
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="inventory_analytics"></table>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>