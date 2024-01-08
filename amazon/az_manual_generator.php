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
			Manual Process <small>Amazon</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="form">
					<form method="post" id="amazon-schedule-pickup" action="ajax_load.php" enctype="multipart/form-data" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;">
						<div class="form-body">
							<div class="alert alert-danger display-hide">
								<button class="close" data-close="alert"></button>
								You have some form errors. Please check below.
							</div>
							<div class="alert alert-success display-hide">
								<button class="close" data-close="alert"></button>
								Your form validation is successful!
							</div>
							<div class="form-group col-md-3">
								<label for="fileinput" class="control-label">Order File<span class="required"> * </span></label><br />
								<div class="fileinput fileinput-new" data-provides="fileinput">
									<span class="btn btn-default btn-file">
										<span class="fileinput-new">
											Select file </span>
										<span class="fileinput-exists">
											Change </span>
										<input type="file" name="order_file" id="order_file" accept="text/plain">
									</span>
									<span class="fileinput-filename">
									</span>
									&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
									</a>
								</div>
								<div id="order_file_error"></div>
								<p class="help-block">
									<span class="label label-warning label-sm">Note:</span>&nbsp;Only .txt tab delimited file allowed.
								</p>
							</div>
							<div class="form-group col-md-3">
								<label for="form_datetime" class="control-label">Date<span class="required"> * </span></label>
								<div class="input-group input-medium date form_datetime" id="pickup_date" data-date-format="yyyy-mm-dd" data-date-start-date="+0d">
									<input name="pickup_date" type="text" class="form-control" readonly>
									<span class="input-group-btn">
										<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
									</span>
								</div>
								<span class="help-block">Select Date</span>
							</div>
							<div class="form-group col-md-4">
								<label for="pickup_time" class="control-label">Time<span class="required"> * </span></label><br />
								<div class="col-md-9">
									<div class="radio-list" id="pickup_time">
										<label class="radio-inline">
											<input type="radio" name="pickup_time" value="10:00 AM"> 10:00 AM </label>
										<label class="radio-inline">
											<input type="radio" name="pickup_time" value="01:00 PM" checked> 1:00 PM </label>
									</div>
								</div>
							</div>
							<div class="form-group right col-md-2">
								<label for="exampleInputEmail1">&nbsp;</label><br />
								<input type="hidden" name="action" value="schedule_pickup">
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