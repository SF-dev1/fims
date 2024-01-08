<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_parties" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Party</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-party" id="add-party">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Party Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" id="party_name" name="party_name" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Party GST<!-- <span class="required"> * </span> --></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" id="party_gst" name="party_gst" class="form-control round-right input-medium party_gst" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Address<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<textarea id="party_address" name="party_address" class="form-control"></textarea>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Contact Person<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="text" id="party_poc" name="party_poc" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Email<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="email" id="party_email" name="party_email" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Mobile No.<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="tel" id="party_mobile" name="party_mobile" class="form-control party_mobile">
										</div>
									</div>
								</div>
								<div class="form-group last">
									<label class="col-sm-4 control-label">Party Type<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="checkbox-list">
											<label class="checkbox-inline">
												<input type="checkbox" id="party_type_distributor" name="party_type_distributor" value="1" class="form-control"> Distributor </label>
											<label class="checkbox-inline">
												<input type="checkbox" id="party_type_customer" name="party_type_customer" value="1" class="form-control"> Customer </label>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Parties <small>Customer</small>
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
					<div class="portlet-title">
						<div class="tools">
							<button data-target="#add_parties" id="add_parties" role="button" class="btn btn-xs btn-primary search_order" data-toggle="modal">Add New <i class="fa fa-plus"></i></button>
							<button class="btn btn-xs btn-primary reload" id="reload-parties">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_customers"></table>
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